<?php

namespace Database\Seeders;

use App\Models\StateFee;
use Illuminate\Database\Seeder;

/**
 * Riiklikud tasud senti/kWh, KÄIBEMAKSUTA.
 *
 * KÕIK numbrid on kontrollitud allikast 18.08.2026. Ükski väärtus ei tohi
 * tulla mälust ega arvutusest — vt plaani Task 10.
 *
 * Allikad:
 *  - Elektrilevi võrguteenuse hinnakiri (kehtib 01.06.2026), lk 7
 *    "Riiklikud tasud ja maksud": taastuvenergia 0,84 · elektriaktsiis 0,21 ·
 *    varustuskindluse tasu 0,758 (kõik KM-ta)
 *  - Elering, tasakaalustamisvõimsuse kulu: 3,73 €/MWh KM-ta 2026, 2,97 €/MWh 2027
 */
class StateFeeSeeder extends Seeder
{
    private const HINNAKIRI = 'https://public-docs.elektrilevi.ee/2/7/vorguteenuse_kehtiv_hinnakiri_71ed51b53d.pdf';

    private const ELERING_TASAKAALUSTAMINE = 'https://elering.ee/tasakaalustamisvoimsuse-kulu';

    public function run(): void
    {
        $now = now();

        $read = [
            // Taastuvenergia tasu — 2026. aastal ei muutunud
            ['code' => 'renewable', 'valid_from' => '2026-01-01', 'valid_to' => null,
                'cents_per_kwh' => 0.8400, 'source_url' => self::HINNAKIRI],

            // Elektriaktsiis 2,1 €/MWh. NB: EMTA teatas 01.05.2026 tõusu 3,07-le,
            // aga Elektrilevi 01.06.2026 hinnakirjas on endiselt 0,21 — arvel on see
            ['code' => 'excise', 'valid_from' => '2025-05-01', 'valid_to' => null,
                'cents_per_kwh' => 0.2100, 'source_url' => self::HINNAKIRI],

            // Varustuskindluse tasu — uus tasu alates 01.01.2026
            ['code' => 'supply_security', 'valid_from' => '2026-01-01', 'valid_to' => null,
                'cents_per_kwh' => 0.7580, 'source_url' => self::HINNAKIRI],

            // Tasakaalustamisvõimsuse kulu — müüja arvel eraldi reana
            ['code' => 'balancing_capacity', 'valid_from' => '2026-01-01', 'valid_to' => '2026-12-31',
                'cents_per_kwh' => 0.3730, 'source_url' => self::ELERING_TASAKAALUSTAMINE],
            ['code' => 'balancing_capacity', 'valid_from' => '2027-01-01', 'valid_to' => null,
                'cents_per_kwh' => 0.2970, 'source_url' => self::ELERING_TASAKAALUSTAMINE],
        ];

        foreach ($read as $rida) {
            StateFee::updateOrCreate(
                ['code' => $rida['code'], 'valid_from' => $rida['valid_from']],
                [...$rida, 'verified_at' => $now],
            );
        }
    }
}
