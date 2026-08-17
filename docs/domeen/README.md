# Domeeniteadmus — päritud materjal

Need failid **ei ole selle rakenduse kood**. Need on varasemast projektist
`gert-rilbaum/Elektriportaal` (Python MVP, november 2025) üle toodud
domeeniteadmus, mida ei tasu teist korda koguda.

## Mida siit kasutada

| Fail | Väärtus | Usaldusaste |
|---|---|---|
| `ANDMEALLIKAD.md` | **Elektrilevi CSV täpne vorming** — semikoolon, koma kümnendkohana, UTF-8-BOM, päiseread, päev/öö juba märgitud, max ~6 kuud eksporti korraga | Kõrge — kirjeldatud päris failide põhjal. Etapp 4 alus |
| `MUUJATE_INFO.md` | Müüjate hinnastusmudelid ja mõisted | Keskmine — kontrolli enne kasutamist |
| `TERMINOLOOGIA.md` | Eestikeelne terminoloogia (marginaal, ühisarve, ampritasu jne) | Kõrge — sõnavara, mitte numbrid |
| `paketid.json` | 8 Alexela müüjapaketi **struktuur**: börsimarginaal, fikseeritud päev/öö hind, kuutasu, lepingu kestus, katkestamistasu | Struktuur kõrge, **numbrid aegunud** (`kehtib_alates: 2024-11-01`) |
| `borsihinnad.json` | Alexela kuu kaalutud keskmised börsihinnad | Ajalooline võrdlusmaterjal |
| `python-mvp/` | `csv_loader.py`, `calculator.py`, `comparator.py` — töötav tarbimispõhine paketivõrdlus | Loogika väärtuslik, **kood ei migreeru** |

## Mida siit MITTE kasutada

**Ühtegi numbrit ei tohi siit otse andmebaasi seemendada.** Võrgupakettide hinnad
selles failis on 2024. aasta omad ja ei ühti tänase Elektrilevi hinnakirjaga —
näiteks `paketid.json` Võrk 4 kuutasu on `9.17 €`, tänane hinnakiri on oluliselt
kõrgem. Seemneandmed tulevad **alati** allikast koos `source_url` ja
`verified_at` väljadega (vt plaani Task 10).

## Miks see siin on

Vana Python MVP lahendas juba probleemi, mis meil on etapp 4: loe kasutaja
tarbimine CSV-st ja võrdle pakette. Selle loogika ja eriti **CSV-vormingu
teadmus** säästab etapp 4-s päevi. Kood ise on Pythonis ja jääb siia
referentsiks, mitte kasutusse.

Algne repo: <https://github.com/gert-rilbaum/Elektriportaal>
Üle toodud: 18.08.2026
