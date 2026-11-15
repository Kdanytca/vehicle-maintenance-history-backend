<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // DatabaseSeeder mantiene el orden: usuarios antes de los datos relacionados.
            $this->call(DatabaseSeeder::class);
        });
    }
}
