<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Võrguteenuse kataloog.
 *
 * Keskne mõte: iga hinnanumber on rida kehtivusperioodi ja allikaviitega.
 * Kuutasu (base_monthly_eur) ja läbilaskevõime ehk ampritasu (grid_capacity_fees)
 * on ERALDI — Elektrilevi hinnakirjas on need kaks eri tasu ja nende kokkupakkimine
 * teeb paketivõrdluse valeks (spec §5.2).
 *
 * Kõik hinnad on KÄIBEMAKSUTA.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grid_operators', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('grid_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->constrained('grid_operators')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->enum('scheme', ['single', 'dual', 'dual_peak']);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['operator_id', 'code']);
        });

        Schema::create('grid_package_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('grid_packages')->cascadeOnDelete();
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->decimal('base_monthly_eur', 8, 2);
            $table->string('source_url');
            $table->string('approval_ref')->nullable();     // Konkurentsiameti otsus
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['package_id', 'valid_from']);
        });

        Schema::create('grid_energy_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_id')->constrained('grid_package_versions')->cascadeOnDelete();
            $table->enum('rate_kind', ['all', 'day', 'night', 'peak', 'weekend_peak']);
            $table->decimal('cents_per_kwh', 8, 4);

            $table->unique(['version_id', 'rate_kind']);
        });

        Schema::create('grid_capacity_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_id')->constrained('grid_package_versions')->cascadeOnDelete();
            $table->unsignedSmallInteger('amperage');
            $table->unsignedTinyInteger('phases')->default(1);
            $table->decimal('monthly_eur', 8, 2);

            $table->unique(['version_id', 'amperage', 'phases']);
        });

        Schema::create('grid_time_patterns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('grid_packages')->cascadeOnDelete();
            $table->enum('rate_kind', ['all', 'day', 'night', 'peak', 'weekend_peak']);
            $table->string('weekdays', 7)->default('1234567');   // ISO: 1 = esmaspäev
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('holiday_behaviour', ['as_weekend', 'normal'])->default('as_weekend');
            $table->unsignedTinyInteger('priority')->default(10); // väiksem võidab

            $table->index(['package_id', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grid_time_patterns');
        Schema::dropIfExists('grid_capacity_fees');
        Schema::dropIfExists('grid_energy_rates');
        Schema::dropIfExists('grid_package_versions');
        Schema::dropIfExists('grid_packages');
        Schema::dropIfExists('grid_operators');
    }
};
