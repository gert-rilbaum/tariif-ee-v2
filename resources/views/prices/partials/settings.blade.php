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

    @if ($uhendusValikud !== [])
        <div class="mt-3">
            <p class="mb-1.5 text-xs text-ink-2">
                Peakaitse — määrab kuutasu, mitte kWh hinda
            </p>
            <div class="flex flex-wrap gap-1.5">
                @foreach ($uhendusValikud as $valik)
                    @php
                        $aktiivne = $valik['connection_type'] === $uhendus['connection_type']
                            && $valik['amperage'] === $uhendus['amperage'];
                    @endphp
                    <a href="{{ request()->fullUrlWithQuery(['conn' => $valik['key']]) }}"
                       class="inline-flex min-h-11 items-center gap-1 rounded-lg border px-3 text-xs font-medium transition
                              {{ $aktiivne ? 'border-ink bg-ink text-plane' : 'border-hairline bg-raised text-ink-2 hover:border-baseline' }}">
                        {{ $valik['label'] }}
                        <span class="ml-1 font-normal {{ $aktiivne ? 'text-plane/70' : 'text-ink-muted' }}">
                            {{ number_format($valik['monthly_eur'], 2, ',', ' ') }} €
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="mt-3 flex items-start gap-2.5 rounded-xl border border-dashed border-baseline px-3.5 py-3 text-xs leading-relaxed text-ink-2">
        <x-icon name="info" class="mt-0.5 size-3.5 shrink-0 text-ink-muted"/>
        <div>
            <strong class="font-semibold text-ink">Arvutuse eeldused:</strong>
            {{ $valitud->name }} · {{ $uhendus['connection_type'] === 'apartment' ? 'korter' : $uhendus['amperage'].' A peakaitse' }} ·
            müüja marginaal <strong class="text-ink">{{ number_format($leping->supplierMarginCentsPerKwh, 2, ',', ' ') }} senti/kWh</strong>
            — tüüpiline eeldus, sinu leping võib erineda.
            <span class="block mt-0.5 text-ink-muted">Oma lepingu parameetrite sisestamine tuleb järgmise etapiga.</span>
        </div>
    </div>

</section>
