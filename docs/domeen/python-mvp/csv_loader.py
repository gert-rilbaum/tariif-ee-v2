"""
CSV Loader - Elektrilevi tarbimisandmete lugemine.

Vaata: docs/ARHITEKTUUR.md jaotis "1. CSV Loader"

See moodul vastutab Elektrilevi CSV failide laadimise ja parseerimise eest.
Toetab UTF-8-BOM encoding'ut, koma→punkt konversiooni ja päev/öö tuvastust.
"""

from typing import List, Dict, Any, IO
from datetime import datetime


def lae_elektrilevi_csv(csv_file: IO[str]) -> List[Dict[str, Any]]:
    """
    Loeb ja parseerib Elektrilevi CSV faili.

    Stateless funktsioon - ei salvesta midagi, tagastab ainult parsed andmed.

    Args:
        csv_file: Avatud CSV fail (file handle)

    Returns:
        List of dicts, kus iga dict sisaldab:
        - aeg (datetime): Kuupäev ja kellaaeg
        - import (float): Tarbimine kWh
        - eksport (float): Võrku andmine kWh
        - tsooni (str): "Päev" või "Öö"

    Raises:
        ValueError: Kui CSV formaat on vale

    Vaata: docs/ARHITEKTUUR.md jaotis "1. CSV Loader"
    """
    raise NotImplementedError("CSV loader pole veel implementeeritud")
