<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IngestionRun;
use App\Models\MarketPrice;
use App\Models\TariffSourceCheck;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Andmete värskus ühe päringuga.
 *
 * Vana süsteemi toide suri jaanuaris ja seda märgati augustis. Selle otspunkti
 * mõte on, et vaikne vananemine oleks nähtav enne, kui kasutaja seda avastab.
 */
class HealthController extends Controller
{
    /** Mitu tundi tohib viimane hinnarida vana olla, enne kui seisund on "stale". */
    private const MAX_AGE_HOURS = 3;

    public function __invoke(): JsonResponse
    {
        $latest = MarketPrice::latestFirst()->first();
        $lastRun = IngestionRun::lastSuccessful();
        $now = CarbonImmutable::now();

        $ageHours = $latest
            ? (int) floor($latest->period_start_utc->diffInMinutes($now, absolute: false) / 60)
            : null;

        // Negatiivne vanus tähendab, et meil on juba homsed hinnad — see on hea
        $healthy = $latest !== null && $ageHours !== null && $ageHours <= self::MAX_AGE_HOURS;

        return response()->json([
            'status' => $healthy ? 'ok' : 'stale',
            'latest_period_utc' => $latest?->period_start_utc?->toIso8601String(),
            'data_age_hours' => $ageHours,
            'rows_total' => MarketPrice::count(),
            'last_ingestion' => [
                'finished_at' => $lastRun?->finished_at?->toIso8601String(),
                'kind' => $lastRun?->kind,
                'rows_written' => $lastRun?->rows_written,
            ],
            // Eristab kahte eri riket: "Elering vaikib" vs "cron ei jookse"
            'scheduler_last_run' => Cache::get('scheduler_last_run'),
            // Kas mõni tariifiallikas on muutunud ja üle vaatamata?
            'tariff_sources' => TariffSourceCheck::query()
                ->get()
                ->map(fn (TariffSourceCheck $c) => [
                    'source' => $c->source_key,
                    'checked_at' => $c->checked_at?->toIso8601String(),
                    'changed_at' => $c->changed_at?->toIso8601String(),
                    'needs_review' => ! $c->acknowledged,
                    'last_error' => $c->last_error,
                ])
                ->values(),
        ], $healthy ? 200 : 503);
    }
}
