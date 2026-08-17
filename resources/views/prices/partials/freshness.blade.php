@php
    $vanus = $varskus['vanus_tunnid'];
@endphp

@if ($varskus['uuendatud'] === null)
    <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <strong class="font-semibold">Hinnaandmed pole hetkel saadaval.</strong>
        Tegeleme sellega — proovi mõne minuti pärast uuesti.
    </div>
@elseif ($varskus['aegunud'])
    <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <strong class="font-semibold">Hinnaandmed on {{ $vanus }} tundi vana.</strong>
        Viimane uuendus {{ $varskus['uuendatud']->format('d.m.Y H:i') }}.
        Näitame viimast teadaolevat hinda.
    </div>
@else
    <p class="mb-6 text-xs text-slate-500">
        Andmed uuenes {{ $varskus['uuendatud']->format('H:i') }} · allikas Elering
    </p>
@endif
