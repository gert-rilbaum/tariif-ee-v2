<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GridTimePattern extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'package_id', 'rate_kind', 'weekdays', 'start_time', 'end_time',
        'holiday_behaviour', 'priority',
    ];

    protected $casts = ['priority' => 'integer'];

    public function package(): BelongsTo
    {
        return $this->belongsTo(GridPackage::class, 'package_id');
    }
}
