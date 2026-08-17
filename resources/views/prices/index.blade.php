<!DOCTYPE html>
<html lang="et" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Elektri tegelik hind Eestis | tariif.ee</title>
    <meta name="description" content="Elektri tegelik lõpphind koos võrgutasu, riiklike tasude ja käibemaksuga. Era- ja väiketarbijale.">
    <meta name="color-scheme" content="light dark">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-full bg-plane text-ink antialiased">

<header class="border-b border-hairline bg-surface">
    <div class="mx-auto flex max-w-5xl items-center gap-2 px-4 py-3 sm:px-6">
        <span class="flex size-7 items-center justify-center rounded-lg bg-series-1 text-white">
            <x-icon name="bolt" class="size-4"/>
        </span>
        <span class="font-semibold tracking-tight">tariif<span class="text-ink-muted">.ee</span></span>
        <span class="ml-auto flex items-center gap-1.5 text-xs text-ink-muted">
            <x-icon name="clock" class="size-3.5"/>
            @if ($varskus['uuendatud'])
                Andmed uuenes {{ $varskus['uuendatud']->format('H:i') }}
            @else
                Andmed puuduvad
            @endif
        </span>
    </div>
</header>

<main class="mx-auto max-w-5xl px-4 py-6 sm:px-6 sm:py-10">

    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">Elektri tegelik hind</h1>
        <p class="mt-1 max-w-2xl text-sm text-ink-2">
            Börsihind koos võrgutasu, riiklike tasude ja käibemaksuga — see, mis arvele jõuab.
        </p>
    </div>

    @include('prices.partials.freshness')

    @include('prices.partials.current')

    @include('prices.partials.settings')

    @include('prices.partials.chart')

    @include('prices.partials.sources')

</main>

</body>
</html>
