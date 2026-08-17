<?php

namespace App\Domain\Pricing;

use App\Domain\Calendar\HolidayCalendar;
use App\Models\GridPackage;
use App\Models\GridTimePattern;
use Carbon\CarbonImmutable;

/**
 * Otsustab, milline võrgutariifi liik antud hetkel kehtib.
 *
 * Kolm asja, mille vana tariif.ee tegi valesti:
 *   1. riigipühad olid kõvakodeeritud massiiv, mis vananes (HOLIDAYS_2025)
 *   2. päev/öö piirid olid koodis, mitte andmetes
 *   3. ajavöönd tuletati fikseeritud nihkega, mitte päris ajavööndist
 *
 * Siin ei ole ühtegi kellaaega ega kuupäeva kõvakodeeritud — kõik tuleb
 * tabelist grid_time_patterns.
 */
class RateResolver
{
    public function __construct(private readonly HolidayCalendar $holidays) {}

    /**
     * @throws \RuntimeException kui ükski muster ei kata seda hetke.
     *                           Vaikne varuvariant tähendaks vale hinda.
     */
    public function resolve(GridPackage $package, CarbonImmutable $instant): string
    {
        $local = $instant->setTimezone('Europe/Tallinn');

        $isRestDay = $local->isoWeekday() >= 6 || $this->holidays->isHoliday($local);
        $isoDay = (string) $local->isoWeekday();
        $time = $local->format('H:i:s');

        foreach ($package->timePatterns->sortBy('priority') as $pattern) {
            if ($this->matches($pattern, $isoDay, $time, $isRestDay)) {
                return $pattern->rate_kind;
            }
        }

        throw new \RuntimeException(sprintf(
            "Võrgupaketil '%s' puudub ajamuster hetkeks %s (%s)",
            $package->code,
            $local->toDateTimeString(),
            $isRestDay ? 'puhkepäev' : 'tööpäev'
        ));
    }

    private function matches(GridTimePattern $pattern, string $isoDay, string $time, bool $isRestDay): bool
    {
        // Muster, mis kehtib ainult tööpäeviti, ei kehti nädalavahetusel ega riigipühal
        if ($pattern->holiday_behaviour === 'as_weekend' && $isRestDay) {
            return false;
        }

        if (! str_contains($pattern->weekdays, $isoDay)) {
            return false;
        }

        return $this->inWindow($time, $pattern->start_time, $pattern->end_time);
    }

    /** Algus kaasav, lõpp välistav. Toetab üle südaöö ulatuvat akent. */
    private function inWindow(string $time, string $start, string $end): bool
    {
        $start = $this->normalise($start);
        $end = $this->normalise($end);

        if ($end === '00:00:00') {
            $end = '24:00:00';
        }

        if ($start === $end) {
            return true;                       // kogu ööpäev
        }

        return $start < $end
            ? ($time >= $start && $time < $end)
            : ($time >= $start || $time < $end);
    }

    private function normalise(string $time): string
    {
        return strlen($time) === 5 ? $time.':00' : $time;
    }
}
