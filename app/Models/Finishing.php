<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'nama_finishing',
    'ekstra_kiri_cm',
    'ekstra_kanan_cm',
    'ekstra_atas_cm',
    'ekstra_bawah_cm',
])]
class Finishing extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ekstra_kiri_cm' => 'decimal:2',
            'ekstra_kanan_cm' => 'decimal:2',
            'ekstra_atas_cm' => 'decimal:2',
            'ekstra_bawah_cm' => 'decimal:2',
        ];
    }
}
