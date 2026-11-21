<?php

namespace App\Http\Controllers\Owner;

use App\Models\AdvertisingSpk;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends OwnerController
{
    public function index(): View
    {
        $this->pastikanOwner();

        $statistik = [
            'desain' => OrderItem::query()
                ->whereIn('status_produksi', [
                    OrderItem::STATUS_MENUNGGU_DESAIN,
                    OrderItem::STATUS_PROSES_DESAIN,
                    OrderItem::STATUS_MENUNGGU_ACC,
                ])->count(),
            'cetak' => OrderItem::query()
                ->whereIn('status_produksi', [
                    OrderItem::STATUS_ANTREAN_CETAK,
                    OrderItem::STATUS_PROSES_CETAK,
                ])->count(),
            'finishing' => OrderItem::query()
                ->whereIn('status_produksi', [
                    OrderItem::STATUS_ANTREAN_FINISHING,
                    OrderItem::STATUS_PROSES_FINISHING,
                ])->count(),
            'advertising' => AdvertisingSpk::query()
                ->where('status_lapangan', '!=', AdvertisingSpk::STATUS_SELESAI)
                ->count(),
            'siap_diambil' => OrderItem::query()
                ->where('status_produksi', OrderItem::STATUS_SIAP_DIAMBIL)
                ->count(),
        ];

        $deadlines = Order::query()
            ->with('customer')
            ->whereNotNull('deadline')
            ->where('deadline', '<=', now()->addDays(3))
            ->whereHas('items', fn (Builder $item): Builder => $item
                ->whereNotIn('status_produksi', [OrderItem::STATUS_SELESAI]))
            ->orderBy('deadline')
            ->limit(10)
            ->get()
            ->map(fn (Order $order): array => [
                'id' => $order->id,
                'no_nota' => $order->no_nota,
                'instansi' => $order->customer?->instansi ?: Customer::INSTANSI_DEFAULT,
                'pic' => $order->customer?->nama_pelanggan ?: '-',
                'deadline' => $order->deadline,
                'terlambat' => $order->deadline?->isPast() ?? false,
                'sisa_hari' => (int) now()->diffInDays($order->deadline, false),
            ]);

        $rekap = $this->rekapInstansi();

        return view('owner.dashboard', [
            'statistik' => $statistik,
            'deadlines' => $deadlines,
            'rekap' => $rekap,
            'totalRekap' => [
                'nota' => $rekap->sum('total_nota'),
                'omzet' => $rekap->sum('omzet'),
                'piutang' => $rekap->sum('piutang'),
            ],
        ]);
    }

    /**
     * Rekap transaksi berbasis order (satu baris = satu nota) dengan pagination.
     */
    public function rekapOrders(Request $request): View
    {
        $this->pastikanOwner();

        $q = trim((string) $request->query('q', ''));

        $query = Order::query()
            ->with(['customer', 'items.product', 'items.finishing'])
            ->withCount('items');

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

        return view('owner.rekap-orders', [
            'orders' => $orders,
            'q' => $q,
            'totalNota' => Order::query()->count(),
            'totalOmzet' => (float) Order::query()->sum('total_tagihan'),
            'totalPiutang' => (float) Order::query()->sum('total_tagihan')
                - (float) Order::query()->sum('jumlah_dp'),
        ]);
    }

    /**
     * Total nota/omzet/piutang per instansi dengan rincian per PIC.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function rekapInstansi(): Collection
    {
        $orders = Order::query()
            ->with('customer:id,instansi,nama_pelanggan')
            ->get(['id', 'customer_id', 'total_tagihan', 'jumlah_dp']);

        $grup = [];

        foreach ($orders as $order) {
            $instansi = $order->customer?->instansi ?: Customer::INSTANSI_DEFAULT;
            $pic = $order->customer?->nama_pelanggan ?: '-';
            $tagihan = (float) $order->total_tagihan;
            $piutang = max($tagihan - (float) $order->jumlah_dp, 0.0);

            if (! isset($grup[$instansi])) {
                $grup[$instansi] = [
                    'instansi' => $instansi,
                    'total_nota' => 0,
                    'omzet' => 0.0,
                    'piutang' => 0.0,
                    'pics' => [],
                ];
            }

            if (! isset($grup[$instansi]['pics'][$pic])) {
                $grup[$instansi]['pics'][$pic] = [
                    'nama' => $pic,
                    'total_nota' => 0,
                    'omzet' => 0.0,
                    'piutang' => 0.0,
                ];
            }

            $grup[$instansi]['total_nota']++;
            $grup[$instansi]['omzet'] += $tagihan;
            $grup[$instansi]['piutang'] += $piutang;

            $grup[$instansi]['pics'][$pic]['total_nota']++;
            $grup[$instansi]['pics'][$pic]['omzet'] += $tagihan;
            $grup[$instansi]['pics'][$pic]['piutang'] += $piutang;
        }

        return collect($grup)
            ->map(function (array $item): array {
                $item['pics'] = collect($item['pics'])
                    ->sortBy('nama')
                    ->values()
                    ->all();

                return $item;
            })
            ->sortByDesc('omzet')
            ->values();
    }
}
