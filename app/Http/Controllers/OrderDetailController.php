<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Support\RiwayatSelesai;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman detail order dan detail item yang dipakai bersama oleh seluruh role.
 * Akses tetap dibatasi middleware role pada masing-masing grup rute.
 */
class OrderDetailController extends Controller
{
    /**
     * Halaman detail nota: daftar item terpagINASI dengan tombol Detail per item.
     */
    public function show(Request $request, Order $order): View
    {
        $divisi = $this->divisiDariRute($request);

        $order->load(['customer', 'user', 'items.product', 'items.finishing', 'advertisingSpks']);

        $items = $order->items()
            ->with(['product', 'finishing'])
            ->orderBy('id')
            ->paginate(15, ['*'], 'item_page')
            ->withQueryString();

        return view('orders.detail', [
            'order' => $order,
            'items' => $items,
            'divisi' => $divisi,
            'judulDivisi' => $this->judulDivisi($divisi),
            'tampilkanHarga' => $this->tampilkanHarga($divisi),
            'kembaliRoute' => match ($divisi) {
                'admin' => 'admin.orders.index',
                'owner' => 'owner.rekap-orders',
                default => $divisi.'.riwayat',
            },
        ]);
    }

    /**
     * Halaman detail satu item pada nota, lengkap dengan navigasi antar item.
     */
    public function showItem(Request $request, Order $order, OrderItem $orderItem): View
    {
        $divisi = $this->divisiDariRute($request);

        abort_unless($orderItem->order_id === $order->id, 404, 'Item tidak ditemukan pada nota ini.');

        $order->load([
            'customer',
            'user',
            'designRevisions' => fn ($query) => $query->orderByDesc('revisi_ke'),
        ]);
        $orderItem->load(['product', 'finishing', 'designRevisions.admin', 'accAdmin']);

        $ids = $order->items()->orderBy('id')->pluck('id')->all();
        $posisi = (int) array_search($orderItem->id, $ids, true);

        /** @var OrderItem|null $sebelumnya */
        $sebelumnya = $posisi > 0 ? OrderItem::query()->find($ids[$posisi - 1]) : null;
        /** @var OrderItem|null $berikutnya */
        $berikutnya = $posisi < count($ids) - 1 ? OrderItem::query()->find($ids[$posisi + 1]) : null;

        return view('orders.item-detail', [
            'order' => $order,
            'item' => $orderItem,
            'divisi' => $divisi,
            'judulDivisi' => $this->judulDivisi($divisi),
            'tampilkanHarga' => $this->tampilkanHarga($divisi),
            'kembaliRoute' => $divisi.'.orders.show',
            'itemSebelumnya' => $sebelumnya,
            'itemBerikutnya' => $berikutnya,
            'posisiItem' => $posisi + 1,
            'totalItem' => count($ids),
        ]);
    }

    private function divisiDariRute(Request $request): string
    {
        return match (true) {
            $request->routeIs('admin.*') => 'admin',
            $request->routeIs('desainer.*') => RiwayatSelesai::DESAINER,
            $request->routeIs('operator.*') => RiwayatSelesai::OPERATOR,
            $request->routeIs('finishing.*') => RiwayatSelesai::FINISHING,
            $request->routeIs('advertising.*') => RiwayatSelesai::ADVERTISING,
            $request->routeIs('owner.*') => 'owner',
            default => abort(403, 'Role tidak dikenal.'),
        };
    }

    private function judulDivisi(string $divisi): string
    {
        return in_array($divisi, ['admin', 'owner'], true)
            ? ucfirst($divisi)
            : RiwayatSelesai::label($divisi);
    }

    private function tampilkanHarga(string $divisi): bool
    {
        return in_array($divisi, ['admin', 'owner'], true);
    }
}
