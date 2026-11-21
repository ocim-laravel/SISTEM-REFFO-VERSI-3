<?php

namespace Database\Seeders;

use App\Models\Finishing;
use Illuminate\Database\Seeder;

class FinishingSeeder extends Seeder
{
    /**
     * Seed the internal finishing material formulas.
     */
    public function run(): void
    {
        $finishings = [
            [
                'nama_finishing' => 'Siming',
                'ekstra_kiri_cm' => '2.5',
                'ekstra_kanan_cm' => '2.5',
                'ekstra_atas_cm' => '2.5',
                'ekstra_bawah_cm' => '2.5',
            ],
            [
                'nama_finishing' => 'Siming Keling',
                'ekstra_kiri_cm' => '2.5',
                'ekstra_kanan_cm' => '2.5',
                'ekstra_atas_cm' => '2.5',
                'ekstra_bawah_cm' => '2.5',
            ],
            [
                'nama_finishing' => 'Kolong AB Samping Potong Press',
                'ekstra_kiri_cm' => '0',
                'ekstra_kanan_cm' => '0',
                'ekstra_atas_cm' => '7',
                'ekstra_bawah_cm' => '7',
            ],
            [
                'nama_finishing' => 'Kolong AB Samping Siming',
                'ekstra_kiri_cm' => '2.5',
                'ekstra_kanan_cm' => '2.5',
                'ekstra_atas_cm' => '7',
                'ekstra_bawah_cm' => '7',
            ],
            [
                'nama_finishing' => 'Kolong KK AB Siming',
                'ekstra_kiri_cm' => '7',
                'ekstra_kanan_cm' => '7',
                'ekstra_atas_cm' => '2.5',
                'ekstra_bawah_cm' => '2.5',
            ],
            [
                'nama_finishing' => 'Kolong KK AB Samping Potong Press',
                'ekstra_kiri_cm' => '7',
                'ekstra_kanan_cm' => '7',
                'ekstra_atas_cm' => '0',
                'ekstra_bawah_cm' => '0',
            ],
            [
                'nama_finishing' => 'Kolong Baliho',
                'ekstra_kiri_cm' => '7.5',
                'ekstra_kanan_cm' => '7.5',
                'ekstra_atas_cm' => '7.5',
                'ekstra_bawah_cm' => '7.5',
            ],
        ];

        foreach ($finishings as $finishing) {
            Finishing::updateOrCreate(
                ['nama_finishing' => $finishing['nama_finishing']],
                $finishing,
            );
        }
    }
}
