# Üleminek: test.tariif.ee → tariif.ee

Vana staatiline sait elab kaustas `htdocs/`. Uus Laravel elab kaustas `tariif/`
ja on nähtav aadressil `test.tariif.ee`. See dokument kirjeldab ümberlülitust ja
— mis tähtsam — **taganemisteed**.

---

## Eeldused, mis peavad olema täidetud ENNE

- [ ] `php artisan test` roheline (praegu 132 testi)
- [ ] `/api/v1/health` → `status: ok`
- [ ] Ajastus on **iseseisvalt** täitnud vähemalt 24 h jagu käivitusi
      (`ingestion_runs` tabelis `kind=fetch`, `status=ok`, ilma käsitsi käivitamata)
- [ ] Vähemalt üks päev, kus homsed hinnad jõudsid saidile ilma inimese abita
- [ ] Pariteedikontroll tehtud (vt allpool)

## Pariteedikontroll

Vana ja uus sait kõrvuti, sama tund, sama pakett. **Erinevused on oodatud** —
osa neist on paranduste tagajärg. Dokumenteeri iga erinevus ja põhjus:

| Kust erinevus tuleb | Kumb on õige |
|---|---|
| Võrk 1 kWh hind | **uus** — vana kasutas KM-ga numbrit 9,57 ja liitis KM peale teist korda |
| Kuutasud | **uus** — vana oli 2–3 aastat vana (Võrk 2: 5,81 € vs tegelik 6,80 €) |
| Päevatariifi lõpp | **uus** — vana kasutas kella 23, hinnakirjas on 22 |
| Riigipühad | **uus** — vana tundis ainult 2025. aasta pühi |
| Tasakaalustamisvõimsuse kulu | mõlemad 0,373, aga uues on ta õiges kohas (müüja pool) |

Kui leiad erinevuse, mida see tabel ei seleta — **PEATU**. Seletamata erinevus
tähendab, et üks pooltest arvutab valesti.

---

## Ümberlülitus

```bash
# 1. Varukoopia (kohustuslik)
ssh tariif "cd /data01/virt143084/domeenid/www.tariif.ee && \
  tar -czf ~/backups/tariif_htdocs_$(date +%Y%m%d_%H%M%S).tar.gz htdocs/"

# 2. Andmebaasi varukoopia
ssh tariif "mysqldump -h d143516.mysql.zonevs.eu -u d143516_testtariif -p'<parool>' \
  d143516_testdbtariif | gzip > ~/backups/tariif_db_$(date +%Y%m%d_%H%M%S).sql.gz"

# 3. Vana sait kõrvale, uus ette
ssh tariif "cd /data01/virt143084/domeenid/www.tariif.ee && \
  mv htdocs htdocs_vana_$(date +%Y%m%d) && ln -s tariif/public htdocs"

# 4. Kontroll
curl -fsS -o /dev/null -w '%{http_code}\n' https://tariif.ee/
curl -fsS https://tariif.ee/api/v1/health
```

## Taganemistee (alla minuti)

```bash
ssh tariif "cd /data01/virt143084/domeenid/www.tariif.ee && \
  rm htdocs && mv htdocs_vana_YYYYMMDD htdocs"
```

Vana `htdocs` jääb alles **vähemalt kuu aja**. Ära kustuta seda enne, kui uus
sait on tootmises kuu aega ilma probleemideta töötanud.

---

## Vana Node-cron'i väljalülitamine

**Alles pärast ümberlülitust ja veel 24 h jälgimist.**

Zone paneelis on cron-rida:
```
cd [[$ACC_HOMEDIR_A]]/tariif-api && /usr/bin/node src/scripts/import_elering_daily.js
```

Kuni ta töötab, on ta kahjutu turvavõrk: sisselugemine on idempotentne ja
Eleringist loetud read on ülimuslikud. Aga kaks kirjutajat ühte tabelisse on
tarbetu risk, seega lülita ta välja, kui uus on end tõestanud.

Skriptid on hoiul `docs/domeen/legacy-node/` (paroolid eemaldatud) — nad ei
olnud üheski git-repos ja oleksid muidu kadunud.

Samuti võib eemaldada PM2 all seisva surnud protsessi:
```bash
ssh tariif "pm2 delete tariif-api && pm2 save"
```

---

## Pärast ümberlülitust

- [ ] 48 h jälgi `ingestion_runs` ja `/api/v1/health`
- [ ] Kontrolli, et `scheduler_last_run` liigub
- [ ] Vaata veebiserveri logi: `logs/apache.ssl.access.log` — 5xx ei tohi tulla
- [ ] Uuenda `.env`: `APP_URL=https://tariif.ee`, `APP_ENV=production`,
      `APP_DEBUG=false`
- [ ] Otsusta, mida teha `test.tariif.ee`-ga: kas jääb eraldi testkeskkonnaks
      (vajab oma andmebaasi) või suunatakse tariif.ee peale

## Mida ÜLEMINEK EI lahenda

Vt `BACKLOG.md` — müüja kuutasu puudub püsikulust, mikrotootmine ei ole
modelleeritud, Võrk 5 on seemendamata. Need on teadlikud, dokumenteeritud augud,
mitte üllatused.
