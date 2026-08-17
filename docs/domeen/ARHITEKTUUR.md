# Süsteemi arhitektuur

## 🎯 Ülevaade

Elektritarbimise optimeerija on personaliseeritud analüüsitööriist, mis võrdleb erinevaid elektri- ja võrgupakette kasutaja **tegeliku** tarbimise põhjal.

## 🏗️ Komponentide ülevaade

```
┌─────────────────────────────────────────────────────────┐
│                    KASUTAJALIIDES                        │
│  (Streamlit / Flask / CLI)                              │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│                 ANALÜÜSI MOOTOR                          │
│  - CSV Parser                                            │
│  - Tarbimusanalüüs                                       │
│  - Hinnakalkulaator                                      │
│  - Võrdlusmootor                                         │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│                  ANDMEBAAS                               │
│  - Pakettide hinnakirjad (JSON)                         │
│  - Börsihinnad (JSON/API)                               │
│  - Kasutaja CSV andmed (ajutine)                        │
└─────────────────────────────────────────────────────────┘
```

## 📦 Moodulite kirjeldus

### 1. CSV Loader (`src/csv_loader.py`)

**Eesmärk:** Elektrilevi CSV failide lugemine ja parseerimine

**Sisend:**
- Elektrilevi tarbimisandmete CSV fail
- Formaat: Kuupäev; Kellaaeg; Tarbimine; Võrku andmine

**Väljund:**
- Struktureeritud andmed (Python dict/list):
```python
[
    {
        "aeg": datetime(2024, 11, 1, 0, 0),
        "import": 0.98,      # kWh
        "eksport": 0.025,    # kWh
        "tsooni": "Öö"       # Päev/Öö
    },
    ...
]
```

**Funktsionaalsus:**
- ✅ UTF-8-BOM encoding tugi
- ✅ Koma→punkt konversioon (0,98 → 0.98)
- ✅ Päev/öö automaattuvastus
- ✅ Mitme faili ühendamine
- ✅ Andmete sorteerimine aja järgi

**Vigade käsitlemine:**
- Puuduvad veerud → ValueError
- Vale formaat → Warning + jäta rida vahele
- Tühi fail → Tühi list

---

### 2. Analyzer (`src/analyzer.py`)

**Eesmärk:** Tarbimisandmete analüüs ja grupeerimine

**Sisend:** CSV Loader väljund (tunnipõhine)

**Väljund:** Kuupõhine kokkuvõte:
```python
{
    "2024-11": {
        "import_kokku": 1036.05,
        "eksport_kokku": 9.62,
        "import_päev": 266.31,
        "import_öö": 769.74,
        "eksport_päev": 5.12,
        "eksport_öö": 4.50,
        "tunnid": [...]  # Viide algsetele andmetele
    },
    ...
}
```

**Funktsionaalsus:**
- ✅ Kuude kaupa grupeerimine
- ✅ Päev/öö eraldamine
- ✅ Import/eksport eraldamine
- ✅ Profiili tuvastus:
  - Mikrotootja (eksport > 0)
  - Öötarbija (öö > 60% tarbimisest)
  - Soojuspump (talv >> suvi)

---

### 3. Calculator (`src/calculator.py`)

**Eesmärk:** Elektri- ja võrgupakettide hinnakalkulatsioon

#### 3.1 Võrgupakett (`arvuta_võrgukulu`)

**Valem:**
```python
kulu = kuutasu + (päev_kWh × päevahind) + (öö_kWh × ööhind)
```

**Näide (Võrk 4):**
```python
# November 2024: 1036 kWh (266 päev + 770 öö)
kulu = 9.17 + (266 × 0.0369) + (770 × 0.021)
     = 9.17 + 9.82 + 16.17
     = 35.16€
```

#### 3.2 Elektripakett - Börss (`arvuta_elektrikulu_borss`)

**Valem:**
```python
kulu = kuutasu + päev_kWh × (börsihind_päev + marginaal) +
                 öö_kWh × (börsihind_öö + marginaal)
```

**Näide (Alexela Börss, November 2024):**
```python
# Börsihind: 13.3s päev, 4.68s öö
# Marginaal: 0.457s
# Kuutasu: 1.99€

kulu = 1.99 + 266 × (0.133 + 0.00457) + 770 × (0.0468 + 0.00457)
     = 1.99 + 36.60 + 39.56
     = 78.15€
```

#### 3.3 Elektripakett - Kindel (`arvuta_elektrikulu_fiks`)

**Valem:**
```python
kulu = kuutasu + (päev_kWh × päevahind) + (öö_kWh × ööhind)
```

**Näide (Alexela Kindel, November 2024):**
```python
# Päevahind: 12.4s, Ööhind: 10.4s
# Kuutasu: 1.50€

kulu = 1.50 + (266 × 0.124) + (770 × 0.104)
     = 1.50 + 32.98 + 80.08
     = 114.56€
```

---

### 4. Võrdlusmootor (`src/comparator.py`)

**Eesmärk:** Erinevate strateegiate võrdlemine

**Funktsionaalsus:**

#### 4.1 Võrgupakett optimeerija
- Võrdleb Võrk 2 vs Võrk 4 iga kuu kohta
- Leiab parima paketi igale kuule
- Arvutab kokkuhoiu

#### 4.2 Elektripakett võrdleja
- Võrdleb börsi vs kindel vs kodupakett
- Arvutab kasutaja strateegia maksumuse
- Näitab alternatiive

#### 4.3 Strateegia hindaja
```python
strateegiad = [
    "kogu_aasta_borss",
    "kogu_aasta_kindel",
    "borss_suvi_kindel_talv",
    "kasutaja_oma"
]

for strateegia in strateegiad:
    maksumus = arvuta_maksumus(strateegia)
    print(f"{strateegia}: {maksumus}€")
```

---

## 🔄 Andmevoog

```
1. SISEND
   ├─> Kasutaja laadib CSV üles
   └─> Kasutaja valib/sisestab oma paketid

2. TÖÖTLEMINE
   ├─> CSV Parser: CSV → tunnipõhised andmed
   ├─> Analyzer: tunnid → kuud
   ├─> Calculator: kuud × pakett → kulud
   └─> Comparator: võrdle pakette

3. VÄLJUND
   ├─> Tabel: iga kuu kulu iga paketiga
   ├─> Kokkuvõte: aastane kulu
   └─> Soovitus: parim strateegia
```

---

## 🗄️ Andmestruktuurid

### Tunnipõhine andmekirje
```python
{
    "aeg": datetime(2024, 11, 1, 0, 0),
    "import": float,      # kWh võrgust
    "eksport": float,     # kWh võrku
    "tsooni": str         # "Päev" või "Öö"
}
```

### Kuupõhine kokkuvõte
```python
{
    "kuu": "2024-11",
    "import_kokku": float,
    "eksport_kokku": float,
    "import_päev": float,
    "import_öö": float,
    "eksport_päev": float,
    "eksport_öö": float,
    "tunnid": List[dict]
}
```

### Pakett (võrk või elekter)
```python
{
    "id": "alexela-borss",
    "nimi": "Alexela Börsipakett",
    "tüüp": "börss",  # või "fikseeritud"
    "kuutasu": float,
    "börsimarginaal": float,  # kui börss
    "päevahind": float,       # kui fikseeritud
    "ööhind": float,          # kui fikseeritud
    "kehtib_alates": "2024-01-01"
}
```

---

## ⚙️ Konfiguratsiooni parameetrid

### Elektrilevi päev/öö määratlus
```python
PÄEV_ALGUS = 7   # 07:00
PÄEV_LÕPP = 22   # 21:59
# Öö: 22:00 - 06:59
```

### Võrgupaketid (Elektrilevi 2024/2025)
```python
VÕRGUPAKETID = {
    "Võrk 2": {
        "kuutasu": 1.81,
        "päevahind": 0.038,
        "ööhind": 0.038
    },
    "Võrk 4": {
        "kuutasu": 9.17,
        "päevahind": 0.0369,
        "ööhind": 0.021
    }
}
```

---

## 🧪 Testimine

### Unit testid
- `test_csv_loader.py` - CSV lugemise testid
- `test_analyzer.py` - Analüüsi loogika testid
- `test_calculator.py` - Arvutuste täpsus

### Integratsioonitestid
- `test_full_flow.py` - Täielik voog CSV → tulemus

### Testimisandmed
- `tests/data/test_sample.csv` - Näidis CSV
- `tests/data/expected_output.json` - Oodatav tulemus

---

## 🔒 Turvalisus ja privaatsus

### Andmete käsitlemine
- ✅ CSV failid **EI SALVESTATA** serverisse
- ✅ Kõik töötlemine toimub **mälus**
- ✅ Kasutaja andmed kustutatakse pärast analüüsi
- ✅ Ei kogu isikuandmeid (EIC, seerianumber)

### Pakettide andmebaas
- ✅ Avalik info (hinnakirjad)
- ✅ Ei sisalda kasutajaandmeid
- ✅ Versioonihaldus (JSON failid Gitis)

---

## 🚀 Jõudlus

### Eeldatavad mahud (MVP)
- Kasutajaid: < 100 päevas
- CSV suurus: 8000-30000 rida (1-3 aastat andmeid)
- Töötlusaeg: < 5 sekundit

### Optimeerimine
- CSV lugemine: `pandas` (kiire)
- Arvutused: Pythoni native (piisav)
- Tulevikus: PostgreSQL pakettide jaoks

---

## 📈 Skaleeritavus (tulevikus)

### Faas 1 (MVP): Ainult Pythoni skript
- Kasutajad: < 10
- Töötlus: Lokaalne

### Faas 2: Streamlit rakendus
- Kasutajad: < 100
- Töötlus: Streamlit Cloud (tasuta)

### Faas 3: Flask/Next.js
- Kasutajad: < 1000
- Töötlus: VPS (nt DigitalOcean)
- Andmebaas: PostgreSQL

### Faas 4: Täismahus SaaS
- Kasutajad: 10000+
- Töötlus: AWS/GCP
- Andmebaas: PostgreSQL + Redis cache
- API: REST + WebSockets

---

## 🔧 Tehnilised valikud

| Komponent | Tehnoloogia | Põhjendus |
|-----------|-------------|-----------|
| Backend | Python 3.10+ | Lihtne, kiire arendus |
| CSV parsing | `csv` + custom | Kontroll formaadi üle |
| Andmebaas (MVP) | JSON failid | Lihtne, versioonikontroll |
| Andmebaas (v2) | PostgreSQL | Skaleeruv, päringud |
| UI (MVP) | Streamlit | Kiire prototüüp |
| UI (v2) | Next.js + React | Professionaalne |
| API (v2) | FastAPI | Kiire, automaatne docs |

---

## 🐛 Teadaolevad piirangud (MVP)

1. **Börsihinnad:** Ainult Alexela keskmised (mitte tund-tunnilt)
2. **Paketid:** Käsitsi sisestatud JSON (ei uuendata automaatselt)
3. **Kasutajaliides:** Lihtne (pole ilus)
4. **Graafikud:** Puuduvad
5. **Riskianalüüs:** Puudub

---

## 📝 Versioonihaldus

### v0.1 (MVP) - November 2025
- CSV lugemine
- Põhiline arvutus
- Konsooli väljund

### v0.2 - Detsember 2025
- Streamlit UI
- Pakettide valimine
- Visualisatsioonid

### v1.0 - Jaanuar 2026
- Flask rakendus
- PostgreSQL
- Kasutajakontod
- API

---

## 🤝 Arenduse põhimõtted

1. **KISS** - Keep It Simple, Stupid
2. **DRY** - Don't Repeat Yourself
3. **Dokumenteeri** kõike
4. **Testi** enne deployimist
5. **Privaatsus** kõigepealt
