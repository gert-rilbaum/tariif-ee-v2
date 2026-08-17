<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sisselugemiste logi.
 *
 * Vana süsteemi toide suri 29.01.2026 ja keegi ei saanud teada enne augustit,
 * sest kusagil ei olnud kirjas, kas ja millal viimati õnnestus. Andmete värskus
 * peab olema MÕÕDETAV, mitte oletatav (spec §7).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingestion_runs', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 32);                 // fetch | backfill | gapfill
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->enum('status', ['ok', 'failed'])->default('ok');
            $table->unsignedInteger('rows_written')->default(0);
            $table->text('error')->nullable();

            $table->index(['kind', 'started_at']);
            $table->index(['status', 'finished_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingestion_runs');
    }
};
