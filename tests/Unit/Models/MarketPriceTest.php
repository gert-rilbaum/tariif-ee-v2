<?php

namespace Tests\Unit\Models;

use App\Models\MarketPrice;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketPriceTest extends TestCase
{
    use RefreshDatabase;

    private function hind(string $utc, float $eurMwh, int $resolution = 15): MarketPrice
    {
        return MarketPrice::create([
            'zone_code' => 'EE',
            'period_start_utc' => CarbonImmutable::parse($utc, 'UTC'),
            'resolution_minutes' => $resolution,
            'price_eur_mwh' => $eurMwh,
            'source' => 'elering',
            'fetched_at' => now(),
        ]);
    }

    public function test_eur_mwh_teisendub_sendiks_kwh_kohta(): void
    {
        $this->assertSame(5.0, $this->hind('2026-08-18 09:00', 50.0)->centsPerKwh());
        $this->assertSame(0.581, $this->hind('2026-08-18 09:15', 5.81)->centsPerKwh());
        $this->assertSame(-0.25, $this->hind('2026-08-18 09:30', -2.5)->centsPerKwh());
    }

    public function test_kohalik_oopaev_katab_oiged_utc_read(): void
    {
        // Eesti 18.08.2026 on suveajal 17.08 21:00 UTC → 18.08 21:00 UTC
        $this->hind('2026-08-17 20:45', 1.0);   // eelmine päev
        $this->hind('2026-08-17 21:00', 2.0);   // päeva esimene
        $this->hind('2026-08-18 20:45', 3.0);   // päeva viimane
        $this->hind('2026-08-18 21:00', 4.0);   // järgmine päev

        $read = MarketPrice::forLocalDay(CarbonImmutable::parse('2026-08-18', 'Europe/Tallinn'))->get();

        $this->assertCount(2, $read);
        $this->assertEqualsCanonicalizing([2.0, 3.0], $read->pluck('price_eur_mwh')->all());
    }

    public function test_talvel_nihkub_oopaeva_piir_tunni_vorra(): void
    {
        // Eesti 18.01.2026 on talveajal 17.01 22:00 UTC → 18.01 22:00 UTC
        $this->hind('2026-01-17 21:45', 1.0);   // eelmine päev
        $this->hind('2026-01-17 22:00', 2.0);   // päeva esimene
        $this->hind('2026-01-18 21:45', 3.0);   // päeva viimane

        $read = MarketPrice::forLocalDay(CarbonImmutable::parse('2026-01-18', 'Europe/Tallinn'))->get();

        $this->assertCount(2, $read);
        $this->assertEqualsCanonicalizing([2.0, 3.0], $read->pluck('price_eur_mwh')->all());
    }

    public function test_sama_periood_ei_saa_korduda(): void
    {
        $this->hind('2026-08-18 09:00', 50.0);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->hind('2026-08-18 09:00', 60.0);
    }
}
