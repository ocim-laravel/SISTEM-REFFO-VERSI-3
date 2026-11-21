<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MaterialOffcutSeeder extends Seeder
{
    /**
     * No initial offcut stock is seeded.
     *
     * Offcuts are created automatically from the remaining side material when
     * the operator cuts an active roll during production (Tahap 4).
     */
    public function run(): void
    {
        //
    }
}
