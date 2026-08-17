<?php

namespace App\Domain\Pricing;

use App\Models\MarketPrice;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Koostab ühe ööpäeva hinnad vaate jaoks.
 *
 * Kaks lahutusvõimet:
 *   'hour'    — 15-min intervallid keskmistatakse tunniks (vaikimisi, loetavam)
 *   'quarter' — iga 15-min intervall omaette (Elering läks sellele 2025-10-01)
 *
 * Ükski arvutus ei toimu brauseris — vana tariif.ee arvutas JS-is ja numbrid
 * vananesid vaikselt.
 */
class DayPriceAssembler
{
    public const HOUR = 'hour';

    public const QUARTER = 'quarter';

    public function __construct(private readonly PriceCalculator $calculator) {}

    /**
     * @return array{
     *     date: string,
     *     available: bool,
     *     partial: bool,
     *     granularity: string,
     *     quarter_available: bool,
     *     slots_expected: int,
     *     points: array<int, array<string, mixed>>,
     *     stats: array{min: float, max: float, avg: float, min_at: string, max_at: string}|null,
     *     data_updated_at: string|null
     * }
     */
    public function assemble(CarbonImmutable $localDay, ContractContext $ctx, string $granularity = self::HOUR): array
    {
        $day = $localDay->setTimezone('Europe/Tallinn')->startOfDay();
        $read = MarketPrice::forLocalDay($day)->orderBy('period_start_utc')->get();
        $tunde = $this->expectedHours($day);

        // 15-min vaade on võimalik ainult siis, kui andmed ON 15-min sammuga.
        // Vanem ajalugu (kuni 2025-09-30) on tunnisammuga — siis ei teeskle
        // neljandikke, vaid langeb tagasi tunnivaatele.
        $quarterAvailable = $read->isNotEmpty()
            && $read->every(fn (MarketPrice $p) => $p->resolution_minutes === 15);

        if ($read->isEmpty()) {
            return [
                'date' => $day->toDateString(),
                'available' => false,
                'partial' => false,
                'granularity' => self::HOUR,
                'quarter_available' => false,
                'slots_expected' => $tunde,
                'points' => [],
                'stats' => null,
                'data_updated_at' => null,
            ];
        }

        $kasutatav = ($granularity === self::QUARTER && $quarterAvailable) ? self::QUARTER : self::HOUR;

        $points = $kasutatav === self::QUARTER
            ? $this->asQuarters($read, $ctx)
            : $this->asHours($read, $ctx);

        $slots = $kasutatav === self::QUARTER ? $tunde * 4 : $tunde;

        return [
            'date' => $day->toDateString(),
            'available' => true,
            // Osaliselt avaldatud päev ei tohi näida täisväärtuslikuna
            'partial' => count($points) < $slots,
            'granularity' => $kasutatav,
            'quarter_available' => $quarterAvailable,
            'slots_expected' => $slots,
            'points' => $points,
            'stats' => $this->stats($points),
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
    private function asHours(Collection $read, ContractContext $ctx): array
    {
        return $read
            ->groupBy(fn (MarketPrice $p) => $p->period_start_utc->setTimezone('Europe/Tallinn')->format('Y-m-d H'))
            ->map(function (Collection $group) use ($ctx) {
                $spot = $group->avg(fn (MarketPrice $p) => $p->centsPerKwh());
                $first = $group->first()->period_start_utc;

                return $this->point($first, $spot, $ctx, $group->count());
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, MarketPrice>  $read
     * @return array<int, array<string, mixed>>
     */
    private function asQuarters(Collection $read, ContractContext $ctx): array
    {
        return $read
            ->map(fn (MarketPrice $p) => $this->point($p->period_start_utc, $p->centsPerKwh(), $ctx, 1))
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function point(CarbonImmutable $utc, float $spot, ContractContext $ctx, int $intervals): array
    {
        $local = $utc->setTimezone('Europe/Tallinn');
        $breakdown = $this->calculator->forInstant($spot, $utc, $ctx);

        return [
            'starts_at' => $local->toIso8601String(),
            'hour' => (int) $local->format('G'),
            'minute' => (int) $local->format('i'),
            'label' => $local->format('H:i'),
            'intervals' => $intervals,
            'total_inc_vat' => round($breakdown->totalIncVat, 3),
            'breakdown' => $breakdown->toArray(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $points
     * @return array{min: float, max: float, avg: float, min_at: string, max_at: string}|null
     */
    private function stats(array $points): ?array
    {
        if ($points === []) {
            return null;
        }

        $totals = array_column($points, 'total_inc_vat');
        $minIndex = array_search(min($totals), $totals, true);
        $maxIndex = array_search(max($totals), $totals, true);

        return [
            'min' => min($totals),
            'max' => max($totals),
            'avg' => array_sum($totals) / count($totals),
            'min_at' => $points[$minIndex]['label'],
            'max_at' => $points[$maxIndex]['label'],
        ];
    }
}
