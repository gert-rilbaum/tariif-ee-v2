<?php

namespace Tests\Feature\Ingestion;

use App\Domain\Ingestion\MarketPriceIngestor;
use App\Models\IngestionRun;
use App\Models\MarketPrice;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MarketPriceIngestorTest extends TestCase
{
    use RefreshDatabase;

    private function fakeElering(array $rows): void
    {
        Http::fake(['dashboard.elering.ee/*' => Http::response(['data' => ['ee' => $rows]])]);
    }

    private function paev(string $date = '2026-08-18'): CarbonImmutable
    {
        return CarbonImmutable::parse($date, 'Europe/Tallinn');
    }

    public function test_korduv_sisselugemine_ei_tekita_duplikaate(): void
    {
        $this->fakeElering([
            ['timestamp' => 1787000400, 'price' => 5.81],
            ['timestamp' => 1787001300, 'price' => 5.64],
        ]);

        $ingestor = app(MarketPriceIngestor::class);
        $ingestor->ingestDay($this->paev());
        $ingestor->ingestDay($this->paev());

        $this->assertSame(2, MarketPrice::count());
    }

    public function test_uuem_hind_kirjutab_vana_ule(): void
    {
        MarketPrice::create([
            'zone_code' => 'EE',
            'period_start_utc' => CarbonImmutable::createFromTimestampUTC(1787000400),
            'resolution_minutes' => 15, 'price_eur_mwh' => 1.11,
            'source' => 'legacy', 'fetched_at' => now()->subDay(),
        ]);

        $this->fakeElering([['timestamp' => 1787000400, 'price' => 9.99]]);
        app(MarketPriceIngestor::class)->ingestDay($this->paev());

        $hind = MarketPrice::first();
        $this->assertSame(9.99, $hind->price_eur_mwh);
        $this->assertSame('elering', $hind->source);
    }

    public function test_resolutsioon_15_min_tuletatakse_ridade_vahest(): void
    {
        $this->fakeElering([
            ['timestamp' => 1787000400, 'price' => 5.81],
            ['timestamp' => 1787001300, 'price' => 5.64],
        ]);

        app(MarketPriceIngestor::class)->ingestDay($this->paev());

        $this->assertSame(15, MarketPrice::first()->resolution_minutes);
    }

    public function test_resolutsioon_60_min_tuletatakse_ridade_vahest(): void
    {
        // Ajalugu kuni 2025-09-30 on tunnisammuga
        $this->fakeElering([
            ['timestamp' => 1704067200, 'price' => 28.46],
            ['timestamp' => 1704070800, 'price' => 27.10],
        ]);

        app(MarketPriceIngestor::class)->ingestDay($this->paev('2024-01-01'));

        $this->assertSame(60, MarketPrice::first()->resolution_minutes);
    }

    public function test_uksik_rida_eeldab_tunnisammu(): void
    {
        $this->fakeElering([['timestamp' => 1704067200, 'price' => 28.46]]);

        app(MarketPriceIngestor::class)->ingestDay($this->paev('2024-01-01'));

        $this->assertSame(60, MarketPrice::first()->resolution_minutes);
    }

    public function test_kaivitus_logitakse_edukana(): void
    {
        $this->fakeElering([['timestamp' => 1787000400, 'price' => 5.81]]);

        app(MarketPriceIngestor::class)->ingestDay($this->paev());

        $run = IngestionRun::latest('id')->first();
        $this->assertSame('ok', $run->status);
        $this->assertSame('fetch', $run->kind);
        $this->assertSame(1, $run->rows_written);
        $this->assertNotNull($run->finished_at);
    }

    public function test_torge_logitakse_ja_visatakse_edasi(): void
    {
        Http::fake(['dashboard.elering.ee/*' => Http::response('no available server', 503)]);

        try {
            app(MarketPriceIngestor::class)->ingestDay($this->paev());
            $this->fail('Erind oleks pidanud tulema');
        } catch (\RuntimeException) {
            // oodatud
        }

        $run = IngestionRun::latest('id')->first();
        $this->assertSame('failed', $run->status);
        $this->assertStringContainsString('503', $run->error);
    }

    public function test_tuhi_vastus_ei_ole_torge(): void
    {
        $this->fakeElering([]);

        $kirjutatud = app(MarketPriceIngestor::class)->ingestDay($this->paev());

        // Homsed hinnad pole veel avaldatud — normaalne seisund, mitte viga
        $this->assertSame(0, $kirjutatud);
        $this->assertSame('ok', IngestionRun::latest('id')->first()->status);
    }

    public function test_augutaide_loeb_puuduva_paeva_uuesti(): void
    {
        // Eile on täielik (24 tundi), täna on puudu
        $eile = CarbonImmutable::now('Europe/Tallinn')->subDay()->startOfDay();
        for ($i = 0; $i < 24; $i++) {
            MarketPrice::create([
                'zone_code' => 'EE',
                'period_start_utc' => $eile->addHours($i)->utc(),
                'resolution_minutes' => 60, 'price_eur_mwh' => 10.0,
                'source' => 'elering', 'fetched_at' => now(),
            ]);
        }

        $this->fakeElering([['timestamp' => 1787000400, 'price' => 5.81]]);
        $taidetud = app(MarketPriceIngestor::class)->fillGaps(24);

        $this->assertGreaterThan(0, $taidetud);
        $this->assertGreaterThan(0, IngestionRun::where('kind', 'gapfill')->count());
    }
}
