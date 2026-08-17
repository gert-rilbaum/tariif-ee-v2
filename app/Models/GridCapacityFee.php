<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GridCapacityFee extends Model
{
    public $timestamps = false;

    protected $fillable = ['version_id', 'connection_type', 'amperage', 'phases', 'monthly_eur'];

    protected $casts = ['amperage' => 'integer', 'phases' => 'integer', 'monthly_eur' => 'float'];

    public function version(): BelongsTo
    {
        return $this->belongsTo(GridPackageVersion::class, 'version_id');
    }
}
