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
        $uhendus = $this->valiUhendus($request, $valitud);

        $leping = new ContractContext(
            package: $valitud,
            supplierMarginCentsPerKwh: (float) config('tariif.assumed_supplier_margin_cents'),
            amperage: $uhendus['amperage'],
            phases: (int) config('tariif.default_phases'),
            connectionType: $uhendus['connection_type'],
            vatApplicable: $kmGa,
        );

        $today = CarbonImmutable::now('Europe/Tallinn')->startOfDay();
        $res = $request->query('res') === DayPriceAssembler::QUARTER
            ? DayPriceAssembler::QUARTER
            : DayPriceAssembler::HOUR;

        $tana = $this->assembler->assemble($today, $leping, $res);
        $praegu = $this->praeguneHind($leping);

        // Võrdlusalus peab olema hero-hinnaga sama lahutusvõimega. Hero näitab
        // viimast 15-min intervalli; kui võrrelda tunni keskmistega, võib hero
        // sattuda allapoole väidetavat päeva miinimumi — kasutaja jaoks vastuolu.
        $vordlusAlus = $tana['granularity'] === DayPriceAssembler::QUARTER
            ? $tana
            : $this->assembler->assemble($today, $leping, DayPriceAssembler::QUARTER);

        return view('prices.index', [
            'paketid' => $paketid,
            'valitud' => $valitud,
            'kmGa' => $kmGa,
            'leping' => $leping,
            'tana' => $tana,
            'homme' => $this->assembler->assemble($today->addDay(), $leping, $res),
            'valitudPaev' => $request->query('day') === 'homme' ? 'homme' : 'tana',
            'uhendus' => $uhendus,
            'uhendusValikud' => $this->uhendusValikud($valitud),
            'praegu' => $praegu,
            'vordlus' => $this->vordlusPaevaga($praegu, $vordlusAlus),
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

    /**
     * Ühenduse valik: kasutaja päring > paketi vaikeväärtus > varuvariant.
     *
     * Iga pakett on mõeldud eri tarbijale, seega on ka tüüpiline peakaitse eri
     * suurusega — Võrk 1 korterile, Võrk 4 energiamahukale kodule.
     *
     * @return array{connection_type: string, amperage: int}
     */
    private function valiUhendus(Request $request, GridPackage $pakett): array
    {
        $vaikimisi = config('tariif.package_defaults.'.$pakett->code)
            ?? config('tariif.fallback_connection');

        $valikud = $this->uhendusValikud($pakett);

        $soovitud = $request->query('conn');

        foreach ($valikud as $valik) {
            if ($soovitud === $valik['key']) {
                return ['connection_type' => $valik['connection_type'], 'amperage' => $valik['amperage']];
            }
        }

        // Kui paketil vaikeväärtust ei ole hinnakirjas, võta esimene olemasolev
        foreach ($valikud as $valik) {
            if ($valik['connection_type'] === $vaikimisi['connection_type']
                && $valik['amperage'] === $vaikimisi['amperage']) {
                return $vaikimisi;
            }
        }

        return $valikud !== []
            ? ['connection_type' => $valikud[0]['connection_type'], 'amperage' => $valikud[0]['amperage']]
            : $vaikimisi;
    }

    /**
     * Millised ühendused on selle paketi kehtivas hinnakirjas päriselt olemas.
     * Valikud tulevad andmetest, mitte koodis olevast nimekirjast.
     *
     * @return array<int, array{key: string, connection_type: string, amperage: int, monthly_eur: float, label: string}>
     */
    private function uhendusValikud(GridPackage $pakett): array
    {
        $versioon = $pakett->versionAt(CarbonImmutable::now('Europe/Tallinn'));

        if (! $versioon) {
            return [];
        }

        return $versioon->capacityFees
            ->sortBy([['connection_type', 'desc'], ['amperage', 'asc']])
            ->map(fn ($tasu) => [
                'key' => $tasu->connection_type === 'apartment' ? 'korter' : $tasu->amperage.'a',
                'connection_type' => $tasu->connection_type,
                'amperage' => $tasu->amperage,
                'monthly_eur' => (float) $tasu->monthly_eur,
                'label' => $tasu->connection_type === 'apartment' ? 'Korter' : $tasu->amperage.' A',
            ])
            ->values()
            ->all();
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

    /**
     * Kus praegune hind päeva skaalal asub. Sõnaline hinnang tuleb koos ikooniga
     * ja tekstiga — värv üksi ei kanna tähendust.
     *
     * @param  array<string, mixed>|null  $praegu
     * @param  array<string, mixed>  $paev
     * @return array{kood: string, tekst: string}|null
     */
    private function vordlusPaevaga(?array $praegu, array $paev): ?array
    {
        if (! $praegu || isset($praegu['viga']) || ! $paev['available'] || $paev['stats'] === null) {
            return null;
        }

        $hind = $praegu['breakdown']->totalIncVat;
        $min = $paev['stats']['min'];
        $max = $paev['stats']['max'];
        $ulatus = $max - $min;

        if ($ulatus < 0.001) {
            return null;
        }

        $suhe = ($hind - $min) / $ulatus;

        return match (true) {
            $suhe <= 0.25 => ['kood' => 'odav', 'tekst' => 'odavamate tundide seas'],
            $suhe >= 0.75 => ['kood' => 'kallis', 'tekst' => 'kallimate tundide seas'],
            default => ['kood' => 'keskmine', 'tekst' => 'keskmises vahemikus'],
        };
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
