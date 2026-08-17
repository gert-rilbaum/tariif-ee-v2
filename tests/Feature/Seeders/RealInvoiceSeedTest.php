<?php

namespace Tests\Feature\Seeders;

use App\Models\GridPackage;
use App\Models\StateFee;
use App\Models\VatRate;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kuldne test päris arve vastu.
 *
 * Kontrollitud Eesti kodutarbija elektriarve vastu, arveldusperiood
 * 01.07.2026–31.07.2026: müüja Alexela, võrguettevõtja Elektrilevi,
 * hinnapakett Võrk 4, peakaitse 20 A.
 *
 * Isikuandmeid siin ei ole ja ei tohi olla — ainult kogused ja tariifid.
 * Nimi, aadress, lepingu- ja arvestinumbrid jäävad arvele.
 *
 * Kui mõni seemendatud number ekslikult muutub, kukub see test kohe:
 * arve on väline tõde, mille vastu meie kataloogi mõõdame.
 */
class RealInvoiceSeedTest extends TestCase
{
    use RefreshDatabase;

    /** Arvel: tarbimine päevaajal */
    private const KWH_PAEV = 245.557;

    /** Arvel: tarbimine ööajal */
    private const KWH_OO = 229.254;

    /** Arvel: kogutarbimine, mille pealt arvestatakse riiklikke tasusid */
    private const KWH_KOKKU = 474.811;

    private const PEAKAITSE = 20;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function hetk(): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-07-15 12:00', 'Europe/Tallinn');
    }

    public function test_vorgutasud_vastavad_arvele(): void
    {
        $versioon = GridPackage::where('code', 'vork4')->firstOrFail()->versionAt($this->hetk());
        $versioon->load('energyRates');

        // Arvel: "Elektri edastamine, päev 0,0369 €/kWh" ja "öö 0,021 €/kWh"
        $this->assertSame(3.69, $versioon->rateFor('day'));
        $this->assertSame(2.10, $versioon->rateFor('night'));
    }

    public function test_kuutasu_vastab_arvele(): void
    {
        $versioon = GridPackage::where('code', 'vork4')->firstOrFail()->versionAt($this->hetk());
        $versioon->load('capacityFees');

        // Arvel: "Kuutasu, 20A — 20,64 €/tk"
        $this->assertSame(20.64, $versioon->capacityFeeFor(self::PEAKAITSE, 1, 'main_fuse'));
    }

    public function test_riiklikud_tasud_vastavad_arvele(): void
    {
        $tasud = StateFee::activeAt($this->hetk());

        // Arvel: taastuvenergia 0,0084 · varustuskindlus 0,00758 · aktsiis 0,0021 €/kWh
        $this->assertSame(0.84, $tasud['renewable']);
        $this->assertSame(0.758, $tasud['supply_security']);
        $this->assertSame(0.21, $tasud['excise']);

        // Arvel müüja plokis: "Tarbimise tasakaalustamisvõimsuse kulu 0,00373 €/kWh"
        $this->assertSame(0.373, $tasud['balancing_capacity']);
    }

    public function test_kaibemaksumaar_vastab_arvele(): void
    {
        // Arvel: "Käibemaks 24%"
        $this->assertSame(0.24, VatRate::atMoment($this->hetk()));
    }

    public function test_vorguarve_summa_reprodutseerib_arve_senti_tapsusega(): void
    {
        $versioon = GridPackage::where('code', 'vork4')->firstOrFail()->versionAt($this->hetk());
        $versioon->load(['energyRates', 'capacityFees']);
        $tasud = StateFee::activeAt($this->hetk());

        $senti = fn (float $kwh, float $centsPerKwh): float => $kwh * $centsPerKwh / 100;

        $read = [
            'edastamine päev' => $senti(self::KWH_PAEV, $versioon->rateFor('day')),
            'edastamine öö' => $senti(self::KWH_OO, $versioon->rateFor('night')),
            'kuutasu' => $versioon->capacityFeeFor(self::PEAKAITSE, 1, 'main_fuse'),
            'taastuvenergia' => $senti(self::KWH_KOKKU, $tasud['renewable']),
            'varustuskindlus' => $senti(self::KWH_KOKKU, $tasud['supply_security']),
            'aktsiis' => $senti(self::KWH_KOKKU, $tasud['excise']),
        ];

        // Arve real-real ümardatud summad
        $this->assertSame(9.06, round($read['edastamine päev'], 2));
        $this->assertSame(4.81, round($read['edastamine öö'], 2));
        $this->assertSame(20.64, round($read['kuutasu'], 2));
        $this->assertSame(3.99, round($read['taastuvenergia'], 2));
        $this->assertSame(3.60, round($read['varustuskindlus'], 2));
        $this->assertSame(1.00, round($read['aktsiis'], 2));

        // Arvel: "Maksustatav käive (24%) 43,10 €"
        $kmTa = array_sum(array_map(fn (float $v): float => round($v, 2), $read));
        $this->assertSame(43.10, round($kmTa, 2));

        // Arvel: "Käibemaks 24% 10,34 €" ja "KOKKU 53,44 €"
        $km = round($kmTa * VatRate::atMoment($this->hetk()), 2);
        $this->assertSame(10.34, $km);
        $this->assertSame(53.44, round($kmTa + $km, 2));
    }

    public function test_tasakaalustamisvoimsuse_kulu_summa_vastab_arvele(): void
    {
        $tasud = StateFee::activeAt($this->hetk());

        // Arvel müüja plokis: 474,811 kWh × 0,00373 €/kWh = 1,77 €
        $summa = self::KWH_KOKKU * $tasud['balancing_capacity'] / 100;

        $this->assertSame(1.77, round($summa, 2));
    }
}
