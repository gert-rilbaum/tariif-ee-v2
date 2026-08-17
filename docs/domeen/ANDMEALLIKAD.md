# Andmeallikad

## 📊 Ülevaade

Elektritarbimise optimeerija kasutab kolme peamist andmeallikat:
1. **Kasutaja tarbimisandmed** (Elektrilevi CSV)
2. **Börsihinnad** (Alexela / Nord Pool)
3. **Pakettide hinnakirjad** (Elektrimüüjad + Elektrilevi)

---

## 1. Kasutaja tarbimisandmed

### Allikas: Elektrilevi / Elering

**Kust saada:**
- Elektrilevi portaal: https://www.elektrilevi.ee
- Minu Elektrilevi → Tarbimisandmed → Ekspordi CSV

**Formaat:**
```csv
EIC;XXXXXXXXXXXXX;;;;;;;;
Seerianumber;YYYYYYYYYYYY;;;;;;;;
Periood;17.10.2024;;;;;;;;
Koostamise aeg;17.11.2025;;;;;;;;
;;Tarbimine 2-ajatsooni;...
Kokku;;21,467;...
Päeva tarbimine;;13,235;...
Öö tarbimine;;8,232;...
;;;;;;;;;
Algusaeg;Päev/öö/tipp päev/tipp puhkepäev;Tarbimine;...;Võrku andmine;...
17.10.2024 00:00;Öö;0,98;Tegelik;0,025;Tegelik;...
17.10.2024 01:00;Öö;0,883;Tegelik;0,026;Tegelik;...
...
```

**Olulised veerud:**
| Veerg | Nimi | Selgitus | Näide |
|-------|------|----------|-------|
| 1 | Algusaeg | Kuupäev + kellaaeg | 17.10.2024 00:00 |
| 2 | Päev/öö | Hinnatsooni tunnus | Öö, Päev |
| 3 | Tarbimine | Import võrgust (kWh) | 0,98 |
| 5 | Võrku andmine | Eksport võrku (kWh) | 0,025 |

**Eripärad:**
- ⚠️ **Koma eraldaja** (0,98 mitte 0.98)
- ⚠️ **UTF-8-BOM** encoding
- ⚠️ **Päiseridu** enne tunnipõhiseid andmeid
- ✅ Päev/öö juba märgitud (ei pea ise arvutama)

**Perioodid:**
- Korraga saab eksportida max ~6 kuud
- Aasta andmete jaoks: 2 faili

---

## 2. Börsihinnad

### Allikas: Alexela

**URL:** https://www.alexela.ee/et/viimase-12-kuu-kaalutud-keskmised-borsihinnad

**Formaat:**
- HTML tabel veebilehel
- Uuendatakse igakuiselt (hiljemalt 7. kuupäevaks)

**Andmed:**
```
Kuu          Päev (s/kWh)  Öö (s/kWh)  Keskmine (s/kWh)
November 2024    13.30         4.68          8.25
Detsember 2024   15.22         7.40          8.43
Jaanuar 2025     13.26         5.96          9.20
...
```

**Meie kasutamine:**
- 📥 Käsitsi kopeeritud → `data/borsihinnad_alexela.json`
- 🔄 Uuendatakse kord kuus (kui uued numbrid ilmuvad)

**JSON struktuur:**
```json
{
  "allikas": "Alexela",
  "uuendatud": "2025-11-17",
  "börsihinnad": {
    "2024-11": {
      "päev": 0.1330,
      "öö": 0.0468,
      "keskmine": 0.0825
    }
  }
}
```

---

### Alternatiivne allikas: Nord Pool (tulevikus)

**URL:** https://www.nordpoolgroup.com/  
**API:** ENTSO-E Transparency Platform

**Plussid:**
- ✅ Tasuta API
- ✅ Tunnipõhised hinnad (täpsem!)
- ✅ Automaatne uuendamine

**Miinused:**
- ⚠️ Keerulisem integreerimine
- ⚠️ Vajab API võtit
- ⚠️ Andmeid tuleb ise töödelda

**Kasutamine (v2.0):**
```python
# ENTSO-E API näide
from entsoe import EntsoePandasClient

client = EntsoePandasClient(api_key='...')
prices = client.query_day_ahead_prices(
    'EE',  # Eesti
    start=pd.Timestamp('2024-11-01', tz='Europe/Tallinn'),
    end=pd.Timestamp('2024-11-30', tz='Europe/Tallinn')
)
```

---

## 3. Pakettide hinnakirjad

### 3.1 Elektrimüüjad

**Allikad:**
- Alexela: https://www.alexela.ee/et/elekter
- Sunly: https://sunly.ee
- Elenger: https://elenger.ee
- Elektrum: https://elektrum.ee
- Enefit: https://enefit.ee

**Meie andmekogumine:**
- 📥 Käsitsi kopeeritud veebilehtedelt → `data/paketid.json`
- 🔄 Uuendatakse kord kuus või kui hinnad muutuvad

**JSON struktuur:**
```json
{
  "elektripaketid": {
    "alexela": [
      {
        "id": "alexela-borss",
        "nimi": "Börsihinnaga elekter",
        "tüüp": "börss",
        "börsimarginaal": 0.00457,
        "kuutasu": 1.99,
        "kehtib_alates": "2024-11-01"
      },
      {
        "id": "alexela-kindel-9",
        "nimi": "Tähtajaline fikseeritud 9 kuud",
        "tüüp": "fikseeritud",
        "päevahind": 0.124,
        "ööhind": 0.104,
        "kuutasu": 1.50,
        "kehtib_alates": "2024-11-01"
      }
    ]
  }
}
```

---

### 3.2 Võrguettevõte (Elektrilevi)

**Allikas:** https://www.elektrilevi.ee/et/hinnakiri

**Hinnakirja PDF-id:**
- 2024: https://www.elektrilevi.ee/.../vorguteenus_2024.pdf
- 2025: https://www.elektrilevi.ee/.../vorguteenus_2025.pdf

**Pakettid (3x25A):**
```
Võrk 1:
  Kuutasu: 0.57 €
  Päev: 7.9 s/kWh
  Öö: 7.9 s/kWh

Võrk 2:
  Kuutasu: 1.81 €
  Päev: 3.8 s/kWh
  Öö: 3.8 s/kWh

Võrk 4:
  Kuutasu: 9.17 €
  Päev: 3.69 s/kWh
  Öö: 2.1 s/kWh
```

**Märkused:**
- ⚠️ Hinnad sõltuvad **ampritest** (me kasutame 3x25A)
- ⚠️ Ei ole "piiranguid" kWh kohta
- ✅ Kuutasu on fikseeritud

---

## 📋 Andmete uuendamise graafik

| Andmeallikas | Uuendamise sagedus | Viimati uuendatud | Järgmine uuendus |
|--------------|-------------------|-------------------|------------------|
| Alexela börsihinnad | Kord kuus (7. kpv) | 17.11.2025 | 07.12.2025 |
| Alexela paketid | Kui hinnad muutuvad | 17.11.2025 | Jälgida |
| Elektrilevi võrk | 1-2x aastas | 01.01.2024 | 01.01.2026 |
| Sunly paketid | Kui hinnad muutuvad | - | - |
| Elenger paketid | Kui hinnad muutuvad | - | - |

---

## 🔄 Andmete kvaliteet

### Börsihinnad (Alexela)

**Täpsus:** ⭐⭐⭐⭐⭐ (5/5)
- Päris börsihinnad Nord Pool Estlink
- Aritmeetiline keskmine
- Kaalutud päev/öö eraldi

**Kasutamine:**
- ✅ Piisavalt täpne analüüsiks
- ⚠️ Ei arvesta tunni-põhist kõikumist
- ✅ Alexela kasutab samu numbreid oma arvetes

---

### Pakettide hinnakirjad

**Täpsus:** ⭐⭐⭐⭐ (4/5)
- Õiged hinnad müüjate veebilehtedelt
- ⚠️ Võivad muutuda ootamatult
- ⚠️ Tuleb käsitsi uuendada

**Valideerimise protsess:**
1. Võrdleme elektri-hind.ee-ga
2. Kontrollime müüja ametlikult lehelt
3. Salvestame `kehtib_alates` kuupäeva

---

### Võrguhinnad (Elektrilevi)

**Täpsus:** ⭐⭐⭐⭐⭐ (5/5)
- Ametlik hinnakirja PDF
- Muutuvad harva (1-2x aastas)
- Reguleeritud Konkurentsiameti poolt

---

## 🚨 Andmete piirangud ja riskid

### 1. Börsihinnad

**Probleem:** Alexela näitab ainult **keskmisi**

**Mõju:**
- ✅ Piisav üldiseks hinnangule
- ❌ Ei näita **tippude** mõju (nt 2023 talv: 100s/kWh)
- ❌ Ei arvesta **negatiivset** hinda (suvel 0-2 s/kWh)

**Lahendus (v2.0):**
- Kasuta Nord Pool tunnipõhiseid hindasid
- Arvuta **min, keskmine, max** iga kuu kohta
- Näita kasutajale riskistsenaariumid

---

### 2. Pakettide hinnad

**Probleem:** Hinnad võivad muutuda **keset kuud**

**Mõju:**
- ⚠️ Meie andmebaas võib olla **aegunud**
- ⚠️ Kasutaja võib saada **vale soovituse**

**Lahendus:**
- Märgi iga pakett kuupäevaga: `kehtib_alates`
- Näita hoiatust: "Kontrolli hinda müüja lehelt!"
- Tulevikus: scrape automaatselt või API

---

### 3. Võrguhinnad

**Probleem:** Sõltuvad **ampraazhist**

**Mõju:**
- ⚠️ Me eeldame **3x25A** (kõige levinum)
- ❌ Kui kasutajal on 3x16A või 3x40A, siis numbrid on valed

**Lahendus:**
- Küsi kasutajalt amprit
- Hoia andmebaasis eri amprite hindu

---

## 📚 Lisaressursid

### Ametlikud allikad:
- Elektrilevi hinnakirjad: https://www.elektrilevi.ee/et/hinnakiri
- Nord Pool börsihinnad: https://www.nordpoolgroup.com/
- Konkurentsiamet: https://www.konkurentsiamet.ee/

### Võrdluslehed:
- elektri-hind.ee: https://elektri-hind.ee/
- Energiatalgud: https://energiatalgud.ee/

### API-d:
- ENTSO-E Transparency: https://transparency.entsoe.eu/
- Elering AVP: https://www.elering.ee/avp-liitumine

---

## 🔮 Tuleviku plaanid

### Q1 2026:
- [ ] Nord Pool API integreerimine
- [ ] Automaatne pakettide uuendamine (web scraping)
- [ ] Elektrlievi API (kui saab)

### Q2 2026:
- [ ] Teiste võrgupiirkondade tugi (Imatra, Elektrum)
- [ ] Päikesepaneelide toodangu prognoos
- [ ] Ilmateenuse integratsioon (tuule/päikese prognoos)

---

## 📝 Versioonihaldus

**Andmete versioonihaldus:**
- Kõik JSON failid on Gitis
- Iga muudatus = commit
- Commit message formaat: `"Data update: Alexela börss nov 2025"`

**Näide:**
```bash
git add data/borsihinnad_alexela.json
git commit -m "Data update: Alexela börsihinnad detsember 2024"
git push
```
