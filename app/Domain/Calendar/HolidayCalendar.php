<?php

namespace App\Domain\Calendar;

use Carbon\CarbonImmutable;

/**
 * Eesti riigipühad.
 *
 * Liikuvad pühad ARVUTATAKSE, mitte ei loetleta. Vana tariif.ee sisaldas
 * massiivi HOLIDAYS_2025 ja arvutas seetõttu 2026. a pühadel kahetariifsele
 * võrgupaketile päevahinda — vt spec §2.
 */
class HolidayCalendar
{
    /** Kuu-päev => püha nimi */
    private const FIXED = [
        '01-01' => 'uusaasta',
        '02-24' => 'iseseisvuspäev',
        '05-01' => 'kevadpüha',
        '06-23' => 'võidupüha',
        '06-24' => 'jaanipäev',
        '08-20' => 'taasiseseisvumispäev',
        '12-24' => 'jõululaupäev',
        '12-25' => 'esimene jõulupüha',
        '12-26' => 'teine jõulupüha',
    ];

    /** @var array<int, array<string, string>> aasta => ['Y-m-d' => nimi] */
    private array $cache = [];

    /** @return array<string, string> 'Y-m-d' => püha nimi */
    public function forYear(int $year): array
    {
        if (isset($this->cache[$year])) {
            return $this->cache[$year];
        }

        $days = [];

        foreach (self::FIXED as $monthDay => $name) {
            $days[$year.'-'.$monthDay] = $name;
        }

        $easter = $this->easterSunday($year);
        $days[$easter->subDays(2)->toDateString()] = 'suur reede';
        $days[$easter->toDateString()] = 'ülestõusmispühade 1. püha';
        $days[$easter->addDays(49)->toDateString()] = 'nelipühade 1. püha';

        ksort($days);

        return $this->cache[$year] = $days;
    }

    public function isHoliday(CarbonImmutable $moment): bool
    {
        $date = $moment->toDateString();

        return isset($this->forYear((int) $moment->format('Y'))[$date]);
    }

    public function nameFor(CarbonImmutable $moment): ?string
    {
        return $this->forYear((int) $moment->format('Y'))[$moment->toDateString()] ?? null;
    }

    /**
     * Ülestõusmispühade püha pühapäev (anonymous Gregorian algorithm,
     * Meeus/Jones/Butcher). Kehtib gregooriuse kalendris igal aastal.
     */
    private function easterSunday(int $year): CarbonImmutable
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return CarbonImmutable::create($year, $month, $day, 0, 0, 0, 'UTC');
    }
}
