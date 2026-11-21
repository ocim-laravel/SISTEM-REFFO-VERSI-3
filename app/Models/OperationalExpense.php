<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'tanggal',
    'kategori_biaya',
    'keterangan',
    'nominal',
])]
class OperationalExpense extends Model
{
    public const KATEGORI_GAJI = 'Gaji Karyawan';

    public const KATEGORI_LISTRIK_AIR = 'Listrik & Air';

    public const KATEGORI_TINTA = 'Pembelian Tinta';

    public const KATEGORI_PERAWATAN = 'Perawatan Mesin';

    public const KATEGORI_LAIN = 'Lain-lain';

    /**
     * @var array<int, string>
     */
    public const KATEGORI_BIAYA = [
        self::KATEGORI_GAJI,
        self::KATEGORI_LISTRIK_AIR,
        self::KATEGORI_TINTA,
        self::KATEGORI_PERAWATAN,
        self::KATEGORI_LAIN,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nominal' => 'decimal:2',
        ];
    }
}
