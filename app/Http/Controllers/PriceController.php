<?php

namespace App\Http\Controllers;

use App\Domain\Pricing\ContractContext;
use App\Domain\Pricing\DayPriceAssembler;
use App\Domain\Pricing\PriceCalculator;
use App\Models\GridPackage;
use App\Models\MarketPrice;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Avalik hinnavaade.
 *
 * Ei kutsu ühtegi välist teenust — loeb ainult oma andmebaasist. Kui andmeid
 * pole või need on vanad, ütleb seda otse, aga renderdub alati (spec §9).
 */
class PriceController extends Controller
{
    public function __construct(
        private readonly DayPriceAssembler $assembler,
        private readonly PriceCalculator $calculator,
    ) {}

    public function index(Request $request): View
    {
        $paketid = GridPackage::with('timePatterns')
            ->where('active', true)
            ->orderBy('code')
            ->get();

        $valitud = $this->valiPakett($request, $paketid);
        $kmGa = $this->kasKmGa($request);

        $leping = new ContractContext(
            package: $valitud,
            supplierMarginCentsPerKwh: (float) config('tariif.assumed_supplier_margin_cents'),
            amperage: (int) config('tariif.default_amperage'),
            phases: (int) config('tariif.default_phases'),
            connectionType: (string) config('tariif.default_connection_type'),
            vatApplicable: $kmGa,
        );

        $today = CarbonImmutable::now('Europe/Tallinn')->startOfDay();

        return view('prices.index', [
            'paketid' => $paketid,
            'valitud' => $valitud,
            'kmGa' => $kmGa,
            'leping' => $leping,
            'tana' => $this->assembler->assemble($today, $leping),
            'homme' => $this->assembler->assemble($today->addDay(), $leping),
            'praegu' => $this->praeguneHind($leping),
            'pysikulu' => $this->pysikulu($leping),
            'varskus' => $this->varskus(),
        ]);
    }

    /** @param Collection<int, GridPackage> $paketid */
    private function valiPakett(Request $request, $paketid): GridPackage
    {
        $kood = $request->query('package') ?? $request->cookie('tariif_package');

        // Tundmatu sisend ei tohi lehte lõhkuda — langeb tagasi vaikepaketile
        return $paketid->firstWhere('code', $kood)
            ?? $paketid->firstWhere('code', config('tariif.default_package'))
            ?? $paketid->first();
    }

    private function kasKmGa(Request $request): bool
    {
        if ($request->has('vat')) {
            return $request->query('vat') !== '0';
        }

        return $request->cookie('tariif_vat') !== '0';
    }

    /** @return array<string, mixed>|null */
    private function praeguneHind(ContractContext $leping): ?array
    {
        $rida = MarketPrice::where('period_start_utc', '<=', CarbonImmutable::now('UTC'))
            ->latestFirst()
            ->first();

        if (! $rida) {
            return null;
        }

        try {
            $breakdown = $this->calculator->forInstant($rida->centsPerKwh(), $rida->period_start_utc, $leping);
        } catch (\RuntimeException $e) {
            // Puuduv tariif → hinda EI näidata. Vale number on halvem kui tühi koht
            return ['viga' => $e->getMessage()];
        }

        $algus = $rida->period_start_utc->setTimezone('Europe/Tallinn');

        return [
            'algus' => $algus,
            'label' => $algus->format('H:i'),
            'breakdown' => $breakdown,
        ];
    }

    /** @return array<string, float>|null */
    private function pysikulu(ContractContext $leping): ?array
    {
        try {
            return $this->calculator->fixedMonthlyCost(CarbonImmutable::now('UTC'), $leping);
        } catch (\RuntimeException) {
            return null;
        }
    }

    /** @return array{uuendatud: CarbonImmutable|null, vanus_tunnid: int|null, aegunud: bool} */
    private function varskus(): array
    {
        $viimane = MarketPrice::latestFirst()->first();

        if (! $viimane) {
            return ['uuendatud' => null, 'vanus_tunnid' => null, 'aegunud' => true];
        }

        $vanus = (int) floor(
            $viimane->period_start_utc->diffInMinutes(CarbonImmutable::now('UTC'), absolute: false) / 60
        );

        return [
            'uuendatud' => $viimane->fetched_at?->setTimezone('Europe/Tallinn'),
            'vanus_tunnid' => $vanus,
            'aegunud' => $vanus > (int) config('tariif.stale_after_hours'),
        ];
    }
}
