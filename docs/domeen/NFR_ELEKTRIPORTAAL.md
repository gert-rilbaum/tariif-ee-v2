# Mittefunktsionaalsed nõuded - Elektriportaal

Elektriportaali arhitektuursed põhimõtted ja mittefunktsionaalsed reeglid (Non-Functional Requirements).

**Kuupäev:** 17.11.2025
**Versioon:** 1.0

---

## 1. Stateless arvutusmootor

Elektriportaali arvutusmootor peab olema **täielikult stateless** (olekuta).

**Põhimõte:**
- Iga analüüs on eraldiseisev operatsioon, mis ei sõltu varasematest analüüsidest.
- Kõik vajalik info (tarbimisandmed, börsihinnad, paketid) antakse sisendina ja tulemus tagastatakse väljundina.
- Mootor ei hoia mälus kasutaja sessioone, vahetulemusi ega ajalugu.

**Eelised:**
- Lihtne skaleerida (iga päring võib minna erinevale serverile)
- Lihtne testida (puhas funktsioon: sisend → väljund)
- Vähem vigu (pole "peidetud olekut")
- Võimalik käivitada paralleelselt mitut analüüsi

**Praktikas:**
```python
# ✅ ÕIge - stateless funktsioon
def arvuta_pakett(tarbimisandmed, borsihinnad, pakett):
    return tulemus

# ❌ Vale - hoiab olekut
class Kalkulaator:
    def __init__(self):
        self.viimane_tulemus = None  # ❌ Olek!
```

---

## 2. Isikuandmete mitte-salvestamine

Elektriportaal **ei salvesta kasutajate isikuandmeid ega tarbimisandmeid** püsivalt.

**Põhimõte:**
- Kasutaja laeb CSV üles → analüüs toimub → tulemus näidatakse → andmed kustutatakse.
- CSV failid ei jää serverisse (ei ketta ega andmebaasi).
- Ei kogu kasutajanimesid, e-poste, aadresse ega muid isikuandmeid (välja arvatud vajadusel opt-in e-posti teavitused).

**Erandid (opt-in):**
- Kui kasutaja SOOVIB e-posti teavitusi → salvestame ainult e-posti aadressi ja eelistused.
- Premium kasutajad võivad valida "salvesta minu analüüs" → siis salvestame krüpteeritult.
- Kõik salvestamine toimub ainult kasutaja **selge nõusolekul**.

**Õiguslik alus:**
- GDPR - minimaalse andmetöötluse põhimõte ("data minimisation")
- Kasutaja usaldus - me ei müü ega jaga andmeid kolmandatele osapooltele.

**Praktikas:**
```python
# Analüüs käivitatakse
result = analyze_csv(uploaded_file)

# Näita tulemust kasutajale
show_result(result)

# ✅ Kustuta andmed kohe pärast analüüsi
del uploaded_file
del result
```

---

## 3. Üks "tõde" hindade kohta

Kõik elektri- ja võrgupakettide hinnad, börsihinnad ja tasud pärinevad **ühest autoriteetset allikast** - `data/paketid.json` ja `data/borsihinnad.json` failidest.

**Põhimõte:**
- JSON failid on **ainuke tõde** ("single source of truth").
- Kui turul toimub hinnamuutus → uuendame JSON faili → kõik arvutused kasutavad automaatselt uusi hindu.
- Ei ole "kohalikke" hinnavariante, ülekirjutamisi ega erandeid koodis.

**Miks see oluline:**
- **Järjepidevus:** Kõik kasutajad näevad samu hindu (ei ole A/B variante).
- **Auditeeritavus:** Saame alati vaadata, milliseid hindu kasutati (Git ajalugu).
- **Lihtsus:** Hindade muutmine = JSON faili muutmine (ei pea koodi muutma).
- **Usaldusväärsus:** Kasutaja teab, et hinnad on päris (võime viidata Elektrilevi hinnakirjale).

**Praktikas:**
```python
# ✅ Õige - loe alati JSON-ist
paketid = load_json("data/paketid.json")
borsihinnad = load_json("data/borsihinnad.json")

# ❌ Vale - hardcoded hinnad koodis
VORK2_PAEV = 6.07  # ❌ Ei tohi!
VORK2_OO = 3.51    # ❌ Ei tohi!
```

**Uuendamise protsess:**
1. Elektrilevi avaldab uue hinnakiri
2. Uuendame `data/paketid.json`
3. Commit: `git commit -m "Uuenda Elektrilevi hindu (kehtib 01.01.2026)"`
4. Deploy → kõik arvutused kasutavad nüüd uusi hindu

**JSON struktuuri näide:**
```json
{
  "paketid": {
    "vork2": {
      "nimi": "Võrk 2",
      "paev": 6.07,
      "oo": 3.51,
      "kuutasu": 5.81,
      "kehtib_alates": "2025-01-01",
      "allikas": "https://elektrilevi.ee/hinnakirjad"
    }
  }
}
```

---

## Kokkuvõte

Need kolm põhimõtet tagavad, et Elektriportaal on:

1. **Skaleeruv** (stateless mootor)
2. **Privaatne** (isikuandmeid ei salvestata)
3. **Usaldusväärne** (JSON on tõde)

Need reeglid kehtivad kõigile arendajatele ja kõigile projekti osadele (MVP, v1.0, v2.0+).

---

**Viimati uuendatud:** 17.11.2025
**Vastutaja:** Gert Roots (RILBAUM-IT OÜ)
**Staatus:** Kehtiv
