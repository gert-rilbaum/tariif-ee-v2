<?php

namespace App\Console\Commands;

use App\Models\MarketPrice;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class VerifyPricesCommand extends Command
{
    protected $signature = 'prices:verify {--days=7 : Mitut viimast päeva kontrollida}';

    protected $description = 'Kontrollib, kas viimastel päevadel on kõik hinnaread olemas';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $today = CarbonImmutable::now('Europe/Tallinn')->startOfDay();
        $puudulikud = [];

        for ($i = $days; $i >= 1; $i--) {
            $day = $today->subDays($i);
            $olemas = MarketPrice::forLocalDay($day)->count();
            $oodatud = $this->expectedRowCount($day);

            if ($olemas < $oodatud) {
                $puudulikud[] = sprintf('%s (%d/%d)', $day->toDateString(), $olemas, $oodatud);
            }
        }

        if ($puudulikud !== []) {
            $this->error('Puudulikud päevad: '.implode(', ', $puudulikud));

            return self::FAILURE;
        }

        $this->info(sprintf('Viimased %d päeva on terviklikud.', $days));

        return self::SUCCESS;
    }

    /** Arvestab suveaja üleminekuga — 25.10 on 25-tunnine päev. */
    private function expectedRowCount(CarbonImmutable $localDay): int
    {
        $start = $localDay->setTimezone('Europe/Tallinn')->startOfDay();
        $hours = (int) round($start->diffInMinutes($start->addDay()) / 60);

        $naide = MarketPrice::forLocalDay($localDay)->first();
        $perHour = $naide && $naide->resolution_minutes === 15 ? 4 : 1;

        return $hours * $perHour;
    }
}
