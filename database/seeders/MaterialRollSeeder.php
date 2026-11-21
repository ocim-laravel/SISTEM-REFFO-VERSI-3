<?php

namespace Database\Seeders;

use App\Models\MaterialRoll;
use Illuminate\Database\Seeder;

class MaterialRollSeeder extends Seeder
{
    /**
     * Seed the initial active roll stock.
     */
    public function run(): void
    {
        $materials = [
            ['jenis_bahan' => 'MMT 280', 'kode' => 'MMT280', 'lebar_m' => '3.20', 'panjang_m' => '70.00'],
            ['jenis_bahan' => 'MMT 340', 'kode' => 'MMT340', 'lebar_m' => '3.20', 'panjang_m' => '70.00'],
            ['jenis_bahan' => 'MMT 440', 'kode' => 'MMT440', 'lebar_m' => '3.20', 'panjang_m' => '70.00'],
            ['jenis_bahan' => 'Backlit', 'kode' => 'BACKLIT', 'lebar_m' => '3.20', 'panjang_m' => '70.00'],
            ['jenis_bahan' => 'Stiker 1m', 'kode' => 'STIKER1M', 'lebar_m' => '1.00', 'panjang_m' => '50.00'],
            ['jenis_bahan' => 'Stiker 1.5m', 'kode' => 'STIKER15M', 'lebar_m' => '1.50', 'panjang_m' => '50.00'],
        ];

        foreach ($materials as $material) {
            for ($index = 1; $index <= 2; $index++) {
                MaterialRoll::updateOrCreate(
                    ['kode_roll' => sprintf('%s-R%02d', $material['kode'], $index)],
                    [
                        'jenis_bahan' => $material['jenis_bahan'],
                        'lebar_m' => $material['lebar_m'],
                        'panjang_awal_m' => $material['panjang_m'],
                        'panjang_sisa_m' => $material['panjang_m'],
                        'status' => MaterialRoll::STATUS_AKTIF,
                    ],
                );
            }
        }
    }
}
