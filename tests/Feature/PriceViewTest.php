<?php

namespace Tests\Feature;

use App\Models\MarketPrice;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PriceViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function tanaseHinnad(float $eurMwh = 50.0): void
    {
        $algus = CarbonImmutable::now('Europe/Tallinn')->startOfDay();

        for ($tund = 0; $tund < 24; $tund++) {
            MarketPrice::create([
                'zone_code' => 'EE',
                'period_start_utc' => $algus->addHours($tund)->utc(),
                'resolution_minutes' => 60,
                'price_eur_mwh' => $eurMwh,
                'source' => 'elering',
                'fetched_at' => now(),
            ]);
        }
    }

    public function test_leht_avaneb_ja_naitab_hinda(): void
    {
        $this->tanaseHinnad();

        $this->get('/')
            ->assertOk()
            ->assertSee('senti/kWh', false)
            ->assertSee('Võrk 2', false);
    }

    public function test_tuhi_andmebaas_ei_anna_viga_vaid_hoiatuse(): void
    {
        // Ükski hind puudub — leht peab ikka renderduma
        $this->get('/')
            ->assertOk()
            ->assertSee('Hinnaandmed', false);
    }

    public function test_homsed_hinnad_puuduvad_annab_selge_teate(): void
    {
        $this->tanaseHinnad();

        $this->get('/')->assertOk()->assertSee('avaldatakse', false);
    }

    public function test_km_luliti_muudab_kuvatavat_hinda(): void
    {
        $this->tanaseHinnad();

        $kmGa = $this->get('/?vat=1');
        $kmTa = $this->get('/?vat=0');

        $kmGa->assertOk()->assertSee('KM-ga', false);
        $kmTa->assertOk()->assertSee('KM-ta', false);
        $this->assertNotSame($kmGa->getContent(), $kmTa->getContent());
    }

    public function test_paketi_valik_muudab_vorgutasu(): void
    {
        $this->tanaseHinnad();

        $vork2 = $this->get('/?package=vork2')->getContent();
        $vork4 = $this->get('/?package=vork4')->getContent();

        $this->assertNotSame($vork2, $vork4);
        $this->assertStringContainsString('Võrk 4', $vork4);
    }

    public function test_tundmatu_pakett_ei_lohu_lehte(): void
    {
        $this->tanaseHinnad();

        // Vigane sisend langeb tagasi vaikepaketile, mitte 500-le
        $this->get('/?package=olematu')->assertOk()->assertSee('Võrk 2', false);
    }

    public function test_vaade_naitab_eeldusi_avalikult(): void
    {
        $this->tanaseHinnad();

        // Müüja marginaal peab olema nähtav eeldus, mitte peidetud hinna sees
        $this->get('/')
            ->assertOk()
            ->assertSee('marginaal', false)
            ->assertSee('eeldus', false);
    }

    public function test_vaade_naitab_andmete_varskust(): void
    {
        $this->tanaseHinnad();

        $this->get('/')->assertOk()->assertSee('uuenes', false);
    }

    public function test_vananenud_andmed_annavad_hoiatuse(): void
    {
        // Ainult kaks päeva vanad andmed
        MarketPrice::create([
            'zone_code' => 'EE',
            'period_start_utc' => CarbonImmutable::now('UTC')->subDays(2),
            'resolution_minutes' => 60, 'price_eur_mwh' => 50.0,
            'source' => 'elering', 'fetched_at' => now()->subDays(2),
        ]);

        $this->get('/')->assertOk()->assertSee('vana', false);
    }

    public function test_leht_ei_tee_ainsatki_valispaaringut(): void
    {
        $this->tanaseHinnad();

        // Arhitektuurne piire: kasutaja päring ei kutsu kunagi välist API-t
        Http::preventStrayRequests();

        $this->get('/')->assertOk();
    }
}
