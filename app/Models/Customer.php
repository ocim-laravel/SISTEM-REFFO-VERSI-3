<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['instansi_id', 'instansi', 'nama_pelanggan', 'no_wa', 'kategori', 'alamat'])]
class Customer extends Model
{
    public const INSTANSI_DEFAULT = Instansi::DEFAULT;

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

    /**
     * Kolom string instansi tetap tersinkron dengan baris instansis.
     */
    protected static function booted(): void
    {
        static::saving(function (Customer $customer): void {
            if ($customer->instansi_id !== null) {
                $instansi = Instansi::query()->find($customer->instansi_id);

                if ($instansi !== null) {
                    $customer->instansi = $instansi->nama_instansi;

                    return;
                }
            }

            $nama = trim((string) ($customer->instansi ?? ''));
            $nama = $nama === '' ? Instansi::DEFAULT : $nama;
            $instansi = Instansi::query()->firstOrCreate(['nama_instansi' => $nama]);

            $customer->instansi_id = $instansi->id;
            $customer->instansi = $instansi->nama_instansi;
        });
    }

    public function instansi(): BelongsTo
    {
        return $this->belongsTo(Instansi::class);
    }
}
