<?php

namespace App\Http\Controllers\Api;

use App\Domain\Pricing\ContractContext;
use App\Domain\Pricing\DayPriceAssembler;
use App\Http\Controllers\Controller;
use App\Models\GridPackage;
use App\Models\MarketPrice;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

/**
 * Avalik hinna-API.
 *
 * Loeb ainult oma andmebaasist — ükski päring ei kutsu Eleringit.
 *
 * Vastus kannab `meta.assumptions` plokki, sest müüja marginaal on EELDUS.
 * Ilma selleta võiks API tarbija arvata, et tegu on tema lepingu hinnaga.
 */
class PriceApiController extends Controller
{
    private const CACHE_MINUTES = 10;

    public function __invoke(Request $request, DayPriceAssembler $assembler): JsonResponse
    {
        $valid = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'package' => ['required', 'string', Rule::exists('grid_packages', 'code')],
            'vat' => ['sometimes', 'boolean'],
            'res' => ['sometimes', Rule::in([DayPriceAssembler::HOUR, DayPriceAssembler::QUARTER])],
        ]);

        $vat = ! isset($valid['vat']) || (bool) $valid['vat'];
        $res = $valid['res'] ?? DayPriceAssembler::HOUR;
        $key = sprintf('prices:%s:%s:%s:%s', $valid['date'], $valid['package'], $vat ? 1 : 0, $res);

        $payload = Cache::remember(
            $key,
            now()->addMinutes(self::CACHE_MINUTES),
            fn () => $this->build($valid['date'], $valid['package'], $vat, $res, $assembler),
        );

        /*
         * Puuduv hinnakiri EI ole sama, mis puuduvad hinnaandmed.
         *
         * Veebileht näitab mõlemal juhul ausat teadet ja renderdub edasi.
         * API annab tariifi puudumisel 409, sest see on MEIE kataloogi auk —
         * masintarbija peab seda eristama olukorrast, kus Nord Pool pole veel
         * homseid hindu avaldanud (200, available: false).
         */
        if (($payload['data']['error'] ?? null) !== null) {
            return response()->json([
                'error' => [
                    'code' => 'tariff_missing',
                    'message' => $payload['data']['error'],
                ],
                'meta' => $payload['meta'],
            ], 409);
        }

        return response()
            ->json($payload)
            ->header('Cache-Control', 'public, max-age='.(self::CACHE_MINUTES * 60));
    }

    /** @return array<string, mixed> */
    private function build(string $date, string $packageCode, bool $vat, string $res, DayPriceAssembler $assembler): array
    {
        $package = GridPackage::with('timePatterns')->where('code', $packageCode)->firstOrFail();

        $ctx = new ContractContext(
            package: $package,
            supplierMarginCentsPerKwh: (float) config('tariif.assumed_supplier_margin_cents'),
            amperage: (int) config('tariif.default_amperage'),
            phases: (int) config('tariif.default_phases'),
            connectionType: (string) config('tariif.default_connection_type'),
            vatApplicable: $vat,
        );

        $day = CarbonImmutable::parse($date, 'Europe/Tallinn');

        return [
            'data' => $assembler->assemble($day, $ctx, $res),
            'meta' => [
                'generated_at' => CarbonImmutable::now()->toIso8601String(),
                'data_age_hours' => $this->dataAgeHours(),
                'assumptions' => [
                    'package' => $package->code,
                    'amperage' => $ctx->amperage,
                    'phases' => $ctx->phases,
                    'connection_type' => $ctx->connectionType,
                    'supplier_margin_cents' => $ctx->supplierMarginCentsPerKwh,
                    'supplier_margin_is_assumed' => true,
                    'vat_applicable' => $ctx->vatApplicable,
                ],
                'unit' => 'senti/kWh',
                'timezone' => 'Europe/Tallinn',
            ],
        ];
    }

    private function dataAgeHours(): ?int
    {
        $latest = MarketPrice::latestFirst()->first();

        if (! $latest) {
            return null;
        }

        return (int) floor(
            $latest->period_start_utc->diffInMinutes(CarbonImmutable::now('UTC'), absolute: false) / 60
        );
    }
}
