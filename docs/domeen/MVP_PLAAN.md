# MVP Tegevusplaan

**Versioon:** 0.1  
**Kuupäev:** 17.11.2025  
**Eesmärk:** Valmis töötav prototüüp 1-2 nädalaga

---

## 🎯 MVP Definitsioon

**"Minimum Viable Product" - Minimaalselt toimiv toode**

### Mis MVP PEAB OSKAMA:

1. ✅ Laeb kasutaja CSV-d (Elektrilevi formaat)
2. ✅ Näitab kuupõhist tarbimist
3. ✅ Arvutab võrgupaketi kulu (Võrk 2 vs Võrk 4)
4. ✅ Arvutab elektrihinna (börss vs kindel)
5. ✅ Näitab kasutaja strateegia maksumust
6. ✅ Võrdleb alternatiividega

### Mis MVP EI PEA OSKAMA:

❌ Graafikud ja visualisatsioonid  
❌ Riskianalüüs (worst-case stsenaariumid)  
❌ Automaatne optimeerija  
❌ Kasutajakontod  
❌ Andmebaas (kasutame JSON faile)  
❌ API integratsioonid  
❌ PDF raport  

---

## 📅 Ajakava

### Nädal 1 (18-24 november 2025)

#### **Päev 1-2: Koodi refaktoreerimine**
- [x] Projekti struktuur loodud
- [x] Dokumentatsioon kirjutatud
- [ ] Liiguta kood moodulitesse:
  - [ ] `src/csv_loader.py`
  - [ ] `src/analyzer.py`
  - [ ] `src/calculator.py`
  - [ ] `src/comparator.py`
- [ ] Liiguta andmed:
  - [ ] `data/paketid.json`
  - [ ] `data/borsihinnad.json`

#### **Päev 3: Testimine**
- [ ] Kirjuta unit testid
- [ ] Testi oma CSV-ga
- [ ] Testi sõbra CSV-ga
- [ ] Paranda vead

#### **Päev 4-5: Kasutajaliides (Streamlit)**
- [ ] Installi Streamlit
- [ ] Loo põhiline UI:
  - [ ] CSV üleslaadija
  - [ ] Pakettide valija
  - [ ] Tulemuste tabel
- [ ] Testi kohalikult

#### **Päev 6-7: Poleerimie**
- [ ] Paranda UI
- [ ] Lisa selgitavad tekstid
- [ ] Testi veel kord
- [ ] Salvesta Git repositooriumisse

---

### Nädal 2 (25 nov - 1 dets 2025)

#### **Päev 8: Deploy**
- [ ] Deploy Streamlit Cloud (tasuta)
- [ ] Testi live versiooni
- [ ] Jaga link 2-3 sõbraga

#### **Päev 9-10: Tagasiside**
- [ ] Kogu kasutajate tagasisidet
- [ ] Tee nimekirja vigadest/soovidest
- [ ] Prioriteeri parandused

#### **Päev 11-14: Parandused**
- [ ] Paranda kriitilised vead
- [ ] Lisa puuduvad paketid
- [ ] Uuenda börsihinnad (kui muutunud)
- [ ] Salvesta v0.1 versioon

---

## ✅ Valmiduse kriteeriumid

MVP on VALMIS, kui:

1. ✅ Kasutaja saab laadida oma CSV-d
2. ✅ Süsteem näitab õigeid numbreid
3. ✅ Numbrid klapivad päris arvetega (±5%)
4. ✅ 3 testikasutajat on proovinud ja annud positiivset tagasisidet
5. ✅ Ei ole kriitilisi vigu (app ei kraši)

---

## 🏗️ Tehnilised ülesanded

### 1. Koodi refaktoreerimine

**Praegune:** Kõik ühes failis (`elekter_analüüs.py`)

**Sihtmärk:** Moodulite struktuur

```python
# src/csv_loader.py
def lae_elektrilevi_csv(failinimi) -> List[Dict]:
    """Loeb Elektrilevi CSV."""
    pass

# src/analyzer.py
def grupeeri_kuude_kaupa(tunnid) -> Dict:
    """Grupeerib tunnid kuude kaupa."""
    pass

# src/calculator.py
def arvuta_võrgukulu(kuud, pakett) -> Dict:
    """Arvutab võrgukulu."""
    pass

def arvuta_elektrikulu_borss(kuud, borsihinnad, pakett) -> Dict:
    """Arvutab elektrihinna börsipaketiga."""
    pass

# src/comparator.py
def võrdle_võrgupakette(kuud) -> Dict:
    """Võrdleb võrgupakette."""
    pass

# src/ui.py (Streamlit)
def main():
    """Streamlit UI."""
    st.title("Elektritarbimise optimeerija")
    # ...
```

---

### 2. Streamlit UI (MVP)

**Vajalik:**
```bash
pip install streamlit pandas
```

**Põhiline UI struktuur:**

```python
import streamlit as st
import pandas as pd
from src.csv_loader import lae_elektrilevi_csv
from src.analyzer import grupeeri_kuude_kaupa
from src.comparator import võrdle_võrgupakette, võrdle_elektripakette

st.title("⚡ Elektritarbimise optimeerija")

# 1. CSV üleslaadimine
st.header("1️⃣ Lae oma tarbimisandmed")
uploaded_file = st.file_uploader("Vali CSV fail", type=['csv'])

if uploaded_file:
    # Lae andmed
    tunnid = lae_elektrilevi_csv(uploaded_file)
    kuud = grupeeri_kuude_kaupa(tunnid)
    
    # Näita kokkuvõtet
    st.success(f"✅ Loetud {len(tunnid)} tundi andmeid")
    
    # 2. Pakettide valimine
    st.header("2️⃣ Vali oma paketid")
    
    vogupakett = st.selectbox(
        "Võrgupakett",
        ["Võrk 2", "Võrk 4"]
    )
    
    elektripakett_suvi = st.selectbox(
        "Elektripakett (mai-okt)",
        ["Alexela Börss", "Alexela Kindel", "Sunly 0-marginaal"]
    )
    
    elektripakett_talv = st.selectbox(
        "Elektripakett (nov-apr)",
        ["Alexela Börss", "Alexela Kindel", "Sunly 0-marginaal"]
    )
    
    # 3. Arvuta
    if st.button("🔍 Arvuta maksumus"):
        # Võrdle pakette
        võrk_tulemus = võrdle_võrgupakette(kuud)
        elekter_tulemus = võrdle_elektripakette(kuud, borsihinnad)
        
        # Näita tulemusi
        st.header("📊 Tulemused")
        
        col1, col2, col3 = st.columns(3)
        
        with col1:
            st.metric("Elekter", f"{elekter_tulemus['kokku']}€")
        
        with col2:
            st.metric("Võrk", f"{võrk_tulemus['kokku']}€")
        
        with col3:
            st.metric("KOKKU", f"{elekter_tulemus['kokku'] + võrk_tulemus['kokku']}€")
        
        # Detailne tabel
        st.subheader("Kuude kaupa")
        df = pd.DataFrame(tulemus_tabel)
        st.dataframe(df)
```

---

### 3. Andmete struktuur

**`data/paketid.json`:**
```json
{
  "elektripaketid": {
    "alexela": [...],
    "sunly": [...],
    "elenger": [...]
  },
  "võrgupaketid": {
    "elektrilevi": [...]
  }
}
```

**`data/borsihinnad.json`:**
```json
{
  "allikas": "Alexela",
  "börsihinnad": {
    "2024-11": {"päev": 0.133, "öö": 0.0468},
    ...
  }
}
```

---

## 🧪 Testimise plaan

### Unit testid

**`tests/test_csv_loader.py`:**
```python
def test_lae_csv():
    tunnid = lae_elektrilevi_csv("tests/data/test.csv")
    assert len(tunnid) == 24
    assert tunnid[0]['import'] == 0.98
```

**`tests/test_calculator.py`:**
```python
def test_võrk4_arvutus():
    kuud = {"2024-11": {"import_päev": 266, "import_öö": 770}}
    kulu = arvuta_võrgukulu(kuud, "Võrk 4")
    assert kulu["2024-11"] == pytest.approx(30.93, 0.1)
```

---

### Integratsioonid testid

**`tests/test_integration.py`:**
```python
def test_täielik_voog():
    # 1. Lae CSV
    tunnid = lae_elektrilevi_csv("tests/data/aasta.csv")
    
    # 2. Grupeeri
    kuud = grupeeri_kuude_kaupa(tunnid)
    
    # 3. Arvuta
    tulemus = võrdle_pakette(kuud)
    
    # 4. Kontrolli
    assert tulemus['kokku'] > 0
    assert 'börss' in tulemus
```

---

### Käsitsi testimine

**Checklist:**
- [ ] Lae oma CSV → töötab?
- [ ] Vaata numbreid → klapib arvetega?
- [ ] Vaheta paketti → muutub tulemus?
- [ ] Lae sõbra CSV → töötab?
- [ ] Proovi vigast CSV-d → näitab viga selgelt?

---

## 🚀 Deployment

### Variant A: Streamlit Cloud (SOOVITATUD MVP-ks)

**Plussid:**
- ✅ Tasuta
- ✅ Lihtne (push to GitHub → automatic deploy)
- ✅ SSL (https)

**Sammud:**
1. Push kood GitHubi
2. Mine https://streamlit.io/cloud
3. Connect GitHub repo
4. Deploy!

**URL:** `https://elekter-optimeerija.streamlit.app`

---

### Variant B: Lokaalne kasutamine

**Käivita:**
```bash
cd elekter-optimeerija
streamlit run src/ui.py
```

**URL:** `http://localhost:8501`

---

## 📊 Edu mõõdikud (MVP)

| Mõõdik | Sihtmärk | Praegu |
|--------|----------|--------|
| Kasutajaid (beta) | 5 | 0 |
| Analüüse tehtud | 20 | 0 |
| Keskmine aeg CSV → tulemus | < 30 sek | - |
| Rahulolu skoor (1-5) | > 4.0 | - |
| Kriitilisi vigu | 0 | 0 |

---

## 🐛 Riskid ja leevendused

| Risk | Tõenäosus | Mõju | Leevendus |
|------|-----------|------|-----------|
| CSV formaat muutub | Keskmine | Kõrge | Testi erinevate CSV-dega |
| Börsihinnad aeguvad | Kõrge | Keskmine | Lisa hoiatus "viimati uuendatud" |
| Streamlit ei tööta | Madal | Kõrge | Test lokaalselt enne deploy |
| Kasutajad ei saa aru | Keskmine | Keskmine | Lisa selgitavad tekstid |

---

## 📝 Järgmised sammud (pärast MVP-d)

### v0.2 (detsember 2025):
- [ ] Graafikud (plotly)
- [ ] Eksport PDF-na
- [ ] Rohkem pakette (Elenger, Enefit)

### v1.0 (jaanuar 2026):
- [ ] Flask rakendus
- [ ] PostgreSQL andmebaas
- [ ] Kasutajakontod
- [ ] Riskianalüüs

---

## 🤝 Meeskond

**MVP arendus:**
- **Gert Roots** - Kõik (full-stack soolo)

**Beta testijad (vaja leida):**
- [ ] Testija 1 (mikrotootja)
- [ ] Testija 2 (tavaline tarbija)
- [ ] Testija 3 (soojuspumbaga)

---

## 📞 Kontakt

**Küsimused?**
- Email: gert@rilbaum.ee
- GitHub Issues: https://github.com/RILBAUM-IT/elekter-optimeerija/issues

---

**Viimati uuendatud:** 17.11.2025  
**Järgmine ülevaatus:** 24.11.2025
