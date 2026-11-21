<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Seed the product catalog with its four customer price tiers.
     */
    public function run(): void
    {
        $products = [
            [
                'nama_produk' => 'MMT 280',
                'kategori_produk' => Product::KATEGORI_METERAN,
                'harga_umum' => 30000,
                'harga_reseller' => 27000,
                'harga_prioritas' => 25000,
                'harga_pegawai' => 22000,
                'harga_modal' => 18000,
                'stok_pcs' => null,
            ],
            [
                'nama_produk' => 'MMT 340',
                'kategori_produk' => Product::KATEGORI_METERAN,
                'harga_umum' => 35000,
                'harga_reseller' => 31000,
                'harga_prioritas' => 29000,
                'harga_pegawai' => 25000,
                'harga_modal' => 21000,
                'stok_pcs' => null,
            ],
            [
                'nama_produk' => 'MMT 440',
                'kategori_produk' => Product::KATEGORI_METERAN,
                'harga_umum' => 45000,
                'harga_reseller' => 40000,
                'harga_prioritas' => 37000,
                'harga_pegawai' => 32000,
                'harga_modal' => 27000,
                'stok_pcs' => null,
            ],
            [
                'nama_produk' => 'Backlit',
                'kategori_produk' => Product::KATEGORI_METERAN,
                'harga_umum' => 60000,
                'harga_reseller' => 54000,
                'harga_prioritas' => 50000,
                'harga_pegawai' => 45000,
                'harga_modal' => 38000,
                'stok_pcs' => null,
            ],
            [
                'nama_produk' => 'Stiker Meteran',
                'kategori_produk' => Product::KATEGORI_METERAN,
                'harga_umum' => 50000,
                'harga_reseller' => 45000,
                'harga_prioritas' => 42000,
                'harga_pegawai' => 38000,
                'harga_modal' => 32000,
                'stok_pcs' => null,
            ],
            [
                'nama_produk' => 'X-Banner',
                'kategori_produk' => Product::KATEGORI_SATUAN,
                'harga_umum' => 85000,
                'harga_reseller' => 75000,
                'harga_prioritas' => 70000,
                'harga_pegawai' => 65000,
                'harga_modal' => 55000,
                'stok_pcs' => 25,
            ],
            [
                'nama_produk' => 'Roll Banner',
                'kategori_produk' => Product::KATEGORI_SATUAN,
                'harga_umum' => 150000,
                'harga_reseller' => 135000,
                'harga_prioritas' => 125000,
                'harga_pegawai' => 115000,
                'harga_modal' => 95000,
                'stok_pcs' => 25,
            ],
            [
                'nama_produk' => 'Poster',
                'kategori_produk' => Product::KATEGORI_SATUAN,
                'harga_umum' => 15000,
                'harga_reseller' => 13000,
                'harga_prioritas' => 12000,
                'harga_pegawai' => 10000,
                'harga_modal' => 8000,
                'stok_pcs' => 25,
            ],
            [
                'nama_produk' => 'Stiker A3',
                'kategori_produk' => Product::KATEGORI_SATUAN,
                'harga_umum' => 20000,
                'harga_reseller' => 17500,
                'harga_prioritas' => 16000,
                'harga_pegawai' => 14000,
                'harga_modal' => 11000,
                'stok_pcs' => 25,
            ],
            [
                'nama_produk' => 'A-Board',
                'kategori_produk' => Product::KATEGORI_SATUAN,
                'harga_umum' => 250000,
                'harga_reseller' => 225000,
                'harga_prioritas' => 210000,
                'harga_pegawai' => 195000,
                'harga_modal' => 170000,
                'stok_pcs' => 25,
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['nama_produk' => $product['nama_produk']],
                $product,
            );
        }
    }
}
