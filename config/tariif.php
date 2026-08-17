<?php

return [

    /*
     * Vaikimisi võrgupakett avalikul lehel.
     * Gerdi otsus 18.08.2026: Võrk 2 (eramu), mitte Võrk 4 nagu vanas saidis —
     * Võrk 4 on energiamahuka kodu pakett ja teeb esimese numbri enamikule eksitavaks.
     */
    'default_package' => 'vork2',

    'default_connection_type' => 'main_fuse',
    'default_amperage' => 25,
    'default_phases' => 1,

    /*
     * Müüja marginaal senti/kWh.
     *
     * See on AINUS hinnanumber, mis on koodis — ja see on teadlik EELDUS, mida
     * kasutajale avalikult näidatakse ("tüüpiline eeldus, sinu leping võib
     * erineda"). Etapp 2-s asendab selle kasutaja enda number.
     *
     * NB: see EI ole tasakaalustamisvõimsuse kulu — see on reguleeritud tasu
     * andmebaasis (state_fees.balancing_capacity).
     */
    'assumed_supplier_margin_cents' => 0.40,

    /* Mitu tundi tohib viimane hinnarida vana olla, enne kui vaade hoiatab. */
    'stale_after_hours' => 3,
];
