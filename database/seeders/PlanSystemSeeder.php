<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PlanSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            PlanPermissionsSeeder::class,
            PlanConfigurationsSeeder::class,
        ]);
    }
}
