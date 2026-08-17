<?php

use Illuminate\Support\Facades\Schedule;

/*
 * Hinnaandmete sisselugemine.
 *
 * Vana süsteem luges üks kord päevas kell 14:55. Kui see käivitus ebaõnnestus,
 * oli päev kadunud. Siin loeme tunnis ja lisaks tihedamalt siis, kui Nord Pool
 * homsed hinnad avaldab (~13:45 Eesti aja järgi).
 */
Schedule::command('prices:fetch')->hourlyAt(5)->withoutOverlapping();
Schedule::command('prices:fetch')->dailyAt('13:55')->withoutOverlapping();
Schedule::command('prices:fetch')->dailyAt('14:25')->withoutOverlapping();
Schedule::command('prices:fetch')->dailyAt('15:05')->withoutOverlapping();

/* Öine terviklikkuse kontroll — leiab augud, mida tunnitöö ei märganud. */
Schedule::command('prices:verify')->dailyAt('03:15');
