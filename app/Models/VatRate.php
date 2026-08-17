<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

class VatRate extends Model
{
    protected $fillable = ['valid_from', 'valid_to', 'rate', 'source_url', 'verified_at'];

    protected $casts = [
        'valid_from' => 'immutable_date',
        'valid_to' => 'immutable_date',
        'rate' => 'float',
        'verified_at' => 'immutable_datetime',
    ];

    /**
     * Käibemaksumäär antud hetkel (nt 0.24).
     *
     * Hetk teisendatakse alati Eesti kuupäevaks — UTC-hetk 30.06 21:30 on
     * Eestis juba 01.07 ja peab saama uue määra.
     *
     * @throws \RuntimeException kui määra ei leidu — parem viga kui vaikselt vale hind
     */
    public static function atMoment(CarbonImmutable $moment): float
    {
        $date = $moment->setTimezone('Europe/Tallinn')->toDateString();

        $row = static::query()
            ->whereDate('valid_from', '<=', $date)
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhereDate('valid_to', '>=', $date))
            ->orderByDesc('valid_from')
            ->first();

        if (! $row) {
            throw new \RuntimeException("Käibemaksumäär puudub kuupäevale {$date}");
        }

        return (float) $row->rate;
    }
}
