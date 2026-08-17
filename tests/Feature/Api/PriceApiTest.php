<?php

namespace Tests\Feature\Api;

use App\Models\MarketPrice;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PriceApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function hinnad(string $localDate, int $tunde = 24, float $eurMwh = 50.0): void
    {
        $algus = CarbonImmutable::parse($localDate, 'Europe/Tallinn')->startOfDay();

        for ($i = 0; $i < $tunde; $i++) {
            MarketPrice::create([
                'zone_code' => 'EE',
                'period_start_utc' => $algus->addHours($i)->utc(),
                'resolution_minutes' => 60,
                'price_eur_mwh' => $eurMwh + $i,
                'source' => 'elering',
                'fetched_at' => now(),
            ]);
        }
    }

    public function test_api_tagastab_paeva_hinnad(): void
    {
        $this->hinnad('2026-08-18');

        $this->getJson('/api/v1/prices?date=2026-08-18&package=vork2')
            ->assertOk()
            ->assertJsonPath('data.available', true)
            ->assertJsonPath('data.granularity', 'hour')
            ->assertJsonCount(24, 'data.points')
            ->assertJsonStructure([
                'data' => ['date', 'available', 'partial', 'granularity', 'points', 'stats'],
                'meta' => ['generated_at', 'data_age_hours', 'assumptions'],
            ]);
    }

    public function test_vastus_sisaldab_eeldusi(): void
    {
        $this->hinnad('2026-08-18');

        // API tarbija peab teadma, et müüja marginaal on EELDUS, mitte fakt
        $this->getJson('/api/v1/prices?date=2026-08-18&package=vork2')
            ->assertOk()
            ->assertJsonPath('meta.assumptions.package', 'vork2')
            ->assertJsonPath('meta.assumptions.supplier_margin_cents', 0.4)
            ->assertJsonPath('meta.assumptions.supplier_margin_is_assumed', true)
            ->assertJsonPath('meta.assumptions.vat_applicable', true);
    }

    public function test_km_ta_paring_jatab_kaibemaksu_valja(): void
    {
        $this->hinnad('2026-08-18');

        $kmGa = $this->getJson('/api/v1/prices?date=2026-08-18&package=vork2&vat=1')->json('data.points.0.breakdown');
        $kmTa = $this->getJson('/api/v1/prices?date=2026-08-18&package=vork2&vat=0')->json('data.points.0.breakdown');

        $this->assertGreaterThan(0, $kmGa['vat']);
        // JSON-is kaotab 0.0 ujukoma tüübi — võrdle väärtust, mitte tüüpi
        $this->assertEquals(0, $kmTa['vat']);
        $this->assertEquals($kmGa['subtotal_ex_vat'], $kmTa['subtotal_ex_vat']);
    }

    public function test_tundmatu_pakett_annab_422(): void
    {
        $this->getJson('/api/v1/prices?date=2026-08-18&package=olematu')
            ->assertStatus(422)
            ->assertJsonValidationErrors('package');
    }

    public function test_vigane_kuupaev_annab_422(): void
    {
        $this->getJson('/api/v1/prices?date=18.08.2026&package=vork2')
            ->assertStatus(422)
            ->assertJsonValidationErrors('date');
    }

    public function test_andmete_puudumine_ei_ole_viga(): void
    {
        // Vana saidi vea vastand: teadmata ≠ katki
        $this->getJson('/api/v1/prices?date=2030-01-01&package=vork2')
            ->assertOk()
            ->assertJsonPath('data.available', false)
            ->assertJsonPath('data.points', []);
    }

    public function test_puuduv_hinnakiri_annab_selge_vea(): void
    {
        // 2020. aastal ei ole ühtegi seemendatud hinnaversiooni
        $this->hinnad('2020-06-01');

        $this->getJson('/api/v1/prices?date=2020-06-01&package=vork2')
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'tariff_missing');
    }

    public function test_api_ei_tee_ainsatki_valispaaringut(): void
    {
        $this->hinnad('2026-08-18');
        Http::preventStrayRequests();

        $this->getJson('/api/v1/prices?date=2026-08-18&package=vork2')->assertOk();
    }

    public function test_vastus_on_vahemalustatav(): void
    {
        $this->hinnad('2026-08-18');

        $this->getJson('/api/v1/prices?date=2026-08-18&package=vork2')
            ->assertOk()
            ->assertHeader('Cache-Control');
    }
}
