@php
    $paev = $valitudPaev === 'homme' ? $homme : $tana;
    $onHomme = $valitudPaev === 'homme';
    $stats = $paev['stats'];
    $nyyd = \Carbon\CarbonImmutable::now('Europe/Tallinn');
    $veerandVaade = ($paev['granularity'] ?? 'hour') === 'quarter';
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

        <p class="mb-2 text-xs text-ink-muted">
            Päeva ülevaade · {{ $veerandVaade ? '15-minutilised hinnad' : 'tunni keskmised' }} · {{ $kmGa ? 'käibemaksuga' : 'käibemaksuta' }}
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
            /*
             * Telg algab NULLIST. Tulpdiagramm, mille telg algab miinimumist,
             * võimendab erinevusi ja valetab suurusjärgu kohta — vana tariif.ee
             * tegi siin õigesti ja minu esimene versioon valesti.
             *
             * Ülemine piir ümardatakse ülespoole "ilusale" arvule, et teljemärgid
             * oleksid loetavad numbrid.
             */
            $max = max($stats['max'], 0.01);
            $samm = $max <= 10 ? 2 : ($max <= 25 ? 5 : ($max <= 60 ? 10 : 20));
            $teljeMax = ceil($max / $samm) * $samm;
            $margid = range(0, (int) ($teljeMax / $samm));
        @endphp

        <div class="mt-5 flex gap-2">

            {{-- Ühik kuulub telje juurde, mitte legendi --}}
            <div class="relative w-9 shrink-0 text-[10px] tabular-nums text-ink-muted" style="height: 12rem">
                <span class="absolute -top-4 right-0 whitespace-nowrap">senti/kWh</span>
                @foreach (array_reverse($margid) as $i)
                    <span class="absolute right-0 -translate-y-1/2"
                          style="top: {{ (1 - $i * $samm / $teljeMax) * 100 }}%">{{ $i * $samm }}</span>
                @endforeach
            </div>

            <div class="relative min-w-0 flex-1">

                {{-- Abijooned: hiuspeen, pidev, tagasihoidlik --}}
                <div class="pointer-events-none absolute inset-0" style="height: 12rem">
                    @foreach ($margid as $i)
                        <div class="absolute inset-x-0 border-t border-hairline"
                             style="top: {{ (1 - $i * $samm / $teljeMax) * 100 }}%"></div>
                    @endforeach
                </div>

                <div class="relative grid items-end gap-[2px]"
                     style="height: 12rem; grid-template-columns: repeat({{ $paev['slots_expected'] }}, minmax(0, 1fr))">
                    @foreach ($paev['points'] as $punkt)
                        @php
                            $korgus = max($punkt['total_inc_vat'], 0) / $teljeMax * 100;
                            $liik = $punkt['breakdown']['rate_kind'];
                            $onPraegu = ! $onHomme
                                && $punkt['hour'] === (int) $nyyd->format('G')
                                && (! $veerandVaade || $punkt['minute'] === intdiv((int) $nyyd->format('i'), 15) * 15);
                            // Öö = sinine (jahe), päev = oranž (soe) — intuitiivne seos
                            $varv = $onPraegu ? 'bg-ink' : ($liik === 'night' ? 'bg-series-1' : 'bg-series-2');
                        @endphp

                        {{-- Nupp, mitte div: töötab hiirega, klaviatuuriga JA puutel.
                             Vana saidi title-atribuut puuteekraanil ei avanenud. --}}
                        <button type="button"
                                class="group relative flex h-full flex-col justify-end focus:outline-none"
                                aria-label="{{ $punkt['label'] }} — {{ number_format($punkt['total_inc_vat'], 2, ',', ' ') }} senti/kWh">
                            <span class="bar-mark relative w-full {{ $varv }} transition-opacity group-hover:opacity-80"
                                  style="height: {{ $korgus }}%">
                                @unless ($veerandVaade)
                                    @if ($korgus >= 22)
                                        {{-- Väärtus tulba SEES, püstiselt. Valge tekst värvilisel
                                             täidisel on ainus koht, kus tekst tohib olla muud värvi
                                             kui tekstitoon — ja ainult siis, kui tulp on piisavalt
                                             kõrge, et number ei jääks kärbituks. --}}
                                        <span class="pointer-events-none absolute inset-x-0 bottom-1 hidden justify-center text-[10px]
                                                     font-medium tabular-nums leading-none text-white sm:flex"
                                              style="writing-mode: vertical-rl; transform: rotate(180deg)">
                                            {{ number_format($punkt['total_inc_vat'], 1, ',', ' ') }}
                                        </span>
                                    @else
                                        {{-- Madal tulp: number läheb kohale, mitte ei kärbita --}}
                                        <span class="pointer-events-none absolute inset-x-0 bottom-full mb-1 hidden justify-center
                                                     text-[10px] tabular-nums leading-none text-ink-muted sm:flex">
                                            {{ number_format($punkt['total_inc_vat'], 1, ',', ' ') }}
                                        </span>
                                    @endif
                                @endunless
                            </span>

                            <span class="pointer-events-none invisible absolute bottom-full left-1/2 z-20 mb-2 w-56 -translate-x-1/2
                                         rounded-xl border border-hairline bg-surface p-3 text-left opacity-0 shadow-lg
                                         transition group-hover:visible group-hover:opacity-100
                                         group-focus:visible group-focus:opacity-100">
                                <span class="block text-sm font-semibold">{{ $punkt['date_label'] }} {{ $punkt['label'] }}</span>
                                <span class="block text-xs text-ink-muted">
                                    {{ $punkt['weekday'] }} ·
                                    {{ $liik === 'night' ? 'öötariif' : ($liik === 'day' ? 'päevatariif' : 'ühetariifne') }}
                                </span>

                                <span class="mt-2 block border-t border-hairline pt-2 text-xs">
                                    <span class="flex justify-between font-semibold">
                                        <span>Lõpphind</span>
                                        <span class="tabular-nums">{{ number_format($punkt['total_inc_vat'], 2, ',', ' ') }}</span>
                                    </span>
                                    @php $bd = $punkt['breakdown']; @endphp
                                    <span class="mt-1 flex justify-between text-ink-2">
                                        <span><span class="mr-1 inline-block size-2 rounded-sm bg-series-1 align-middle"></span>Elekter</span>
                                        <span class="tabular-nums">{{ number_format($bd['spot'] + $bd['supplier_margin'] + $bd['balancing_capacity'], 2, ',', ' ') }}</span>
                                    </span>
                                    <span class="flex justify-between text-ink-2">
                                        <span><span class="mr-1 inline-block size-2 rounded-sm bg-series-2 align-middle"></span>Võrguteenus</span>
                                        <span class="tabular-nums">{{ number_format($bd['grid_energy'] + $bd['renewable'] + $bd['supply_security'] + $bd['excise'], 2, ',', ' ') }}</span>
                                    </span>
                                    @if ($kmGa)
                                        <span class="flex justify-between text-ink-2">
                                            <span><span class="mr-1 inline-block size-2 rounded-sm bg-series-3 align-middle"></span>Käibemaks</span>
                                            <span class="tabular-nums">{{ number_format($bd['vat'], 2, ',', ' ') }}</span>
                                        </span>
                                    @endif
                                </span>

                                @if (count($punkt['parts']) > 1)
                                    <span class="mt-2 block border-t border-hairline pt-2 text-xs">
                                        <span class="block text-ink-muted">15-min börsihind</span>
                                        @foreach ($punkt['parts'] as $osa)
                                            <span class="flex justify-between text-ink-2">
                                                <span class="tabular-nums">{{ $osa['label'] }}</span>
                                                <span class="tabular-nums">{{ number_format($osa['spot'], 2, ',', ' ') }}</span>
                                            </span>
                                        @endforeach
                                    </span>
                                @endif
                            </span>
                        </button>
                    @endforeach
                </div>

                <div class="mt-1 grid gap-[2px] text-[10px] tabular-nums text-ink-muted"
                     style="grid-template-columns: repeat({{ $paev['slots_expected'] }}, minmax(0, 1fr))">
                    @php $samm2 = $veerandVaade ? 8 : 2; @endphp
                    @for ($i = 0; $i < $paev['slots_expected']; $i++)
                        <div class="text-center">
                            @if ($i % $samm2 === 0)
                                {{ $veerandVaade ? intdiv($i, 4) : $i }}
                            @endif
                        </div>
                    @endfor
                </div>

            </div>
        </div>

        {{-- Kaks seeriat (päev/öö) → legend on kohustuslik, mitte valikuline --}}
        <div class="mt-3 flex flex-wrap items-center justify-center gap-x-5 gap-y-1 text-xs text-ink-2">
            <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-series-1"></span> öötariif</span>
            <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-series-2"></span> päevatariif</span>
            <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-ink"></span> praegune aeg</span>
        </div>

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
