<?php

namespace Tests\Unit\Calendar;

use App\Domain\Calendar\HolidayCalendar;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class HolidayCalendarTest extends TestCase
{
    public function test_fikseeritud_puhad(): void
    {
        $cal = new HolidayCalendar;

        $this->assertTrue($cal->isHoliday(CarbonImmutable::parse('2026-02-24')));  // iseseisvuspäev
        $this->assertTrue($cal->isHoliday(CarbonImmutable::parse('2026-08-20')));  // taasiseseisvumispäev
        $this->assertTrue($cal->isHoliday(CarbonImmutable::parse('2026-12-26')));  // teine jõulupüha
        $this->assertFalse($cal->isHoliday(CarbonImmutable::parse('2026-08-17')));
    }

    public function test_liikuvad_puhad_arvutatakse(): void
    {
        $cal = new HolidayCalendar;

        // Ülestõusmispühad 2026: 5. aprill → suur reede 3. aprill
        $this->assertTrue($cal->isHoliday(CarbonImmutable::parse('2026-04-03')));
        $this->assertTrue($cal->isHoliday(CarbonImmutable::parse('2026-04-05')));
        // Nelipühad = ülestõusmispühad + 49 päeva = 24. mai 2026
        $this->assertTrue($cal->isHoliday(CarbonImmutable::parse('2026-05-24')));
    }

    public function test_liikuvad_puhad_ka_teistel_aastatel(): void
    {
        $cal = new HolidayCalendar;

        // Ülestõusmispühad 2025: 20. aprill; 2027: 28. märts
        $this->assertTrue($cal->isHoliday(CarbonImmutable::parse('2025-04-20')));
        $this->assertTrue($cal->isHoliday(CarbonImmutable::parse('2025-04-18')));  // suur reede
        $this->assertTrue($cal->isHoliday(CarbonImmutable::parse('2027-03-28')));
        $this->assertTrue($cal->isHoliday(CarbonImmutable::parse('2027-03-26')));  // suur reede
    }

    public function test_tootab_ka_kaugetel_tulevastel_aastatel(): void
    {
        $cal = new HolidayCalendar;

        $this->assertTrue($cal->isHoliday(CarbonImmutable::parse('2031-06-23'))); // võidupüha
        $this->assertCount(12, $cal->forYear(2035));                              // 9 fikseeritud + 3 liikuvat
    }

    public function test_kellaaeg_ei_moju_tulemusele(): void
    {
        $cal = new HolidayCalendar;

        $this->assertTrue($cal->isHoliday(CarbonImmutable::parse('2026-08-20 23:59:59', 'Europe/Tallinn')));
        $this->assertTrue($cal->isHoliday(CarbonImmutable::parse('2026-08-20 00:00:00', 'Europe/Tallinn')));
    }
}
