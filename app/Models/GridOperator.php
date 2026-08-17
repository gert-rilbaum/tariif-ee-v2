<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GridOperator extends Model
{
    protected $fillable = ['code', 'name', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function packages(): HasMany
    {
        return $this->hasMany(GridPackage::class, 'operator_id');
    }
}
