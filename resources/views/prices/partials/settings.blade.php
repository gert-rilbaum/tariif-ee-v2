<section class="mb-6">

    <h2 class="mb-2 text-sm font-semibold text-slate-700">Vali oma võrgupakett</h2>

    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
        @foreach ($paketid as $pakett)
            @php $aktiivne = $pakett->code === $valitud->code; @endphp
            <a href="{{ request()->fullUrlWithQuery(['package' => $pakett->code]) }}"
               class="rounded-lg border px-4 py-3 text-sm transition
                      {{ $aktiivne
                         ? 'border-slate-900 bg-slate-900 text-white'
                         : 'border-slate-200 bg-white text-slate-700 hover:border-slate-400' }}">
                <span class="block font-semibold">{{ $pakett->name }}</span>
                <span class="block text-xs {{ $aktiivne ? 'text-slate-300' : 'text-slate-500' }}">
                    {{ $pakett->scheme === 'single' ? 'ühetariifne' : 'päev / öö' }}
                </span>
            </a>
        @endforeach
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
        <span class="text-slate-600">Hinnad:</span>
        <a href="{{ request()->fullUrlWithQuery(['vat' => 1]) }}"
           class="rounded-md border px-3 py-1 {{ $kmGa ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-700' }}">
            KM-ga
        </a>
        <a href="{{ request()->fullUrlWithQuery(['vat' => 0]) }}"
           class="rounded-md border px-3 py-1 {{ $kmGa ? 'border-slate-200 bg-white text-slate-700' : 'border-slate-900 bg-slate-900 text-white' }}">
            KM-ta
        </a>
        <span class="text-xs text-slate-500">KM-ta vaade sobib käibemaksukohustuslasele</span>
    </div>

    <div class="mt-3 rounded-lg border border-dashed border-slate-300 bg-white px-4 py-3 text-xs leading-relaxed text-slate-600">
        <strong class="font-semibold text-slate-700">Arvutuse eeldused:</strong>
        võrgupakett {{ $valitud->name }} ·
        peakaitse {{ config('tariif.default_amperage') }} A ·
        müüja marginaal <strong>{{ number_format($leping->supplierMarginCentsPerKwh, 2, ',', ' ') }} senti/kWh</strong>
        <span class="text-slate-500">— tüüpiline eeldus, sinu leping võib erineda.</span>
        <br>
        Oma lepingu parameetrite sisestamine tuleb järgmise etapiga.
    </div>

</section>
