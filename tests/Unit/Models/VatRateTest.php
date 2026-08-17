<?php

namespace Tests\Unit\Models;

use App\Models\VatRate;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VatRateTest extends TestCase
{
    use RefreshDatabase;

    private function seedKmAjalugu(): void
    {
        VatRate::create(['valid_from' => '2009-07-01', 'valid_to' => '2023-12-31',
            'rate' => 0.2000, 'source_url' => 'test', 'verified_at' => now()]);
        VatRate::create(['valid_from' => '2024-01-01', 'valid_to' => '2025-06-30',
            'rate' => 0.2200, 'source_url' => 'test', 'verified_at' => now()]);
        VatRate::create(['valid_from' => '2025-07-01', 'valid_to' => null,
            'rate' => 0.2400, 'source_url' => 'test', 'verified_at' => now()]);
    }

    public function test_maar_voetakse_syndmuse_hetke_jargi(): void
    {
        $this->seedKmAjalugu();

        $this->assertSame(0.20, VatRate::atMoment(CarbonImmutable::parse('2023-12-31 12:00', 'Europe/Tallinn')));
        $this->assertSame(0.22, VatRate::atMoment(CarbonImmutable::parse('2024-06-01 12:00', 'Europe/Tallinn')));
        $this->assertSame(0.24, VatRate::atMoment(CarbonImmutable::parse('2026-08-18 12:00', 'Europe/Tallinn')));
    }

    public function test_maara_muutumise_piir_on_tapne(): void
    {
        $this->seedKmAjalugu();

        // 30.06.2025 23:00 Tallinnas = veel 22%, 01.07.2025 01:00 = juba 24%
        $this->assertSame(0.22, VatRate::atMoment(CarbonImmutable::parse('2025-06-30 23:00', 'Europe/Tallinn')));
        $this->assertSame(0.24, VatRate::atMoment(CarbonImmutable::parse('2025-07-01 01:00', 'Europe/Tallinn')));
    }

    public function test_utc_hetk_teisendatakse_eesti_kuupaevaks(): void
    {
        $this->seedKmAjalugu();

        // 30.06.2025 21:30 UTC = 01.07.2025 00:30 Eestis (EEST) → juba 24%
        $this->assertSame(0.24, VatRate::atMoment(CarbonImmutable::parse('2025-06-30 21:30', 'UTC')));
        // 30.06.2025 20:30 UTC = 30.06.2025 23:30 Eestis → veel 22%
        $this->assertSame(0.22, VatRate::atMoment(CarbonImmutable::parse('2025-06-30 20:30', 'UTC')));
    }

    public function test_puuduv_maar_viskab_erindi(): void
    {
        $this->seedKmAjalugu();

        // Parem vali viga kui vaikimisi vale määr — vt spec §9
        $this->expectException(\RuntimeException::class);
        VatRate::atMoment(CarbonImmutable::parse('2005-01-01 12:00', 'Europe/Tallinn'));
    }
}
