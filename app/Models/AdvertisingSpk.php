<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'jenis_pekerjaan',
    'ukuran_konstruksi',
    'lokasi_pasang',
    'catatan_teknis',
    'status_lapangan',
])]
class AdvertisingSpk extends Model
{
    public const STATUS_ANTREAN = 'antrean';

    public const STATUS_PERAKITAN = 'perakitan';

    public const STATUS_PEMASANGAN = 'pemasangan';

    public const STATUS_SELESAI = 'selesai';

    /**
     * @var array<int, string>
     */
    public const STATUS_LAPANGAN_LIST = [
        self::STATUS_ANTREAN,
        self::STATUS_PERAKITAN,
        self::STATUS_PEMASANGAN,
        self::STATUS_SELESAI,
    ];

    /**
     * @var array<string, string>
     */
    public const LABEL_STATUS_LAPANGAN = [
        self::STATUS_ANTREAN => 'Antrean',
        self::STATUS_PERAKITAN => 'Perakitan',
        self::STATUS_PEMASANGAN => 'Pemasangan',
        self::STATUS_SELESAI => 'Selesai',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function statusLapanganLabel(): string
    {
        return self::LABEL_STATUS_LAPANGAN[$this->status_lapangan] ?? $this->status_lapangan;
    }
}
