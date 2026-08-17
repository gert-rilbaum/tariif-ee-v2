<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kaks parandust, mis tulid päris hinnakirjast (kontrollitud 18.08.2026).
 *
 * 1) TASAKAALUSTAMISVÕIMSUSE KULU on reguleeritud riiklik tasu, mitte müüja
 *    marginaal. Elering: 3,73 €/MWh KM-ta 2026, 2,97 €/MWh 2027. Müüja näitab
 *    seda elektrituruseaduse järgi arvel eraldi reana. Vana tariif.ee liitis
 *    selle hinnale nimega "tasakaalustamise tasu" — number õige, mudel vale.
 *
 * 2) KORTERI KUUTASU on eraldi rida, mitte peakaitsme suurus. Elektrilevi
 *    hinnakiri: korteri kuutasu kehtib kortermajades, kus peakaitsme jaotatud
 *    osa on kuni 16 A. Ilma eristuseta saaks korteriomanik eramu kuutasu.
 *
 * Sammud on korduvkindlad, sest MySQL-i DDL ei ole transaktsiooniline: kui
 * migratsioon kukub keskel, jääb osa muudatustest kohale.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->laiendaStateFeeEnum();

        if (! Schema::hasColumn('grid_capacity_fees', 'connection_type')) {
            Schema::table('grid_capacity_fees', function (Blueprint $table) {
                $table->enum('connection_type', ['main_fuse', 'apartment'])
                    ->default('main_fuse')
                    ->after('version_id');
            });
        }

        $this->vahetaUnikaalneIndeks();
    }

    public function down(): void
    {
        if ($this->indeksOlemas('grid_capacity_fees_unique')) {
            Schema::table('grid_capacity_fees', function (Blueprint $table) {
                $table->dropUnique('grid_capacity_fees_unique');
            });
        }

        if (Schema::hasColumn('grid_capacity_fees', 'connection_type')) {
            Schema::table('grid_capacity_fees', function (Blueprint $table) {
                $table->dropColumn('connection_type');
                $table->unique(['version_id', 'amperage', 'phases']);
            });
        }
    }

    private function laiendaStateFeeEnum(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            // SQLite hoiab enum'i varchar'ina — laiendust pole vaja
            return;
        }

        Schema::getConnection()->statement(
            "ALTER TABLE state_fees MODIFY COLUMN code
             ENUM('renewable', 'supply_security', 'excise', 'balancing_capacity') NOT NULL"
        );
    }

    /**
     * Uus indeks luuakse ENNE vana kustutamist.
     *
     * MySQL keeldub kustutamast indeksit, mida võõrvõti vajab
     * ("Cannot drop index ...: needed in a foreign key constraint"). Kuna uues
     * indeksis on version_id samuti kõige vasakpoolsem veerg, saab see
     * võõrvõtme toe üle ja vana muutub kustutatavaks.
     */
    private function vahetaUnikaalneIndeks(): void
    {
        if (! $this->indeksOlemas('grid_capacity_fees_unique')) {
            Schema::table('grid_capacity_fees', function (Blueprint $table) {
                $table->unique(
                    ['version_id', 'connection_type', 'amperage', 'phases'],
                    'grid_capacity_fees_unique'
                );
            });
        }

        $vana = 'grid_capacity_fees_version_id_amperage_phases_unique';

        if ($this->indeksOlemas($vana)) {
            Schema::table('grid_capacity_fees', function (Blueprint $table) use ($vana) {
                $table->dropUnique($vana);
            });
        }
    }

    private function indeksOlemas(string $nimi): bool
    {
        return collect(Schema::getIndexes('grid_capacity_fees'))
            ->contains(fn (array $index) => $index['name'] === $nimi);
    }
};
