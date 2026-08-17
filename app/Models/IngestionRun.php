<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IngestionRun extends Model
{
    public $timestamps = false;

    protected $fillable = ['kind', 'started_at', 'finished_at', 'status', 'rows_written', 'error'];

    protected $casts = [
        'started_at' => 'immutable_datetime',
        'finished_at' => 'immutable_datetime',
        'rows_written' => 'integer',
    ];

    public static function lastSuccessful(): ?self
    {
        return static::query()->where('status', 'ok')->latest('id')->first();
    }
}
