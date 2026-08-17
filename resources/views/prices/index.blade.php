<!DOCTYPE html>
<html lang="et" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Elektri tegelik hind Eestis | tariif.ee</title>
    <meta name="description" content="Elektri tegelik lõpphind koos võrgutasu, riiklike tasude ja käibemaksuga. Era- ja väiketarbijale.">
    @vite(['resources/css/app.css'])
</head>
<body class="h-full bg-slate-50 text-slate-900 antialiased">

<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 sm:py-12">

    <header class="mb-8">
        <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">Elektri tegelik hind</h1>
        <p class="mt-1 text-sm text-slate-600">
            Börsihind koos võrgutasu, riiklike tasude ja käibemaksuga — see, mis arvele jõuab.
        </p>
    </header>

    @include('prices.partials.freshness', ['varskus' => $varskus])

    @include('prices.partials.current', [
        'praegu' => $praegu,
        'kmGa' => $kmGa,
        'pysikulu' => $pysikulu,
        'valitud' => $valitud,
    ])

    @include('prices.partials.settings', [
        'paketid' => $paketid,
        'valitud' => $valitud,
        'kmGa' => $kmGa,
        'leping' => $leping,
    ])

    @include('prices.partials.day', [
        'paev' => $tana,
        'pealkiri' => 'Täna',
        'homme' => false,
        'kmGa' => $kmGa,
    ])

    @include('prices.partials.day', [
        'paev' => $homme,
        'pealkiri' => 'Homme',
        'homme' => true,
        'kmGa' => $kmGa,
    ])

    @include('prices.partials.sources')

</div>

</body>
</html>
