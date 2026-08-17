# Rollide jaotus - Elektriportaali projekti

**Kuupäev:** 17.11.2025
**Versioon:** 1.0

---

## 🎯 Ülevaade

Projektil on kolm põhilist osapoolt:
1. **Gert Roots** - Projektijuht ja omanik
2. **ChatGPT** - Mentor ja strateegiline nõustaja
3. **Claude Code (CC)** - Tehniline arendaja ja täitja

---

## 👥 Rollide kirjeldused

### 1. Gert Roots (Projektijuht)

**Vastutus:**
- Projekti visioon ja ärilised eesmärgid
- Prioriteetide seadmine
- Ajakava ja milestone'id
- Lõppkasutaja perspektiiv
- Kvaliteedi kontrolli ja testimine
- Otsuste tegemine (millal ChatGPT soovitab mitu varianti)

**Töövahendid:**
- ChatGPT (planeerimine, nõuanded)
- Claude Code (kodeerimine, deployment)
- GitHub (koodi ülevaatus)
- Браузer (testimine)

**Ei pea:**
- Kirjutama koodi
- Käivitama Git käske
- Debugima
- Deploy'ma

---

### 2. ChatGPT (Mentor ja Strateegiline Nõustaja)

**Vastutus:**
- Arhitektuursed soovitused
- Tehnoloogia valikud (Streamlit vs Flask, PostgreSQL vs JSON, jne)
- Arendusplaanide koostamine (MVP, v1.0, v2.0)
- Probleemide analüüs ja lahendusstrateegiad
- Parimate praktikate soovitamine
- Dokumentatsiooni struktuuri nõuanded
- Konkurentide analüüs
- Turundusstrateegiad (tulevikus)

**Töövahendid:**
- Web browsing (turu-uuring)
- Analüüs ja võrdlused
- Planeerimise raamistikud

**Ei tee:**
- Ei kirjuta koodi otse projektile
- Ei tee Git operatsioone
- Ei deploy'i
- Annab ainult **juhiseid ja plaane**

**Väljund:**
- Plaanid (markdown)
- Arhitektuuridiagrammid (tekst)
- Soovitused ja analüüsid
- Samm-sammult juhised

---

### 3. Claude Code / CC (Tehniline Arendaja)

**Vastutus:**
- Koodi kirjutamine (Python, JavaScript, jne)
- Testimine (unit testid, integratsioonitestid)
- Git operatsioonid (commit, push, pull)
- GitHub haldamine (repo, branches)
- Deployment (Streamlit Cloud, VPS, jne)
- Debugging ja veaparandused
- Refaktoreerimine
- Dokumentatsiooni uuendamine (tehniline osa)
- Andmete uuendamine (JSON failid)

**Töövahendid:**
- Python, Streamlit, FastAPI (kood)
- Git/GitHub (versioonihaldus)
- WSL/Linux käsud
- Read, Write, Edit, Bash tools

**Ei tee:**
- Ei tee strateegilisi otsuseid (küsib Gerdilt)
- Ei muuda arhitektuuri ilma kinnituseta
- Ei deploy'i productioni ilma testimiseta

**Väljund:**
- Töötav kood
- Commitid GitHubis
- Deployed rakendused
- Tehnilised raportid (mida tehti, mida testiti)

---

## 🔄 Töövoogude näited

### **Stsenaarium 1: Uus funktsionaalsus**

**Näide:** Lisa PDF ekspordi võimalus

```
1️⃣ Gert → ChatGPT:
   "Tahan lisada PDF ekspordi. Kuidas teha?"

2️⃣ ChatGPT → Gert:
   "Soovitan kasutada ReportLab või WeasyPrint.
    Samm-sammult plaan:
    1. Installi reportlab
    2. Loo moodul src/pdf_generator.py
    3. Lisa export nupp UI-sse
    4. Testi 3 erineva CSV-ga

    Arhitektuur:
    - Funktsioon generate_pdf(analysis_result)
    - Template failid templates/report.html
    - CSS stiilid static/report.css"

3️⃣ Gert → Claude Code:
   "CC, ChatGPT soovitas lisada PDF ekspordi.
    Vaata tema plaani ja teosta see.
    Kasuta ReportLab teeki."

4️⃣ CC → Gert:
   ✅ Kood kirjutatud
   ✅ Testitud
   ✅ Commit: "Lisa PDF ekspordi funktsionaalsus"
   ✅ Pushed GitHubisse

   "Valmis! Testi seda URL: http://localhost:8501
    PDF nupp ilmub tulemuste lehel."

5️⃣ Gert:
   Testib, annab tagasisidet
```

---

### **Stsenaarium 2: Tehniline probleem**

**Näide:** CSV laadimine ei tööta UTF-8-BOM failidega

```
1️⃣ Gert → CC:
   "CSV laadimine krahhib, kui avan Elektrilevi faili"

2️⃣ CC:
   - Debug: kontrollib viga
   - Leiab: UTF-8-BOM encoding probleem
   - Parandab koodi
   - Testib
   - Commit + push

   "Parandatud! Probleem oli UTF-8-BOM encoding.
    Lisasin encoding='utf-8-sig' parameetri."

3️⃣ Gert:
   Testib → töötab ✅

   (Valikuliselt:)
   Gert → ChatGPT: "Miks see juhtus? Kuidas vältida tulevikus?"

4️⃣ ChatGPT → Gert:
   "UTF-8-BOM on Microsoft'i lisatud byte order mark.
    Soovitan:
    - Alati kasuta utf-8-sig
    - Lisa error handling
    - Näita kasutajale selge veateade
    - Tee unit test erinevate encoding'utega"

5️⃣ Gert → CC:
   "Lisa ChatGPT soovitatud täiendused (error handling, testid)"

6️⃣ CC:
   ✅ Täiendused lisatud
   ✅ Testid kirjutatud
   ✅ Commit + push
```

---

### **Stsenaarium 3: Strateegiline otsus**

**Näide:** Streamlit vs Flask valik

```
1️⃣ Gert → ChatGPT:
   "Kas kasutada Streamlit või Flask UI jaoks?
    Tahaksin kiiresti MVP-d, aga tulevikus professionaalset väljanägemist."

2️⃣ ChatGPT → Gert:
   "VÕRDLUS:

   Streamlit:
   ✅ Kiire (2-3 päeva MVP)
   ✅ Pole vaja frontend kogemust
   ✅ Tasuta deploy (Streamlit Cloud)
   ❌ Piiratud disain
   ❌ Vähem kontrolli

   Flask + Next.js:
   ✅ Professionaalne välimus
   ✅ Täielik kontroll
   ❌ Aeglasem (2-3 nädalat)
   ❌ Vajab frontend oskusi

   SOOVITUS:
   - MVP (v0.1-0.2): Streamlit
   - v1.0+: Flask + Next.js

   Põhjus: Kiire turule jõudmine, tagasiside kogumine"

3️⃣ Gert:
   Otsus: "Alustame Streamlit'iga"

4️⃣ Gert → CC:
   "Tee Streamlit UI (vaata MVP_PLAAN.md)"

5️⃣ CC:
   ✅ Streamlit UI kood
   ✅ Deployed Streamlit Cloud
   ✅ Link: https://elekter-optimeerija.streamlit.app
```

---

## 📊 Vastutuse maatriks (RACI)

| Tegevus | Gert | ChatGPT | Claude Code |
|---------|------|---------|-------------|
| **Planeerimine** |
| Projekti visioon | A | C | I |
| Arhitektuur | A | R | C |
| MVP scope | A | C | I |
| Ajakava | A | C | I |
| **Arendus** |
| Koodi kirjutamine | I | - | R/A |
| Testimine (unit) | I | - | R/A |
| Testimine (käsitsi) | R/A | - | C |
| Code review | A | - | R |
| **Deployment** |
| Git commit/push | I | - | R/A |
| Deploy staging | I | - | R/A |
| Deploy production | A | C | R |
| **Dokumentatsioon** |
| Tehniline dok | I | - | R/A |
| Äriline dok | R/A | C | - |
| Kasutajajuhend | R/A | C | I |
| **Probleemilahendus** |
| Bug'i leidmine | R | - | A |
| Bug'i parandamine | I | - | R/A |
| Root cause analüüs | A | R | C |
| **Strateegia** |
| Tehnoloogia valikud | A | R | C |
| Konkurentide analüüs | A | R | - |
| Turundusplaan | R/A | C | - |

**Legend:**
- **R** = Responsible (teostaja)
- **A** = Accountable (vastutaja, otsustaja)
- **C** = Consulted (nõustaja)
- **I** = Informed (teavitatav)

---

## 🔧 Praktilised töövahendid

### **Gert'i tööruum:**

```bash
# Põhiline kommunikatsioon:
1. ChatGPT (brainstorming, planeerimine)
   → Brauser: chat.openai.com

2. Claude Code (kodeerimine, deployment)
   → Terminal: claude code

3. GitHub (koodi ülevaatus)
   → Brauser: github.com/gert-rilbaum/Elektriportaal

4. Testimine
   → Brauser: http://localhost:8501 või live URL
```

---

### **ChatGPT töövoog:**

```
INPUT (Gert):
"Kuidas optimeerida börsihinnad JSON faili struktuuri?"

OUTPUT (ChatGPT):
1. Analüüs (praegune struktuur vs alternatiivid)
2. Soovitused (nt lisa indexing, cache layer)
3. Plaan (sammud, koodinäited PSEUDO-koodina)
4. Riskid ja kaalutlused

→ Gert otsustab
→ Gert edastab CC-le
```

---

### **Claude Code töövoog:**

```
INPUT (Gert):
"CC, tee Streamlit UI põhiline struktuur (vaata MVP_PLAAN.md)"

PROCESS (CC):
1. ✅ Loe MVP_PLAAN.md
2. ✅ Loo src/ui.py
3. ✅ Kirjuta kood
4. ✅ Testi lokaalselt (streamlit run src/ui.py)
5. ✅ Commit + push
6. ✅ Deploy Streamlit Cloud (kui küsitud)

OUTPUT (CC → Gert):
"✅ Valmis!
 - Kood: src/ui.py
 - Commit: 'Lisa Streamlit põhiline UI'
 - Lokaalne test: http://localhost:8501
 - Live (kui deployed): https://elekter-optimeerija.streamlit.app

 Järgmised sammud:
 - Testi CSV üleslaadimist
 - Kontrolli pakettide valikut"
```

---

## 📝 Kommunikatsiooni näidised

### ✅ Hea kommunikatsioon:

**Gert → ChatGPT:**
```
"ChatGPT, vajan nõu:

Probleem: Kasutajad ei saa aru, miks Võrk 4 on odavam kui Võrk 2.

Küsimus: Kuidas seletada seda UI-s?

Kontekst: Võrk 4 on kõrgem kuutasu, aga madalam kWh hind.
Kasutajad näevad ainult 9.17€ vs 1.81€ ja arvavad, et Võrk 2 odavam."
```

**ChatGPT vastus:**
```
SOOVITUSED:

1. Visualiseerimine:
   - Näita murdepunkti (433 kWh)
   - Graafik: kuidas kulu muutub tarbimise kasvades

2. Konkretne näide:
   "Sinu tarbimine: 1036 kWh
    Võrk 2: 41.18€
    Võrk 4: 35.16€
    ⚡ Võrk 4 säästab 6.02€ kuus!"

3. Tooltip/selgitus:
   "Kuigi kuutasu on kõrgem, on kWh hind madalam.
    Sinu tarbimise juures tasub Võrk 4 ära."

4. "Soovitatud" märgis:
   Roheline badge "✅ SOOVITATUD" optimaalse paketi kõrval
```

---

**Gert → CC:**
```
"CC, ChatGPT soovitas lisada murdepunkti visualiseerimise.

Palun lisa UI-sse:
1. Näita murdepunkti 433 kWh
2. Lisa tooltip Võrk 4 kõrvale selgitusega
3. Lisa roheline badge "SOOVITATUD" optimaalse paketi kõrval

Vaata ChatGPT täielikku plaani MENTORI_MÄRKMED.md failis."
```

**CC vastus:**
```
✅ Lisatud!

Muudatused:
- src/ui.py: lisa võrgupaketid comparison_chart()
- src/ui.py: lisa tooltip st.info()
- src/ui.py: lisa badge "✅ SOOVITATUD"

Commit: "Lisa võrgupakettide visualiseerimine ja selgitused"

Testi: http://localhost:8501
```

---

### ❌ Halb kommunikatsioon (vältida):

**Gert → CC:**
```
"Tee see paremaks"  ❌ (Ebaselge - mis? kuidas?)
```

**Parem:**
```
"CC, kasutajad kurdavad, et CSV üleslaadimine on segane.
ChatGPT soovitas lisada sammude näitaja (1/3, 2/3, 3/3).
Palun lisa see."  ✅
```

---

## 🎯 Eesmärgid ja edu kriteeriumid

### **Gert'i eesmärgid:**
- ✅ Fokuseerida ärilisele poolele (mitte tehnilisele)
- ✅ Teha kiireid otsuseid (ChatGPT nõuannete põhjal)
- ✅ Testida ja anda tagasisidet
- ✅ Suhelda kasutajatega (tulevikus)

### **ChatGPT eesmärgid:**
- ✅ Anda parimaid praktikaid ja soovitusi
- ✅ Analüüsida alternatiive
- ✅ Aidata planeerida (MVP → v1.0 → v2.0)
- ✅ Hoiatada riskide eest

### **CC eesmärgid:**
- ✅ Kirjutada kvaliteetset, töötavat koodi
- ✅ Hoida Git ajalugu puhtana
- ✅ Testida enne commit'i
- ✅ Dokumenteerida muudatusi
- ✅ Küsida, kui ebaselge

---

## 📅 Regulaarsed tegevused

### **Iga nädal:**

**Gert + ChatGPT (Planeerimine):**
- Eelmise nädala ülevaade
- Järgmise nädala eesmärgid
- Prioriteetide seadmine

**Gert + CC (Arendus):**
- Eelmise nädala tulemused
- Järgmise nädala ülesanded
- Probleemide lahendamine

---

### **Iga kuu:**

**Gert + ChatGPT (Strateegia):**
- Konkurentide analüüs
- Turutrende
- Pikaajaline plaan (3-6 kuud)

**Gert + CC (Tehnoloogia):**
- Koodi ülevaatus
- Refaktoreerimise vajadus
- Dependency update'id

---

## 🚀 Järgmised sammud

**Lähiaeg (november-detsember 2025):**
1. [ ] Gert + ChatGPT: MVP täpsustamine
2. [ ] Gert → CC: MVP kodeerimine
3. [ ] Gert: Testimine
4. [ ] CC: Deploy Streamlit Cloud
5. [ ] Gert: Beta kasutajate leidmine (5 inimest)

**Keskmine tulevik (jaanuar-märts 2026):**
6. [ ] Gert + ChatGPT: v1.0 planeerimine
7. [ ] Gert → CC: v1.0 kodeerimine
8. [ ] Gert: Turundus (elektri-hind.ee alternatiiv)

---

**Viimati uuendatud:** 17.11.2025
**Kehtiv:** v0.1 - v2.0 (kogu projekti elutsükkel)
**Vastutaja:** Gert Roots

---

## ❓ KKK

**K: Mis juhtub, kui ChatGPT ja CC on eri meelt?**
A: Gert otsustab. ChatGPT annab soovituse, aga Gert on projektijuht.

**K: Kas CC võib küsida ChatGPT-lt abi?**
A: Ei otse, aga Gert võib edastada CC küsimuse ChatGPT-le.

**K: Kas ChatGPT võib kirjutada koodi?**
A: Jah, NÄITEID ja PSEUDO-koodi, aga mitte otse projekti (seda teeb CC).

**K: Kui tihti Gert peab CC-ga suhtlema?**
A: Igapäevaselt arendusfaasis (nov-dets), seejärel vajaduse korral.

**K: Mis juhtub, kui CC jääb kinni probleemiga?**
A: CC raporteerib Gerdile → Gert küsib ChatGPT-lt nõu → Gert annab CC-le lahenduse.

---

**Kontakt:**
Email: gert@rilbaum.ee
GitHub: https://github.com/gert-rilbaum/Elektriportaal
