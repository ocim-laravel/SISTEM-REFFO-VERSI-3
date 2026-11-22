<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nama_instansi'])]
class Instansi extends Model
{
    public const DEFAULT = 'Order Pribadi';

    /**
     * @var array<int, string>
     */
    public const DEFAULT_LIST = [
        self::DEFAULT,
        'Telkomsel',
        'Telkomsel Area Bandung',
        'RS Sehat Bersama',
        'Universitas Langit Bangsa',
        'Bank Jabar Cabang Dago',
        'PTO Mutiara Motor',
        'Diskominfo Kota Bandung',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
