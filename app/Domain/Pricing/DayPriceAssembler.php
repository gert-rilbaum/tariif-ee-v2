<?php

namespace App\Domain\Pricing;

use App\Models\MarketPrice;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Koostab ühe ööpäeva tunnihinnad vaate jaoks.
 *
 * 15-minutilised intervallid keskmistatakse tunniks, aga originaalintervallid
 * jäävad alles (kasutaja saab tunni sees hinnahüpped näha). Ükski arvutus ei
 * toimu brauseris — vana tariif.ee arvutas JS-is ja numbrid vananesid vaikselt.
 */
class DayPriceAssembler
{
    public function __construct(private readonly PriceCalculator $calculator) {}

    /**
     * @return array{
     *     date: string,
     *     available: bool,
     *     partial: bool,
     *     hours_expected: int,
     *     hours: array<int, array<string, mixed>>,
     *     stats: array{min: float, max: float, avg: float}|null,
     *     data_updated_at: string|null
     * }
     */
    public function assemble(CarbonImmutable $localDay, ContractContext $ctx): array
    {
        $day = $localDay->setTimezone('Europe/Tallinn')->startOfDay();
        $read = MarketPrice::forLocalDay($day)->orderBy('period_start_utc')->get();

        if ($read->isEmpty()) {
            return [
                'date' => $day->toDateString(),
                'available' => false,
                'partial' => false,
                'hours_expected' => $this->expectedHours($day),
                'hours' => [],
                'stats' => null,
                'data_updated_at' => null,
            ];
        }

        $hours = $this->groupIntoHours($read, $ctx);
        $totals = array_map(static fn (array $h): float => $h['total_inc_vat'], $hours);

        $oodatud = $this->expectedHours($day);

        return [
            'date' => $day->toDateString(),
            'available' => true,
            // Osaliselt avaldatud päev ei tohi näida täisväärtuslikuna: Nord Pool
            // avaldab homse ~13.45 ja enne seda on olemas ainult ööpäeva algus
            'partial' => count($hours) < $oodatud,
            'hours_expected' => $oodatud,
            'hours' => $hours,
            'stats' => [
                'min' => min($totals),
                'max' => max($totals),
                'avg' => array_sum($totals) / count($totals),
            ],
            'data_updated_at' => $read->max('fetched_at')?->toIso8601String(),
        ];
    }

    /**
     * Ööpäeva tundide arv. Suveaja üleminekul on 23 või 25 tundi — fikseeritud
     * 24 annaks kaks korda aastas vale vastuse.
     */
    private function expectedHours(CarbonImmutable $day): int
    {
        $algus = $day->startOfDay();

        return (int) round($algus->diffInMinutes($algus->addDay()) / 60);
    }

    /**
     * @param  Collection<int, MarketPrice>  $read
     * @return array<int, array<string, mixed>>
     */
    private function groupIntoHours(Collection $read, ContractContext $ctx): array
    {
        return $read
            ->groupBy(fn (MarketPrice $p) => $p->period_start_utc->setTimezone('Europe/Tallinn')->format('Y-m-d H'))
            ->map(function (Collection $group) use ($ctx) {
                $spot = $group->avg(fn (MarketPrice $p) => $p->centsPerKwh());
                $first = $group->first()->period_start_utc;
                $local = $first->setTimezone('Europe/Tallinn');

                $breakdown = $this->calculator->forInstant($spot, $first, $ctx);

                return [
                    'starts_at' => $local->toIso8601String(),
                    'hour' => (int) $local->format('G'),
                    'label' => $local->format('H:i'),
                    'intervals' => $group->map(fn (MarketPrice $p) => [
                        'label' => $p->period_start_utc->setTimezone('Europe/Tallinn')->format('H:i'),
                        'spot' => round($p->centsPerKwh(), 3),
                    ])->values()->all(),
                    'total_inc_vat' => round($breakdown->totalIncVat, 3),
                    'breakdown' => $breakdown->toArray(),
                ];
            })
            ->values()
            ->all();
    }
}
