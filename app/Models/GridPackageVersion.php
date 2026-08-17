<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GridPackageVersion extends Model
{
    protected $fillable = [
        'package_id', 'valid_from', 'valid_to', 'base_monthly_eur',
        'source_url', 'approval_ref', 'verified_at',
    ];

    protected $casts = [
        'valid_from' => 'immutable_date',
        'valid_to' => 'immutable_date',
        'base_monthly_eur' => 'float',
        'verified_at' => 'immutable_datetime',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(GridPackage::class, 'package_id');
    }

    public function energyRates(): HasMany
    {
        return $this->hasMany(GridEnergyRate::class, 'version_id');
    }

    public function capacityFees(): HasMany
    {
        return $this->hasMany(GridCapacityFee::class, 'version_id');
    }

    /**
     * Võrgutasu senti/kWh antud tariifiliigi kohta (KM-ta).
     *
     * @throws \RuntimeException kui liiki ei ole — pakett ei tohi vaikselt
     *                           tagastada nulli ega mõne teise liigi hinda
     */
    public function rateFor(string $rateKind): float
    {
        $rate = $this->energyRates->firstWhere('rate_kind', $rateKind);

        if (! $rate) {
            throw new \RuntimeException(
                "Võrgupaketi versioonil {$this->id} puudub tariif '{$rateKind}'"
            );
        }

        return (float) $rate->cents_per_kwh;
    }

    /**
     * Läbilaskevõime ehk ampritasu €/kuus (KM-ta).
     *
     * @throws \RuntimeException kui sellist peakaitset ei ole hinnakirjas.
     *                           Vaikne 0 jätaks püsikulu arvest välja ja
     *                           moonutaks paketivõrdlust.
     */
    public function capacityFeeFor(int $amperage, int $phases = 1, string $connectionType = 'main_fuse'): float
    {
        $fee = $this->capacityFees->first(
            fn (GridCapacityFee $f) => $f->amperage === $amperage
                && $f->phases === $phases
                && $f->connection_type === $connectionType
        );

        if (! $fee) {
            throw new \RuntimeException(
                "Võrgupaketi versioonil {$this->id} puudub kuutasu {$connectionType} {$amperage}A / {$phases} faasi"
            );
        }

        return (float) $fee->monthly_eur;
    }
}
