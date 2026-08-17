<?php

namespace App\Console\Commands;

use App\Domain\Ingestion\MarketPriceIngestor;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class FetchPricesCommand extends Command
{
    protected $signature = 'prices:fetch
                            {--date= : Loe ainult see kuupäev (Y-m-d, Eesti aeg)}
                            {--no-gapfill : Jäta augutäide vahele}';

    protected $description = 'Loeb Eleringist tänase ja homse hinnad ning täidab viimaste päevade augud';

    public function handle(MarketPriceIngestor $ingestor): int
    {
        $days = $this->targetDays();
        $total = 0;
        $failures = 0;

        foreach ($days as $day) {
            try {
                $rows = $ingestor->ingestDay($day);
                $total += $rows;
                $this->line(sprintf('  %s: %d rida', $day->toDateString(), $rows));
            } catch (\Throwable $e) {
                // Homse puudumine enne kella 14 ei ole tõrge, aga logime siiski
                $failures++;
                $this->warn(sprintf('  %s: %s', $day->toDateString(), $e->getMessage()));
            }
        }

        if (! $this->option('no-gapfill')) {
            $filled = $ingestor->fillGaps();

            if ($filled > 0) {
                $this->line(sprintf('  augutäide: %d rida', $filled));
            }
        }

        $this->info(sprintf('Sisse loetud %d rida.', $total));

        // Tõrge ainult siis, kui KÕIK päevad ebaõnnestusid — muidu jääks
        // ajastatud töö igal hommikul punaseks ainult puuduva homse pärast
        return $failures === count($days) ? self::FAILURE : self::SUCCESS;
    }

    /** @return array<int, CarbonImmutable> */
    private function targetDays(): array
    {
        if ($date = $this->option('date')) {
            return [CarbonImmutable::parse($date, 'Europe/Tallinn')];
        }

        $today = CarbonImmutable::now('Europe/Tallinn')->startOfDay();

        return [$today, $today->addDay()];
    }
}
