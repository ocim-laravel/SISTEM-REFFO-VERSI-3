<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Seed individual customers and the Telkomsel PIC group.
     */
    public function run(): void
    {
        $customers = [
            [
                'instansi' => Customer::INSTANSI_DEFAULT,
                'nama_pelanggan' => 'Budi Santoso',
                'no_wa' => '081234567801',
                'kategori' => Customer::KATEGORI_UMUM,
                'alamat' => 'Jl. Merdeka No. 10, Bandung',
            ],
            [
                'instansi' => Customer::INSTANSI_DEFAULT,
                'nama_pelanggan' => 'Siti Aminah',
                'no_wa' => '081234567802',
                'kategori' => Customer::KATEGORI_UMUM,
                'alamat' => 'Jl. Melati No. 5, Bandung',
            ],
            [
                'instansi' => Customer::INSTANSI_DEFAULT,
                'nama_pelanggan' => 'Rudi Hartono',
                'no_wa' => '081234567803',
                'kategori' => Customer::KATEGORI_RESELLER,
                'alamat' => 'Jl. Cihampelas No. 88, Bandung',
            ],
            [
                'instansi' => Customer::INSTANSI_DEFAULT,
                'nama_pelanggan' => 'Dewi Lestari',
                'no_wa' => '081234567804',
                'kategori' => Customer::KATEGORI_PRIORITAS,
                'alamat' => 'Jl. Asia Afrika No. 12, Bandung',
            ],
            [
                'instansi' => Customer::INSTANSI_DEFAULT,
                'nama_pelanggan' => 'Andi Wijaya',
                'no_wa' => '081234567805',
                'kategori' => Customer::KATEGORI_PEGAWAI,
                'alamat' => 'Jl. Soekarno-Hatta No. 45, Bandung',
            ],
            [
                'instansi' => 'Telkomsel',
                'nama_pelanggan' => 'Sigit',
                'no_wa' => '081234567811',
                'kategori' => Customer::KATEGORI_RESELLER,
                'alamat' => 'Jl. Gatot Subroto No. 1, Jakarta',
            ],
            [
                'instansi' => 'Telkomsel',
                'nama_pelanggan' => 'Hendi',
                'no_wa' => '081234567812',
                'kategori' => Customer::KATEGORI_RESELLER,
                'alamat' => 'Jl. Gatot Subroto No. 1, Jakarta',
            ],
            [
                'instansi' => 'Telkomsel',
                'nama_pelanggan' => 'Nurokhim',
                'no_wa' => '081234567813',
                'kategori' => Customer::KATEGORI_RESELLER,
                'alamat' => 'Jl. Gatot Subroto No. 1, Jakarta',
            ],
        ];

        foreach ($customers as $customer) {
            Customer::updateOrCreate(
                [
                    'instansi' => $customer['instansi'],
                    'nama_pelanggan' => $customer['nama_pelanggan'],
                ],
                $customer,
            );
        }
    }
}
