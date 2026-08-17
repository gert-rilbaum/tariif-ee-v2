@php
    $b = $praegu['breakdown'] ?? null;

    /*
     * Rühmitus järgib seda, kuidas kasutaja päriselt arveid saab — kaks eri arvet:
     *
     *  ELEKTRI ARVE (müüjalt): börsihind + müüja marginaal + tasakaalustamisvõimsuse
     *  kulu. Elering: müüja "esitab selle arvel eraldi reana" (elektrituruseadus).
     *
     *  VÕRGUARVE (võrguettevõtjalt): võrgutasu + riiklikud tasud. Elektrilevi
     *  hinnakiri: "Võrguteenuse arvele lisanduvad riiklikud tasud ja maksud
     *  (elektriaktsiis, taastuvenergia tasu ja varustuskindluse tasu)."
     *
     * Värvid on pesad 1-2-3 (sinine, oranž, akva) — valideeritud all-pairs
     * mõlemas režiimis. Kollane oleks oranžist liiga vähe eristuv (ΔE 13,7 < 15).
     */
    $elektriArve = $b ? [
        'Börsihind' => $b->spot,
        'Müüja marginaal' => $b->supplierMargin,
        'Tasakaalustamisvõimsuse kulu' => $b->balancingCapacity,
    ] : [];

    $vorguArve = $b ? [
        'Võrgutasu (edastamine)' => $b->gridEnergy,
        'Taastuvenergia tasu' => $b->renewable,
        'Varustuskindluse tasu' => $b->supplySecurity,
        'Elektriaktsiis' => $b->excise,
    ] : [];

    $elektriSumma = array_sum($elektriArve);
    $vorguSumma = array_sum($vorguArve);

    $osad = $b ? array_values(array_filter([
        ['nimi' => 'Elektri arve', 'vaartus' => max($elektriSumma, 0), 'varv' => 'bg-series-1'],
        ['nimi' => 'Võrguarve', 'vaartus' => $vorguSumma, 'varv' => 'bg-series-2'],
        $kmGa ? ['nimi' => 'Käibemaks', 'vaartus' => $b->vat, 'varv' => 'bg-series-3'] : null,
    ])) : [];

    $osadeSumma = array_sum(array_column($osad, 'vaartus')) ?: 1;
@endphp

<section class="mb-4 overflow-hidden rounded-2xl border border-hairline bg-surface shadow-sm">

    @if ($praegu === null)
        <div class="flex items-center gap-2 p-5 text-sm text-ink-2 sm:p-6">
            <x-icon name="info" class="size-4 shrink-0"/>
            Praegust hinda ei saa kuvada — hinnaandmed puuduvad.
        </div>

    @elseif (isset($praegu['viga']))
        <div class="p-5 sm:p-6">
            <div class="flex items-start gap-2.5 rounded-xl border border-hairline bg-raised p-4 text-sm">
                <x-icon name="alert" class="mt-0.5 size-4 shrink-0 text-state-critical"/>
                <div>
                    <p class="font-semibold">Hinda ei saa arvutada.</p>
                    <p class="mt-0.5 text-ink-2">{{ $praegu['viga'] }}</p>
                    <p class="mt-1 text-ink-muted">Me ei näita hinda, mille õigsuses pole kindlad.</p>
                </div>
            </div>
        </div>

    @else
        <div class="grid gap-5 p-5 sm:grid-cols-[1fr_auto] sm:items-start sm:gap-8 sm:p-6">

            <div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-ink-muted">
                        <x-icon name="clock" class="size-3.5"/>
                        Praegune hind · {{ $praegu['label'] }}
                    </p>

                    {{-- KM-lüliti elab hinna juures, sest ta muudab just seda numbrit --}}
                    <div class="inline-flex gap-0.5 rounded-lg border border-hairline p-1 text-xs">
                        <a href="{{ request()->fullUrlWithQuery(['vat' => 1]) }}"
                           class="whitespace-nowrap rounded-md px-3 py-1.5 font-medium transition {{ $kmGa ? 'bg-ink text-plane' : 'text-ink-2 hover:text-ink' }}">KM-ga</a>
                        <a href="{{ request()->fullUrlWithQuery(['vat' => 0]) }}"
                           class="whitespace-nowrap rounded-md px-3 py-1.5 font-medium transition {{ $kmGa ? 'text-ink-2 hover:text-ink' : 'bg-ink text-plane' }}">KM-ta</a>
                    </div>
                </div>

                <p class="mt-1.5 flex items-baseline gap-2">
                    <span class="text-5xl font-semibold leading-none tracking-tight sm:text-6xl">{{ number_format($b->totalIncVat, 2, ',', ' ') }}</span>
                    <span class="text-lg text-ink-2">senti/kWh</span>
                </p>

                <p class="mt-2 flex flex-wrap items-center gap-1.5 text-xs">
                    <span class="inline-flex items-center gap-1 rounded-full border border-hairline px-2 py-0.5 text-ink-2">
                        {{ $b->rateKind === 'night' ? 'öötariif' : ($b->rateKind === 'day' ? 'päevatariif' : 'ühetariifne') }}
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full border border-hairline px-2 py-0.5 text-ink-2">
                        {{ $valitud->name }}
                    </span>
                    @if ($vordlus)
                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-medium
                                     {{ $vordlus['kood'] === 'odav' ? 'text-state-good' : ($vordlus['kood'] === 'kallis' ? 'text-state-critical' : 'text-ink-2') }}">
                            <x-icon name="{{ $vordlus['kood'] === 'odav' ? 'trend-down' : ($vordlus['kood'] === 'kallis' ? 'trend-up' : 'info') }}" class="size-3.5"/>
                            {{ $vordlus['tekst'] }}
                        </span>
                    @endif
                </p>
            </div>

            @if ($pysikulu)
                <div class="rounded-xl border border-hairline bg-raised px-4 py-3 sm:text-right">
                    <p class="flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-ink-muted sm:justify-end">
                        <x-icon name="receipt" class="size-3.5"/>
                        Püsikulu
                    </p>
                    <p class="mt-1 text-2xl font-semibold">
                        {{ number_format($kmGa ? $pysikulu['inc_vat'] : $pysikulu['ex_vat'], 2, ',', ' ') }} €
                    </p>
                    <p class="text-xs text-ink-muted">kuus · {{ $uhendus['connection_type'] === 'apartment' ? 'korter' : $uhendus['amperage'].' A' }} · võrguarvel</p>
                </div>
            @endif

        </div>

        {{-- Koosseisuriba. Iga segment kannab nähtavat silti ja väärtust
             (relief rule — akva jääb heleda pinna vastu alla 3:1). --}}
        <div class="border-t border-hairline bg-raised px-5 py-4 sm:px-6">
            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-ink-muted">Millest hind koosneb</p>

            <div class="composition flex h-3 overflow-hidden rounded-full">
                @foreach ($osad as $osa)
                    <div class="{{ $osa['varv'] }} first:rounded-l-full last:rounded-r-full"
                         style="width: {{ max($osa['vaartus'] / $osadeSumma * 100, 0.5) }}%"></div>
                @endforeach
            </div>

            <ul class="mt-3 grid grid-cols-1 gap-2 text-xs sm:grid-cols-3">
                @foreach ($osad as $osa)
                    <li class="flex items-baseline gap-2 rounded-lg border border-hairline bg-surface px-3 py-2">
                        <span class="{{ $osa['varv'] }} size-2.5 shrink-0 translate-y-px rounded-sm"></span>
                        <span class="min-w-0 flex-1 truncate text-ink-2">{{ $osa['nimi'] }}</span>
                        <span class="font-semibold tabular-nums">{{ number_format($osa['vaartus'], 2, ',', ' ') }}</span>
                        <span class="text-ink-muted tabular-nums">{{ number_format($osa['vaartus'] / $osadeSumma * 100, 0) }}%</span>
                    </li>
                @endforeach
            </ul>

            <details class="mt-3 border-t border-hairline pt-3">
                <summary class="flex cursor-pointer list-none items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-ink-muted hover:text-ink">
                    <x-icon name="receipt" class="size-3.5"/>
                    Kõik komponendid — kahe arve kaupa
                </summary>

                <div class="mt-3 grid gap-3 sm:grid-cols-2">

                    <div class="rounded-xl border border-hairline bg-surface p-3.5">
                        <p class="mb-2 flex items-center gap-1.5 text-xs font-semibold">
                            <span class="size-2.5 rounded-sm bg-series-1"></span>
                            Elektri arve
                            <span class="font-normal text-ink-muted">· müüjalt</span>
                        </p>
                        <dl class="space-y-1 text-xs">
                            @foreach ($elektriArve as $nimi => $vaartus)
                                <div class="flex justify-between gap-3">
                                    <dt class="text-ink-2">{{ $nimi }}</dt>
                                    <dd class="tabular-nums">{{ number_format($vaartus, 3, ',', ' ') }}</dd>
                                </div>
                            @endforeach
                            <div class="flex justify-between gap-3 border-t border-hairline pt-1 font-semibold">
                                <dt>Kokku</dt>
                                <dd class="tabular-nums">{{ number_format($elektriSumma, 3, ',', ' ') }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-xl border border-hairline bg-surface p-3.5">
                        <p class="mb-2 flex items-center gap-1.5 text-xs font-semibold">
                            <span class="size-2.5 rounded-sm bg-series-2"></span>
                            Võrguarve
                            <span class="font-normal text-ink-muted">· {{ $valitud->operator->name ?? 'võrguettevõtjalt' }}</span>
                        </p>
                        <dl class="space-y-1 text-xs">
                            @foreach ($vorguArve as $nimi => $vaartus)
                                <div class="flex justify-between gap-3">
                                    <dt class="text-ink-2">{{ $nimi }}</dt>
                                    <dd class="tabular-nums">{{ number_format($vaartus, 3, ',', ' ') }}</dd>
                                </div>
                            @endforeach
                            <div class="flex justify-between gap-3 border-t border-hairline pt-1 font-semibold">
                                <dt>Kokku</dt>
                                <dd class="tabular-nums">{{ number_format($vorguSumma, 3, ',', ' ') }}</dd>
                            </div>
                        </dl>
                        @if ($pysikulu)
                            <p class="mt-2 text-xs text-ink-muted">
                                + kuutasu {{ number_format($kmGa ? $pysikulu['inc_vat'] : $pysikulu['ex_vat'], 2, ',', ' ') }} € kuus, ei sõltu tarbimisest
                            </p>
                        @endif
                    </div>

                </div>

                <div class="mt-3 flex justify-between border-t border-hairline pt-2 text-base font-semibold">
                    <span>Kokku {{ $kmGa ? 'käibemaksuga' : 'käibemaksuta' }}</span>
                    <span class="tabular-nums">{{ number_format($b->totalIncVat, 3, ',', ' ') }} senti/kWh</span>
                </div>
            </details>
        </div>
    @endif

</section>
