<?php

namespace Tests\Feature\Console;

use App\Models\IngestionRun;
use App\Models\MarketPrice;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FetchPricesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_fetch_loeb_tana_ja_homme(): void
    {
        Http::fake(['dashboard.elering.ee/*' => Http::response(['data' => ['ee' => [
            ['timestamp' => 1787000400, 'price' => 5.81],
        ]]])]);

        $this->artisan('prices:fetch --no-gapfill')->assertSuccessful();

        $this->assertSame(2, IngestionRun::where('kind', 'fetch')->count());
    }

    public function test_fetch_kindla_kuupaevaga_loeb_uhe_paeva(): void
    {
        Http::fake(['dashboard.elering.ee/*' => Http::response(['data' => ['ee' => [
            ['timestamp' => 1704067200, 'price' => 28.46],
        ]]])]);

        $this->artisan('prices:fetch --date=2024-01-01 --no-gapfill')->assertSuccessful();

        $this->assertSame(1, IngestionRun::where('kind', 'fetch')->count());
        $this->assertSame(1, MarketPrice::count());
    }

    public function test_koigi_paevade_torge_annab_veakoodi(): void
    {
        Http::fake(['dashboard.elering.ee/*' => Http::response('no available server', 503)]);

        $this->artisan('prices:fetch --no-gapfill')->assertFailed();
    }

    public function test_backfill_nouab_vahemikku(): void
    {
        $this->artisan('prices:backfill')->assertFailed();
    }

    public function test_backfill_loeb_vahemiku(): void
    {
        Http::fake(['dashboard.elering.ee/*' => Http::response(['data' => ['ee' => [
            ['timestamp' => 1704067200, 'price' => 28.46],
        ]]])]);

        $this->artisan('prices:backfill --from=2024-01-01 --to=2024-01-03 --sleep=0')->assertSuccessful();

        $this->assertSame(3, IngestionRun::where('kind', 'backfill')->count());
    }

    public function test_verify_leiab_puuduliku_paeva(): void
    {
        // Eile on ainult 5 rida 24-st
        $eile = CarbonImmutable::now('Europe/Tallinn')->subDay()->startOfDay();
        for ($i = 0; $i < 5; $i++) {
            MarketPrice::create([
                'zone_code' => 'EE', 'period_start_utc' => $eile->addHours($i)->utc(),
                'resolution_minutes' => 60, 'price_eur_mwh' => 10.0,
                'source' => 'elering', 'fetched_at' => now(),
            ]);
        }

        $this->artisan('prices:verify --days=1')->assertFailed();
    }

    public function test_verify_on_rahul_kui_paev_on_taielik(): void
    {
        $eile = CarbonImmutable::now('Europe/Tallinn')->subDay()->startOfDay();
        for ($i = 0; $i < 24; $i++) {
            MarketPrice::create([
                'zone_code' => 'EE', 'period_start_utc' => $eile->addHours($i)->utc(),
                'resolution_minutes' => 60, 'price_eur_mwh' => 10.0,
                'source' => 'elering', 'fetched_at' => now(),
            ]);
        }

        $this->artisan('prices:verify --days=1')->assertSuccessful();
    }
}
