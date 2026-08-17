<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GridEnergyRate extends Model
{
    public $timestamps = false;

    protected $fillable = ['version_id', 'rate_kind', 'cents_per_kwh'];

    protected $casts = ['cents_per_kwh' => 'float'];

    public function version(): BelongsTo
    {
        return $this->belongsTo(GridPackageVersion::class, 'version_id');
    }
}
