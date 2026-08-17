<?php

namespace Tests\Feature\Api;

use App\Models\IngestionRun;
use App\Models\MarketPrice;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthTest extends TestCase
{
    use RefreshDatabase;

    private function hind(CarbonImmutable $periodStartUtc): void
    {
        MarketPrice::create([
            'zone_code' => 'EE', 'period_start_utc' => $periodStartUtc,
            'resolution_minutes' => 15, 'price_eur_mwh' => 5.0,
            'source' => 'elering', 'fetched_at' => now(),
        ]);
    }

    public function test_terve_kui_andmed_on_varsked(): void
    {
        $this->hind(CarbonImmutable::now('UTC')->startOfHour());
        IngestionRun::create(['kind' => 'fetch', 'started_at' => now(),
            'finished_at' => now(), 'status' => 'ok', 'rows_written' => 96]);

        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('last_ingestion.rows_written', 96)
            ->assertJsonStructure(['status', 'latest_period_utc', 'data_age_hours', 'rows_total', 'last_ingestion']);
    }

    public function test_stale_kui_andmed_on_vanad(): void
    {
        $this->hind(CarbonImmutable::now('UTC')->subDays(2));

        $this->getJson('/api/v1/health')
            ->assertStatus(503)
            ->assertJsonPath('status', 'stale');
    }

    public function test_stale_kui_andmeid_ei_ole_uldse(): void
    {
        $this->getJson('/api/v1/health')
            ->assertStatus(503)
            ->assertJsonPath('status', 'stale')
            ->assertJsonPath('latest_period_utc', null)
            ->assertJsonPath('rows_total', 0);
    }

    public function test_homsed_hinnad_ei_tee_seisundit_halvaks(): void
    {
        // Meil on juba homsed hinnad — vanus on negatiivne, aga see on hea seisund
        $this->hind(CarbonImmutable::now('UTC')->addHours(10));

        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }
}
