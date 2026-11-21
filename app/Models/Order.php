<?php

namespace App\Models;

use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'no_nota',
    'customer_id',
    'user_id',
    'total_tagihan',
    'jumlah_dp',
    'status_bayar',
    'status_desain',
    'catatan_desain',
    'lampiran_briefing',
    'deadline',
])]
class Order extends Model
{
    public const STATUS_BAYAR_BELUM_LUNAS = 'belum_lunas';

    public const STATUS_BAYAR_DP = 'dp';

    public const STATUS_BAYAR_LUNAS = 'lunas';

    /**
     * @var array<int, string>
     */
    public const STATUS_BAYAR_LIST = [
        self::STATUS_BAYAR_BELUM_LUNAS,
        self::STATUS_BAYAR_DP,
        self::STATUS_BAYAR_LUNAS,
    ];

    /**
     * @var array<string, string>
     */
    public const LABEL_STATUS_BAYAR = [
        self::STATUS_BAYAR_BELUM_LUNAS => 'Belum Lunas',
        self::STATUS_BAYAR_DP => 'DP',
        self::STATUS_BAYAR_LUNAS => 'Lunas',
    ];

    public const STATUS_DESAIN_BELUM_ADA_FILE = 'belum_ada_file';

    public const STATUS_DESAIN_FILE_MENTAH = 'file_mentah';

    public const STATUS_DESAIN_CORETAN_KERTAS = 'coretan_kertas';

    public const STATUS_DESAIN_SIAP_CETAK_TIFF = 'siap_cetak_tiff';

    /**
     * @var array<int, string>
     */
    public const STATUS_DESAIN_LIST = [
        self::STATUS_DESAIN_BELUM_ADA_FILE,
        self::STATUS_DESAIN_FILE_MENTAH,
        self::STATUS_DESAIN_CORETAN_KERTAS,
        self::STATUS_DESAIN_SIAP_CETAK_TIFF,
    ];

    /**
     * @var array<string, string>
     */
    public const LABEL_STATUS_DESAIN = [
        self::STATUS_DESAIN_BELUM_ADA_FILE => 'Belum Punya Desain / Materi Teks',
        self::STATUS_DESAIN_FILE_MENTAH => 'File Desain Mentah',
        self::STATUS_DESAIN_CORETAN_KERTAS => 'Foto Coretan Kertas',
        self::STATUS_DESAIN_SIAP_CETAK_TIFF => 'File Siap Cetak .TIFF',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_tagihan' => 'decimal:2',
            'jumlah_dp' => 'decimal:2',
            'deadline' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function advertisingSpks(): HasMany
    {
        return $this->hasMany(AdvertisingSpk::class);
    }

    /**
     * Nota dianggap siap diambil bila seluruh item sudah selesai finishing
     * dan minimal satu item berstatus siap_diambil.
     */
    public function scopeSiapDiambil(Builder $query): Builder
    {
        return $query
            ->whereHas('items', fn (Builder $item) => $item->where('status_produksi', OrderItem::STATUS_SIAP_DIAMBIL))
            ->whereDoesntHave('items', fn (Builder $item) => $item->whereNotIn('status_produksi', [
                OrderItem::STATUS_SIAP_DIAMBIL,
                OrderItem::STATUS_SELESAI,
            ]));
    }

    public function sisaTagihan(): float
    {
        return max((float) $this->total_tagihan - (float) $this->jumlah_dp, 0);
    }

    public function statusBayarLabel(): string
    {
        return self::LABEL_STATUS_BAYAR[$this->status_bayar] ?? $this->status_bayar;
    }

    public function statusDesainLabel(): string
    {
        return self::LABEL_STATUS_DESAIN[$this->status_desain] ?? $this->status_desain;
    }

    public function lampiranBriefingUrl(): ?string
    {
        if (! $this->lampiran_briefing) {
            return null;
        }

        return Storage::disk(MediaStorage::DISK)->url($this->lampiran_briefing);
    }
}
