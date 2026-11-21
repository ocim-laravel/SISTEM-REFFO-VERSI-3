<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_item_id',
    'material_roll_id',
    'material_offcut_id',
    'luas_netto_m2',
    'wos_finishing_m2',
    'wos_potong_m2',
    'gagal_cetak_m2',
    'keterangan',
])]
class MaterialLog extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'luas_netto_m2' => 'decimal:2',
            'wos_finishing_m2' => 'decimal:2',
            'wos_potong_m2' => 'decimal:2',
            'gagal_cetak_m2' => 'decimal:2',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function materialRoll(): BelongsTo
    {
        return $this->belongsTo(MaterialRoll::class);
    }

    public function materialOffcut(): BelongsTo
    {
        return $this->belongsTo(MaterialOffcut::class);
    }
}
