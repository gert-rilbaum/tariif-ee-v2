<?php

use Illuminate\Support\Facades\Schedule;

/*
 * Hinnaandmete sisselugemine.
 *
 * Ajad on valitud nii, et need langeksid kokku Zone'i paneelis seadistatud
 * cron-minutitega (00 + kõik 5-ga lõppevad). Seetõttu :05 ja :35, mitte
 * ümmargune :00/:30 — muidu jookseks pool ajastusest tühja.
 *
 * Kaks korda tunnis, sest:
 *  - Nord Pool avaldab homsed hinnad ~13.45; poole tunni täpsus tähendab, et
 *    need on saidil hiljemalt 14.05
 *  - vana süsteem luges ÜKS kord päevas ja üksik tõrge tähendas kadunud päeva;
 *    48 katset päevas teeb Eleringi lühikesed 503-katkestused tähtsusetuks
 *
 * Iga käivitus täidab ka viimase 48 h augud — süsteem parandab end ise.
 */
Schedule::command('prices:fetch')
    ->hourlyAt([5, 35])
    ->withoutOverlapping();

/*
 * Südamelöök: märgib iga käivituse. Ilma selleta ei ole võimalik vahet teha,
 * kas andmed on vanad sellepärast, et Elering vaikib, või sellepärast, et cron
 * ei jookse üldse. Vana süsteem suri just nii — vaikselt.
 */
Schedule::call(fn () => cache()->forever('scheduler_last_run', now()->toIso8601String()))
    ->everyMinute()
    ->name('scheduler-heartbeat');

/* Öine terviklikkuse kontroll — leiab augud, mida sisselugemine ei märganud. */
Schedule::command('prices:verify')
    ->dailyAt('03:15');
