# Terminoloogia ja mõisted

**Viimati uuendatud:** 17.11.2025

---

## 📚 Põhimõisted

### Elektrituru osapooled

**Elektrimüüja**  
Ettevõte, kes müüb elektrienergia lõpptarbijale. Elektrimüüja ostab elektri börsi(lt (Nord Pool) ja müüb edasi kliendile lisades oma marginaali.

**Võrguettevõte / Jaotusvõrguettevõte**  
Ettevõte, kes omab ja haldab elektrivõrku ning toob elektri tarbija liitumispunktini. Eestis suurim: Elektrilevi OÜ (88% turuosast).

**Süsteemihaldur**  
Elering AS - vastutab kogu Eesti elektrisüsteemi toimimise eest ja haldab põhivõrku (110-330 kV).

**Nord Pool**  
Põhjamaade ja Baltimaade ühine elektribörss, kus kaubeldakse elektrit päev ette (day-ahead market). Hind määratakse iga tunni kohta eraldi.

---

## 💡 Elektripakettide tüübid

### 1. Börsipakett (muutuv hind)

**Definitsioon:**  
Elektri hind järgib Nord Pool börsi hinda + elektrimüüja marginaal + kuutasu.

**Valem:**
```
Hind = Börsihind + Marginaal + Kuutasu
```

**Näide:**  
Alexela Börsipakett
- Marginaal: 0,457 s/kWh
- Kuutasu: 1,99 €
- Börsihind (november 2024): 8,25 s/kWh keskmine
- Kokku: (8,25 + 0,457) × tarbimine + 1,99€

**Plussid:**
- ✅ Odav, kui börs on madal
- ✅ Paindlik (tähtajatu)

**Miinused:**
- ⚠️ Risk - hind võib tõusta
- ⚠️ Ettearvamatu

---

### 2. Fikseeritud pakett (kindel hind)

**Definitsioon:**  
Elektri hind on fikseeritud kogu lepingu perioodiks (tavaliselt 6-36 kuud).

**Valem:**
```
Hind = Fikseeritud hind (päev/öö) + Kuutasu
```

**Näide:**  
Alexela Tähtajaline fikseeritud 9 kuud
- Päevahind: 12,4 s/kWh
- Ööhind: 10,4 s/kWh  
- Kuutasu: 1,50 €
- Lepingu kestus: 9 kuud

**Plussid:**
- ✅ Ennustatav (teadvõimalik täpne kulu)
- ✅ Kaitse börsi tõusu eest

**Miinused:**
- ⚠️ Kallim, kui börs jääb madalaks
- ⚠️ Katkestamistasu (sageli)
- ⚠️ Tähtajaline (3-36 kuud)

---

### 3. Hübriidpakett

**Definitsioon:**  
Kombinatsioon börsi ja fikseeritud hinnast. Näiteks "Vali ise" - saab igakuiselt valida börsi või fiksi vahel.

**Näide:**  
Alexela "Vali ise"
- Võimalus valida börss VÕI fiks igakuiselt
- Marginaal: 0,457 s/kWh
- Kuutasu: 2,02 €

---

### 4. Marginaalivaba pakett

**Definitsioon:**  
Börsipakett, kus elektrimüüja ei lisa marginaali. Klient maksab ainult puhas börsihind + kuutasu.

**Näide:**  
Sunly Marginaalivaba
- Marginaal: 0 s/kWh
- Kuutasu: ~3-6 € (sõltub tarbimisest)
- Hind: Puhas börsihind

**Erinevus tavalisest börsipaketist:**
```
Tavaline börss: Börs 10s + marginaal 0,5s = 10,5s
Marginaalivaba: Börs 10s + 0s = 10s
Aga kuutasu on kõrgem!
```

---

## 🔌 Võrgupakettide tüübid (Elektrilevi)

### Võrk 1
- **Kuutasu:** 0,57 €
- **Päevahind:** 7,9 s/kWh
- **Ööhind:** 7,9 s/kWh
- **Sobib:** Väga väike tarbimine (<75 kWh/kuu)

### Võrk 2
- **Kuutasu:** 1,81 €
- **Päevahind:** 3,8 s/kWh
- **Ööhind:** 3,8 s/kWh
- **Sobib:** Väike tarbimine (75-150 kWh/kuu)

### Võrk 4
- **Kuutasu:** 9,17 €
- **Päevahind:** 3,69 s/kWh  
- **Ööhind:** 2,1 s/kWh
- **Sobib:** Keskmine ja suur tarbimine (>200 kWh/kuu)

### Võrk 5 (tiputarbimine)
- **Kuutasu:** 0 €
- **Päevahind:** 4,07 s/kWh
- **Ööhind:** 2,09 s/kWh
- **Tipuhind (talvel 9-12, 16-20):** 12,69 s/kWh
- **Sobib:** Tarbijad, kes suudavad vältida tipptunde

**Oluline:** Hinnad sõltuvad **ampritest** (3x16A, 3x25A, 3x40A jne). Meie süsteemis eeldame 3x25A (kõige levinum).

---

## ⏰ Ajavööndid

### Päev (Peak / Day)
**Kellaaeg:** 07:00 - 21:59  
**Kirjeldus:** Kõrgem elektritarbimine → kõrgem hind

### Öö (Off-peak / Night)  
**Kellaaeg:** 22:00 - 06:59  
**Kirjeldus:** Madalam elektritarbimine → madalam hind

**Erand: Tiputund (ainult Võrk 5)**  
- **Kellaaeg:** E-R kl 09-12 ja 16-20 (nov-märts)
- **Kirjeldus:** Kõige kallim periood talvel

---

## 📊 Hinna komponendid

### Elektrimüüja arvel

```
Elektrimüüja arve = Elektrienergia + (Taastuvenergiatasu) + (KM)
```

**Elektrienergia:**
- Börsipakett: Börsihind + marginaal
- Fikseeritud: Fikseeritud hind

**Taastuvenergiatasu:**
- Põhimõte: Riik toetab taastuvenergia tootjaid
- Lisandub kõikidele pakettidele
- 2024-2025: ~4-5 s/kWh
- Märkus: Mõned müüjad näitavad "käibemaksuvaba miinimum", mis tühistab selle (nt Alexela)

**Käibemaks:**
- 2024 (kuni 30.06): 22%
- 2025 (alates 01.07): 24%

---

### Võrguettevõtte arvel

```
Võrguarve = Võrgutasu (päev + öö) + Muud tasud + KM
```

**Võrgutasu:**
- Kuutasu + (Päevakulu × päevahind) + (Öökulu × ööhind)

**Muud tasud:**
- Kohaliku omavalitsuse tasu
- Mõõdiku teenustasu (kui rakendub)
- jne

---

## 🏭 Eripärad

### Mikrotootja (päikesepaneelidega tarbija)

**Definitsioon:**  
Tarbija, kes toodab osa elektrist ise (nt päikesepaneelid) ja müüb ülejäägi võrku.

**CSV näide:**
```
Kuu       Import (ost)  Eksport (müük)  Neto
Mai       477 kWh       625 kWh         -148 kWh (TOOTIS rohkem!)
Detsember 1423 kWh      0 kWh           +1423 kWh
```

**Võrgutasu:** Arvutatakse **importi** pealt (mitte neto!).

**Elektrimüüja:**
- Börsipakett: Maksab ainult ostud
- Tagasiost: Mõned müüjad ostavad ülejäägi tagasi (nt Sunly)

---

### Soojuspumbaga tarbija

**Tunnus:**  
- Talvine tarbimine 3-5x kõrgem kui suvel
- Suur öötarbimine (soojuspump töötab öösel)

**Sobib:**
- Võrk 4 (madal ööhind!)
- Börsipakett talvel (kui julgeb) VÕI kindel (kui kardab tõusu)

---

### Elektriautoga tarbija

**Tunnus:**
- Suur öötarbimine (laeb auto öösel)
- Võimalik "tark laadimine" (laeb odavatel tundidel)

**Sobib:**
- Võrk 4 (madal ööhind)
- Börsipakett + nutik laadija (laeb siis, kui börs madal)

---

## 🧮 Arvutused

### Kuidas arvutada võrgutasu?

**Võrk 4 näide (November):**
```
Tarbimine:
- Päev: 266 kWh
- Öö: 770 kWh

Arvutus:
Kuutasu:      9,17 €
Päev:     266 × 0,0369 = 9,82 €
Öö:       770 × 0,021  = 16,17 €
─────────────────────────────
KOKKU:                   35,16 €
```

---

### Kuidas arvutada börsipakett?

**Alexela Börss näide (November):**
```
Tarbimine:
- Päev: 266 kWh
- Öö: 770 kWh

Börsihind (november):
- Päev: 13,3 s/kWh
- Öö: 4,68 s/kWh

Arvutus:
Kuutasu:          1,99 €
Päev: 266 × (0,133 + 0,00457) = 36,60 €
Öö:   770 × (0,0468 + 0,00457) = 39,56 €
────────────────────────────────────
KOKKU:                             78,15 €
```

---

### Kuidas arvutada fikseeritud pakett?

**Alexela Kindel näide (November):**
```
Tarbimine:
- Päev: 266 kWh
- Öö: 770 kWh

Hinnad:
- Päev: 12,4 s/kWh
- Öö: 10,4 s/kWh

Arvutus:
Kuutasu:     1,50 €
Päev: 266 × 0,124 = 32,98 €
Öö:   770 × 0,104 = 80,08 €
────────────────────────
KOKKU:             114,56 €
```

---

## 📐 Ühikud ja konversioonid

### Elektrienergia ühikud

| Ühik | Kirjeldus | Suhe |
|------|-----------|------|
| Wh | Vatt-tund | Baasühik |
| kWh | Kilovatt-tund | 1 kWh = 1000 Wh |
| MWh | Megavatt-tund | 1 MWh = 1000 kWh |

**Näited:**
- LED lamp 10W × 100h = 1 kWh
- Pesumasin üks pesu ~1-2 kWh
- Elektriautob täislaadimine ~50-80 kWh
- Keskmine Eesti kodu ~250-400 kWh/kuu

---

### Hinna ühikud

| Ühik | Kasutus | Konversioon |
|------|---------|-------------|
| s/kWh | Senti kilovatt-tunni kohta | 1 s = 0,01 € |
| €/kWh | Eurot kilovatt-tunni kohta | 1 €/kWh = 100 s/kWh |
| €/MWh | Eurot megavatt-tunni kohta (börs) | 100 €/MWh = 10 s/kWh |

**Näide:**
- Börsihind: 82,5 €/MWh = 8,25 s/kWh = 0,0825 €/kWh

---

## 🔑 Lühendid

| Lühend | Täispikk | Selgitus |
|--------|----------|----------|
| **kWh** | Kilovatt-tund | Elektrienergia mõõtühik |
| **MWh** | Megavatt-tund | 1000 kWh |
| **EIC** | Energy Identification Code | Tarbimiskoha unikaalne kood |
| **AVP** | Avatud andmevahetus platvormi | Elering andmete keskkond |
| **KM** | Käibemaks | 22% (2024) → 24% (2025) |
| **ÜT** | Üldteenus | Võrguettevõtte pakutav elekter (kallim!) |

---

## 🎓 Kasulikud valemid

### 1. Võrgupakett odavam/kallim?

**Murdepunkt Võrk 2 vs Võrk 4:**

```
Võrk 2 kulu = 1,81 + tarbimine × 0,038
Võrk 4 kulu = 9,17 + tarbimine × 0,021

Murdepunkt:
1,81 + X × 0,038 = 9,17 + X × 0,021
X × (0,038 - 0,021) = 9,17 - 1,81
X = 7,36 / 0,017
X ≈ 433 kWh

Järeldus:
- Kui tarbimine < 433 kWh → Võrk 2 odavam
- Kui tarbimine > 433 kWh → Võrk 4 odavam
```

---

### 2. Börss vs Kindel?

**Murdepunkt:**

```
Börss = (börs + marginaal) × tarbimine + kuutasu
Kindel = fiks_hind × tarbimine + kuutasu

Kui börs < (fiks_hind - marginaal):
  → Börss odavam
Muidu:
  → Kindel odavam
```

**Näide (öötarbimine 770 kWh):**
```
Börsiöö: 4,68 s + 0,457s = 5,137 s/kWh
Fiksöö: 10,4 s/kWh

5,137 < 10,4 → BÖRSS ODAVAM!
```

---

## ❓ KKK (Korduma kippuvad küsimused)

**Q: Miks börsipakett on talvel odavam kui kindel, kui 2023 talvel oli börs 100s+?**  
**A:** 2024/2025 talv oli rahulikum. Aga risk on alati olemas! Seepärast mõned valivad kindla paketi = kindlustus.

**Q: Kas ma saan kuutasu tagasi, kui ma ei tarbi?**  
**A:** Ei. Kuutasu on fikseeritud, sõltumata tarbimisest.

**Q: Miks Võrk 4 ööhind on odavam kui päevahind?**  
**A:** Soodustab öötarbimist (soojuspumbad, elektriautod) → vähem koormus päeval.

**Q: Kas ma pean võrgupaketti vahetades teavitama elektrimüüjat?**  
**A:** Ei. Võrgupakett ja elektripakett on eraldi lepingud.

**Q: Kui tihti saan võrgupaketti vahetada?**  
**A:** 1 kord kuus. Muudatus kehtib järgmise kuu 1. kuupäevast.

---

**Küsimused või täiendused?**  
Lisa issue: https://github.com/RILBAUM-IT/elekter-optimeerija/issues  
Või saada email: gert@rilbaum.ee
