<?php

namespace Database\Seeders;

use App\Models\Instansi;
use Illuminate\Database\Seeder;

class InstansiSeeder extends Seeder
{
    /**
     * Instansi default dan instansi yang dipakai data awal sistem.
     */
    public function run(): void
    {
        foreach (Instansi::DEFAULT_LIST as $namaInstansi) {
            Instansi::query()->firstOrCreate(['nama_instansi' => $namaInstansi]);
        }
    }
}
