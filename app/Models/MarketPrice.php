<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MarketPrice extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'zone_code', 'period_start_utc', 'resolution_minutes',
        'price_eur_mwh', 'source', 'fetched_at',
    ];

    protected $casts = [
        'period_start_utc' => 'immutable_datetime',
        'fetched_at' => 'immutable_datetime',
        'price_eur_mwh' => 'float',
        'resolution_minutes' => 'integer',
    ];

    /**
     * EUR/MWh → senti/kWh.
     * 1 MWh = 1000 kWh; EUR → senti = ×100. Kokku: ÷10.
     */
    public function centsPerKwh(): float
    {
        return $this->price_eur_mwh / 10;
    }

    /** Eesti kohaliku ööpäeva read — ajavöönd teisendatakse, mitte ei eeldata. */
    public function scopeForLocalDay(Builder $query, CarbonImmutable $localDay, string $zone = 'EE'): Builder
    {
        $start = $localDay->setTimezone('Europe/Tallinn')->startOfDay();

        return $query->where('zone_code', $zone)
            ->where('period_start_utc', '>=', $start->utc())
            ->where('period_start_utc', '<', $start->addDay()->utc());
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('period_start_utc');
    }
}
