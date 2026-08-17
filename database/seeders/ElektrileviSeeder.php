<?php

namespace Database\Seeders;

use App\Models\GridCapacityFee;
use App\Models\GridEnergyRate;
use App\Models\GridOperator;
use App\Models\GridPackage;
use App\Models\GridPackageVersion;
use App\Models\GridTimePattern;
use Illuminate\Database\Seeder;

/**
 * Elektrilevi võrgupaketid madalpingel kuni 63 A.
 *
 * KÕIK numbrid pärinevad hinnakirjast "Elektrilevi võrguteenuse hinnakiri,
 * kehtib alates 1. juuni 2026", kooskõlastatud Konkurentsiameti otsusega
 * nr 7-3/2026-028 (02.03.2026). Kontrollitud 18.08.2026.
 *
 * Hinnad on KÄIBEMAKSUTA — hinnakiri ütleb otse: "Võrguteenuse arved
 * koostatakse käibemaksuta hindade alusel. Käibemaks lisandub arve kogusummale."
 *
 * AJAMUSTRID hinnakirjast, mitte oletusest:
 *   Päevahind kehtib esmaspäevast reedeni, v.a riiklik püha, kell 7.00–22.00.
 *   Ööhind kehtib tööpäevadel 22.00–7.00 ja laupäeval, pühapäeval ning riiklikul
 *   pühal kogu ööpäeva jooksul.
 *
 * NB: vana tariif.ee kasutas päevatariifi lõpuna kella 23.00 — see oli vale.
 *
 * Võrk 5 (tiputunni pakett) on Gerdi otsusega etapp 1-st väljas. Skeem toetab
 * teda juba (scheme 'dual_peak', rate_kind 'peak' / 'weekend_peak').
 */
class ElektrileviSeeder extends Seeder
{
    private const ALLIKAS = 'https://public-docs.elektrilevi.ee/2/7/vorguteenuse_kehtiv_hinnakiri_71ed51b53d.pdf';

    private const OTSUS = 'Konkurentsiamet 7-3/2026-028 (02.03.2026)';

    private const KEHTIB_ALATES = '2026-06-01';

    public function run(): void
    {
        $operator = GridOperator::updateOrCreate(
            ['code' => 'elektrilevi'],
            ['name' => 'Elektrilevi OÜ', 'active' => true],
        );

        $this->vork1($operator);
        $this->vork2($operator);
        $this->vork4($operator);
    }

    private function vork1(GridOperator $operator): void
    {
        $pakett = $this->pakett($operator, 'vork1', 'Võrk 1', 'single');
        $versioon = $this->versioon($pakett);

        // Ühetariifne: põhihind kehtib ööpäevaringselt
        $this->maar($versioon, 'all', 7.7200);

        $this->kuutasud($versioon, [
            'apartment' => [16 => 2.81],
            'main_fuse' => [16 => 4.85, 20 => 5.66, 25 => 6.48, 32 => 7.63,
                40 => 8.94, 50 => 10.58, 63 => 12.71],
        ]);

        $this->uhetariifneMuster($pakett);
    }

    private function vork2(GridOperator $operator): void
    {
        $pakett = $this->pakett($operator, 'vork2', 'Võrk 2', 'dual');
        $versioon = $this->versioon($pakett);

        $this->maar($versioon, 'day', 6.0700);
        $this->maar($versioon, 'night', 3.5100);

        $this->kuutasud($versioon, [
            'apartment' => [16 => 3.65],
            'main_fuse' => [16 => 6.80, 20 => 8.34, 25 => 9.83, 32 => 11.91,
                40 => 14.29, 50 => 17.27, 63 => 21.14],
        ]);

        $this->kahetariifneMuster($pakett);
    }

    private function vork4(GridOperator $operator): void
    {
        $pakett = $this->pakett($operator, 'vork4', 'Võrk 4', 'dual');
        $versioon = $this->versioon($pakett);

        $this->maar($versioon, 'day', 3.6900);
        $this->maar($versioon, 'night', 2.1000);

        $this->kuutasud($versioon, [
            'apartment' => [16 => 7.87],
            'main_fuse' => [16 => 16.91, 20 => 20.64, 25 => 24.96, 32 => 31.01,
                40 => 37.92, 50 => 46.56, 63 => 57.79],
        ]);

        $this->kahetariifneMuster($pakett);
    }

    private function pakett(GridOperator $operator, string $code, string $name, string $scheme): GridPackage
    {
        return GridPackage::updateOrCreate(
            ['operator_id' => $operator->id, 'code' => $code],
            ['name' => $name, 'scheme' => $scheme, 'active' => true],
        );
    }

    private function versioon(GridPackage $pakett): GridPackageVersion
    {
        // Madalpingel kuni 63 A ei ole eraldi baaskuutasu — kogu kuutasu sõltub
        // peakaitsmest ja on grid_capacity_fees tabelis
        return GridPackageVersion::updateOrCreate(
            ['package_id' => $pakett->id, 'valid_from' => self::KEHTIB_ALATES],
            [
                'valid_to' => null,
                'base_monthly_eur' => 0.00,
                'source_url' => self::ALLIKAS,
                'approval_ref' => self::OTSUS,
                'verified_at' => now(),
            ],
        );
    }

    private function maar(GridPackageVersion $versioon, string $kind, float $centsPerKwh): void
    {
        GridEnergyRate::updateOrCreate(
            ['version_id' => $versioon->id, 'rate_kind' => $kind],
            ['cents_per_kwh' => $centsPerKwh],
        );
    }

    /** @param array<string, array<int, float>> $tasud */
    private function kuutasud(GridPackageVersion $versioon, array $tasud): void
    {
        foreach ($tasud as $tyyp => $read) {
            foreach ($read as $amper => $eurKuus) {
                GridCapacityFee::updateOrCreate(
                    ['version_id' => $versioon->id, 'connection_type' => $tyyp,
                        'amperage' => $amper, 'phases' => 1],
                    ['monthly_eur' => $eurKuus],
                );
            }
        }
    }

    private function uhetariifneMuster(GridPackage $pakett): void
    {
        GridTimePattern::updateOrCreate(
            ['package_id' => $pakett->id, 'rate_kind' => 'all'],
            ['weekdays' => '1234567', 'start_time' => '00:00', 'end_time' => '24:00',
                'holiday_behaviour' => 'normal', 'priority' => 10],
        );
    }

    private function kahetariifneMuster(GridPackage $pakett): void
    {
        // Päev: E–R 7.00–22.00, v.a riiklik püha
        GridTimePattern::updateOrCreate(
            ['package_id' => $pakett->id, 'rate_kind' => 'day'],
            ['weekdays' => '12345', 'start_time' => '07:00', 'end_time' => '22:00',
                'holiday_behaviour' => 'as_weekend', 'priority' => 10],
        );

        // Öö: kõik ülejäänu — madalaim prioriteet katab terve ööpäeva
        GridTimePattern::updateOrCreate(
            ['package_id' => $pakett->id, 'rate_kind' => 'night'],
            ['weekdays' => '1234567', 'start_time' => '00:00', 'end_time' => '24:00',
                'holiday_behaviour' => 'normal', 'priority' => 90],
        );
    }
}
