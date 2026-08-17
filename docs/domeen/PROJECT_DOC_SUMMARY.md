# Projekti dokumentatsiooni kokkuvõte

**Eesmärk:** Anda mentorile kiire ülevaade kõigist projekti dokumentidest
**Kuupäev:** 17.11.2025
**Projekt:** Elektritarbimise Optimeerija (Elektriportaal)

---

## 📋 Dokumentide nimekiri

| # | Fail | Tüüp | Kellele |
|---|------|------|---------|
| 1 | README.md | Projekti tutvustus | Kõik |
| 2 | PROJEKT_SUMMARY.md | Projekti kokkuvõte | Mentor, PM |
| 3 | ARHITEKTUUR.md | Tehniline arhitektuur | Arendaja, Mentor |
| 4 | MVP_PLAAN.md | Arendusplaan | Arendaja, PM |
| 5 | ANDMEALLIKAD.md | Andmeallikad | Arendaja, Mentor |
| 6 | TERMINOLOOGIA.md | Sõnastik | Kõik |
| 7 | MUUJATE_INFO.md | Turuülevaade | Mentor, Analüütik |
| 8 | NFR_ELEKTRIPORTAAL.md | Mittefunktsionaalsed nõuded | Arendaja, Mentor |
| 9 | MENTORI_MÄRKMED.md | Arenguajalugu | Mentor, CC |
| 10 | CLAUDE_PROMPT.md | CC sessioonide juhend | CC (Claude Code) |
| 11 | data/README.md | Andmete kirjeldus | Arendaja |

---

## 📄 Faili kokkuvõtted

### 1. README.md (Projekti tutvustus)

**Asukoht:** `/README.md`
**Eesmärk:** Projekti põhiline tutvustus ja kiire alustamine

**Peamised teemad:**
- Projekti eesmärk: personaliseeritud elektripakettide võrdlus CSV põhjal
- Erinevus konkurentidest (elektri-hind.ee): päris andmed, mitte teoreetilised
- Projekti staatus: MVP arenduses, versioon 0.1
- Tehnoloogiapinu: Python, Streamlit/Flask (tulevikus)
- Kiire alustamine: installeerimine ja käivitamine

**Kellele:** Kõik, kes tulevad projekti esimest korda juurde

**Märksõnad:** `projekti ülevaade`, `eesmärk`, `erinevus`, `MVP`

---

### 2. PROJEKT_SUMMARY.md (Projekti kokkuvõte)

**Asukoht:** `/PROJEKT_SUMMARY.md`
**Eesmärk:** Detailne projekti kokkuvõte - ärimudel, ajakava, eesmärgid

**Peamised teemad:**
- Probleem ja lahendus: 50-200€ aastas säästu targema paketiivalikuga
- MVP eesmärgid: 5 funktsiooni (CSV, võrgupaketid, elektripaketid, soovitus)
- Projekti struktuur: kaustad, failid, moodulid
- Ärimudel (tulevikus): tasuta MVP, premium 9.99€
- Ajakava: MVP detsember 2025, v1.0 märts 2026
- Õppetunnid: võrgupakett on oluline, mikrotootjad, psühholoogia vs matemaatika
- Riskid: CSV formaat, börsihinnad, turundus

**Kellele:** Mentor, projektijuht, investor (tulevikus)

**Märksõnad:** `ärimudel`, `ajakava`, `eesmärgid`, `edu kriteeriumid`

---

### 3. ARHITEKTUUR.md (Süsteemi arhitektuur)

**Asukoht:** `/docs/ARHITEKTUUR.md`
**Eesmärk:** Tehnilise arhitektuuri kirjeldus ja komponentide ülevaade

**Peamised teemad:**
- Komponentide ülevaade: kasutajaliides → analüüsimootor → andmebaas (JSON)
- Moodulite kirjeldus:
  - csv_loader.py: Elektrilevi CSV parseerimine (UTF-8-BOM, koma→punkt)
  - analyzer.py: kuupõhine grupeerimine, profiili tuvastus (mikrotootja, öötarbija)
  - calculator.py: hinnakalkulatsioon (võrk, börss, fiks)
  - comparator.py: pakettide võrdlus ja soovitused
- Andmevoog: CSV → tunnid → kuud → arvutused → soovitus
- Andmestruktuurid: tunnipõhine, kuupõhine, pakett
- Turvalisus: CSV ei salvestata, kõik in-memory
- Skaleeritavus: MVP (lokaalne) → Streamlit Cloud → Flask/Next.js → SaaS

**Kellele:** Arendaja (koodija), tehniline mentor

**Märksõnad:** `arhitektuur`, `moodulid`, `andmevoog`, `turvalisus`

---

### 4. MVP_PLAAN.md (MVP arendusplaan)

**Asukoht:** `/docs/MVP_PLAAN.md`
**Eesmärk:** Detailne tegevusplaan MVP loomiseks 1-2 nädala jooksul

**Peamised teemad:**
- MVP definitsioon: 6 põhifunktsiooni, mida PEAB oskama
- Välistused: graafikud, riskianalüüs, kasutajakontod, PDF (tulevikus)
- Ajakava:
  - Päev 1-2: koodi refaktoreerimine moodulitesse
  - Päev 3: testimine
  - Päev 4-5: Streamlit UI
  - Päev 6-7: poleerimime
  - Päev 8: deploy Streamlit Cloud
  - Päev 9-14: tagasiside ja parandused
- Valmiduse kriteeriumid: CSV töötab, numbrid klapivad (±5%), 3 testikasutajat
- Tehnilised ülesanded: moodulite struktuur, Streamlit UI kood, JSON struktuur
- Testimine: unit testid, integratsioonitestid, käsitsi testimine
- Deployment: Streamlit Cloud (tasuta) vs lokaalne

**Kellele:** Arendaja (teostaja), projektijuht

**Märksõnad:** `MVP`, `ajakava`, `tegevusplaan`, `testimine`, `deploy`

---

### 5. ANDMEALLIKAD.md (Andmeallikate kirjeldus)

**Asukoht:** `/docs/ANDMEALLIKAD.md`
**Eesmärk:** Kirjeldada kõiki andmeallikaid ja nende uuendamise protseduuri

**Peamised teemad:**
- 3 peamist andmeallikat:
  1. Kasutaja CSV (Elektrilevi) - formaat, eripärad (koma, UTF-8-BOM, päiserida)
  2. Börsihinnad (Alexela) - kuupõhised keskmised, uuendamine kord kuus
  3. Pakettide hinnakirjad (elektrimüüjad, Elektrilevi) - käsitsi uuendamine
- Nord Pool API tulevikus (tunnipõhised hinnad, ENTSO-E)
- Elektrimüüjad: Alexela, Sunly, Elenger, Elektrum, Enefit
- Võrguettevõte: Elektrilevi (3x25A hinnad)
- Andmete uuendamise graafik: börsihinnad (7. kpv), paketid (kui muutuvad)
- Andmete kvaliteet: börsihinnad 5/5, paketid 4/5, võrguhinnad 5/5
- Riskid ja piirangud:
  - Börsihinnad: ainult keskmised, mitte tippude mõju
  - Paketid: võivad muutuda keset kuud
  - Võrguhinnad: sõltuvad amprist (3x25A eeldus)
- Tuleviku plaanid: Nord Pool API, automaatne scraping

**Kellele:** Arendaja (andmete haldaja), mentor (kvaliteedi kontroll)

**Märksõnad:** `andmed`, `allikad`, `uuendamine`, `kvaliteet`, `riskid`

---

### 6. TERMINOLOOGIA.md (Sõnastik ja mõisted)

**Asukoht:** `/docs/TERMINOLOOGIA.md`
**Eesmärk:** Selgitada projekti terminoloogiat ja elektrituru mõisteid

**Peamised teemad:**
- Elektrituru osapooled: elektrimüüja, võrguettevõte, süsteemihaldur, Nord Pool
- Elektripakettide tüübid:
  1. Börsipakett: börs + marginaal + kuutasu (paindlik, riskantne)
  2. Fikseeritud: kindel hind, tähtajaline (ettearvamatu, kallim)
  3. Hübriidpakett: saab valida börsi/fiksi igakuiselt
  4. Marginaalivaba: Sunly 0-marginaal (kõrgem kuutasu)
- Võrgupakettide tüübid: Võrk 1/2/4/5 (kuutasu vs kWh hind)
- Ajavööndid: päev (07:00-21:59), öö (22:00-06:59), tipp (Võrk 5)
- Hinna komponendid: elekter + taastuvenergiatasu + KM 22%→24%
- Eripärad:
  - Mikrotootja: päikesepaneelid, eksport > 0, võrgutasu arvutatakse impordilt
  - Soojuspumbaga: talv >> suvi, öötarbimine 77%+
  - Elektriautoga: öötarbimine, tark laadimine
- Arvutuste näited: võrgutasu, börsipakett, fikseeritud pakett
- Ühikud: kWh, s/kWh, €/MWh konversioonid
- Lühendid: EIC, AVP, KM, ÜT
- Kasulikud valemid: murdepunkt Võrk 2 vs 4 (433 kWh)
- KKK: kuutasu, ööhind, võrgupaketi vahetamine

**Kellele:** Kõik (eriti uued arendajad, mentor, kasutajad)

**Märksõnad:** `terminoloogia`, `mõisted`, `börss`, `võrk`, `arvutused`

---

### 7. MUUJATE_INFO.md (Elektrimüüjate turuülevaade)

**Asukoht:** `/docs/MUUJATE_INFO.md`
**Eesmärk:** Koondinfo Eesti elektrimüüjate pakettide kohta

**Peamised teemad:**
- Peamised elektrimüüjad Eestis (2024/2025):
  1. Alexela (15-20% turuosa) - börss, fiks, hübriid, virtuaalaku
  2. Elenger (10-15%) - börss, fiks, rahvusvaheline
  3. Elektrum (20-25%) - Läti riigifirma, kindlustusega paketid
  4. Enefit (30-40%) - riigile kuuluv, suurim, universaalteenus
  5. Sunly (2-5%) - 0-marginaal, roheline, mikrotootjatele
  6. Viru Elektrivõrgud (3%) - Ida-Virumaa
  7. eTerminal (1-2%) - roheline, elektriautod
- Pakettide tüüpide võrdlus: tabel börss/fiks/hübriid/virtuaalaku
- Hinnavahemikud:
  - Börsipaketid: marginaal 0-0.67s, kuutasu 0-6€
  - Fikseeritud 9k: päev 11-13s, öö 9-10s
- Soovitused eri kasutajatele:
  - Mikrotootja → Sunly 0-marginaal
  - Soojuspumbaga → Alexela/Elektrum kindel talvel (madal ööhind)
  - Tavaline pere → börsipakett
  - Elektriautoga → börss + tark laadija
- Mida jälgida: marginaal vs kuutasu (sõltub tarbimisest), katkestamistasu
- Hindade ajalugu: 2023 kriis (35s jaanuar), 2024/2025 normaliseerumine
- Tuleviku trendid: 15-min hinnad (okt 2025), taastuvenergia kasv, universaalteenuse lõpp
- Parimad strateegiad 2025:
  - Konservatiivne: talv fiks, suvi börss
  - Agressiivne: kogu aasta börss
  - Mikrotootja: Sunly 0-marginaal

**Kellele:** Mentor (analüüs), turundusspetsialist, strateegia planeerija

**Märksõnad:** `elektrimüüjad`, `paketid`, `turuülevaade`, `soovitused`, `trendid`

---

### 8. NFR_ELEKTRIPORTAAL.md (Mittefunktsionaalsed nõuded)

**Asukoht:** `/docs/NFR_ELEKTRIPORTAAL.md`
**Eesmärk:** Defineerida projekti mittefunktsionaalsed arhitektuursed põhimõtted

**Peamised teemad:**
- 3 põhiprintsiipi:
  1. **Stateless arvutusmootor:**
     - Iga analüüs on eraldiseisev (ei sõltu varasematest)
     - Sisend (CSV, paketid) → väljund (tulemus)
     - Ei hoia mälus sessioone, vahetulemusi
     - Eelised: lihtne skaleerida, testida, vähem vigu, paralleelne töötlus
  2. **Isikuandmete mitte-salvestamine:**
     - CSV → analüüs → tulemus → kustuta
     - Ei kogu isikuandmeid (nimi, e-post, aadress)
     - Erandid: opt-in (e-posti teavitused, premium salvestused)
     - GDPR: andmete minimeerimine
  3. **Üks "tõde" hindade kohta:**
     - JSON failid = autoriteetne allikas (paketid.json, borsihinnad.json)
     - Hinnamuutus = JSON uuendamine (ei muuda koodi)
     - Järjepidevus, auditeeritavus, lihtsus, usaldusväärsus
     - Uuendamise protsess: JSON muutmine → Git commit → deploy
- Kokkuvõte: skaleeruv, privaatne, usaldusväärne

**Kellele:** Arendaja (arhitekt), mentor, tehnilise juht

**Märksõnad:** `NFR`, `arhitektuur`, `stateless`, `GDPR`, `JSON`

---

### 9. MENTORI_MÄRKMED.md (Arenguajalugu)

**Asukoht:** `/docs/MENTORI_MÄRKMED.md`
**Eesmärk:** Jälgida projekti ajalugu ja mentori juhiseid

**Peamised teemad:**
- Reeglid: CC ei kustuta varasemaid märkmeid, lisab lõppu kuupäevaga
- Ajalugu:
  - 17.11.2025: Repo algseis
    - Dokumentatsioon valmis
    - JSON andmefailid olemas
    - src/ ja tests/ tühjad (kood tuleb)
    - GitHub: https://github.com/gert-rilbaum/Elektriportaal

**Kellele:** Mentor, CC (Claude Code), tulevased sessioonid

**Märksõnad:** `ajalugu`, `juhised`, `staatus`, `märkmed`

---

### 10. CLAUDE_PROMPT.md (CC sessioonide juhend)

**Asukoht:** `/CLAUDE_PROMPT.md`
**Eesmärk:** Anda CC-le kontekst tulevaste sessioonide jaoks

**Peamised teemad:**
- Projekti kontekst: elektritarbimise optimeerimine, CSV analüüs
- Kus failid asuvad: Windows ja WSL teed
- GitHub: https://github.com/gert-rilbaum/Elektriportaal
- Git config: kasutaja ja e-post
- Projekti struktuur: data/, docs/, src/, tests/
- OLULINE: Loe KÕIGEPEALT docs/MENTORI_MÄRKMED.md
- Projekti staatus: dokumentatsioon valmis, kood tuleb
- Workflow:
  1. Kontrolli asukohta
  2. Loe MENTORI_MÄRKMED.md
  3. Kirjuta kood src/ kausta
  4. Commit ja push
  5. Uuenda MENTORI_MÄRKMED.md
- Kasulikud käsud: tree, find, git log, pytest
- Probleemide lahendamine: SSH, töötav kaust, requirements.txt
- Viited: Nord Pool, Elektrilevi, Alexela, elektri-hind.ee
- Kiire start: kopeeritav plokk uutele sessioonidele

**Kellele:** CC (Claude Code), tulevased AI sessioonid

**Märksõnad:** `CC prompt`, `kontekst`, `workflow`, `Git`, `juhised`

---

### 11. data/README.md (Andmete kirjeldus)

**Asukoht:** `/data/README.md`
**Eesmärk:** Kirjeldada data/ kausta sisu ja uuendamise protseduuri

**Peamised teemad:**
- Failid:
  1. paketid.json: elektri- ja võrgupakettide hinnakirjad (Alexela, Sunly, Elenger, Elektrilevi)
  2. borsihinnad.json: Nord Pool börsihinnad (kuupõhised keskmised, Alexela allikas)
- Uuendamine:
  - Börsihinnad: kord kuus (7. kpv) - Alexela lehelt, uuenda JSON, commit
  - Paketid: kui hinnad muutuvad - kontrolli müüja lehte, uuenda JSON, commit
- Hoiatused:
  - ÄRA lisa kasutajate CSV-sid (privaatsus!)
  - ÄRA kanna vale infot (kontrolli allikast)
  - KONTROLLI JSON kehtivust
- Andmete kvaliteet: börsihinnad 5/5, elektripaketid 4/5, võrgupaketid 5/5
- Tulevikus: Nord Pool API, automaatne scraping, PostgreSQL, versioonihaldus

**Kellele:** Arendaja (andmehaldur), mentor (kvaliteedi kontroll)

**Märksõnad:** `data`, `JSON`, `uuendamine`, `kvaliteet`, `hoiatused`

---

## 🔍 Kiire otsing teemal

Kui vajad infot konkreetse teema kohta, vaata siit:

| Teema | Fail(id) |
|-------|----------|
| **Projekti ülevaade** | README.md, PROJEKT_SUMMARY.md |
| **Tehniline arhitektuur** | ARHITEKTUUR.md, NFR_ELEKTRIPORTAAL.md |
| **Arendusplaan** | MVP_PLAAN.md |
| **Andmed ja allikad** | ANDMEALLIKAD.md, data/README.md |
| **Terminid ja mõisted** | TERMINOLOOGIA.md |
| **Turuanalüüs** | MUUJATE_INFO.md |
| **Ajalugu ja staatus** | MENTORI_MÄRKMED.md |
| **CC sessioonid** | CLAUDE_PROMPT.md |
| **JSON struktuur** | ARHITEKTUUR.md, data/README.md |
| **Arvutused ja valemid** | TERMINOLOOGIA.md, ARHITEKTUUR.md |
| **Pakettide hinnad** | MUUJATE_INFO.md, ANDMEALLIKAD.md |
| **CSV formaat** | ANDMEALLIKAD.md, ARHITEKTUUR.md |
| **Testimine** | MVP_PLAAN.md, ARHITEKTUUR.md |
| **Deployment** | MVP_PLAAN.md, ARHITEKTUUR.md |
| **Turvalisus/GDPR** | NFR_ELEKTRIPORTAAL.md, ARHITEKTUUR.md |

---

## 📊 Dokumentatsiooni statistika

| Mõõdik | Väärtus |
|--------|---------|
| Dokumente kokku | 11 |
| Märksõnu kokku | ~88 |
| Lehekülgi (A4) | ~75-80 (hinnanguline) |
| Viimati uuendatud | 17.11.2025 |
| Kehtiv versioon | 0.1-dev |

---

## ✅ Dokumentatsiooni terviklikkuse kontroll

**Kas dokumentatsioon katab kõik olulised aspektid?**

- [x] Projekti eesmärk ja visioon → README.md, PROJEKT_SUMMARY.md
- [x] Tehniline arhitektuur → ARHITEKTUUR.md, NFR_ELEKTRIPORTAAL.md
- [x] Arendusplaan ja ajakava → MVP_PLAAN.md, PROJEKT_SUMMARY.md
- [x] Andmeallikad ja kvaliteet → ANDMEALLIKAD.md, data/README.md
- [x] Terminoloogia ja mõisted → TERMINOLOOGIA.md
- [x] Turuülevaade ja konkurendid → MUUJATE_INFO.md
- [x] Ajalugu ja staatus → MENTORI_MÄRKMED.md
- [x] AI sessioonide juhend → CLAUDE_PROMPT.md
- [ ] Kasutusjuhend lõppkasutajale → PUUDUB (tulevikus)
- [ ] API dokumentatsioon → PUUDUB (v2.0)

---

## 🚀 Järgmised sammud

**Dokumentatsiooni osas:**
1. [ ] Loo kasutusjuhend lõppkasutajale (kui UI valmis)
2. [ ] Lisa skriptide/koodiblokkide näited
3. [ ] Lisa ekraanipildid (kui UI valmis)
4. [ ] Loo API dokumentatsioon (kui Flask/FastAPI valmis)

**Projekti osas:**
1. [ ] Kirjuta kood (`src/` kaust - praegu tühi!)
2. [ ] Tee Streamlit UI
3. [ ] Testimine
4. [ ] Deploy

---

**Viimati uuendatud:** 17.11.2025
**Vastutaja:** Gert Roots (RILBAUM-IT OÜ)
**Fail:** `docs/PROJECT_DOC_SUMMARY.md`
**Versioon:** 1.0

---

## 📞 Kontakt

**Küsimused dokumentatsiooni kohta?**
Email: gert@rilbaum.ee
GitHub: https://github.com/gert-rilbaum/Elektriportaal
