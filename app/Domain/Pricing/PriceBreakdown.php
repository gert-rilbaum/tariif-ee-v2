<?php

namespace App\Domain\Pricing;

/**
 * Ühe hetke hinnakomponendid, senti/kWh.
 *
 * Kõik väljad on KM-ta, välja arvatud $vat ja $totalIncVat.
 * Püsikulud (kuutasu, ampritasu) EI ole siin — need ei ole senti/kWh
 * ja nende liitmine energiahinnale annaks vale numbri (spec §6).
 */
final readonly class PriceBreakdown
{
    public function __construct(
        public float $spot,
        public float $supplierMargin,
        public float $gridEnergy,
        public float $renewable,
        public float $supplySecurity,
        public float $excise,
        public float $balancingCapacity,
        public float $subtotalExVat,
        public float $vat,
        public float $totalIncVat,
        public string $rateKind,
    ) {}

    /** @return array<string, float|string> */
    public function toArray(): array
    {
        return [
            'spot' => round($this->spot, 4),
            'supplier_margin' => round($this->supplierMargin, 4),
            'grid_energy' => round($this->gridEnergy, 4),
            'renewable' => round($this->renewable, 4),
            'supply_security' => round($this->supplySecurity, 4),
            'excise' => round($this->excise, 4),
            'balancing_capacity' => round($this->balancingCapacity, 4),
            'subtotal_ex_vat' => round($this->subtotalExVat, 4),
            'vat' => round($this->vat, 4),
            'total_inc_vat' => round($this->totalIncVat, 4),
            'rate_kind' => $this->rateKind,
        ];
    }
}
