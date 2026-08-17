<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class StateFee extends Model
{
    protected $fillable = ['code', 'valid_from', 'valid_to', 'cents_per_kwh', 'source_url', 'verified_at'];

    protected $casts = [
        'valid_from' => 'immutable_date',
        'valid_to' => 'immutable_date',
        'cents_per_kwh' => 'float',
        'verified_at' => 'immutable_datetime',
    ];

    /**
     * Antud hetkel kehtivad riiklikud tasud: code => senti/kWh (KM-ta).
     *
     * Puuduv tasu EI ole null — teda lihtsalt ei ole kogumis. Kalkulaator
     * peab vahet tegema "tasu on 0" ja "tasu pole teada" vahel (spec §9).
     *
     * @return Collection<string, float>
     */
    public static function activeAt(CarbonImmutable $moment): Collection
    {
        $date = $moment->setTimezone('Europe/Tallinn')->toDateString();

        return static::query()
            ->whereDate('valid_from', '<=', $date)
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhereDate('valid_to', '>=', $date))
            ->orderByDesc('valid_from')
            ->get()
            ->keyBy('code')
            ->map(fn (StateFee $fee) => (float) $fee->cents_per_kwh);
    }
}
