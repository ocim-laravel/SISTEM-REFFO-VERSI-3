<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'material_roll_id',
    'jenis_bahan',
    'lebar_m',
    'panjang_m',
    'luas_m2',
    'status',
])]
class MaterialOffcut extends Model
{
    public const STATUS_TERSEDIA = 'tersedia';

    public const STATUS_TERPAKAI = 'terpakai';

    /**
     * @var array<int, string>
     */
    public const STATUSES = [
        self::STATUS_TERSEDIA,
        self::STATUS_TERPAKAI,
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
            'panjang_m' => 'decimal:2',
            'luas_m2' => 'decimal:2',
        ];
    }

    public function materialRoll(): BelongsTo
    {
        return $this->belongsTo(MaterialRoll::class);
    }

    public function materialLogs(): HasMany
    {
        return $this->hasMany(MaterialLog::class);
    }
}
