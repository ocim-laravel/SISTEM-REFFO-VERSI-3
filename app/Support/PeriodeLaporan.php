<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Rentang tanggal bersama untuk seluruh laporan owner.
 */
final class PeriodeLaporan
{
    public function __construct(
        public readonly string $dari,
        public readonly string $sampai,
    ) {}

    public static function dariRequest(Request $request): self
    {
        $data = $request->validate([
            'dari' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'sampai' => ['sometimes', 'required', 'date_format:Y-m-d'],
        ]);

        $dari = $data['dari'] ?? now()->startOfMonth()->toDateString();
        $sampai = $data['sampai'] ?? now()->toDateString();

        if ($dari > $sampai) {
            throw ValidationException::withMessages([
                'sampai' => 'Tanggal akhir tidak boleh lebih awal dari tanggal awal.',
            ]);
        }

        return new self($dari, $sampai);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function rentangWaktu(): array
    {
        return [
            Carbon::parse($this->dari)->startOfDay(),
            Carbon::parse($this->sampai)->endOfDay(),
        ];
    }

    public function whereCreatedAt(Builder $query, string $kolom = 'created_at'): Builder
    {
        return $query->whereBetween($kolom, $this->rentangWaktu());
    }
}
