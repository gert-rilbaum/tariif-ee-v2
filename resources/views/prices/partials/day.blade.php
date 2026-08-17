@php
    $onAndmeid = $paev['available'];
    $stats = $paev['stats'];
@endphp

<section class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">

    <div class="mb-4 flex flex-wrap items-baseline justify-between gap-2">
        <h2 class="text-base font-semibold">
            {{ $pealkiri }}
            <span class="ml-1 text-sm font-normal text-slate-500">
                {{ \Carbon\CarbonImmutable::parse($paev['date'])->format('d.m.Y') }}
            </span>
        </h2>

        @if ($onAndmeid)
            <p class="text-xs tabular-nums text-slate-500">
                madalaim {{ number_format($stats['min'], 2, ',', ' ') }} ·
                keskmine {{ number_format($stats['avg'], 2, ',', ' ') }} ·
                kõrgeim {{ number_format($stats['max'], 2, ',', ' ') }} senti/kWh
            </p>
        @endif
    </div>

    @if (! $onAndmeid)
        <p class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
            @if ($homme)
                Homsed hinnad avaldatakse tavaliselt kell ~14.00. Tule siis tagasi.
            @else
                Selle päeva hinnaandmed puuduvad.
            @endif
        </p>
    @else
        @if ($paev['partial'])
            <p class="mb-3 rounded-lg border border-sky-200 bg-sky-50 px-4 py-2.5 text-sm text-sky-900">
                Avaldatud on {{ count($paev['hours']) }} tundi {{ $paev['hours_expected'] }}-st.
                @if ($homme)
                    Ülejäänud tunnid avaldatakse tavaliselt kell ~14.00.
                @endif
            </p>
        @endif

        @php
            $min = $stats['min'];
            $max = $stats['max'];
            $ulatus = max($max - $min, 0.001);
            $nyyd = \Carbon\CarbonImmutable::now('Europe/Tallinn');
        @endphp

        <div class="flex h-40 items-end gap-[3px]">
            @foreach ($paev['hours'] as $tund)
                @php
                    $suhe = ($tund['total_inc_vat'] - $min) / $ulatus;
                    $korgus = 12 + $suhe * 88;
                    $onPraegu = ! $homme && $tund['hour'] === (int) $nyyd->format('G');
                    $varv = $onPraegu ? 'bg-slate-900'
                        : ($suhe < 0.25 ? 'bg-emerald-400' : ($suhe > 0.75 ? 'bg-rose-400' : 'bg-sky-300'));
                @endphp
                <div class="group relative flex flex-1 flex-col justify-end">
                    <div class="{{ $varv }} rounded-t" style="height: {{ $korgus }}%"></div>
                </div>
            @endforeach
        </div>

        <div class="mt-1 flex gap-[3px] text-[10px] text-slate-400">
            @foreach ($paev['hours'] as $tund)
                <div class="flex-1 text-center">{{ $tund['hour'] % 3 === 0 ? $tund['hour'] : '' }}</div>
            @endforeach
        </div>

        <details class="mt-4">
            <summary class="cursor-pointer text-sm text-slate-600 hover:text-slate-900">
                Näita tunnitabelit
            </summary>

            <table class="mt-3 w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="py-1.5 font-medium">Tund</th>
                        <th class="py-1.5 font-medium">Tariif</th>
                        <th class="py-1.5 text-right font-medium">Börs</th>
                        <th class="py-1.5 text-right font-medium">Kokku</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($paev['hours'] as $tund)
                        <tr class="border-b border-slate-50">
                            <td class="py-1.5 tabular-nums">{{ $tund['label'] }}</td>
                            <td class="py-1.5 text-slate-600">
                                {{ $tund['breakdown']['rate_kind'] === 'night' ? 'öö'
                                   : ($tund['breakdown']['rate_kind'] === 'day' ? 'päev' : '—') }}
                            </td>
                            <td class="py-1.5 text-right tabular-nums text-slate-600">
                                {{ number_format($tund['breakdown']['spot'], 2, ',', ' ') }}
                            </td>
                            <td class="py-1.5 text-right font-medium tabular-nums">
                                {{ number_format($tund['total_inc_vat'], 2, ',', ' ') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </details>
    @endif

</section>
