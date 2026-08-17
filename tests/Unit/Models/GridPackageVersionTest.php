<?php

namespace Tests\Unit\Models;

use App\Models\GridCapacityFee;
use App\Models\GridEnergyRate;
use App\Models\GridOperator;
use App\Models\GridPackage;
use App\Models\GridPackageVersion;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GridPackageVersionTest extends TestCase
{
    use RefreshDatabase;

    private function pakett(): GridPackage
    {
        $operator = GridOperator::create(['code' => 'elektrilevi', 'name' => 'Elektrilevi OÜ', 'active' => true]);

        return GridPackage::create([
            'operator_id' => $operator->id,
            'code' => 'vork2',
            'name' => 'Võrk 2',
            'scheme' => 'dual',
            'active' => true,
        ]);
    }

    public function test_versioon_valitakse_kuupaeva_jargi(): void
    {
        $pakett = $this->pakett();

        $vana = GridPackageVersion::create(['package_id' => $pakett->id, 'valid_from' => '2025-08-01',
            'valid_to' => '2026-05-31', 'base_monthly_eur' => 5.00, 'source_url' => 'test', 'verified_at' => now()]);
        $uus = GridPackageVersion::create(['package_id' => $pakett->id, 'valid_from' => '2026-06-01',
            'valid_to' => null, 'base_monthly_eur' => 6.00, 'source_url' => 'test', 'verified_at' => now()]);

        $this->assertSame($vana->id, $pakett->versionAt(CarbonImmutable::parse('2026-01-15'))->id);
        $this->assertSame($uus->id, $pakett->versionAt(CarbonImmutable::parse('2026-08-18'))->id);
    }

    public function test_kehtivuseta_kuupaev_annab_nulli(): void
    {
        $pakett = $this->pakett();
        GridPackageVersion::create(['package_id' => $pakett->id, 'valid_from' => '2026-06-01',
            'valid_to' => null, 'base_monthly_eur' => 6.00, 'source_url' => 'test', 'verified_at' => now()]);

        // Enne esimest hinnakirja ei ole hinda — ei tohi tagastada uusimat
        $this->assertNull($pakett->versionAt(CarbonImmutable::parse('2020-01-01')));
    }

    public function test_tariifimaar_leitakse_liigi_jargi(): void
    {
        $pakett = $this->pakett();
        $versioon = GridPackageVersion::create(['package_id' => $pakett->id, 'valid_from' => '2026-06-01',
            'valid_to' => null, 'base_monthly_eur' => 6.00, 'source_url' => 'test', 'verified_at' => now()]);

        GridEnergyRate::create(['version_id' => $versioon->id, 'rate_kind' => 'day', 'cents_per_kwh' => 4.5800]);
        GridEnergyRate::create(['version_id' => $versioon->id, 'rate_kind' => 'night', 'cents_per_kwh' => 2.6000]);

        $versioon->load('energyRates');
        $this->assertSame(4.58, $versioon->rateFor('day'));
        $this->assertSame(2.60, $versioon->rateFor('night'));
    }

    public function test_puuduv_tariifimaar_viskab_erindi(): void
    {
        $pakett = $this->pakett();
        $versioon = GridPackageVersion::create(['package_id' => $pakett->id, 'valid_from' => '2026-06-01',
            'valid_to' => null, 'base_monthly_eur' => 6.00, 'source_url' => 'test', 'verified_at' => now()]);
        GridEnergyRate::create(['version_id' => $versioon->id, 'rate_kind' => 'day', 'cents_per_kwh' => 4.5800]);
        $versioon->load('energyRates');

        $this->expectException(\RuntimeException::class);
        $versioon->rateFor('night');
    }

    public function test_ampritasu_soltub_peakaitsmest_ja_faasidest(): void
    {
        $pakett = $this->pakett();
        $versioon = GridPackageVersion::create(['package_id' => $pakett->id, 'valid_from' => '2026-06-01',
            'valid_to' => null, 'base_monthly_eur' => 3.48, 'source_url' => 'test', 'verified_at' => now()]);

        GridCapacityFee::create(['version_id' => $versioon->id, 'amperage' => 16, 'phases' => 1, 'monthly_eur' => 2.53]);
        GridCapacityFee::create(['version_id' => $versioon->id, 'amperage' => 25, 'phases' => 3, 'monthly_eur' => 11.85]);

        $versioon->load('capacityFees');

        // Kuutasu ja ampritasu on ERALDI — vana sait pakkis need üheks numbriks (spec §5.2)
        $this->assertSame(3.48, (float) $versioon->base_monthly_eur);
        $this->assertSame(2.53, $versioon->capacityFeeFor(16, 1));
        $this->assertSame(11.85, $versioon->capacityFeeFor(25, 3));
    }

    public function test_tundmatu_peakaitse_viskab_erindi(): void
    {
        $pakett = $this->pakett();
        $versioon = GridPackageVersion::create(['package_id' => $pakett->id, 'valid_from' => '2026-06-01',
            'valid_to' => null, 'base_monthly_eur' => 3.48, 'source_url' => 'test', 'verified_at' => now()]);
        GridCapacityFee::create(['version_id' => $versioon->id, 'amperage' => 16, 'phases' => 1, 'monthly_eur' => 2.53]);
        $versioon->load('capacityFees');

        // Vaikne 0 tähendaks, et kuutasu jääb arvest välja — parem viga
        $this->expectException(\RuntimeException::class);
        $versioon->capacityFeeFor(63, 3);
    }
}
