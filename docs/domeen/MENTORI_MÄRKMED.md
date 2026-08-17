# Mentori märkmed

See fail sisaldab juhiseid ja kokkuvõtteid, mida mentor (GPT) annab projekti jaoks.
CC loeb siit järgmised sammud ja teeb nende põhjal muudatusi koodis.

## Reeglid:

- CC ei kustuta siit varasemaid märkmeid.
- Uued juhised lisatakse faili lõppu kuupäeva ja lühikese pealkirjaga.
- Commit'i muudatus loogilise sõnumiga.

---

## 2025-11-17 – Repo algseis

### Kaustad

```
elekter-optimeerija/
├── data/           # Andmefailid (JSON formaadis)
├── docs/           # Dokumentatsioon ja juhendid
├── src/            # Lähtekood (hetkel tühi)
└── tests/          # Testid (hetkel tühi)
```

### Põhifailid

**Juurkaustas:**
- `README.md` - Projekti põhiline kirjeldus ja kasutusjuhend
- `PROJEKT_SUMMARY.md` - Projekti kokkuvõte, eesmärgid, ajakava
- `.gitignore` - Git-i välistuste konfiguratsioon

**data/ kaustas:**
- `README.md` - Andmete kirjeldus
- `borsihinnad.json` - Alexela börsihinnad (kuupõhised)
- `paketid.json` - Elektri- ja võrgupakettide andmed
- `paketid_backup_original.json` - Varukoopia algsest paketid failist

**docs/ kaustas:**
- `MENTORI_MÄRKMED.md` - See fail (mentori juhised)
- `ANDMEALLIKAD.md` - Andmeallikate kirjeldus ja kasutamine
- `ARHITEKTUUR.md` - Süsteemi arhitektuur ja loogika
- `MUUJATE_INFO.md` - Info muutujate ja terminoloogia kohta
- `MVP_PLAAN.md` - MVP arendusplaan ja tegevused
- `TERMINOLOOGIA.md` - Projektis kasutatav terminoloogia

**src/ kaustas:**
- (hetkel tühi - kood tuleb siia)

**tests/ kaustas:**
- (hetkel tühi - testid tulevad siia)

### Märkused

- Projekt on dokumenteeritud, kuid kood on veel puudu
- JSON andmefailid on olemas ja valmis kasutamiseks
- Valmis GitHubis: https://github.com/gert-rilbaum/Elektriportaal

---

## 2025-11-17 – Ülesanne: Loo moodulite karkass ja sõltuvused

### Miks

- README, PROJEKT_SUMMARY ja ARHITEKTUUR kirjeldavad `src/` ja `tests/` struktuuri, kuid neid kaustu pole veel.
- CC vajab selget alust, et hakata MVP loogikat implementeerima, järgides NFR nõudeid (stateless funktsioonid, JSON kui tõde, andmeid ei salvestata).
- Soovime fikseerida baas-sõltuvused, et iga arendaja kasutaks sama versioonibaasi.

### Ülesanded (järjekorras)

1. **Loo kaustad ja `__init__.py`:**
   - Kaustad: `src/` ja `tests/` repo juures.
   - Failid `src/__init__.py` ja `tests/__init__.py` võivad olla tühjad, kuid lisada kommentaar, et moodul on stateless (vt `docs/NFR_ELEKTRIPORTAAL.md`).

2. **Lisa moodulite karkass:**
   - Failid: `src/csv_loader.py`, `src/analyzer.py`, `src/calculator.py`, `src/comparator.py`, `src/ui.py`.
   - Igas failis lisa:
     - Moduuli docstring, mis viitab vastavale sektsioonile failis `docs/ARHITEKTUUR.md`.
     - Vähemalt üks funktsioonisignatuur (vastavalt arhitektuuri kirjeldusele) koos tüübianotate ja `NotImplementedError`-iga. Näide: `def lae_elektrilevi_csv(csv_file: IO[str]) -> List[Dict[str, Any]]: ...`.
     - `calculator.py` puhul lisa eraldi funktsioonid võrgukulu, börsipaketi ja fikseeritud paketi jaoks.
     - `ui.py` failis lisa `def run_cli_demo() -> None` ja `def run_streamlit()` stubid, mis praegu ainult logivad/annavad teada, et UI pole veel valmis.

3. **Konfigureeri sõltuvused:**
   - Loo `requirements.txt` juurkausta. Lisa hetkel vajalikud paketid (versioonid võib lukustada konservatiivselt):
     - `pandas`
     - `python-dateutil`
     - `streamlit`
   - Kui kasutad muid teeke (nt `numpy`), lisa põhjendus kommentaarina.

4. **Testide karkass:**
   - Loo `tests/test_structure.py`, mis kontrollib vähemalt seda, et iga eespool nimetatud funktsioon on imporditav. Kasuta `pytest` stiili (`def test_module_imports(): ...`).
   - Lisa `pytest` ka `requirements.txt` failis arendus-sõltuvusena kommentaariga `# dev/test`.

5. **Kvaliteedikriteeriumid:**
   - Kõik funktsioonid peavad olema puhtad/stateless (vt `docs/NFR_ELEKTRIPORTAAL.md`).
   - Docstring peab viitama allikale (nt "Vaata docs/ARHITEKTUUR.md jaotis 1. CSV Loader").
   - `tests/test_structure.py` peab jooksma edukalt käsuga `pytest tests/test_structure.py`.
   - Kommiti sõnum: `feat: add initial src skeleton and deps`.

Kui ülesanne valmis, märgi siia järgmise jaotise alla kokkuvõte (mida lisasid ja kas test jooksis).

---

### ✅ Ülesanne täidetud - Kokkuvõte

**Kuupäev:** 17.11.2025
**Täitja:** Claude Code (CC)

#### Loodud failid:

**src/ kaust:**
- ✅ `src/__init__.py` - Paketi init koos versiooniga (0.1.0)
- ✅ `src/csv_loader.py` - Elektrilevi CSV laadija (1 funktsioon: `lae_elektrilevi_csv`)
- ✅ `src/analyzer.py` - Tarbimisanalüüs (1 funktsioon: `grupeeri_kuude_kaupa`)
- ✅ `src/calculator.py` - Hinnakalkulatsioon (3 funktsiooni: `arvuta_võrgukulu`, `arvuta_elektrikulu_borss`, `arvuta_elektrikulu_fiks`)
- ✅ `src/comparator.py` - Pakettide võrdlus (3 funktsiooni: `võrdle_võrgupakette`, `võrdle_elektripakette`, `hinda_strateegiat`)
- ✅ `src/ui.py` - Kasutajaliides (2 funktsiooni: `run_cli_demo`, `run_streamlit`)

**tests/ kaust:**
- ✅ `tests/__init__.py` - Test paketi init
- ✅ `tests/test_structure.py` - Struktuuri testid (8 testi)

**Juurkaust:**
- ✅ `requirements.txt` - Sõltuvused (pandas, python-dateutil, streamlit, pytest)

#### Testide tulemused:

```
Running structure tests
============================================================

test_module_imports... PASSED
test_csv_loader_functions... PASSED
test_analyzer_functions... PASSED
test_calculator_functions... PASSED
test_comparator_functions... PASSED
test_ui_functions... PASSED
test_stateless_principle... PASSED
test_architecture_compliance... PASSED

============================================================
Results: 8 passed, 0 failed
============================================================
```

#### Kvaliteedikriteeriumid:

- ✅ Kõik funktsioonid on stateless (järgivad NFR nõudeid)
- ✅ Kõik docstringid viitavad `docs/ARHITEKTUUR.md` failile
- ✅ Tüübianotasioonid on paigas (`typing` mooduliga)
- ✅ Kõik stubid tagastavad `NotImplementedError` (ootuspärane)
- ✅ Testid jooksevad edukalt (8/8 PASSED)

#### Git:

- ✅ Commit: `feat: add initial src skeleton and deps`
- ✅ Pushed GitHubisse: https://github.com/gert-rilbaum/Elektriportaal/commit/9e68c59

#### Järgmised sammud:

1. Implementeeri `csv_loader.py` - CSV lugemine ja parseerimine
2. Implementeeri `analyzer.py` - kuupõhine grupeerimine
3. Implementeeri `calculator.py` - hinnaarvutused (JSON failidest)
4. Implementeeri `comparator.py` - pakettide võrdlus
5. Implementeeri `ui.py` - Streamlit kasutajaliides (MVP)
6. Uuenda testid - lisa unit testid igale moodulile
7. Testimine päris CSV failidega

**Märkused:** Kõik on valmis MVP loogika implementeerimiseks!
