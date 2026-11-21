<?php

namespace App\Support;

use App\Models\AdvertisingSpk;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Satu sumber aturan untuk halaman audit "Riwayat Selesai" tiap divisi.
 *
 * Semua daftar tetap berbasis order (satu baris = satu nota). Item yang sudah
 * diproses divisi hanya dipakai sebagai ringkasan pada baris tersebut;
 * rincian lengkapnya dibuka lewat halaman detail order.
 */
final class RiwayatSelesai
{
    public const DESAINER = 'desainer';

    public const OPERATOR = 'operator';

    public const FINISHING = 'finishing';

    public const ADVERTISING = 'advertising';

    /**
     * @var array<int, string>
     */
    public const DIVISI = [
        self::DESAINER,
        self::OPERATOR,
        self::FINISHING,
        self::ADVERTISING,
    ];

    /**
     * Item dianggap selesai diproses operator setelah cetak selesai.
     *
     * @var array<int, string>
     */
    private const STATUS_SELESAI_CETAK = [
        OrderItem::STATUS_ANTREAN_FINISHING,
        OrderItem::STATUS_PROSES_FINISHING,
        OrderItem::STATUS_SIAP_DIAMBIL,
        OrderItem::STATUS_SELESAI,
    ];

    /**
     * @var array<int, string>
     */
    private const STATUS_SELESAI_FINISHING = [
        OrderItem::STATUS_SIAP_DIAMBIL,
        OrderItem::STATUS_SELESAI,
    ];

    public static function label(string $divisi): string
    {
        return match ($divisi) {
            self::DESAINER => 'Desainer',
            self::OPERATOR => 'Operator Cetak',
            self::FINISHING => 'Finishing',
            self::ADVERTISING => 'Advertising',
            default => 'Divisi',
        };
    }

    public static function orders(Request $request, string $divisi): LengthAwarePaginator
    {
        abort_unless(in_array($divisi, self::DIVISI, true), 403);

        $q = trim((string) $request->query('q', ''));

        $query = Order::query()
            ->with(['customer', 'items.product', 'items.finishing', 'advertisingSpks'])
            ->withCount('items');

        self::filterDivisi($query, $divisi);

        if ($q !== '') {
            $query->where(function (Builder $query) use ($q): void {
                $query->where('no_nota', 'like', "%{$q}%")
                    ->orWhereHas('customer', function (Builder $customer) use ($q): void {
                        $customer->where('instansi', 'like', "%{$q}%")
                            ->orWhere('nama_pelanggan', 'like', "%{$q}%");
                    });
            });
        }

        $orders = $query->latest()->paginate(15)->withQueryString();

        foreach ($orders as $order) {
            $order->setRelation('riwayatItems', self::itemsDiproses($order, $divisi));
        }

        return $orders;
    }

    /**
     * Item pada sebuah nota yang sudah diproses oleh divisi tersebut.
     *
     * @return Collection<int, OrderItem>
     */
    public static function itemsDiproses(Order $order, string $divisi): Collection
    {
        return $order->items
            ->filter(fn (OrderItem $item): bool => match ($divisi) {
                self::DESAINER => filled($item->file_master_arsip),
                self::OPERATOR => in_array($item->status_produksi, self::STATUS_SELESAI_CETAK, true),
                self::FINISHING => in_array($item->status_produksi, self::STATUS_SELESAI_FINISHING, true),
                default => false,
            })
            ->values();
    }

    /**
     * @return Collection<int, AdvertisingSpk>
     */
    public static function spkSelesai(Order $order): Collection
    {
        return $order->advertisingSpks
            ->where('status_lapangan', AdvertisingSpk::STATUS_SELESAI)
            ->values();
    }

    /**
     * Terapkan filter relevansi divisi (dipakai riwayat dan serah terima).
     */
    public static function filterDivisi(Builder $query, string $divisi): void
    {
        match ($divisi) {
            self::DESAINER => $query->whereHas(
                'items',
                fn (Builder $item) => $item->whereNotNull('file_master_arsip'),
            ),
            self::OPERATOR => $query->whereHas(
                'items',
                fn (Builder $item) => $item->whereIn('status_produksi', self::STATUS_SELESAI_CETAK),
            ),
            self::FINISHING => $query->whereHas(
                'items',
                fn (Builder $item) => $item->whereIn('status_produksi', self::STATUS_SELESAI_FINISHING),
            ),
            self::ADVERTISING => $query->whereHas(
                'advertisingSpks',
                fn (Builder $spk) => $spk->where('status_lapangan', AdvertisingSpk::STATUS_SELESAI),
            ),
        };
    }
}
