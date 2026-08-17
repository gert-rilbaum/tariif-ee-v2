<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nord Pooli börsihind Eesti hinnapiirkonnas, Eleringi vahendusel.
 *
 * Ajad on UTC-s. resolution_minutes teeb võrreldavaks tunnisammuga ajaloo
 * (kuni 2025-09-30) ja 15-min sammuga oleviku (alates 2025-10-01).
 *
 * Unikaalsus (zone_code, period_start_utc) teeb sisselugemise idempotentseks —
 * korduv käivitus ei tekita duplikaate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_prices', function (Blueprint $table) {
            $table->id();
            $table->string('zone_code', 8)->default('EE');
            $table->dateTime('period_start_utc');
            $table->unsignedSmallInteger('resolution_minutes');
            $table->decimal('price_eur_mwh', 10, 4);
            $table->string('source', 32)->default('elering');
            $table->timestamp('fetched_at');

            $table->unique(['zone_code', 'period_start_utc']);
            $table->index(['zone_code', 'period_start_utc', 'resolution_minutes'], 'market_prices_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_prices');
    }
};
