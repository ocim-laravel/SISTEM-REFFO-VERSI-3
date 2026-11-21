<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'nama_produk',
    'kategori_produk',
    'harga_umum',
    'harga_reseller',
    'harga_prioritas',
    'harga_pegawai',
    'harga_modal',
    'stok_pcs',
])]
class Product extends Model
{
    public const KATEGORI_METERAN = 'meteran';

    public const KATEGORI_SATUAN = 'satuan';

    /**
     * @var array<int, string>
     */
    public const KATEGORI_PRODUK = [
        self::KATEGORI_METERAN,
        self::KATEGORI_SATUAN,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'harga_umum' => 'decimal:2',
            'harga_reseller' => 'decimal:2',
            'harga_prioritas' => 'decimal:2',
            'harga_pegawai' => 'decimal:2',
            'harga_modal' => 'decimal:2',
            'stok_pcs' => 'integer',
        ];
    }

    /**
     * Resolve the selling price for a customer category tier.
     */
    public function hargaUntukKategori(string $kategori): float
    {
        $kolom = match ($kategori) {
            Customer::KATEGORI_RESELLER => 'harga_reseller',
            Customer::KATEGORI_PRIORITAS => 'harga_prioritas',
            Customer::KATEGORI_PEGAWAI => 'harga_pegawai',
            default => 'harga_umum',
        };

        return (float) $this->{$kolom};
    }
}
