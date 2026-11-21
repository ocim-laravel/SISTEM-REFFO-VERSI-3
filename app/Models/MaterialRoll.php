<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'kode_roll',
    'jenis_bahan',
    'lebar_m',
    'panjang_awal_m',
    'panjang_sisa_m',
    'status',
])]
class MaterialRoll extends Model
{
    public const STATUS_AKTIF = 'aktif';

    public const STATUS_HABIS = 'habis';

    /**
     * @var array<int, string>
     */
    public const STATUSES = [
        self::STATUS_AKTIF,
        self::STATUS_HABIS,
    ];

    /**
     * @var array<int, string>
     */
    public const JENIS_STIKER_1M = 'Stiker 1m';

    public const JENIS_STIKER_15M = 'Stiker 1.5m';

    /**
     * @var array<int, string>
     */
    public const JENIS_BAHAN = [
        'MMT 280',
        'MMT 340',
        'MMT 440',
        'Backlit',
        self::JENIS_STIKER_1M,
        self::JENIS_STIKER_15M,
    ];

    /**
     * Ukuran standar roll sesuai spesifikasi workshop.
     *
     * @return array{lebar_m: float, panjang_m: float}|null
     */
    public static function ukuranStandar(string $jenisBahan): ?array
    {
        return match ($jenisBahan) {
            'MMT 280', 'MMT 340', 'MMT 440', 'Backlit' => ['lebar_m' => 3.2, 'panjang_m' => 70.0],
            self::JENIS_STIKER_1M => ['lebar_m' => 1.0, 'panjang_m' => 50.0],
            self::JENIS_STIKER_15M => ['lebar_m' => 1.5, 'panjang_m' => 50.0],
            default => null,
        };
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lebar_m' => 'decimal:2',
            'panjang_awal_m' => 'decimal:2',
            'panjang_sisa_m' => 'decimal:2',
        ];
    }

    public function offcuts(): HasMany
    {
        return $this->hasMany(MaterialOffcut::class);
    }

    public function materialLogs(): HasMany
    {
        return $this->hasMany(MaterialLog::class);
    }
}
