<?php

namespace Tests\Feature\Pricing;

use App\Domain\Pricing\ContractContext;
use App\Domain\Pricing\DayPriceAssembler;
use App\Models\GridPackage;
use App\Models\MarketPrice;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DayPriceAssemblerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function leping(): ContractContext
    {
        return new ContractContext(
            package: GridPackage::with('timePatterns')->where('code', 'vork2')->firstOrFail(),
            supplierMarginCentsPerKwh: 0.40,
            amperage: 25,
            phases: 1,
            connectionType: 'main_fuse',
            vatApplicable: true,
        );
    }

    private function hind(string $utc, float $eurMwh, int $resolution = 15): void
    {
        MarketPrice::create([
            'zone_code' => 'EE',
            'period_start_utc' => CarbonImmutable::parse($utc, 'UTC'),
            'resolution_minutes' => $resolution,
            'price_eur_mwh' => $eurMwh,
            'source' => 'elering',
            'fetched_at' => now(),
        ]);
    }

    public function test_15_min_intervallid_keskmistatakse_tunniks(): void
    {
        // Eesti 18.08.2026 kell 12 = 09:00 UTC (EEST)
        $this->hind('2026-08-18 09:00', 40.0);
        $this->hind('2026-08-18 09:15', 60.0);
        $this->hind('2026-08-18 09:30', 80.0);
        $this->hind('2026-08-18 09:45', 20.0);

        $tulemus = app(DayPriceAssembler::class)->assemble(
            CarbonImmutable::parse('2026-08-18', 'Europe/Tallinn'),
            $this->leping(),
        );

        $this->assertTrue($tulemus['available']);
        $this->assertCount(1, $tulemus['hours']);

        $tund = $tulemus['hours'][0];
        $this->assertSame(12, $tund['hour']);
        $this->assertCount(4, $tund['intervals']);
        // Keskmine (40+60+80+20)/4 = 50 EUR/MWh = 5.0 senti/kWh
        $this->assertSame(5.0, $tund['breakdown']['spot']);
    }

    public function test_intervallid_sailivad_tunni_sees(): void
    {
        $this->hind('2026-08-18 09:00', 40.0);
        $this->hind('2026-08-18 09:15', 60.0);

        $tulemus = app(DayPriceAssembler::class)->assemble(
            CarbonImmutable::parse('2026-08-18', 'Europe/Tallinn'),
            $this->leping(),
        );

        $intervallid = $tulemus['hours'][0]['intervals'];
        $this->assertSame(['12:00', '12:15'], array_column($intervallid, 'label'));
        $this->assertSame([4.0, 6.0], array_column($intervallid, 'spot'));
    }

    public function test_osaliselt_avaldatud_paev_on_margitud(): void
    {
        // Nord Pool avaldab homse ~13.45 — enne seda on olemas ainult ööpäeva algus
        $this->hind('2026-08-18 09:00', 40.0, 60);

        $tulemus = app(DayPriceAssembler::class)->assemble(
            CarbonImmutable::parse('2026-08-18', 'Europe/Tallinn'),
            $this->leping(),
        );

        $this->assertTrue($tulemus['available']);
        $this->assertTrue($tulemus['partial']);
        $this->assertSame(24, $tulemus['hours_expected']);
    }

    public function test_taielik_paev_ei_ole_osaline(): void
    {
        $algus = CarbonImmutable::parse('2026-08-17 21:00', 'UTC');   // 18.08 00:00 Eesti aeg
        for ($i = 0; $i < 24; $i++) {
            $this->hind($algus->addHours($i)->toDateTimeString(), 50.0, 60);
        }

        $tulemus = app(DayPriceAssembler::class)->assemble(
            CarbonImmutable::parse('2026-08-18', 'Europe/Tallinn'),
            $this->leping(),
        );

        $this->assertFalse($tulemus['partial']);
        $this->assertCount(24, $tulemus['hours']);
    }

    public function test_suveaja_uleminekul_on_oodatud_tunde_25(): void
    {
        // 25.10.2026 on kellakeeramise päev — 25-tunnine ööpäev
        $this->hind('2026-10-25 09:00', 40.0, 60);

        $tulemus = app(DayPriceAssembler::class)->assemble(
            CarbonImmutable::parse('2026-10-25', 'Europe/Tallinn'),
            $this->leping(),
        );

        $this->assertSame(25, $tulemus['hours_expected']);
    }

    public function test_puuduvad_andmed_ei_ole_viga(): void
    {
        $tulemus = app(DayPriceAssembler::class)->assemble(
            CarbonImmutable::parse('2030-01-01', 'Europe/Tallinn'),
            $this->leping(),
        );

        $this->assertFalse($tulemus['available']);
        $this->assertSame([], $tulemus['hours']);
        $this->assertNull($tulemus['stats']);
    }

    public function test_statistika_arvutatakse_lopphinnast(): void
    {
        $this->hind('2026-08-18 09:00', 40.0, 60);   // 12:00 Eesti aeg, päevatariif
        $this->hind('2026-08-18 00:00', 10.0, 60);   // 03:00 Eesti aeg, öötariif

        $tulemus = app(DayPriceAssembler::class)->assemble(
            CarbonImmutable::parse('2026-08-18', 'Europe/Tallinn'),
            $this->leping(),
        );

        $this->assertCount(2, $tulemus['hours']);
        $this->assertLessThan($tulemus['stats']['max'], $tulemus['stats']['min']);
        $this->assertGreaterThan(0, $tulemus['stats']['avg']);
    }

    public function test_tunnid_on_ajalises_jarjekorras(): void
    {
        $this->hind('2026-08-18 09:00', 40.0, 60);
        $this->hind('2026-08-18 00:00', 10.0, 60);
        $this->hind('2026-08-18 05:00', 20.0, 60);

        $tulemus = app(DayPriceAssembler::class)->assemble(
            CarbonImmutable::parse('2026-08-18', 'Europe/Tallinn'),
            $this->leping(),
        );

        $this->assertSame([3, 8, 12], array_column($tulemus['hours'], 'hour'));
    }

    public function test_tariifiliik_on_iga_tunni_juures(): void
    {
        $this->hind('2026-08-18 09:00', 40.0, 60);   // 12:00 → päev
        $this->hind('2026-08-18 00:00', 10.0, 60);   // 03:00 → öö

        $tulemus = app(DayPriceAssembler::class)->assemble(
            CarbonImmutable::parse('2026-08-18', 'Europe/Tallinn'),
            $this->leping(),
        );

        $liigid = array_column(array_column($tulemus['hours'], 'breakdown'), 'rate_kind');
        $this->assertSame(['night', 'day'], $liigid);
    }
}
