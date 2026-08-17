<?php

namespace App\Console\Commands;

use App\Domain\Ingestion\MarketPriceIngestor;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class BackfillPricesCommand extends Command
{
    protected $signature = 'prices:backfill
                            {--from= : Algus (Y-m-d, Eesti aeg)}
                            {--to= : Lõpp (Y-m-d, kaasa arvatud)}
                            {--sleep=250 : Paus päevade vahel millisekundites}';

    protected $description = 'Loeb ajaloolised hinnad päev haaval Eleringist';

    public function handle(MarketPriceIngestor $ingestor): int
    {
        if (! $this->option('from') || ! $this->option('to')) {
            $this->error('Nõutud on --from ja --to');

            return self::FAILURE;
        }

        $from = CarbonImmutable::parse($this->option('from'), 'Europe/Tallinn')->startOfDay();
        $to = CarbonImmutable::parse($this->option('to'), 'Europe/Tallinn')->startOfDay();

        if ($from > $to) {
            $this->error('--from peab olema enne --to');

            return self::FAILURE;
        }

        $sleep = (int) $this->option('sleep') * 1000;
        $total = 0;
        $failed = [];

        for ($day = $from; $day <= $to; $day = $day->addDay()) {
            try {
                $total += $ingestor->ingestDay($day, 'backfill');
            } catch (\Throwable $e) {
                $failed[] = $day->toDateString();
                $this->warn(sprintf('%s: %s', $day->toDateString(), $e->getMessage()));
            }

            if ($sleep > 0) {
                usleep($sleep);
            }
        }

        $this->info(sprintf('Sisse loetud %d rida.', $total));

        if ($failed !== []) {
            // Vaikne osaline ebaõnnestumine on hullem kui vali — vt spec §7
            $this->error(sprintf('Ebaõnnestus %d päeva: %s', count($failed), implode(', ', $failed)));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
