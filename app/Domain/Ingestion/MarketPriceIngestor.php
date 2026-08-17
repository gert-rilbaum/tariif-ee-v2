<?php

namespace App\Domain\Ingestion;

use App\Models\IngestionRun;
use App\Models\MarketPrice;
use Carbon\CarbonImmutable;

/**
 * Loeb Eleringi hinnad oma andmebaasi.
 *
 * Kaks omadust, mis vanal süsteemil puudusid:
 *   1. IDEMPOTENTSUS — korduv käivitus ei tekita duplikaate ega muuda ajalugu
 *   2. ENESEPARANDUS — iga käivitus kontrollib viimaseid päevi ja täidab augud
 *
 * Vana skript jooksis üks kord päevas kell 14:55. Kui see käivitus ebaõnnestus,
 * oli päev jäädavalt puudu ja keegi ei märganud.
 */
class MarketPriceIngestor
{
    public function __construct(private readonly EleringClient $client) {}

    /**
     * Loeb ühe Eesti kohaliku ööpäeva hinnad.
     *
     * @return int kirjutatud ridade arv
     */
    public function ingestDay(CarbonImmutable $localDay, string $kind = 'fetch'): int
    {
        $run = IngestionRun::create([
            'kind' => $kind,
            'started_at' => CarbonImmutable::now(),
            'status' => 'ok',
        ]);

        try {
            $start = $localDay->setTimezone('Europe/Tallinn')->startOfDay();

            $rows = $this->client->fetchRange($start->utc(), $start->addDay()->utc());
            $resolution = $this->detectResolution($rows);
            $now = CarbonImmutable::now();

            foreach (array_chunk($rows, 200) as $chunk) {
                MarketPrice::upsert(
                    array_map(static fn (array $row): array => [
                        'zone_code' => 'EE',
                        'period_start_utc' => $row['period_start_utc']->toDateTimeString(),
                        'resolution_minutes' => $resolution,
                        'price_eur_mwh' => $row['price_eur_mwh'],
                        'source' => 'elering',
                        'fetched_at' => $now->toDateTimeString(),
                    ], $chunk),
                    ['zone_code', 'period_start_utc'],
                    ['resolution_minutes', 'price_eur_mwh', 'source', 'fetched_at'],
                );
            }

            $run->update([
                'finished_at' => CarbonImmutable::now(),
                'rows_written' => count($rows),
            ]);

            return count($rows);
        } catch (\Throwable $e) {
            $run->update([
                'finished_at' => CarbonImmutable::now(),
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Kontrollib viimaseid päevi ja loeb puudulikud uuesti sisse.
     *
     * @return int täidetud ridade arv
     */
    public function fillGaps(int $hoursBack = 48): int
    {
        $today = CarbonImmutable::now('Europe/Tallinn')->startOfDay();
        $day = $today->subHours($hoursBack)->startOfDay();
        $filled = 0;

        while ($day <= $today) {
            if ($this->isIncomplete($day)) {
                try {
                    $filled += $this->ingestDay($day, 'gapfill');
                } catch (\Throwable) {
                    // Üks ebaõnnestunud päev ei tohi peatada ülejäänute täitmist;
                    // tõrge on juba ingestion_runs tabelis kirjas
                }
            }

            $day = $day->addDay();
        }

        return $filled;
    }

    private function isIncomplete(CarbonImmutable $localDay): bool
    {
        $existing = MarketPrice::forLocalDay($localDay)->count();

        return $existing < $this->expectedRowCount($localDay);
    }

    /**
     * Oodatav ridade arv. Arvestab suveaja üleminekuga: 25.10 on 25-tunnine ja
     * 29.03 23-tunnine päev — fikseeritud 24 annaks vale vastuse kaks korda aastas.
     */
    private function expectedRowCount(CarbonImmutable $localDay): int
    {
        $start = $localDay->setTimezone('Europe/Tallinn')->startOfDay();
        $hours = (int) round($start->diffInMinutes($start->addDay()) / 60);

        $latest = MarketPrice::latestFirst()->first();
        $perHour = $latest && $latest->resolution_minutes === 15 ? 4 : 1;

        return $hours * $perHour;
    }

    /** @param array<int, array{period_start_utc: CarbonImmutable}> $rows */
    private function detectResolution(array $rows): int
    {
        if (count($rows) < 2) {
            return 60;
        }

        $delta = $rows[1]['period_start_utc']->getTimestamp() - $rows[0]['period_start_utc']->getTimestamp();

        return max(1, (int) round($delta / 60));
    }
}
