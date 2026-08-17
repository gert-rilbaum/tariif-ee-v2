@php
    $b = $praegu['breakdown'] ?? null;

    // Koosseis neljaks: elekter · võrk · riiklikud tasud · käibemaks.
    // Kategoriaalne kodeering, pesad kindlas järjekorras (valideeritud).
    $osad = $b ? [
        ['nimi' => 'Elekter', 'vaartus' => max($b->spot + $b->supplierMargin, 0), 'varv' => 'bg-series-1'],
        ['nimi' => 'Võrgutasu', 'vaartus' => $b->gridEnergy, 'varv' => 'bg-series-2'],
        ['nimi' => 'Riiklikud tasud', 'vaartus' => $b->renewable + $b->supplySecurity + $b->excise + $b->balancingCapacity, 'varv' => 'bg-series-3'],
        ['nimi' => 'Käibemaks', 'vaartus' => $b->vat, 'varv' => 'bg-series-4'],
    ] : [];

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
        <div class="grid gap-5 p-5 sm:grid-cols-[1fr_auto] sm:items-end sm:gap-8 sm:p-6">

            <div>
                <p class="flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-ink-muted">
                    <x-icon name="clock" class="size-3.5"/>
                    Praegune hind · {{ $praegu['label'] }}
                </p>

                <p class="mt-1.5 flex items-baseline gap-2">
                    <span class="text-5xl font-semibold leading-none tracking-tight sm:text-6xl">{{ number_format($b->totalIncVat, 2, ',', ' ') }}</span>
                    <span class="text-lg text-ink-2">senti/kWh</span>
                </p>

                <p class="mt-2 flex flex-wrap items-center gap-1.5 text-xs">
                    <span class="inline-flex items-center gap-1 rounded-full border border-hairline px-2 py-0.5 text-ink-2">
                        {{ $kmGa ? 'KM-ga' : 'KM-ta' }}
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full border border-hairline px-2 py-0.5 text-ink-2">
                        {{ $b->rateKind === 'night' ? 'öötariif' : ($b->rateKind === 'day' ? 'päevatariif' : 'ühetariifne') }}
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
                    <p class="text-xs text-ink-muted">kuus · {{ config('tariif.default_amperage') }} A peakaitse</p>
                </div>
            @endif

        </div>

        {{-- Koosseisuriba: millest hind koosneb. Iga segment kannab nähtavat silti
             (relief rule — osa hüüsid jäävad heleda pinna vastu alla 3:1). --}}
        <div class="border-t border-hairline bg-raised px-5 py-4 sm:px-6">
            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-ink-muted">Millest hind koosneb</p>

            <div class="composition flex h-3 overflow-hidden rounded-full">
                @foreach ($osad as $osa)
                    <div class="{{ $osa['varv'] }} first:rounded-l-full last:rounded-r-full"
                         style="width: {{ max($osa['vaartus'] / $osadeSumma * 100, 0.5) }}%"></div>
                @endforeach
            </div>

            <ul class="mt-3 grid grid-cols-2 gap-x-4 gap-y-1.5 text-xs sm:grid-cols-4">
                @foreach ($osad as $osa)
                    <li class="flex items-center gap-1.5">
                        <span class="{{ $osa['varv'] }} size-2.5 shrink-0 rounded-sm"></span>
                        <span class="text-ink-2">{{ $osa['nimi'] }}</span>
                        <span class="ml-auto font-medium tabular-nums">{{ number_format($osa['vaartus'], 2, ',', ' ') }}</span>
                    </li>
                @endforeach
            </ul>

            <div class="mt-4 border-t border-hairline pt-3">
                <p class="mb-2 text-xs font-medium uppercase tracking-wide text-ink-muted">Kõik komponendid</p>
                <dl class="space-y-1 text-xs sm:text-sm">
                    @foreach ([
                        'Börsihind' => $b->spot,
                        'Müüja marginaal' => $b->supplierMargin,
                        'Võrgutasu' => $b->gridEnergy,
                        'Taastuvenergia tasu' => $b->renewable,
                        'Varustuskindluse tasu' => $b->supplySecurity,
                        'Elektriaktsiis' => $b->excise,
                        'Tasakaalustamisvõimsuse kulu' => $b->balancingCapacity,
                    ] as $nimi => $vaartus)
                        <div class="flex justify-between border-b border-hairline/60 pb-1">
                            <dt class="text-ink-2">{{ $nimi }}</dt>
                            <dd class="tabular-nums">{{ number_format($vaartus, 3, ',', ' ') }}</dd>
                        </div>
                    @endforeach
                    <div class="flex justify-between pt-0.5 text-ink-2">
                        <dt>Kokku ilma käibemaksuta</dt>
                        <dd class="tabular-nums">{{ number_format($b->subtotalExVat, 3, ',', ' ') }}</dd>
                    </div>
                    @if ($kmGa)
                        <div class="flex justify-between text-ink-2">
                            <dt>Käibemaks</dt>
                            <dd class="tabular-nums">{{ number_format($b->vat, 3, ',', ' ') }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between border-t border-hairline pt-1.5 text-base font-semibold">
                        <dt>Kokku</dt>
                        <dd class="tabular-nums">{{ number_format($b->totalIncVat, 3, ',', ' ') }} senti/kWh</dd>
                    </div>
                </dl>
            </div>
        </div>
    @endif

</section>
