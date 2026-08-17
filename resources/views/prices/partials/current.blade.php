<section class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">

    @if ($praegu === null)
        <p class="text-sm text-slate-600">Praegust hinda ei saa kuvada — hinnaandmed puuduvad.</p>

    @elseif (isset($praegu['viga']))
        <div class="rounded-lg border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-900">
            <strong class="font-semibold">Hinda ei saa arvutada.</strong>
            {{ $praegu['viga'] }}
            <p class="mt-1 text-rose-800">Me ei näita hinda, mille õigsuses pole kindlad.</p>
        </div>

    @else
        @php $b = $praegu['breakdown']; @endphp

        <div class="flex flex-wrap items-baseline justify-between gap-3">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                    Praegune tunnihind · {{ $praegu['label'] }}
                </p>
                <p class="mt-1">
                    <span class="text-4xl font-semibold tabular-nums sm:text-5xl">{{ number_format($b->totalIncVat, 2, ',', ' ') }}</span>
                    <span class="ml-1 text-base text-slate-600">senti/kWh</span>
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    {{ $kmGa ? 'käibemaksuga' : 'käibemaksuta' }} ·
                    {{ $b->rateKind === 'night' ? 'öötariif' : ($b->rateKind === 'day' ? 'päevatariif' : 'ühetariifne') }}
                </p>
            </div>

            @if ($pysikulu)
                <div class="text-right">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Püsikulu</p>
                    <p class="mt-1 text-xl font-semibold tabular-nums">
                        {{ number_format($kmGa ? $pysikulu['inc_vat'] : $pysikulu['ex_vat'], 2, ',', ' ') }} €
                    </p>
                    <p class="text-xs text-slate-500">kuus · {{ config('tariif.default_amperage') }} A</p>
                </div>
            @endif
        </div>

        <dl class="mt-5 space-y-1 border-t border-slate-100 pt-4 text-sm">
            @foreach ([
                'Börsihind' => $b->spot,
                'Müüja marginaal' => $b->supplierMargin,
                'Võrgutasu' => $b->gridEnergy,
                'Taastuvenergia tasu' => $b->renewable,
                'Varustuskindluse tasu' => $b->supplySecurity,
                'Elektriaktsiis' => $b->excise,
                'Tasakaalustamisvõimsuse kulu' => $b->balancingCapacity,
            ] as $nimi => $vaartus)
                <div class="flex justify-between">
                    <dt class="text-slate-600">{{ $nimi }}</dt>
                    <dd class="tabular-nums">{{ number_format($vaartus, 3, ',', ' ') }}</dd>
                </div>
            @endforeach

            <div class="flex justify-between border-t border-slate-100 pt-2 text-slate-600">
                <dt>Kokku ilma käibemaksuta</dt>
                <dd class="tabular-nums">{{ number_format($b->subtotalExVat, 3, ',', ' ') }}</dd>
            </div>

            @if ($kmGa)
                <div class="flex justify-between text-slate-600">
                    <dt>Käibemaks</dt>
                    <dd class="tabular-nums">{{ number_format($b->vat, 3, ',', ' ') }}</dd>
                </div>
            @endif

            <div class="flex justify-between border-t border-slate-200 pt-2 font-semibold">
                <dt>Kokku</dt>
                <dd class="tabular-nums">{{ number_format($b->totalIncVat, 3, ',', ' ') }} senti/kWh</dd>
            </div>
        </dl>
    @endif

</section>
