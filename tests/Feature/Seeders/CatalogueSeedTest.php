<?php

namespace Tests\Feature\Seeders;

use App\Domain\Pricing\ContractContext;
use App\Domain\Pricing\PriceCalculator;
use App\Domain\Pricing\RateResolver;
use App\Models\GridPackage;
use App\Models\GridPackageVersion;
use App\Models\StateFee;
use App\Models\VatRate;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogueSeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_iga_tariifikirje_kannab_allikaviidet(): void
    {
        $this->assertGreaterThan(0, GridPackageVersion::count());

        $this->assertSame(0, GridPackageVersion::whereNull('source_url')->count());
        $this->assertSame(0, GridPackageVersion::whereNull('verified_at')->count());
        $this->assertSame(0, StateFee::whereNull('source_url')->count());
        $this->assertSame(0, VatRate::whereNull('source_url')->count());
    }

    public function test_kataloog_katab_kolm_elektrilevi_paketti(): void
    {
        // Võrk 5 on teadlikult etapp 1-st väljas — Gerdi otsus 18.08.2026
        $koodid = GridPackage::pluck('code')->sort()->values()->all();

        $this->assertSame(['vork1', 'vork2', 'vork4'], $koodid);
    }

    public function test_vaikepakett_on_vork2_ja_eksisteerib(): void
    {
        $this->assertSame('vork2', config('tariif.default_package'));
        $this->assertNotNull(GridPackage::where('code', config('tariif.default_package'))->first());
    }

    public function test_igal_paketil_on_ajamuster_koigi_24_tunni_jaoks(): void
    {
        $resolver = app(RateResolver::class);

        foreach (GridPackage::with('timePatterns')->get() as $pakett) {
            foreach (['2026-08-18', '2026-08-15', '2026-08-20'] as $paev) {  // tööpäev, laupäev, riigipüha
                for ($tund = 0; $tund < 24; $tund++) {
                    $hetk = CarbonImmutable::parse(sprintf('%s %02d:30', $paev, $tund), 'Europe/Tallinn')->utc();

                    $this->assertNotEmpty(
                        $resolver->resolve($pakett, $hetk),
                        "{$pakett->code} $paev tund $tund"
                    );
                }
            }
        }
    }

    public function test_paevatariif_lopeb_kell_22_mitte_23(): void
    {
        // Hinnakiri: "Päevahind kehtib esmaspäevast reedeni kell 7.00–22.00".
        // Vana tariif.ee kasutas 7–23 ja eksis terve tunni võrra iga tööpäev.
        $resolver = app(RateResolver::class);
        $vork2 = GridPackage::with('timePatterns')->where('code', 'vork2')->first();

        $kell21 = CarbonImmutable::parse('2026-08-18 21:30', 'Europe/Tallinn')->utc();
        $kell22 = CarbonImmutable::parse('2026-08-18 22:30', 'Europe/Tallinn')->utc();

        $this->assertSame('day', $resolver->resolve($vork2, $kell21));
        $this->assertSame('night', $resolver->resolve($vork2, $kell22));
    }

    public function test_vork1_kwh_hind_on_kaibemaksuta(): void
    {
        // Hinnakirjas: KM-ta 7,72 · KM-ga 9,57. Vana sait salvestas 9,57 ja
        // liitis KM peale — Võrk 1 sai topelt käibemaksu.
        $vork1 = GridPackage::where('code', 'vork1')->first();
        $versioon = $vork1->versionAt(CarbonImmutable::parse('2026-08-18'));
        $versioon->load('energyRates');

        $this->assertSame(7.72, $versioon->rateFor('all'));
    }

    public function test_korteri_ja_peakaitsme_kuutasu_on_erinevad(): void
    {
        $vork2 = GridPackage::where('code', 'vork2')->first();
        $versioon = $vork2->versionAt(CarbonImmutable::parse('2026-08-18'));
        $versioon->load('capacityFees');

        // Hinnakiri: Võrk 2 korter 3,65 · liitumispunkt kuni 16A 6,80
        $this->assertSame(3.65, $versioon->capacityFeeFor(16, 1, 'apartment'));
        $this->assertSame(6.80, $versioon->capacityFeeFor(16, 1, 'main_fuse'));
        $this->assertSame(21.14, $versioon->capacityFeeFor(63, 1, 'main_fuse'));
    }

    public function test_koik_riiklikud_tasud_on_olemas(): void
    {
        $tasud = StateFee::activeAt(CarbonImmutable::parse('2026-08-18 12:00', 'Europe/Tallinn'));

        $this->assertSame(0.84, $tasud['renewable']);
        $this->assertSame(0.21, $tasud['excise']);
        $this->assertSame(0.758, $tasud['supply_security']);
        $this->assertSame(0.373, $tasud['balancing_capacity']);
    }

    public function test_tasakaalustamistasu_muutub_2027_aastal(): void
    {
        $y2026 = StateFee::activeAt(CarbonImmutable::parse('2026-12-31 12:00', 'Europe/Tallinn'));
        $y2027 = StateFee::activeAt(CarbonImmutable::parse('2027-01-01 12:00', 'Europe/Tallinn'));

        $this->assertSame(0.373, $y2026['balancing_capacity']);
        $this->assertSame(0.297, $y2027['balancing_capacity']);
    }

    public function test_paris_andmetega_arvutus_annab_moistliku_hinna(): void
    {
        $vork2 = GridPackage::with('timePatterns')->where('code', 'vork2')->first();

        $leping = new ContractContext(
            package: $vork2,
            supplierMarginCentsPerKwh: config('tariif.assumed_supplier_margin_cents'),
            amperage: 25,
            phases: 1,
            vatApplicable: true,
        );

        // Börsihind 5,00 s/kWh, tööpäeva keskpäev
        $b = app(PriceCalculator::class)->forInstant(
            5.00,
            CarbonImmutable::parse('2026-08-18 12:00', 'Europe/Tallinn')->utc(),
            $leping,
        );

        // 5,00 + 0,40 + 6,07 + 0,84 + 0,758 + 0,21 + 0,373 = 13,651 KM-ta
        $this->assertSame(13.65, round($b->subtotalExVat, 2));
        $this->assertSame(16.93, round($b->totalIncVat, 2));   // × 1,24

        // Füüsiline mõistlikkus: lõpphind jääb vahemikku 5–60 senti/kWh
        $this->assertGreaterThan(5.0, $b->totalIncVat);
        $this->assertLessThan(60.0, $b->totalIncVat);
    }
}
