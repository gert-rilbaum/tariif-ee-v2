@if ($varskus['uuendatud'] === null)
    <div class="mb-4 flex items-start gap-2.5 rounded-xl border border-hairline bg-surface p-4 text-sm shadow-sm">
        <x-icon name="alert" class="mt-0.5 size-4 shrink-0 text-state-warning"/>
        <div>
            <p class="font-semibold">Hinnaandmed pole hetkel saadaval.</p>
            <p class="text-ink-2">Tegeleme sellega — proovi mõne minuti pärast uuesti.</p>
        </div>
    </div>
@elseif ($varskus['aegunud'])
    <div class="mb-4 flex items-start gap-2.5 rounded-xl border border-hairline bg-surface p-4 text-sm shadow-sm">
        <x-icon name="alert" class="mt-0.5 size-4 shrink-0 text-state-warning"/>
        <div>
            <p class="font-semibold">Hinnaandmed on {{ $varskus['vanus_tunnid'] }} tundi vana.</p>
            <p class="text-ink-2">
                Viimane uuendus {{ $varskus['uuendatud']->format('d.m.Y H:i') }}.
                Näitame viimast teadaolevat hinda.
            </p>
        </div>
    </div>
@endif
