@php
    $paev = $valitudPaev === 'homme' ? $homme : $tana;
    $onHomme = $valitudPaev === 'homme';
    $stats = $paev['stats'];
    $nyyd = \Carbon\CarbonImmutable::now('Europe/Tallinn');
@endphp

<section class="mb-4 rounded-2xl border border-hairline bg-surface p-5 shadow-sm sm:p-6">

    {{-- Vahekaardid ja lahutusvõime ühel real: filtrid käivad graafiku kohale --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">

        <div class="inline-flex rounded-lg border border-hairline p-0.5" role="tablist">
            @foreach ([['tana', 'Täna', $tana], ['homme', 'Homme', $homme]] as [$kood, $silt, $andmed])
                <a href="{{ request()->fullUrlWithQuery(['day' => $kood]) }}"
                   role="tab"
                   aria-selected="{{ $valitudPaev === $kood ? 'true' : 'false' }}"
                   class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition
                          {{ $valitudPaev === $kood ? 'bg-ink text-plane' : 'text-ink-2 hover:text-ink' }}">
                    <x-icon name="calendar" class="size-3.5"/>
                    {{ $silt }}
                    @unless ($andmed['available'])
                        <span class="size-1.5 rounded-full bg-state-warning" title="andmed puuduvad"></span>
                    @endunless
                </a>
            @endforeach
        </div>

        @if ($paev['quarter_available'])
            <div class="inline-flex rounded-lg border border-hairline p-0.5">
                @foreach ([['hour', 'Tund'], ['quarter', '15 min']] as [$kood, $silt])
                    <a href="{{ request()->fullUrlWithQuery(['res' => $kood]) }}"
                       class="rounded-md px-3 py-1.5 text-sm font-medium transition
                              {{ $paev['granularity'] === $kood ? 'bg-ink text-plane' : 'text-ink-2 hover:text-ink' }}">
                        {{ $silt }}
                    </a>
                @endforeach
            </div>
        @endif

    </div>

    @if (! $paev['available'])
        <div class="flex items-start gap-2.5 rounded-xl border border-hairline bg-raised p-4 text-sm text-ink-2">
            <x-icon name="info" class="mt-0.5 size-4 shrink-0"/>
            <span>
                @if ($onHomme)
                    Homsed hinnad avaldatakse tavaliselt kell ~14.00. Tule siis tagasi.
                @else
                    Selle päeva hinnaandmed puuduvad.
                @endif
            </span>
        </div>
    @else

        @if ($paev['partial'])
            <p class="mb-4 flex items-center gap-2 rounded-xl border border-hairline bg-raised px-3 py-2 text-xs text-ink-2">
                <x-icon name="info" class="size-3.5 shrink-0"/>
                Avaldatud {{ count($paev['points']) }} {{ $paev['slots_expected'] }}-st.
                @if ($onHomme) Ülejäänu avaldatakse tavaliselt kell ~14.00. @endif
            </p>
        @endif

        {{-- Statistikaplaadid: madalaim / keskmine / kõrgeim koos kellaajaga.
             Lahutusvõime on välja öeldud, sest hero näitab viimast 15-min
             intervalli — ilma märketa tunduks tunni keskmine sellega vastuolus. --}}
        <p class="mb-2 text-xs text-ink-muted">
            Päeva ülevaade · {{ $paev['granularity'] === 'quarter' ? '15-minutilised hinnad' : 'tunni keskmised' }}
        </p>
        <div class="mb-5 grid grid-cols-3 gap-2 sm:gap-3">
            @foreach ([
                ['Madalaim', $stats['min'], $stats['min_at'], 'trend-down', 'text-state-good'],
                ['Keskmine', $stats['avg'], null, 'chart', 'text-ink-2'],
                ['Kõrgeim', $stats['max'], $stats['max_at'], 'trend-up', 'text-state-critical'],
            ] as [$silt, $vaartus, $kell, $ikoon, $toon])
                <div class="rounded-xl border border-hairline bg-raised px-3 py-2.5">
                    <p class="flex items-center gap-1.5 text-xs text-ink-muted">
                        <x-icon name="{{ $ikoon }}" class="size-3.5 {{ $toon }}"/>
                        {{ $silt }}
                    </p>
                    <p class="mt-0.5 text-lg font-semibold leading-tight">{{ number_format($vaartus, 2, ',', ' ') }}</p>
                    <p class="text-xs text-ink-muted">{{ $kell ? 'kell '.$kell : 'senti/kWh' }}</p>
                </div>
            @endforeach
        </div>

        @php
            $min = $stats['min'];
            $max = $stats['max'];
            $ulatus = max($max - $min, 0.001);
        @endphp

        {{-- Võrestik ööpäeva pesade arvu järgi: avaldamata ajad jäävad ausalt
             tühjaks. minmax(0,1fr), sest 1fr = minmax(auto,1fr) ei kahane. --}}
        <div class="grid h-44 items-end gap-[2px]"
             style="grid-template-columns: repeat({{ $paev['slots_expected'] }}, minmax(0, 1fr))">
            @foreach ($paev['points'] as $punkt)
                @php
                    $suhe = ($punkt['total_inc_vat'] - $min) / $ulatus;
                    $korgus = 8 + $suhe * 92;
                    $onPraegu = ! $onHomme
                        && $punkt['hour'] === (int) $nyyd->format('G')
                        && ($paev['granularity'] === 'hour' || $punkt['minute'] === (intdiv((int) $nyyd->format('i'), 15) * 15));
                @endphp
                <div class="group relative flex h-full flex-col justify-end"
                     title="{{ $punkt['label'] }} — {{ number_format($punkt['total_inc_vat'], 2, ',', ' ') }} senti/kWh">
                    <div class="bar-mark w-full {{ $onPraegu ? 'bg-ink' : 'bg-series-1' }}"
                         style="height: {{ $korgus }}%"></div>
                </div>
            @endforeach
        </div>

        <div class="mt-1.5 h-px bg-baseline"></div>

        <div class="mt-1 grid gap-[2px] text-[10px] tabular-nums text-ink-muted"
             style="grid-template-columns: repeat({{ $paev['slots_expected'] }}, minmax(0, 1fr))">
            @php $samm = $paev['granularity'] === 'quarter' ? 12 : 3; @endphp
            @for ($i = 0; $i < $paev['slots_expected']; $i++)
                <div class="text-center">
                    @if ($i % $samm === 0)
                        {{ $paev['granularity'] === 'quarter' ? intdiv($i, 4) : $i }}
                    @endif
                </div>
            @endfor
        </div>

        <p class="mt-2 flex items-center gap-1.5 text-xs text-ink-muted">
            <span class="size-2.5 rounded-sm bg-ink"></span> praegune aeg
            <span class="ml-3 size-2.5 rounded-sm bg-series-1"></span> lõpphind senti/kWh
        </p>

        <details class="mt-4">
            <summary class="cursor-pointer text-sm text-ink-2 hover:text-ink">Näita tabelina</summary>

            <div class="mt-3 overflow-x-auto">
                <table class="w-full min-w-[22rem] text-sm">
                    <thead>
                        <tr class="border-b border-hairline text-left text-xs uppercase tracking-wide text-ink-muted">
                            <th class="py-1.5 font-medium">Aeg</th>
                            <th class="py-1.5 font-medium">Tariif</th>
                            <th class="py-1.5 text-right font-medium">Börs</th>
                            <th class="py-1.5 text-right font-medium">Kokku</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($paev['points'] as $punkt)
                            <tr class="border-b border-hairline/50">
                                <td class="py-1.5 tabular-nums">{{ $punkt['label'] }}</td>
                                <td class="py-1.5 text-ink-2">
                                    {{ $punkt['breakdown']['rate_kind'] === 'night' ? 'öö'
                                       : ($punkt['breakdown']['rate_kind'] === 'day' ? 'päev' : 'ühtne') }}
                                </td>
                                <td class="py-1.5 text-right tabular-nums text-ink-2">
                                    {{ number_format($punkt['breakdown']['spot'], 2, ',', ' ') }}
                                </td>
                                <td class="py-1.5 text-right font-medium tabular-nums">
                                    {{ number_format($punkt['total_inc_vat'], 2, ',', ' ') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    @endif

</section>
