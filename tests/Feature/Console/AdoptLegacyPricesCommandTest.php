<?php

namespace Tests\Feature\Console;

use App\Models\MarketPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdoptLegacyPricesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Vana tabel, mille Node-skript 2025-2026 täitis
        Schema::create('market_price', function ($table) {
            $table->id();
            $table->string('zone_code', 10);
            $table->dateTime('period_start_utc');
            $table->dateTime('period_end_utc')->nullable();
            $table->decimal('price_eur_mwh', 10, 3);
            $table->timestamp('created_at')->nullable();
        });
    }

    private function vanaRida(string $utc, float $hind): void
    {
        DB::table('market_price')->insert([
            'zone_code' => 'EE',
            'period_start_utc' => $utc,
            'price_eur_mwh' => $hind,
            'created_at' => now(),
        ]);
    }

    public function test_ulevotmine_sailitab_hinnad_tapselt(): void
    {
        $this->vanaRida('2024-01-01 00:00:00', 28.460);
        $this->vanaRida('2026-08-17 09:00:00', 5.810);

        $this->artisan('prices:adopt-legacy')->assertSuccessful();

        $this->assertSame(2, MarketPrice::count());
        $this->assertSame(28.46, MarketPrice::where('period_start_utc', '2024-01-01 00:00:00')->first()->price_eur_mwh);
        $this->assertSame(5.81, MarketPrice::where('period_start_utc', '2026-08-17 09:00:00')->first()->price_eur_mwh);
    }

    public function test_resolutsioon_tuletatakse_kuupaeva_jargi(): void
    {
        // Elering läks 15-min sammule 2025-10-01 (kontrollitud päris andmetest)
        $this->vanaRida('2025-09-30 22:00:00', 10.0);
        $this->vanaRida('2025-10-01 00:00:00', 11.0);

        $this->artisan('prices:adopt-legacy')->assertSuccessful();

        $this->assertSame(60, MarketPrice::where('period_start_utc', '2025-09-30 22:00:00')->first()->resolution_minutes);
        $this->assertSame(15, MarketPrice::where('period_start_utc', '2025-10-01 00:00:00')->first()->resolution_minutes);
    }

    public function test_ulevotmine_on_idempotentne(): void
    {
        $this->vanaRida('2024-01-01 00:00:00', 28.460);

        $this->artisan('prices:adopt-legacy')->assertSuccessful();
        $this->artisan('prices:adopt-legacy')->assertSuccessful();

        $this->assertSame(1, MarketPrice::count());
    }

    public function test_ulevotmine_ei_kirjuta_ule_varskemat_elering_rida(): void
    {
        $this->vanaRida('2026-08-17 09:00:00', 1.111);

        MarketPrice::create([
            'zone_code' => 'EE', 'period_start_utc' => '2026-08-17 09:00:00',
            'resolution_minutes' => 15, 'price_eur_mwh' => 5.810,
            'source' => 'elering', 'fetched_at' => now(),
        ]);

        $this->artisan('prices:adopt-legacy')->assertSuccessful();

        // Elering on tõeallikas; ülevõtmine täidab ainult auke
        $rida = MarketPrice::first();
        $this->assertSame(5.81, $rida->price_eur_mwh);
        $this->assertSame('elering', $rida->source);
    }

    public function test_puuduv_vana_tabel_ei_ole_krahh(): void
    {
        Schema::drop('market_price');

        $this->artisan('prices:adopt-legacy')->assertFailed();
    }
}
