"""
UI - Kasutajaliides (CLI demo ja Streamlit).

Vaata: docs/MVP_PLAAN.md jaotis "2. Streamlit UI (MVP)"

See moodul sisaldab kasutajaliidese komponente.
MVP faasis on Streamlit UI, hiljem tuleb Flask/Next.js.
"""


def run_cli_demo() -> None:
    """
    Käivitab lihtsa CLI demo versiooni.

    Kasulik testimiseks ja debugimiseks enne Streamlit UI valmimist.

    Näide:
        $ python -m src.ui
        > CSV faili nimi: data/example.csv
        > Analüüsin...
        > Tulemus: ...
    """
    print("CLI demo pole veel implementeeritud")
    print("Vaata: docs/MVP_PLAAN.md jaotis '2. Streamlit UI'")
    raise NotImplementedError("CLI demo pole veel valmis")


def run_streamlit() -> None:
    """
    Käivitab Streamlit UI.

    Streamlit on MVP jaoks peamine kasutajaliides.

    Käivitamine:
        $ streamlit run src/ui.py

    Vaata: docs/MVP_PLAAN.md jaotis "2. Streamlit UI (MVP)"
    """
    print("Streamlit UI pole veel implementeeritud")
    print("Vaata: docs/MVP_PLAAN.md jaotis '2. Streamlit UI'")
    raise NotImplementedError("Streamlit UI pole veel valmis")


if __name__ == "__main__":
    # Kui käivitatakse otse, näita infot
    print("=" * 60)
    print("Elektritarbimise Optimeerija - UI")
    print("=" * 60)
    print()
    print("Praegu pole UI veel valmis.")
    print()
    print("Kui UI valmis, käivita:")
    print("  CLI demo:  python -m src.ui")
    print("  Streamlit: streamlit run src/ui.py")
    print()
    print("Vaata: docs/MVP_PLAAN.md")
    print("=" * 60)
