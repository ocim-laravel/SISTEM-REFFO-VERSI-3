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
    'designer_id',
    'total_tagihan',
    'jumlah_dp',
    'status_bayar',
    'status_desain',
    'catatan_desain',
    'lampiran_briefing',
    'file_master_arsip',
    'waktu_terima_desain',
    'waktu_selesai_desain',
    'tanda_tangan_desainer',
    'tanda_tangan_admin',
    'bukti_pelunasan',
    'foto_serah_terima',
    'nama_pengambil',
    'no_wa_pengambil',
    'waktu_serah_terima',
    'catatan_pengecualian',
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
            'waktu_serah_terima' => 'datetime',
            'waktu_terima_desain' => 'datetime',
            'waktu_selesai_desain' => 'datetime',
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

    public function designer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'designer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Log preview dan revisi level order (satu preview per nota).
     */
    public function designRevisions(): HasMany
    {
        return $this->hasMany(DesignRevision::class);
    }

    /**
     * Revisi Admin yang belum dijawab desainer pada nota ini.
     */
    public function revisiPending(): bool
    {
        return $this->designRevisions
            ->contains(fn (DesignRevision $revision): bool => $revision->isPending());
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

    public function instansiLabel(): string
    {
        return $this->customer?->instansi ?: Customer::INSTANSI_DEFAULT;
    }

    public function picLabel(): string
    {
        return $this->customer?->nama_pelanggan ?: '-';
    }

    /**
     * Ringkasan produksi pada level nota (bukan per item).
     * Pastikan relasi items sudah dimuat sebelum memanggil method ini.
     */
    public function statusProduksiRingkas(): string
    {
        $statusUnik = $this->items->pluck('status_produksi')->unique();

        if ($statusUnik->isEmpty()) {
            return 'Belum ada item';
        }

        if ($statusUnik->count() === 1) {
            return $this->items->first()?->statusProduksiLabel() ?? '-';
        }

        return 'Beragam ('.$statusUnik->count().' status)';
    }

    /**
     * Nota sudah diambil alih desainer (atau item sudah bergerak dari antrean awal).
     */
    public function diterimaDesainer(): bool
    {
        return filled($this->designer_id)
            || $this->items->contains(
                fn (OrderItem $item): bool => $item->status_produksi !== OrderItem::STATUS_MENUNGGU_DESAIN,
            );
    }

    /**
     * Progres desain nota (0-100) dari rata-rata progres seluruh item.
     * Pastikan relasi items sudah dimuat sebelum memanggil method ini.
     */
    public function progresDesainPersen(): int
    {
        if ($this->items->isEmpty()) {
            return 0;
        }

        if ($this->desainSelesai()) {
            return 100;
        }

        return (int) round(
            $this->items->avg(fn (OrderItem $item): int => $item->progresDesainPersen()),
        );
    }

    public function statusBayarLabel(): string
    {
        return self::LABEL_STATUS_BAYAR[$this->status_bayar] ?? $this->status_bayar;
    }

    public function statusDesainLabel(): string
    {
        return self::LABEL_STATUS_DESAIN[$this->status_desain] ?? $this->status_desain;
    }

    /**
     * Desain selesai hanya bila master nota terisi dan seluruh item memiliki TIFF.
     * Pastikan relasi items sudah dimuat sebelum memanggil method ini.
     */
    public function desainSelesai(): bool
    {
        return filled($this->file_master_arsip)
            && $this->items->isNotEmpty()
            && $this->items->every(fn (OrderItem $item): bool => filled($item->file_cetak_tiff));
    }

    /**
     * Lama proses sejak desainer menerima job sampai seluruh berkas terunggah.
     */
    public function durasiProsesDesain(bool $sampaiSekarang = false): ?string
    {
        if (! $this->waktu_terima_desain) {
            return null;
        }

        if (! $this->waktu_selesai_desain && ! $sampaiSekarang) {
            return null;
        }

        $selisih = $this->waktu_terima_desain->diff($this->waktu_selesai_desain ?? now());
        $bagian = [];

        if ($selisih->d > 0) {
            $bagian[] = $selisih->d.' hari';
        }

        if ($selisih->h > 0) {
            $bagian[] = $selisih->h.' jam';
        }

        if ($selisih->i > 0) {
            $bagian[] = $selisih->i.' menit';
        }

        return $bagian === [] ? '< 1 menit' : implode(' ', $bagian);
    }

    public function lampiranBriefingUrl(): ?string
    {
        if (! $this->lampiran_briefing) {
            return null;
        }

        return Storage::disk(MediaStorage::DISK)->url($this->lampiran_briefing);
    }

    public function buktiPelunasanUrl(): ?string
    {
        if (! $this->bukti_pelunasan) {
            return null;
        }

        return Storage::disk(MediaStorage::DISK)->url($this->bukti_pelunasan);
    }

    public function fotoSerahTerimaUrl(): ?string
    {
        if (! $this->foto_serah_terima) {
            return null;
        }

        return Storage::disk(MediaStorage::DISK)->url($this->foto_serah_terima);
    }

    /**
     * Serah terima hanya dibuka untuk nota yang sudah lunas.
     */
    public function bolehDiserahkan(): bool
    {
        return $this->status_bayar === self::STATUS_BAYAR_LUNAS;
    }
}
