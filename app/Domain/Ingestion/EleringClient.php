<?php

namespace App\Domain\Ingestion;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Eleringi NPS hinna-API klient.
 *
 * Kontrollitud käitumine (18.08.2026): teenus annab lühikesi 503-katkestusi
 * kehaga "no available server", mis tabavad korraga KÕIKI otspunkte ja kaovad
 * sekunditega. Vana tariif.ee luges iga sellise vastuse surmavaks veaks ja
 * näitas kasutajale veateadet. Siin proovime uuesti.
 *
 * See klient on ainus koht, kus rakendus välismaailmaga räägib, ja teda kutsub
 * ainult ajastatud töö — mitte kunagi kasutaja päring (spec §4).
 */
class EleringClient
{
    private const BASE_URL = 'https://dashboard.elering.ee/api/nps/price';

    private const RETRY_TIMES = 3;

    private const RETRY_SLEEP_MS = 500;

    private const TIMEOUT_SECONDS = 20;

    /**
     * @return array<int, array{period_start_utc: CarbonImmutable, price_eur_mwh: float}>
     *
     * @throws \RuntimeException kui teenus ei vasta ka pärast kordusi
     */
    public function fetchRange(CarbonImmutable $fromUtc, CarbonImmutable $toUtc): array
    {
        try {
            $response = Http::retry(self::RETRY_TIMES, self::RETRY_SLEEP_MS, throw: false)
                ->timeout(self::TIMEOUT_SECONDS)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => config('tariif.user_agent'),
                ])
                ->get(self::BASE_URL, [
                    'start' => $fromUtc->utc()->format('Y-m-d\TH:i:s.v\Z'),
                    'end' => $toUtc->utc()->subSecond()->format('Y-m-d\TH:i:s').'.999Z',
                ]);
        } catch (RequestException $e) {
            throw new \RuntimeException('Eleringi päring ebaõnnestus: '.$e->getMessage(), previous: $e);
        }

        if (! $response->successful()) {
            throw new \RuntimeException(sprintf(
                'Eleringi päring ebaõnnestus: HTTP %d %s',
                $response->status(),
                trim(substr($response->body(), 0, 100))
            ));
        }

        $rows = data_get($response->json(), 'data.ee', []);

        $parsed = array_map(static fn (array $row): array => [
            'period_start_utc' => CarbonImmutable::createFromTimestampUTC($row['timestamp']),
            'price_eur_mwh' => (float) $row['price'],
        ], $rows);

        usort($parsed, static fn (array $a, array $b): int => $a['period_start_utc'] <=> $b['period_start_utc']);

        return $parsed;
    }
}
