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
        $this->assertCount(1, $tulemus['points']);

        $tund = $tulemus['points'][0];
        $this->assertSame(12, $tund['hour']);
        $this->assertSame(4, $tund['intervals']);
        // Keskmine (40+60+80+20)/4 = 50 EUR/MWh = 5.0 senti/kWh
        $this->assertSame(5.0, $tund['breakdown']['spot']);
    }

    public function test_tund_teab_mitmest_intervallist_ta_koosneb(): void
    {
        $this->hind('2026-08-18 09:00', 40.0);
        $this->hind('2026-08-18 09:15', 60.0);

        $tulemus = app(DayPriceAssembler::class)->assemble(
            CarbonImmutable::parse('2026-08-18', 'Europe/Tallinn'),
            $this->leping(),
        );

        $this->assertSame(2, $tulemus['points'][0]['intervals']);
        $this->assertSame(5.0, $tulemus['points'][0]['breakdown']['spot']);
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
        $this->assertSame(24, $tulemus['slots_expected']);
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
        $this->assertCount(24, $tulemus['points']);
    }

    public function test_suveaja_uleminekul_on_oodatud_pesi_25(): void
    {
        // 25.10.2026 on kellakeeramise päev — 25-tunnine ööpäev
        $this->hind('2026-10-25 09:00', 40.0, 60);

        $tulemus = app(DayPriceAssembler::class)->assemble(
            CarbonImmutable::parse('2026-10-25', 'Europe/Tallinn'),
            $this->leping(),
        );

        $this->assertSame(25, $tulemus['slots_expected']);
    }

    public function test_puuduvad_andmed_ei_ole_viga(): void
    {
        $tulemus = app(DayPriceAssembler::class)->assemble(
            CarbonImmutable::parse('2030-01-01', 'Europe/Tallinn'),
            $this->leping(),
        );

        $this->assertFalse($tulemus['available']);
        $this->assertSame([], $tulemus['points']);
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

        $this->assertCount(2, $tulemus['points']);
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

        $this->assertSame([3, 8, 12], array_column($tulemus['points'], 'hour'));
    }

    public function test_15_min_vaade_naitab_iga_intervalli_eraldi(): void
    {
        $this->hind('2026-08-18 09:00', 40.0);
        $this->hind('2026-08-18 09:15', 60.0);
        $this->hind('2026-08-18 09:30', 80.0);
        $this->hind('2026-08-18 09:45', 20.0);

        $tulemus = app(DayPriceAssembler::class)->assemble(
            CarbonImmutable::parse('2026-08-18', 'Europe/Tallinn'),
            $this->leping(),
            DayPriceAssembler::QUARTER,
        );

        $this->assertSame('quarter', $tulemus['granularity']);
        $this->assertCount(4, $tulemus['points']);
        $this->assertSame(96, $tulemus['slots_expected']);
        $this->assertSame(['12:00', '12:15', '12:30', '12:45'], array_column($tulemus['points'], 'label'));
        $this->assertSame([4.0, 6.0, 8.0, 2.0], array_column(array_column($tulemus['points'], 'breakdown'), 'spot'));
    }

    public function test_tunnisammuga_andmed_ei_teeskle_neljandikke(): void
    {
        // Kuni 2025-09-30 luges Elering tunnisammuga. Kuupäev on siin hilisem
        // ainult sellepärast, et seemendatud hinnakiri kehtib alates 2026-06-01 —
        // otsustav on resolution_minutes, mitte kuupäev.
        $this->hind('2026-08-18 09:00', 40.0, 60);

        $tulemus = app(DayPriceAssembler::class)->assemble(
            CarbonImmutable::parse('2026-08-18', 'Europe/Tallinn'),
            $this->leping(),
            DayPriceAssembler::QUARTER,
        );

        $this->assertFalse($tulemus['quarter_available']);
        $this->assertSame('hour', $tulemus['granularity']);
    }

    public function test_statistika_sisaldab_aarmuste_kellaaegu(): void
    {
        $this->hind('2026-08-18 09:00', 40.0, 60);   // 12:00
        $this->hind('2026-08-18 00:00', 10.0, 60);   // 03:00

        $tulemus = app(DayPriceAssembler::class)->assemble(
            CarbonImmutable::parse('2026-08-18', 'Europe/Tallinn'),
            $this->leping(),
        );

        $this->assertSame('03:00', $tulemus['stats']['min_at']);
        $this->assertSame('12:00', $tulemus['stats']['max_at']);
    }

    public function test_tariifiliik_on_iga_tunni_juures(): void
    {
        $this->hind('2026-08-18 09:00', 40.0, 60);   // 12:00 → päev
        $this->hind('2026-08-18 00:00', 10.0, 60);   // 03:00 → öö

        $tulemus = app(DayPriceAssembler::class)->assemble(
            CarbonImmutable::parse('2026-08-18', 'Europe/Tallinn'),
            $this->leping(),
        );

        $liigid = array_column(array_column($tulemus['points'], 'breakdown'), 'rate_kind');
        $this->assertSame(['night', 'day'], $liigid);
    }
}
