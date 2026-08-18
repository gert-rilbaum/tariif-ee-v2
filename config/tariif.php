<?php

return [

    /*
     * Vaikimisi võrgupakett avalikul lehel.
     * Gerdi otsus 18.08.2026: Võrk 2 (eramu), mitte Võrk 4 nagu vanas saidis —
     * Võrk 4 on energiamahuka kodu pakett ja teeb esimese numbri enamikule eksitavaks.
     */
    'default_package' => 'vork2',

    /*
     * Paketipõhine vaikeühendus. Iga pakett on mõeldud eri tarbijale, seega on
     * ka tüüpiline peakaitse eri suurusega.
     *
     * NB: Elektrilevi hinnakirjas EI OLE 10 A rida — väikseim on "kuni 16 A".
     * Võrk 1 puhul on täpsem korteri kuutasu, sest paketileht ütleb "Korter •
     * Väike maja" ja korteri rida kehtib majadele, kus peakaitsme jaotatud osa
     * on kuni 16 A.
     *
     * Kasutaja saab peakaitset vaates muuta — need on ainult lähtepunktid.
     */
    'package_defaults' => [
        'vork1' => ['connection_type' => 'apartment', 'amperage' => 16],
        'vork2' => ['connection_type' => 'main_fuse', 'amperage' => 16],
        'vork4' => ['connection_type' => 'main_fuse', 'amperage' => 25],
    ],

    'fallback_connection' => ['connection_type' => 'main_fuse', 'amperage' => 25],

    'default_phases' => 1,

    /*
     * Müüja marginaal senti/kWh.
     *
     * See on AINUS hinnanumber, mis on koodis — ja see on teadlik EELDUS, mida
     * kasutajale avalikult näidatakse ("tüüpiline eeldus, sinu leping võib
     * erineda"). Etapp 2-s asendab selle kasutaja enda number.
     *
     * Suurusjärku kinnitab päris arve: Alexela börsipaketi marginaal on
     * 0,457 senti/kWh. Müüjal on lisaks kuutasu (arvel 1,63 €), mida meie
     * püsikulu praegu EI sisalda — see on teadlik puudujääk, vt BACKLOG.
     */
    'assumed_supplier_margin_cents' => 0.40,

    /* Mitu tundi tohib viimane hinnarida vana olla, enne kui vaade hoiatab. */
    'stale_after_hours' => 3,

    /*
     * Kuidas rakendus end välistele allikatele tutvustab.
     *
     * Ei ole kosmeetika: Elektrilevi CDN (Cloudflare) vastab Guzzle vaikimisi
     * UA-le "GuzzleHttp/7" koodiga 429 ja allikavalve jäi seetõttu pimedaks,
     * ilma et miski oleks katki paistnud. Tõestatud serverist 18.08.2026 —
     * sama URL, ainult UA erines: GuzzleHttp/7 -> 429, tariif.ee/2.0 -> 200.
     *
     * Kontaktaadress URL-is on tahtlik: kui meie päringud kellelegi tüli
     * teevad, peab olema võimalik meid leida ilma logisid uurimata.
     */
    'user_agent' => 'tariif.ee/2.0 (+https://tariif.ee)',
];
