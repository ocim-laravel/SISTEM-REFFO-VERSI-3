<?php

namespace App\Models;

use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'order_id',
    'order_item_id',
    'revisi_ke',
    'catatan_revisi',
    'file_preview',
    'admin_id',
    'status_revisi',
])]
class DesignRevision extends Model
{
    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_SELESAI = 'selesai';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'revisi_ke' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Admin yang mengajukan revisi (null untuk log preview dari desainer).
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function isPending(): bool
    {
        return $this->status_revisi === self::STATUS_MENUNGGU;
    }

    /**
     * URL publik file preview pada disk media lokal.
     */
    public function fileUrl(): ?string
    {
        if (! $this->file_preview) {
            return null;
        }

        return Storage::disk(MediaStorage::DISK)->url($this->file_preview);
    }

    public function statusLabel(): string
    {
        return match ($this->status_revisi) {
            self::STATUS_MENUNGGU => 'Menunggu desainer',
            self::STATUS_SELESAI => 'Selesai',
            default => 'Log preview',
        };
    }
}
