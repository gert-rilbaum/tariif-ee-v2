<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tariifiallikate valve.
 *
 * Vana tariif.ee hinnad vananesid VAIKSELT — kuutasud jäid 2–3 aastat maha ja
 * keegi ei saanud teada. Siin hoiame iga allika kontrollsummat: kui allikas
 * muutub, saab Gert teate ja kontrollib numbrid üle.
 *
 * NB: me EI muuda hindu automaatselt. Hinnamuutus on äriotsus ja vajab inimese
 * kinnitust — automaatne ülekirjutamine tähendaks, et vale parsimine jõuab
 * märkamatult kasutaja arvele.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tariff_source_checks', function (Blueprint $table) {
            $table->id();
            $table->string('source_key')->unique();      // nt 'elektrilevi_hinnakiri'
            $table->string('label');
            $table->text('url');
            $table->string('checksum', 64)->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamp('changed_at')->nullable();  // millal viimati MUUTUS
            $table->boolean('acknowledged')->default(true); // kas inimene on muutuse üle vaadanud
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariff_source_checks');
    }
};
