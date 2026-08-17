<?php

namespace App\Console\Commands;

use App\Models\MarketPrice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Võtab üle vana `market_price` tabeli sisu.
 *
 * Seis 18.08.2026: samas andmebaasis on tabel `market_price` 46 408 reaga
 * (2023-12-25 … 2026-08-18, augusid 0), mida täitis Node-skript cron'ist.
 * See on väärtuslik ajalugu — Eleringi API ei garanteeri vanade andmete
 * kättesaadavust igavesti.
 *
 * Vana tabelit EI muudeta ega kustutata.
 */
class AdoptLegacyPricesCommand extends Command
{
    protected $signature = 'prices:adopt-legacy
                            {--table=market_price : Vana tabeli nimi}
                            {--chunk=2000 : Mitu rida korraga}';

    protected $description = 'Võtab vana market_price tabeli sisu üle market_prices tabelisse';

    /**
     * Elering läks tunnisammult 15-min sammule 2025-10-01.
     * Kontrollitud päris andmetest: 30.09 oli 24 rida, 01.10 oli 96 rida.
     */
    private const FIFTEEN_MIN_FROM = '2025-10-01 00:00:00';

    public function handle(): int
    {
        $table = $this->option('table');

        if (! Schema::hasTable($table)) {
            $this->error("Tabelit '{$table}' ei ole — pole midagi üle võtta.");

            return self::FAILURE;
        }

        $olemas = MarketPrice::count();
        $vanu = DB::table($table)->count();
        $this->line("Vanas tabelis {$vanu} rida, uues juba {$olemas}.");

        $now = now()->toDateTimeString();
        $lisatud = 0;

        DB::table($table)
            ->orderBy('period_start_utc')
            ->chunk((int) $this->option('chunk'), function ($read) use (&$lisatud, $now) {
                $payload = [];

                foreach ($read as $rida) {
                    $algus = (string) $rida->period_start_utc;

                    $payload[] = [
                        'zone_code' => $rida->zone_code,
                        'period_start_utc' => $algus,
                        'resolution_minutes' => $algus >= self::FIFTEEN_MIN_FROM ? 15 : 60,
                        'price_eur_mwh' => $rida->price_eur_mwh,
                        'source' => 'legacy',
                        'fetched_at' => $now,
                    ];
                }

                // insertOrIgnore, MITTE upsert: Eleringist juba loetud read on
                // tõeallikas ja neid ei tohi vana koopiaga üle kirjutada
                $lisatud += MarketPrice::insertOrIgnore($payload);

                $this->output->write('.');
            });

        $this->newLine();
        $this->info(sprintf('Lisatud %d uut rida. Kokku nüüd %d.', $lisatud, MarketPrice::count()));

        return self::SUCCESS;
    }
}
