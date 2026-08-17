<?php

namespace App\Domain\Pricing;

use App\Models\GridPackageVersion;
use App\Models\StateFee;
use App\Models\VatRate;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Elektri lõpphinna arvutus.
 *
 * Puhas: loeb andmebaasist tariife, aga ei kutsu ühtegi välist teenust ega
 * kirjuta midagi. Kogu arvutus on siin ühes kohas ja testitav — vana tariif.ee
 * arvutas brauseris JS-is, mistõttu numbrid vananesid märkamatult (spec §2).
 *
 * Iga puuduv komponent VISKAB ERINDI. Poolik hind on halvem kui puuduv hind.
 */
class PriceCalculator
{
    public function __construct(private readonly RateResolver $resolver) {}

    public function forInstant(float $spotCentsPerKwh, CarbonImmutable $instant, ContractContext $ctx): PriceBreakdown
    {
        $rateKind = $this->resolver->resolve($ctx->package, $instant);
        $version = $this->versionAt($instant, $ctx);
        $version->loadMissing('energyRates');

        $gridEnergy = $version->rateFor($rateKind);
        $fees = StateFee::activeAt($instant);

        $renewable = $this->requireFee($fees, 'renewable', $instant);
        $supplySecurity = $this->requireFee($fees, 'supply_security', $instant);
        $excise = $this->requireFee($fees, 'excise', $instant);

        $subtotal = $spotCentsPerKwh
            + $ctx->supplierMarginCentsPerKwh
            + $gridEnergy
            + $renewable
            + $supplySecurity
            + $excise;

        $vat = $ctx->vatApplicable ? $subtotal * VatRate::atMoment($instant) : 0.0;

        return new PriceBreakdown(
            spot: $spotCentsPerKwh,
            supplierMargin: $ctx->supplierMarginCentsPerKwh,
            gridEnergy: $gridEnergy,
            renewable: $renewable,
            supplySecurity: $supplySecurity,
            excise: $excise,
            subtotalExVat: $subtotal,
            vat: $vat,
            totalIncVat: $subtotal + $vat,
            rateKind: $rateKind,
        );
    }

    /**
     * Püsikulud eurodes kuus: võrgu kuutasu + läbilaskevõime (ampri) tasu.
     *
     * Need EI ole senti/kWh. Paketivõrdlus ilma nendeta annab vale vastuse:
     * Võrk 4 näib odav, kuni arvestad tema kõrget kuutasu.
     *
     * @return array{ex_vat: float, inc_vat: float, base_monthly: float, capacity: float}
     */
    public function fixedMonthlyCost(CarbonImmutable $instant, ContractContext $ctx): array
    {
        $version = $this->versionAt($instant, $ctx);
        $version->loadMissing('capacityFees');

        $base = (float) $version->base_monthly_eur;
        $capacity = $version->capacityFeeFor($ctx->amperage, $ctx->phases);
        $exVat = $base + $capacity;
        $vat = $ctx->vatApplicable ? $exVat * VatRate::atMoment($instant) : 0.0;

        return [
            'base_monthly' => $base,
            'capacity' => $capacity,
            'ex_vat' => $exVat,
            'inc_vat' => $exVat + $vat,
        ];
    }

    private function versionAt(CarbonImmutable $instant, ContractContext $ctx): GridPackageVersion
    {
        $version = $ctx->package->versionAt($instant);

        if (! $version) {
            throw new \RuntimeException(sprintf(
                "Võrgupaketil '%s' puudub kehtiv hinnaversioon kuupäeval %s",
                $ctx->package->code,
                $instant->setTimezone('Europe/Tallinn')->toDateString(),
            ));
        }

        return $version;
    }

    /** @param Collection<string, float> $fees */
    private function requireFee(Collection $fees, string $code, CarbonImmutable $instant): float
    {
        $fee = $fees->get($code);

        if ($fee === null) {
            throw new \RuntimeException(sprintf(
                "Riiklik tasu '%s' puudub kuupäeval %s",
                $code,
                $instant->setTimezone('Europe/Tallinn')->toDateString(),
            ));
        }

        return $fee;
    }
}
