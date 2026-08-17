<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class TariffSourceCheck extends Model
{
    protected $fillable = [
        'source_key', 'label', 'url', 'checksum', 'size_bytes',
        'checked_at', 'changed_at', 'acknowledged', 'last_error',
    ];

    protected $casts = [
        'checked_at' => 'immutable_datetime',
        'changed_at' => 'immutable_datetime',
        'acknowledged' => 'boolean',
        'size_bytes' => 'integer',
    ];

    /** Allikad, mille muutus tähendab, et meie kataloog võib olla vananenud. */
    public static function watched(): array
    {
        return [
            [
                'source_key' => 'elektrilevi_vorguteenuse_hinnakiri',
                'label' => 'Elektrilevi võrguteenuse hinnakiri',
                'url' => 'https://public-docs.elektrilevi.ee/2/7/vorguteenuse_kehtiv_hinnakiri_71ed51b53d.pdf',
            ],
        ];
    }

    /** Kas mõni allikas on muutunud ja inimene pole seda veel üle vaadanud? */
    public static function unacknowledgedChanges(): Collection
    {
        return static::query()->where('acknowledged', false)->get();
    }
}
