<?php

namespace Tests\Unit\Pricing;

use App\Domain\Pricing\ContractContext;
use App\Domain\Pricing\PriceCalculator;
use App\Models\GridCapacityFee;
use App\Models\GridEnergyRate;
use App\Models\GridOperator;
use App\Models\GridPackage;
use App\Models\GridPackageVersion;
use App\Models\GridTimePattern;
use App\Models\StateFee;
use App\Models\VatRate;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kuldsed testid.
 *
 * NB: siinsed numbrid on TESTI FIKSTUUR, mitte väide päris hinnakirja kohta.
 * Päris numbrid tulevad seemnetest koos allikaviitega (Task 10).
 */
class PriceCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private GridPackage $pakett;

    protected function setUp(): void
    {
        parent::setUp();

        $operator = GridOperator::create(['code' => 'elektrilevi', 'name' => 'Elektrilevi OÜ']);

        $this->pakett = GridPackage::create(['operator_id' => $operator->id, 'code' => 'vork2',
            'name' => 'Võrk 2', 'scheme' => 'dual']);

        $versioon = GridPackageVersion::create(['package_id' => $this->pakett->id,
            'valid_from' => '2026-01-01', 'valid_to' => null, 'base_monthly_eur' => 3.00,
            'source_url' => 'fikstuur', 'verified_at' => now()]);

        GridEnergyRate::create(['version_id' => $versioon->id, 'rate_kind' => 'day', 'cents_per_kwh' => 4.0000]);
        GridEnergyRate::create(['version_id' => $versioon->id, 'rate_kind' => 'night', 'cents_per_kwh' => 2.0000]);
        GridCapacityFee::create(['version_id' => $versioon->id, 'amperage' => 25, 'phases' => 1, 'monthly_eur' => 5.00]);

        GridTimePattern::create(['package_id' => $this->pakett->id, 'rate_kind' => 'day',
            'weekdays' => '12345', 'start_time' => '07:00', 'end_time' => '23:00',
            'holiday_behaviour' => 'as_weekend', 'priority' => 10]);
        GridTimePattern::create(['package_id' => $this->pakett->id, 'rate_kind' => 'night',
            'weekdays' => '1234567', 'start_time' => '00:00', 'end_time' => '24:00',
            'holiday_behaviour' => 'normal', 'priority' => 90]);

        StateFee::create(['code' => 'renewable', 'valid_from' => '2026-01-01', 'valid_to' => null,
            'cents_per_kwh' => 0.8000, 'source_url' => 'fikstuur', 'verified_at' => now()]);
        StateFee::create(['code' => 'supply_security', 'valid_from' => '2026-01-01', 'valid_to' => null,
            'cents_per_kwh' => 0.7000, 'source_url' => 'fikstuur', 'verified_at' => now()]);
        StateFee::create(['code' => 'excise', 'valid_from' => '2026-01-01', 'valid_to' => null,
            'cents_per_kwh' => 0.2000, 'source_url' => 'fikstuur', 'verified_at' => now()]);

        VatRate::create(['valid_from' => '2024-01-01', 'valid_to' => '2025-06-30',
            'rate' => 0.2200, 'source_url' => 'fikstuur', 'verified_at' => now()]);
        VatRate::create(['valid_from' => '2025-07-01', 'valid_to' => null,
            'rate' => 0.2400, 'source_url' => 'fikstuur', 'verified_at' => now()]);
    }

    private function leping(bool $vatApplicable = true, float $margin = 0.40): ContractContext
    {
        return new ContractContext(
            package: $this->pakett->fresh(['timePatterns']),
            supplierMarginCentsPerKwh: $margin,
            amperage: 25,
            phases: 1,
            vatApplicable: $vatApplicable,
        );
    }

    private function hetk(string $local): CarbonImmutable
    {
        return CarbonImmutable::parse($local, 'Europe/Tallinn')->setTimezone('UTC');
    }

    public function test_kuldne_naide_toopaeva_paev(): void
    {
        // 5.00 + 0.40 + 4.00 + 0.80 + 0.70 + 0.20 = 11.10 → KM 24% = 2.664 → 13.764
        $b = app(PriceCalculator::class)->forInstant(5.00, $this->hetk('2026-08-18 12:00'), $this->leping());

        $this->assertSame('day', $b->rateKind);
        $this->assertSame(5.00, $b->spot);
        $this->assertSame(4.00, $b->gridEnergy);
        $this->assertSame(11.10, round($b->subtotalExVat, 2));
        $this->assertSame(2.66, round($b->vat, 2));
        $this->assertSame(13.76, round($b->totalIncVat, 2));
    }

    public function test_kuldne_naide_oo(): void
    {
        // Öötariif 2.00 asemel 4.00 → 9.10 → KM 2.184 → 11.284
        $b = app(PriceCalculator::class)->forInstant(5.00, $this->hetk('2026-08-18 03:00'), $this->leping());

        $this->assertSame('night', $b->rateKind);
        $this->assertSame(2.00, $b->gridEnergy);
        $this->assertSame(11.28, round($b->totalIncVat, 2));
    }

    public function test_riigipuhal_kehtib_oohind(): void
    {
        // 20.08.2026 neljapäev, taasiseseisvumispäev — vana sait arvutas siin päevahinda
        $b = app(PriceCalculator::class)->forInstant(5.00, $this->hetk('2026-08-20 12:00'), $this->leping());

        $this->assertSame('night', $b->rateKind);
        $this->assertSame(2.00, $b->gridEnergy);
    }

    public function test_km_kohuslane_naeb_hinda_ilma_kaibemaksuta(): void
    {
        $b = app(PriceCalculator::class)->forInstant(5.00, $this->hetk('2026-08-18 12:00'), $this->leping(vatApplicable: false));

        $this->assertSame(0.0, $b->vat);
        $this->assertSame(11.10, round($b->totalIncVat, 2));
    }

    public function test_km_maar_voetakse_syndmuse_hetkest(): void
    {
        // Lisame 2025. a hinnaversiooni samade võrgutasudega, et isoleerida KM mõju
        $versioon2025 = GridPackageVersion::create(['package_id' => $this->pakett->id,
            'valid_from' => '2025-01-01', 'valid_to' => '2025-12-31', 'base_monthly_eur' => 3.00,
            'source_url' => 'fikstuur', 'verified_at' => now()]);
        GridEnergyRate::create(['version_id' => $versioon2025->id, 'rate_kind' => 'day', 'cents_per_kwh' => 4.0000]);
        GridEnergyRate::create(['version_id' => $versioon2025->id, 'rate_kind' => 'night', 'cents_per_kwh' => 2.0000]);
        StateFee::create(['code' => 'renewable', 'valid_from' => '2025-01-01', 'valid_to' => '2025-12-31',
            'cents_per_kwh' => 0.8000, 'source_url' => 'fikstuur', 'verified_at' => now()]);
        StateFee::create(['code' => 'supply_security', 'valid_from' => '2025-01-01', 'valid_to' => '2025-12-31',
            'cents_per_kwh' => 0.7000, 'source_url' => 'fikstuur', 'verified_at' => now()]);
        StateFee::create(['code' => 'excise', 'valid_from' => '2025-01-01', 'valid_to' => '2025-12-31',
            'cents_per_kwh' => 0.2000, 'source_url' => 'fikstuur', 'verified_at' => now()]);

        $calc = app(PriceCalculator::class);

        // 30.06.2025 12:00 → veel 22%; 01.07.2025 12:00 → juba 24%
        $enne = $calc->forInstant(5.00, $this->hetk('2025-06-30 12:00'), $this->leping());
        $parast = $calc->forInstant(5.00, $this->hetk('2025-07-01 12:00'), $this->leping());

        $this->assertSame(11.10, round($enne->subtotalExVat, 2));
        $this->assertSame(11.10, round($parast->subtotalExVat, 2));
        $this->assertSame(2.44, round($enne->vat, 2));      // 11.10 × 0.22
        $this->assertSame(2.66, round($parast->vat, 2));    // 11.10 × 0.24
    }

    public function test_negatiivne_borsihind_ei_lahe_katki(): void
    {
        // Negatiivne börsihind on Eestis reaalsus
        $b = app(PriceCalculator::class)->forInstant(-2.00, $this->hetk('2026-08-18 12:00'), $this->leping());

        $this->assertSame(-2.00, $b->spot);
        $this->assertSame(4.10, round($b->subtotalExVat, 2));   // -2 + 0.4 + 4 + 0.8 + 0.7 + 0.2
        $this->assertGreaterThan(0, $b->totalIncVat);
    }

    public function test_puuduv_hinnaversioon_viskab_erindi(): void
    {
        // 2020. aastal ei ole ühtegi hinnakirja — parem viga kui vale hind
        $this->expectException(\RuntimeException::class);
        app(PriceCalculator::class)->forInstant(5.00, $this->hetk('2020-06-01 12:00'), $this->leping());
    }

    public function test_puuduv_riiklik_tasu_viskab_erindi(): void
    {
        StateFee::where('code', 'excise')->delete();

        $this->expectException(\RuntimeException::class);
        app(PriceCalculator::class)->forInstant(5.00, $this->hetk('2026-08-18 12:00'), $this->leping());
    }

    public function test_pusikulud_on_eraldi_ega_lisandu_kwh_hinnale(): void
    {
        $kulud = app(PriceCalculator::class)->fixedMonthlyCost($this->hetk('2026-08-18 12:00'), $this->leping());

        // Kuutasu 3.00 + ampritasu 5.00 = 8.00 KM-ta, KM-ga 9.92
        $this->assertSame(8.00, round($kulud['ex_vat'], 2));
        $this->assertSame(9.92, round($kulud['inc_vat'], 2));
    }

    public function test_aasta_summa_ei_triivi(): void
    {
        $calc = app(PriceCalculator::class);
        $leping = $this->leping();
        $algus = $this->hetk('2026-01-01 12:00');

        $uheHind = $calc->forInstant(5.00, $algus, $leping)->totalIncVat;

        $summa = 0.0;
        for ($i = 0; $i < 8760; $i++) {
            $summa += $uheHind;
        }

        // Liitmise triiv 8760 korral peab jääma alla 0,01 sendi
        $this->assertLessThan(0.01, abs($summa - 8760 * $uheHind));
    }
}
