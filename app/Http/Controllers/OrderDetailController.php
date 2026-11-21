<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\RiwayatSelesai;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman detail order yang dipakai bersama oleh seluruh role.
 * Akses tetap dibatasi middleware role pada masing-masing grup rute.
 */
class OrderDetailController extends Controller
{
    public function show(Request $request, Order $order): View
    {
        $divisi = match (true) {
            $request->routeIs('admin.*') => 'admin',
            $request->routeIs('desainer.*') => RiwayatSelesai::DESAINER,
            $request->routeIs('operator.*') => RiwayatSelesai::OPERATOR,
            $request->routeIs('finishing.*') => RiwayatSelesai::FINISHING,
            $request->routeIs('advertising.*') => RiwayatSelesai::ADVERTISING,
            $request->routeIs('owner.*') => 'owner',
            default => abort(403, 'Role tidak dikenal.'),
        };

        $order->load(['customer', 'user', 'items.product', 'items.finishing', 'advertisingSpks']);

        return view('orders.detail', [
            'order' => $order,
            'divisi' => $divisi,
            'judulDivisi' => in_array($divisi, ['admin', 'owner'], true)
                ? ucfirst($divisi)
                : RiwayatSelesai::label($divisi),
            'tampilkanHarga' => in_array($divisi, ['admin', 'owner'], true),
            'kembaliRoute' => match ($divisi) {
                'admin' => 'admin.orders.index',
                'owner' => 'owner.rekap-orders',
                default => $divisi.'.riwayat',
            },
        ]);
    }
}
