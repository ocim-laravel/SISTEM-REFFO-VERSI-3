<?php

namespace App\Http\Controllers\Admin;

use App\Events\PelunasanCompleted;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePelunasanRequest;
use App\Models\Order;
use App\Support\MediaStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PelunasanController extends Controller
{
    public function create(Order $order): View
    {
        // Nota yang sudah lunas tanpa bukti (misalnya dibuat langsung lunas)
        // masih boleh dilengkapi bukti; yang sudah ada buktinya tidak diubah.
        abort_if($order->bolehDiserahkan() && $order->bukti_pelunasan !== null, 403, 'Nota ini sudah lunas dan memiliki bukti.');

        $order->load(['customer', 'items.product']);

        return view('admin.orders.pelunasan', [
            'order' => $order,
        ]);
    }

    public function store(StorePelunasanRequest $request, Order $order): RedirectResponse
    {
        abort_if($order->bolehDiserahkan() && $order->bukti_pelunasan !== null, 403, 'Nota ini sudah lunas dan memiliki bukti.');

        $data = $request->validated();

        $path = $request->hasFile('bukti_pelunasan')
            ? $request->file('bukti_pelunasan')->store(
                MediaStorage::FOLDER_BUKTI_PELUNASAN,
                MediaStorage::DISK,
            )
            : null;

        $catatan = trim((string) ($data['catatan_pengecualian'] ?? ''));

        DB::transaction(function () use ($order, $path, $catatan): void {
            $update = [
                'jumlah_dp' => $order->total_tagihan,
                'status_bayar' => Order::STATUS_BAYAR_LUNAS,
                // Catatan lama tetap dipertahankan untuk audit bila tidak diisi ulang.
                'catatan_pengecualian' => $catatan !== '' ? $catatan : $order->catatan_pengecualian,
            ];

            if ($path !== null) {
                $update['bukti_pelunasan'] = $path;
            }

            $order->update($update);
        });

        PelunasanCompleted::dispatch($order->fresh());

        $pesan = "Pelunasan nota {$order->no_nota} berhasil dicatat. ";
        $pesan .= $path !== null ? 'Bukti tersimpan di media_sistem.' : 'Bukti tidak diunggah.';

        if ($catatan !== '') {
            $pesan .= ' Catatan pengecualian tersimpan untuk audit.';
        }

        return redirect()
            ->route('admin.orders.index', ['tab' => 'siap_diambil'])
            ->with('status', $pesan);
    }
}
