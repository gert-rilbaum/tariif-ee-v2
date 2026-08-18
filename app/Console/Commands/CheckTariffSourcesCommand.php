<?php

namespace App\Console\Commands;

use App\Models\GridPackageVersion;
use App\Models\StateFee;
use App\Models\TariffSourceCheck;
use App\Models\VatRate;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Kontrollib kord päevas, kas tariifiallikad on muutunud.
 *
 * Kaks eri valvet:
 *   1) ALLIKA MUUTUS — hinnakirja faili kontrollsumma erineb eelmisest.
 *      Tähendab: Elektrilevi avaldas uue hinnakirja, meie numbrid võivad
 *      olla vananenud.
 *   2) KIRJE VANANEMINE — mõne tariifirea verified_at on liiga vana või
 *      kehtivusaeg on läbi saanud.
 *
 * Käsk EI muuda ühtegi hinda. Hinnamuutus on äriotsus ja vajab inimest —
 * automaatne ülekirjutamine tähendaks, et vale parsimine jõuab märkamatult
 * kasutaja arvele.
 */
class CheckTariffSourcesCommand extends Command
{
    protected $signature = 'tariff:check-sources {--stale-days=90 : Mitme päeva järel loeb kirje kontrollimata}';

    protected $description = 'Kontrollib, kas tariifiallikad on muutunud või kirjed vananenud';

    public function handle(): int
    {
        [$muutusi, $kontrollimata] = $this->kontrolliAllikaid();
        $aegunud = $this->kontrolliKirjeid((int) $this->option('stale-days'));

        if ($muutusi === 0 && $aegunud === [] && $kontrollimata === 0) {
            $this->info('Tariifiallikad muutumatud, kirjed värsked.');

            return self::SUCCESS;
        }

        if ($kontrollimata > 0) {
            // "Ei saanud kontrollida" EI tähenda "muutumatu" — vaikne vale
            // rahustus on täpselt see, mille vastu see käsk üldse on
            $this->warn("Kontrollimata allikaid: {$kontrollimata} (päring ei õnnestunud).");
        }

        if ($muutusi > 0) {
            $this->warn("Muutunud allikaid: {$muutusi} — numbrid vajavad ülekontrollimist.");
        }

        foreach ($aegunud as $rida) {
            $this->warn($rida);
        }

        // Väljumiskood 1 teeb muutuse cron-logis ja monitooringus nähtavaks
        return self::FAILURE;
    }

    /** @return array{0: int, 1: int} [muutusi, kontrollimata] */
    private function kontrolliAllikaid(): array
    {
        $muutusi = 0;
        $kontrollimata = 0;

        foreach (TariffSourceCheck::watched() as $allikas) {
            $kirje = TariffSourceCheck::firstOrNew(['source_key' => $allikas['source_key']]);
            $kirje->fill(['label' => $allikas['label'], 'url' => $allikas['url']]);

            try {
                [$uus, $suurus] = $this->sormejalg($allikas['url']);
                $vana = $kirje->checksum;

                $kirje->fill([
                    'checksum' => $uus,
                    'size_bytes' => $suurus,
                    'checked_at' => CarbonImmutable::now(),
                    'last_error' => null,
                ]);

                if ($vana !== null && $vana !== $uus) {
                    $kirje->fill(['changed_at' => CarbonImmutable::now(), 'acknowledged' => false]);
                    $muutusi++;
                    $this->warn("MUUTUS: {$allikas['label']}");
                    $this->line('  '.$allikas['url']);
                } else {
                    $this->line("  {$allikas['label']}: muutumatu");
                }
            } catch (\Throwable $e) {
                $kontrollimata++;
                $kirje->fill(['checked_at' => CarbonImmutable::now(), 'last_error' => $e->getMessage()]);
                $this->warn("  {$allikas['label']}: kontroll ebaõnnestus — {$e->getMessage()}");
            }

            $kirje->save();
        }

        return [$muutusi, $kontrollimata];
    }

    /**
     * Allika sõrmejälg.
     *
     * Esmalt HEAD: ETag või Last-Modified + pikkus. See on kordades kergem kui
     * 388 KB PDF-i igapäevane allalaadimine ja ei ärrita CDN-i (Elektrilevi
     * vastas GET-tulvale 429). Alles siis, kui päised puuduvad, laeme keha.
     *
     * @return array{0: string, 1: int|null}
     */
    private function sormejalg(string $url): array
    {
        $head = $this->klient()->head($url);

        if ($head->successful()) {
            $etag = trim((string) $head->header('ETag'));
            $muudetud = trim((string) $head->header('Last-Modified'));
            $pikkus = $head->header('Content-Length');

            if ($etag !== '' || $muudetud !== '') {
                return [hash('sha256', $etag.'|'.$muudetud.'|'.$pikkus), $pikkus !== null ? (int) $pikkus : null];
            }
        }

        // Päised puuduvad → ainus võimalus on keha. 429 puhul EI korda:
        // kordused teeksid piirangu ainult hullemaks.
        $vastus = $this->klient()->timeout(30)->get($url);

        if ($vastus->status() === 429) {
            throw new \RuntimeException('HTTP 429 — allikas piirab päringuid, jätame vahele');
        }

        if (! $vastus->successful()) {
            throw new \RuntimeException('HTTP '.$vastus->status());
        }

        return [hash('sha256', $vastus->body()), strlen($vastus->body())];
    }

    /**
     * Väline päring tuvastatava nimega.
     *
     * Vt config/tariif.php kommentaari: anonüümne Guzzle UA teenis 429.
     */
    private function klient(): PendingRequest
    {
        return Http::timeout(20)->withHeaders([
            'User-Agent' => config('tariif.user_agent'),
        ]);
    }

    /** @return array<int, string> */
    private function kontrolliKirjeid(int $staleDays): array
    {
        $piir = CarbonImmutable::now()->subDays($staleDays);
        $today = CarbonImmutable::now('Europe/Tallinn')->toDateString();
        $teated = [];

        $vanad = GridPackageVersion::where('verified_at', '<', $piir)->count()
            + StateFee::where('verified_at', '<', $piir)->count()
            + VatRate::where('verified_at', '<', $piir)->count();

        if ($vanad > 0) {
            $teated[] = "Kontrollimata üle {$staleDays} päeva: {$vanad} tariifikirjet.";
        }

        $aegunudVersioone = GridPackageVersion::whereNotNull('valid_to')
            ->whereDate('valid_to', '<', $today)
            ->count();

        if ($aegunudVersioone > 0) {
            $teated[] = "Kehtivus läbi: {$aegunudVersioone} hinnaversiooni.";
        }

        return $teated;
    }
}
