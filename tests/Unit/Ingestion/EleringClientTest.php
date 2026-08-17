<?php

namespace Tests\Unit\Ingestion;

use App\Domain\Ingestion\EleringClient;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EleringClientTest extends TestCase
{
    private function vahemik(): array
    {
        return [
            CarbonImmutable::parse('2026-08-17 21:00', 'UTC'),
            CarbonImmutable::parse('2026-08-18 21:00', 'UTC'),
        ];
    }

    public function test_parsib_vastuse_ja_sorteerib_aja_jargi(): void
    {
        Http::fake(['dashboard.elering.ee/*' => Http::response([
            'success' => true,
            'data' => ['ee' => [
                ['timestamp' => 1786914900, 'price' => 5.6400],
                ['timestamp' => 1786914000, 'price' => 5.8100],
            ]],
        ])]);

        [$from, $to] = $this->vahemik();
        $read = (new EleringClient)->fetchRange($from, $to);

        $this->assertCount(2, $read);
        $this->assertSame(1786914000, $read[0]['period_start_utc']->getTimestamp());
        $this->assertSame(5.81, $read[0]['price_eur_mwh']);
        $this->assertSame(5.64, $read[1]['price_eur_mwh']);
    }

    public function test_negatiivne_hind_sailib(): void
    {
        Http::fake(['dashboard.elering.ee/*' => Http::response([
            'data' => ['ee' => [['timestamp' => 1786914000, 'price' => -2.5000]]],
        ])]);

        [$from, $to] = $this->vahemik();
        $read = (new EleringClient)->fetchRange($from, $to);

        // Negatiivne börsihind on Eestis reaalsus, mitte viga
        $this->assertSame(-2.5, $read[0]['price_eur_mwh']);
    }

    public function test_tuhi_vastus_ei_ole_viga(): void
    {
        Http::fake(['dashboard.elering.ee/*' => Http::response(['data' => ['ee' => []]])]);

        [$from, $to] = $this->vahemik();

        // Homsed hinnad pole veel avaldatud — see on normaalne seisund
        $this->assertSame([], (new EleringClient)->fetchRange($from, $to));
    }

    public function test_503_no_available_server_proovitakse_uuesti(): void
    {
        // Eleringi päris käitumine (kontrollitud 18.08.2026): lühikesed 503-katkestused
        Http::fake(['dashboard.elering.ee/*' => Http::sequence()
            ->push('no available server', 503)
            ->push('no available server', 503)
            ->push(['data' => ['ee' => [['timestamp' => 1786914000, 'price' => 5.81]]]], 200),
        ]);

        [$from, $to] = $this->vahemik();
        $read = (new EleringClient)->fetchRange($from, $to);

        $this->assertCount(1, $read);
    }

    public function test_pusiv_tõrge_viskab_erindi(): void
    {
        Http::fake(['dashboard.elering.ee/*' => Http::response('no available server', 503)]);

        [$from, $to] = $this->vahemik();

        $this->expectException(\RuntimeException::class);
        (new EleringClient)->fetchRange($from, $to);
    }

    public function test_paring_kasutab_utc_vahemikku(): void
    {
        Http::fake(['dashboard.elering.ee/*' => Http::response(['data' => ['ee' => []]])]);

        [$from, $to] = $this->vahemik();
        (new EleringClient)->fetchRange($from, $to);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'start=2026-08-17T21%3A00%3A00.000Z')
                && str_contains($request->url(), 'end=2026-08-18T20%3A59%3A59.999Z');
        });
    }
}
