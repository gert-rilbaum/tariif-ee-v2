<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IngestionRun;
use App\Models\MarketPrice;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

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
        ], $healthy ? 200 : 503);
    }
}
