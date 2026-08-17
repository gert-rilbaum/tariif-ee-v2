<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riiklikud tasud senti/kWh, KÄIBEMAKSUTA.
 *
 * renewable       — taastuvenergia tasu
 * supply_security — varustuskindluse tasu
 * excise          — elektriaktsiis
 *
 * NB: tasakaalustamise tasu EI ole siin. See on müüja marginaal, mitte
 * riiklik tasu — vana tariif.ee esitas seda ekslikult riikliku tasuna (spec §5.6).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('state_fees', function (Blueprint $table) {
            $table->id();
            $table->enum('code', ['renewable', 'supply_security', 'excise']);
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->decimal('cents_per_kwh', 8, 4);
            $table->string('source_url');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['code', 'valid_from', 'valid_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('state_fees');
    }
};
