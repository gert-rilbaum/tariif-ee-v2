<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GridPackage extends Model
{
    protected $fillable = ['operator_id', 'code', 'name', 'scheme', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function operator(): BelongsTo
    {
        return $this->belongsTo(GridOperator::class, 'operator_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(GridPackageVersion::class, 'package_id');
    }

    public function timePatterns(): HasMany
    {
        return $this->hasMany(GridTimePattern::class, 'package_id');
    }

    /**
     * Antud hetkel kehtiv hinnaversioon või null.
     *
     * Null tähendab "sellel kuupäeval ei ole hinnakirja" — mitte "kasuta uusimat".
     * Vale hind on halvem kui puuduv hind (spec §9).
     */
    public function versionAt(CarbonImmutable $moment): ?GridPackageVersion
    {
        $date = $moment->setTimezone('Europe/Tallinn')->toDateString();

        return $this->versions()
            ->whereDate('valid_from', '<=', $date)
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhereDate('valid_to', '>=', $date))
            ->orderByDesc('valid_from')
            ->first();
    }
}
