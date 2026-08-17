# BACKLOG

Asjad, mis on teadlikult tegemata. Iga kirje ütleb, **kust see teadmine tuli** —
nii ei pea hiljem arvama, kas see oli oletus või tähelepanek.

---

## Päris arvest tulnud puudujäägid

Allikas: Eesti kodutarbija elektriarve, arveldusperiood 07/2026 (Alexela ühisarve,
Elektrilevi Võrk 4, 20 A). Arve kinnitas **kõik seitse seemendatud tariifi
sendini** — vt `tests/Feature/Seeders/RealInvoiceSeedTest.php`. Aga ta näitas ka
kolme asja, mida meie mudel praegu ei kata.

### 1. Müüja kuutasu puudub püsikulust

Arvel on müüja plokis rida **„Kuutasu 1 tk × 1,63 €"**. Meie `fixedMonthlyCost()`
arvestab ainult võrgu kuutasu (20,64 €). Kasutaja tegelik püsikulu oli seega
22,27 €, meie näitaksime 20,64 €.

Miks praegu nii: müüjapaketid ei ole veel andmemudelis (vt spec §5.5). Kuutasu
suurus sõltub paketist — Alexela börsipaketil 1,99 €, Kodupaketil 5,99 €.

**Lahendus:** etapp 2, kus kasutaja sisestab oma lepingu — sinna kuulub ka
müüja kuutasu väli.

### 2. Mikrotootmine ei ole modelleeritud

Arve sisaldab:
- „Toodetud elektrienergia börsihinnaga −609,95 kWh × 0,00988 €/kWh = −6,03 €"
- „Tootmise tasakaalustamisvõimsuse kulu 609,95 kWh × 0,00373 €/kWh = 2,28 €"
- märkuse „Ei ole käive −6,03 €" (eraisiku müügitulu ei ole käive)

Ehk päikesepaneelidega majapidamine **müüb võrku tagasi** ja maksab selle pealt
eraldi tasakaalustamistasu. Meie mudel tunneb ainult tarbimist.

**Mõju:** etapp 4 paketivõrdlus oleks tootjatele vale, kui seda ei arvesta.
Vajab eraldi spec-i: tootmise hind, tootmise tasakaalustamistasu, KM-käsitlus.

### 3. Müüja börsihind on kuu kaalutud keskmine, mitte tunnihind

Arvel: „Börsihinnaga elekter (päevaajal) 245,557 kWh × 0,05001 €/kWh". See on
**kuu kaalutud keskmine** päevatundide lõikes, mitte tunnipõhine arveldus.

Meie näitame tunnihinda, mis on õige „mis praegu maksab" jaoks, aga etapp 3
tarbimisimpordis tuleb teada, kas müüja arveldab tunni- või kuupõhiselt —
muidu ei klapi meie arvutatud kuusumma arve omaga.

---

## Muud teadlikud väljajätted

- **Võrk 5** (tiputunni pakett) — skeem toetab (`dual_peak`, `peak` /
  `weekend_peak`), seemet ja UI-d ei ole. Gerdi otsus 18.08.2026.
- **Ajaloolised hinnaversioonid** — seemendatud on ainult kehtiv Elektrilevi
  hinnakiri (alates 01.06.2026). Varasema kuupäeva vaatamine viskab ausa vea
  „puudub kehtiv hinnaversioon". Ajaloo seemendamine on eraldi töö.
- **Teised võrguettevõtjad** (Imatra, VKG) — mudel toetab, andmeid ei ole.
- **Kolmefaasiline peakaitse** — hinnakirjas eristust ei ole (madalpingel kuni
  63 A on üks rida peakaitsme kohta), seega `phases` on praegu alati 1.
