<section class="mb-4 rounded-2xl border border-hairline bg-surface p-5 shadow-sm sm:p-6">

    <p class="mb-2.5 flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-ink-muted">
        <x-icon name="sliders" class="size-3.5"/>
        Sinu seaded
    </p>

    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
        @foreach ($paketid as $pakett)
            @php
                $aktiivne = $pakett->code === $valitud->code;
                $ikoon = match ($pakett->code) { 'vork1' => 'building', 'vork4' => 'flame', default => 'home' };
                $vihje = match ($pakett->code) {
                    'vork1' => 'Korter, väike tarbimine',
                    'vork4' => 'Soojuspump, elektriküte, laadija',
                    default => 'Eramu, ridaelamu',
                };
            @endphp
            <a href="{{ request()->fullUrlWithQuery(['package' => $pakett->code]) }}"
               class="flex items-start gap-3 rounded-xl border p-3.5 transition
                      {{ $aktiivne ? 'border-ink bg-ink text-plane' : 'border-hairline bg-raised hover:border-baseline' }}">
                <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg
                             {{ $aktiivne ? 'bg-plane/15' : 'bg-surface text-series-1' }}">
                    <x-icon name="{{ $ikoon }}" class="size-4"/>
                </span>
                <span class="min-w-0">
                    <span class="block text-sm font-semibold">{{ $pakett->name }}</span>
                    <span class="block text-xs {{ $aktiivne ? 'text-plane/70' : 'text-ink-muted' }}">{{ $vihje }}</span>
                    <span class="mt-0.5 block text-xs {{ $aktiivne ? 'text-plane/70' : 'text-ink-muted' }}">
                        {{ $pakett->scheme === 'single' ? 'ühetariifne' : 'päev / öö' }}
                    </span>
                </span>
            </a>
        @endforeach
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
        <span class="text-ink-2">Hinnad:</span>
        <div class="inline-flex rounded-lg border border-hairline p-0.5">
            <a href="{{ request()->fullUrlWithQuery(['vat' => 1]) }}"
               class="rounded-md px-3 py-1 font-medium transition {{ $kmGa ? 'bg-ink text-plane' : 'text-ink-2 hover:text-ink' }}">KM-ga</a>
            <a href="{{ request()->fullUrlWithQuery(['vat' => 0]) }}"
               class="rounded-md px-3 py-1 font-medium transition {{ $kmGa ? 'text-ink-2 hover:text-ink' : 'bg-ink text-plane' }}">KM-ta</a>
        </div>
        <span class="text-xs text-ink-muted">KM-ta sobib käibemaksukohustuslasele</span>
    </div>

    <div class="mt-3 flex items-start gap-2.5 rounded-xl border border-dashed border-baseline px-3.5 py-3 text-xs leading-relaxed text-ink-2">
        <x-icon name="info" class="mt-0.5 size-3.5 shrink-0 text-ink-muted"/>
        <div>
            <strong class="font-semibold text-ink">Arvutuse eeldused:</strong>
            {{ $valitud->name }} · peakaitse {{ config('tariif.default_amperage') }} A ·
            müüja marginaal <strong class="text-ink">{{ number_format($leping->supplierMarginCentsPerKwh, 2, ',', ' ') }} senti/kWh</strong>
            — tüüpiline eeldus, sinu leping võib erineda.
            <span class="block mt-0.5 text-ink-muted">Oma lepingu parameetrite sisestamine tuleb järgmise etapiga.</span>
        </div>
    </div>

</section>
