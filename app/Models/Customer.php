<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['instansi', 'nama_pelanggan', 'no_wa', 'kategori', 'alamat'])]
class Customer extends Model
{
    public const INSTANSI_DEFAULT = 'Order Pribadi';

    public const KATEGORI_UMUM = 'umum';

    public const KATEGORI_RESELLER = 'reseller';

    public const KATEGORI_PRIORITAS = 'prioritas';

    public const KATEGORI_PEGAWAI = 'pegawai';

    /**
     * @var array<int, string>
     */
    public const KATEGORIS = [
        self::KATEGORI_UMUM,
        self::KATEGORI_RESELLER,
        self::KATEGORI_PRIORITAS,
        self::KATEGORI_PEGAWAI,
    ];
}
