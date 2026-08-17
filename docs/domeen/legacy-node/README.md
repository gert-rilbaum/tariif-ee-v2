# Vana Node-sisselugemine — hoiukoopia

**Miks see siin on:** need skriptid on ainsad, mis 18.08.2026 seisuga hinnaandmeid
tegelikult sisse loevad — ja need ei olnud üheski git-repos. Elasid ainult kaustas
`/data01/virt143084/tariif-api/` Zone serveris, varukoopiata.
GitHubi repo `gert-rilbaum/tariif-api` sisaldab **ainult autentimissüsteemi**,
mitte ühtegi impordiskripti.

**Paroolid on eemaldatud** (`process.env.DB_PASSWORD`). Originaalides olid nad
avatekstis.

## Mis mida teeb

| Fail | Roll |
|---|---|
| `scripts/import_elering_daily.js` | **Elav toide.** Cron kell 14:55 → baas `d143516_testdbtariif`. Tõmbab viimased 3 päeva + homse, kontrollib enne API-kutset, kas andmed on olemas |
| `scripts/import_elering_2years.js` | Ühekordne ajaloo import |
| `scripts/import_elering_prices.js` | Varasem impordi variant |
| `database.js` | mysql2 ühenduse pool |
| `sync_tomorrow_full.js`, `fix_today.js`, `delete_extra.js` | Käsitsi parandusskriptid jaanuarist, kuupäevad kõvakodeeritud |

## Mida siit õppida

1. **Ajavööndi viga.** `sync_tomorrow_full.js` sisaldab autori enda kommentaari
   `// Aga see on vale!` päevapiiri arvutuse kohta. Fikseeritud `22:00 UTC` piir
   vastab kohalikule keskööle ainult talvel (EET). Suvel (EEST) algab selle
   "päev" kell 01:00. Uues lahenduses ei kodeerita nihet kunagi.
2. **Üks kord päevas ei ole vastupidav.** Kui 14:55 käivitus ebaõnnestub, on see
   päev kadunud kuni järgmise käivituseni. Uus lahendus loeb tunnis + täidab augud.
3. **Idempotentsus töötas.** 46 408 rida, duplikaate 0 — skript kontrollib
   olemasolu enne kirjutamist. See osa on hea ja kordub uues lahenduses.

## Millal see kustutada

Alles siis, kui Laraveli `prices:fetch` on tootmises tõestanud, et täidab baasi
iseseisvalt (vt plaani Task 11b). Enne seda **ei tohi** vana cron'i välja
lülitada — muidu tekib andmeauk.
