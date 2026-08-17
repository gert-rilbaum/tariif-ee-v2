<?php

namespace Tests\Unit\Models;

use App\Models\StateFee;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StateFeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_kehtivad_tasud_valitakse_kuupaeva_jargi(): void
    {
        StateFee::create(['code' => 'renewable', 'valid_from' => '2025-01-01', 'valid_to' => '2025-12-31',
            'cents_per_kwh' => 1.0000, 'source_url' => 'test', 'verified_at' => now()]);
        StateFee::create(['code' => 'renewable', 'valid_from' => '2026-01-01', 'valid_to' => null,
            'cents_per_kwh' => 0.8400, 'source_url' => 'test', 'verified_at' => now()]);
        StateFee::create(['code' => 'excise', 'valid_from' => '2026-01-01', 'valid_to' => null,
            'cents_per_kwh' => 0.2100, 'source_url' => 'test', 'verified_at' => now()]);

        $fees2026 = StateFee::activeAt(CarbonImmutable::parse('2026-08-18 12:00', 'Europe/Tallinn'));
        $this->assertSame(0.84, $fees2026['renewable']);
        $this->assertSame(0.21, $fees2026['excise']);

        $fees2025 = StateFee::activeAt(CarbonImmutable::parse('2025-06-01 12:00', 'Europe/Tallinn'));
        $this->assertSame(1.0, $fees2025['renewable']);
        $this->assertNull($fees2025->get('excise'));
    }

    public function test_puuduv_tasu_ei_ole_null_vaid_puudub(): void
    {
        $fees = StateFee::activeAt(CarbonImmutable::parse('2026-08-18 12:00', 'Europe/Tallinn'));

        // Tühi kogum, mitte nullidega täidetud — kalkulaator peab vahet tegema
        $this->assertTrue($fees->isEmpty());
        $this->assertNull($fees->get('renewable'));
    }

    public function test_aegunud_kirjed_leitakse_ules(): void
    {
        StateFee::create(['code' => 'supply_security', 'valid_from' => '2024-01-01', 'valid_to' => '2025-12-31',
            'cents_per_kwh' => 0.5000, 'source_url' => 'test', 'verified_at' => now()]);

        // Kirje, mille kehtivus on läbi, ei tohi vaikselt kehtida
        $fees = StateFee::activeAt(CarbonImmutable::parse('2026-08-18 12:00', 'Europe/Tallinn'));
        $this->assertNull($fees->get('supply_security'));
    }
}
