<?php

use Illuminate\Support\Facades\Schedule;

/*
 * Hinnaandmete sisselugemine.
 *
 * Sagedus on TAHTLIKULT jäme: iga 30 minuti tagant. Põhjused:
 *
 * 1) Zone'i cron pakub ainult fikseeritud valikuid (5 min / 15 min / tund /
 *    päev). Täpsetele kellaaegadele ehitatud ajastus (nt 13:55) eeldaks, et
 *    cron tabab just selle minuti — see on haprus, mida ei ole vaja.
 *    :00 ja :30 tabab nii 5- kui 15-minutiline cron.
 *
 * 2) Nord Pool avaldab homsed hinnad ~13:45. Poole tunni täpsus tähendab, et
 *    need jõuavad saidile hiljemalt 14:00 — kasutaja jaoks sama hea kui kohe.
 *
 * 3) Vana süsteem luges ÜKS kord päevas kell 14:55. Kui see käivitus
 *    ebaõnnestus, oli päev kadunud. 48 katset päevas tähendab, et üksik
 *    Eleringi 503-katkestus ei jäta auku.
 *
 * Iga käivitus täidab ka viimase 48 h augud — süsteem parandab end ise.
 */
Schedule::command('prices:fetch')
    ->everyThirtyMinutes()
    ->withoutOverlapping();

/* Öine terviklikkuse kontroll — leiab augud, mida sisselugemine ei märganud. */
Schedule::command('prices:verify')
    ->dailyAt('03:30');
