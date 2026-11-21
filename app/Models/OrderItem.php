<?php

namespace App\Models;

use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'order_id',
    'product_id',
    'finishing_id',
    'lebar_m',
    'tinggi_m',
    'qty',
    'luas_tagihan_m2',
    'harga_satuan_dipakai',
    'subtotal',
    'status_produksi',
    'file_preview_acc',
    'file_cetak_tiff',
    'file_master_arsip',
])]
class OrderItem extends Model
{
    public const STATUS_MENUNGGU_DESAIN = 'menunggu_desain';

    public const STATUS_PROSES_DESAIN = 'proses_desain';

    public const STATUS_MENUNGGU_ACC = 'menunggu_acc';

    public const STATUS_ANTREAN_CETAK = 'antrean_cetak';

    public const STATUS_PROSES_CETAK = 'proses_cetak';

    public const STATUS_ANTREAN_FINISHING = 'antrean_finishing';

    public const STATUS_PROSES_FINISHING = 'proses_finishing';

    public const STATUS_SIAP_DIAMBIL = 'siap_diambil';

    public const STATUS_SELESAI = 'selesai';

    /**
     * @var array<int, string>
     */
    public const STATUS_PRODUKSI_LIST = [
        self::STATUS_MENUNGGU_DESAIN,
        self::STATUS_PROSES_DESAIN,
        self::STATUS_MENUNGGU_ACC,
        self::STATUS_ANTREAN_CETAK,
        self::STATUS_PROSES_CETAK,
        self::STATUS_ANTREAN_FINISHING,
        self::STATUS_PROSES_FINISHING,
        self::STATUS_SIAP_DIAMBIL,
        self::STATUS_SELESAI,
    ];

    /**
     * @var array<string, string>
     */
    public const LABEL_STATUS_PRODUKSI = [
        self::STATUS_MENUNGGU_DESAIN => 'Menunggu Desain',
        self::STATUS_PROSES_DESAIN => 'Proses Desain',
        self::STATUS_MENUNGGU_ACC => 'Menunggu ACC',
        self::STATUS_ANTREAN_CETAK => 'Antrean Cetak',
        self::STATUS_PROSES_CETAK => 'Proses Cetak',
        self::STATUS_ANTREAN_FINISHING => 'Antrean Finishing',
        self::STATUS_PROSES_FINISHING => 'Proses Finishing',
        self::STATUS_SIAP_DIAMBIL => 'Siap Diambil',
        self::STATUS_SELESAI => 'Selesai',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lebar_m' => 'decimal:2',
            'tinggi_m' => 'decimal:2',
            'qty' => 'integer',
            'luas_tagihan_m2' => 'decimal:2',
            'harga_satuan_dipakai' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function finishing(): BelongsTo
    {
        return $this->belongsTo(Finishing::class);
    }

    public function designRevisions(): HasMany
    {
        return $this->hasMany(DesignRevision::class);
    }

    public function materialLogs(): HasMany
    {
        return $this->hasMany(MaterialLog::class);
    }

    public function isMeteran(): bool
    {
        return $this->product?->kategori_produk === Product::KATEGORI_METERAN;
    }

    public function statusProduksiLabel(): string
    {
        return self::LABEL_STATUS_PRODUKSI[$this->status_produksi] ?? $this->status_produksi;
    }

    public function fileUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return Storage::disk(MediaStorage::DISK)->url($path);
    }

    public function previewUrl(): ?string
    {
        return $this->fileUrl($this->file_preview_acc);
    }

    /**
     * Ekstra finishing hanya dipakai internal produksi, bukan tagihan.
     *
     * @return array{kiri_cm: float, kanan_cm: float, atas_cm: float, bawah_cm: float}
     */
    public function ekstraFinishingCm(): array
    {
        return [
            'kiri_cm' => (float) ($this->finishing?->ekstra_kiri_cm ?? 0),
            'kanan_cm' => (float) ($this->finishing?->ekstra_kanan_cm ?? 0),
            'atas_cm' => (float) ($this->finishing?->ekstra_atas_cm ?? 0),
            'bawah_cm' => (float) ($this->finishing?->ekstra_bawah_cm ?? 0),
        ];
    }

    public function isStikerMeteran(): bool
    {
        return $this->isMeteran()
            && str_contains(strtolower((string) $this->product?->nama_produk), 'stiker');
    }

    /**
     * Rekomendasi roll untuk Stiker Meteran berdasarkan lebar kotor.
     */
    public function rekomendasiRollStiker(): ?string
    {
        if (! $this->isStikerMeteran()) {
            return null;
        }

        $lebarKotor = $this->ukuranKotor()['lebar'];

        return match (true) {
            $lebarKotor <= 1.0 => MaterialRoll::JENIS_STIKER_1M,
            $lebarKotor <= 1.5 => MaterialRoll::JENIS_STIKER_15M,
            default => null,
        };
    }

    /**
     * @return array{lebar: float, tinggi: float, luas: float}
     */
    public function ukuranKotor(): array
    {
        $ekstra = $this->ekstraFinishingCm();
        $lebar = round((float) $this->lebar_m + ($ekstra['kiri_cm'] + $ekstra['kanan_cm']) / 100, 2);
        $tinggi = round((float) $this->tinggi_m + ($ekstra['atas_cm'] + $ekstra['bawah_cm']) / 100, 2);

        return [
            'lebar' => $lebar,
            'tinggi' => $tinggi,
            'luas' => round($lebar * $tinggi * (int) $this->qty, 2),
        ];
    }
}
