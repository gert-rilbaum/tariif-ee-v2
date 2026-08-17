<?php

namespace Tests\Feature;

use App\Models\GridPackageVersion;
use App\Models\MarketPrice;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Vastupidavus: tõestab, et vana saidi peamine nõrkus on kadunud.
 *
 * Vana tariif.ee kutsus Eleringi API-t IGA lehelaadimise ajal. Kui Elering
 * andis 503 "no available server" — mida ta teeb lühikeste puhangutena —
 * nägi kasutaja veateadet. Siin jõustatakse arhitektuurne piire testiga.
 */
class ResilienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->tanaseHinnad();
    }

    private function tanaseHinnad(): void
    {
        $algus = CarbonImmutable::now('Europe/Tallinn')->startOfDay();

        for ($tund = 0; $tund < 24; $tund++) {
            MarketPrice::create([
                'zone_code' => 'EE',
                'period_start_utc' => $algus->addHours($tund)->utc(),
                'resolution_minutes' => 60,
                'price_eur_mwh' => 50.0,
                'source' => 'elering',
                'fetched_at' => now(),
            ]);
        }
    }

    public function test_eleringi_taielik_rike_ei_riku_lehte(): void
    {
        // Täpselt see vastus, mida Elering päriselt annab (kontrollitud 18.08.2026)
        Http::fake(['dashboard.elering.ee/*' => Http::response('no available server', 503)]);

        $this->get('/')->assertOk()->assertSee('senti/kWh', false);
    }

    public function test_eleringi_rike_ei_riku_api_t(): void
    {
        Http::fake(['dashboard.elering.ee/*' => Http::response('no available server', 503)]);

        $this->getJson('/api/v1/prices?date='.now('Europe/Tallinn')->toDateString().'&package=vork2')
            ->assertOk()
            ->assertJsonPath('data.available', true);
    }

    public function test_veeb_ei_tee_ainsatki_valispaaringut(): void
    {
        // Arhitektuurne piire: kui keegi lisab hiljem lehele välise API-kutse,
        // kukub see test kohe. Piire on jõustatud, mitte lubatud.
        Http::preventStrayRequests();

        $this->get('/')->assertOk();
        $this->get('/?package=vork4&res=quarter&day=homme')->assertOk();
        $this->getJson('/api/v1/health')->assertOk();
        $this->getJson('/api/v1/prices?date='.now('Europe/Tallinn')->toDateString().'&package=vork1')->assertOk();
    }

    public function test_andmebaasi_tuhjendamine_ei_anna_500(): void
    {
        MarketPrice::query()->delete();
        Http::preventStrayRequests();

        $this->get('/')->assertOk()->assertSee('Hinnaandmed', false);
        $this->getJson('/api/v1/health')->assertStatus(503)->assertJsonPath('status', 'stale');
    }

    public function test_puuduv_tariif_ei_anna_500_vaid_ausa_teate(): void
    {
        // Kõik hinnaversioonid kaovad → hinda EI näidata, aga leht renderdub
        GridPackageVersion::query()->delete();
        Http::preventStrayRequests();

        $this->get('/')->assertOk()->assertSee('ei saa', false);
    }
}
