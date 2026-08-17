<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            VatRateSeeder::class,
            StateFeeSeeder::class,
            ElektrileviSeeder::class,
        ]);
    }
}
