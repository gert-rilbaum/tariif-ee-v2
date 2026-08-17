<?php

namespace App\Domain\Pricing;

use App\Models\GridPackage;

/**
 * Kasutaja lepingu eeldused ühe arvutuse jaoks.
 *
 * Etapp 1-s tulevad need konfist ja on kasutajale NÄHTAVAD eeldused
 * (eriti müüja marginaal). Etapp 2-s sisestab kasutaja omad.
 */
final readonly class ContractContext
{
    public function __construct(
        public GridPackage $package,
        public float $supplierMarginCentsPerKwh,
        public int $amperage,
        public int $phases = 1,
        public string $connectionType = 'main_fuse',
        public bool $vatApplicable = true,
    ) {}
}
