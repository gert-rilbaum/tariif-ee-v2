"""
Calculator - Elektri- ja võrgupakettide hinnakalkulatsioon.

Vaata: docs/ARHITEKTUUR.md jaotis "3. Calculator"

See moodul vastutab hinnaarvutuste eest. Kõik valemid ja hinnad
laetakse JSON failidest (data/paketid.json, data/borsihinnad.json).
"""

from typing import Dict, Any


def arvuta_võrgukulu(kuud: Dict[str, Dict[str, Any]], pakett: Dict[str, Any]) -> Dict[str, float]:
    """
    Arvutab võrgukulu iga kuu kohta.

    Stateless funktsioon - kasutab ainult sisendparameetreid.

    Args:
        kuud: Kuupõhised andmed (analyzer väljund)
        pakett: Võrgupaketi info (data/paketid.json)

    Returns:
        Dict, kus võti on "YYYY-MM" ja väärtus on kulu eurodes

    Valem:
        kulu = kuutasu + (päev_kWh × päevahind) + (öö_kWh × ööhind)

    Vaata: docs/ARHITEKTUUR.md jaotis "3.1 Võrgupakett"
    """
    raise NotImplementedError("Võrgukulu arvutamine pole veel implementeeritud")


def arvuta_elektrikulu_borss(
    kuud: Dict[str, Dict[str, Any]],
    borsihinnad: Dict[str, Dict[str, Any]],
    pakett: Dict[str, Any]
) -> Dict[str, float]:
    """
    Arvutab elektrihinna börsipaketiga.

    Stateless funktsioon - kasutab ainult sisendparameetreid.

    Args:
        kuud: Kuupõhised andmed (analyzer väljund)
        borsihinnad: Börsihinnad kuude kaupa (data/borsihinnad.json)
        pakett: Elektripaketi info (data/paketid.json)

    Returns:
        Dict, kus võti on "YYYY-MM" ja väärtus on kulu eurodes

    Valem:
        kulu = kuutasu + päev_kWh × (börsihind_päev + marginaal) +
                         öö_kWh × (börsihind_öö + marginaal)

    Vaata: docs/ARHITEKTUUR.md jaotis "3.2 Elektripakett - Börss"
    """
    raise NotImplementedError("Börsipakett arvutamine pole veel implementeeritud")


def arvuta_elektrikulu_fiks(
    kuud: Dict[str, Dict[str, Any]],
    pakett: Dict[str, Any]
) -> Dict[str, float]:
    """
    Arvutab elektrihinna fikseeritud paketiga.

    Stateless funktsioon - kasutab ainult sisendparameetreid.

    Args:
        kuud: Kuupõhised andmed (analyzer väljund)
        pakett: Elektripaketi info (data/paketid.json)

    Returns:
        Dict, kus võti on "YYYY-MM" ja väärtus on kulu eurodes

    Valem:
        kulu = kuutasu + (päev_kWh × päevahind) + (öö_kWh × ööhind)

    Vaata: docs/ARHITEKTUUR.md jaotis "3.3 Elektripakett - Kindel"
    """
    raise NotImplementedError("Fikseeritud pakett arvutamine pole veel implementeeritud")
