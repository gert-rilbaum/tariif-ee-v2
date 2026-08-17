<?php

namespace Database\Seeders;

use App\Models\VatRate;
use Illuminate\Database\Seeder;

/**
 * Käibemaksumäära ajalugu.
 *
 * Allikas: Maksu- ja Tolliamet, käibemaksumäärad
 * https://www.emta.ee/en/business-client/taxes-and-payment/value-added-tax/vat-rates-and-supply-exempt-tax
 *
 * Kontrollkäik 18.08.2026: Elektrilevi hinnakirja KM-ga ja KM-ta veerud
 * suhtuvad täpselt teguriga 1,24 (0,84 → 1,04 · 0,21 → 0,26 · 0,758 → 0,94),
 * mis kinnitab kehtivat 24% määra.
 */
class VatRateSeeder extends Seeder
{
    private const ALLIKAS = 'https://www.emta.ee/en/business-client/taxes-and-payment/value-added-tax/vat-rates-and-supply-exempt-tax';

    public function run(): void
    {
        $now = now();

        $maarad = [
            ['valid_from' => '2009-07-01', 'valid_to' => '2023-12-31', 'rate' => 0.2000],
            ['valid_from' => '2024-01-01', 'valid_to' => '2025-06-30', 'rate' => 0.2200],
            ['valid_from' => '2025-07-01', 'valid_to' => null, 'rate' => 0.2400],
        ];

        foreach ($maarad as $maar) {
            VatRate::updateOrCreate(
                ['valid_from' => $maar['valid_from']],
                [...$maar, 'source_url' => self::ALLIKAS, 'verified_at' => $now],
            );
        }
    }
}
