<?php

namespace Tests\Unit\Pricing;

use App\Domain\Calendar\HolidayCalendar;
use App\Domain\Pricing\RateResolver;
use App\Models\GridOperator;
use App\Models\GridPackage;
use App\Models\GridTimePattern;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateResolverTest extends TestCase
{
    use RefreshDatabase;

    private function resolver(): RateResolver
    {
        return new RateResolver(new HolidayCalendar);
    }

    private function kahetariifne(): GridPackage
    {
        $operator = GridOperator::create(['code' => 'elektrilevi', 'name' => 'Elektrilevi OÜ']);
        $pakett = GridPackage::create(['operator_id' => $operator->id, 'code' => 'vork2',
            'name' => 'Võrk 2', 'scheme' => 'dual']);

        // Päev: tööpäeviti 07–23. Öö: kõik ülejäänu (madalaim prioriteet = varuvariant)
        GridTimePattern::create(['package_id' => $pakett->id, 'rate_kind' => 'day',
            'weekdays' => '12345', 'start_time' => '07:00', 'end_time' => '23:00',
            'holiday_behaviour' => 'as_weekend', 'priority' => 10]);
        GridTimePattern::create(['package_id' => $pakett->id, 'rate_kind' => 'night',
            'weekdays' => '1234567', 'start_time' => '00:00', 'end_time' => '24:00',
            'holiday_behaviour' => 'normal', 'priority' => 90]);

        return $pakett->fresh('timePatterns');
    }

    private function yhetariifne(): GridPackage
    {
        $operator = GridOperator::create(['code' => 'elektrilevi', 'name' => 'Elektrilevi OÜ']);
        $pakett = GridPackage::create(['operator_id' => $operator->id, 'code' => 'vork1',
            'name' => 'Võrk 1', 'scheme' => 'single']);

        GridTimePattern::create(['package_id' => $pakett->id, 'rate_kind' => 'all',
            'weekdays' => '1234567', 'start_time' => '00:00', 'end_time' => '24:00',
            'holiday_behaviour' => 'normal', 'priority' => 10]);

        return $pakett->fresh('timePatterns');
    }

    private function hetk(string $local): CarbonImmutable
    {
        return CarbonImmutable::parse($local, 'Europe/Tallinn')->setTimezone('UTC');
    }

    public function test_toopaeva_paev_ja_oo(): void
    {
        $pakett = $this->kahetariifne();

        // 18.08.2026 on teisipäev
        $this->assertSame('day', $this->resolver()->resolve($pakett, $this->hetk('2026-08-18 12:00')));
        $this->assertSame('night', $this->resolver()->resolve($pakett, $this->hetk('2026-08-18 03:00')));
        $this->assertSame('night', $this->resolver()->resolve($pakett, $this->hetk('2026-08-18 23:30')));
    }

    public function test_paeva_piirid_on_kaasavad_algusest_valistavad_lopust(): void
    {
        $pakett = $this->kahetariifne();

        $this->assertSame('night', $this->resolver()->resolve($pakett, $this->hetk('2026-08-18 06:59')));
        $this->assertSame('day', $this->resolver()->resolve($pakett, $this->hetk('2026-08-18 07:00')));
        $this->assertSame('day', $this->resolver()->resolve($pakett, $this->hetk('2026-08-18 22:59')));
        $this->assertSame('night', $this->resolver()->resolve($pakett, $this->hetk('2026-08-18 23:00')));
    }

    public function test_nadalavahetus_on_alati_oohind(): void
    {
        $pakett = $this->kahetariifne();

        // 15.08.2026 laupäev, 16.08.2026 pühapäev
        $this->assertSame('night', $this->resolver()->resolve($pakett, $this->hetk('2026-08-15 12:00')));
        $this->assertSame('night', $this->resolver()->resolve($pakett, $this->hetk('2026-08-16 12:00')));
    }

    public function test_riigipuha_on_oohind_ka_keskpaeval(): void
    {
        $pakett = $this->kahetariifne();

        // 20.08.2026 on NELJAPÄEV ja taasiseseisvumispäev.
        // Vana sait näitas siin päevahinda, sest HOLIDAYS_2025 ei sisaldanud 2026. aastat.
        $this->assertSame('night', $this->resolver()->resolve($pakett, $this->hetk('2026-08-20 12:00')));

        // Liikuv püha: suur reede 03.04.2026 on reede
        $this->assertSame('night', $this->resolver()->resolve($pakett, $this->hetk('2026-04-03 12:00')));
    }

    public function test_suveaja_uleminek_ei_nihuta_tariifi(): void
    {
        $pakett = $this->kahetariifne();

        // 30.03.2026 esmaspäev, EEST juba kehtib → keskpäev on päevatariif
        $this->assertSame('day', $this->resolver()->resolve($pakett, $this->hetk('2026-03-30 12:00')));

        // 26.10.2026 esmaspäev, EET tagasi → keskpäev on ikka päevatariif
        $this->assertSame('day', $this->resolver()->resolve($pakett, $this->hetk('2026-10-26 12:00')));

        // 25.10.2026 on pühapäev ja 25-tunnine päev → kogu päev öötariif
        $this->assertSame('night', $this->resolver()->resolve($pakett, $this->hetk('2026-10-25 12:00')));
    }

    public function test_utc_sisend_teisendatakse_eesti_aega(): void
    {
        $pakett = $this->kahetariifne();

        // 18.08.2026 05:00 UTC = 08:00 Eestis (EEST) → päevatariif
        $this->assertSame('day', $this->resolver()->resolve($pakett, CarbonImmutable::parse('2026-08-18 05:00', 'UTC')));
        // 18.08.2026 03:00 UTC = 06:00 Eestis → veel öötariif
        $this->assertSame('night', $this->resolver()->resolve($pakett, CarbonImmutable::parse('2026-08-18 03:00', 'UTC')));
    }

    public function test_yhetariifne_pakett_annab_alati_sama(): void
    {
        $pakett = $this->yhetariifne();

        $this->assertSame('all', $this->resolver()->resolve($pakett, $this->hetk('2026-08-18 12:00')));
        $this->assertSame('all', $this->resolver()->resolve($pakett, $this->hetk('2026-08-16 03:00')));
        $this->assertSame('all', $this->resolver()->resolve($pakett, $this->hetk('2026-08-20 12:00')));
    }

    public function test_katmata_tund_viskab_erindi(): void
    {
        $operator = GridOperator::create(['code' => 'elektrilevi', 'name' => 'Elektrilevi OÜ']);
        $pakett = GridPackage::create(['operator_id' => $operator->id, 'code' => 'katki',
            'name' => 'Katkine', 'scheme' => 'dual']);
        GridTimePattern::create(['package_id' => $pakett->id, 'rate_kind' => 'day',
            'weekdays' => '12345', 'start_time' => '07:00', 'end_time' => '23:00',
            'holiday_behaviour' => 'as_weekend', 'priority' => 10]);

        // Öömuster puudub → ei tohi vaikselt päevahinda anda
        $this->expectException(\RuntimeException::class);
        $this->resolver()->resolve($pakett->fresh('timePatterns'), $this->hetk('2026-08-18 03:00'));
    }
}
