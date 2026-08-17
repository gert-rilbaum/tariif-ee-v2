<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Käibemaksumäär on ajas muutuv ANDMERIDA, mitte konstant.
 * 20% → 22% (2024-01-01) → 24% (2025-07-01), järgmine muudatus on eeldus.
 * Arvutus võtab määra sündmuse hetke järgi, et ajalooline arvutus jääks õigeks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vat_rates', function (Blueprint $table) {
            $table->id();
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->decimal('rate', 5, 4);              // 0.2400 = 24%
            $table->string('source_url');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['valid_from', 'valid_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vat_rates');
    }
};
