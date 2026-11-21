<?php

namespace App\Http\Controllers\Finishing;

use App\Events\OrderStatusChanged;
use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\User;
use App\Support\MediaStorage;
use App\Support\RiwayatSelesai;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinishingController extends Controller
{
    public function dashboard(): View
    {
        $items = OrderItem::query()
            ->with(['order.customer', 'product', 'finishing'])
            ->whereIn('status_produksi', [
                OrderItem::STATUS_ANTREAN_FINISHING,
                OrderItem::STATUS_PROSES_FINISHING,
            ])
            ->latest()
            ->paginate(15);

        return view('dashboards.finishing', [
            'items' => $items,
            'mediaFolders' => MediaStorage::foldersStatus(),
            'mediaReady' => MediaStorage::isReady(),
        ]);
    }

    /**
     * Audit order yang sudah selesai finishing (satu baris per nota).
     */
    public function riwayat(Request $request): View
    {
        $divisi = RiwayatSelesai::FINISHING;

        return view('dashboards.riwayat', [
            'divisi' => $divisi,
            'judulDivisi' => RiwayatSelesai::label($divisi),
            'orders' => RiwayatSelesai::orders($request, $divisi),
            'q' => trim((string) $request->query('q', '')),
            'dashboardRoute' => 'finishing.dashboard',
        ]);
    }

    public function konfirmasi(OrderItem $orderItem): RedirectResponse
    {
        abort_unless(in_array($orderItem->status_produksi, [
            OrderItem::STATUS_ANTREAN_FINISHING,
            OrderItem::STATUS_PROSES_FINISHING,
        ], true), 403, 'Item ini tidak berada di antrean finishing.');

        DB::transaction(function () use ($orderItem): void {
            $orderItem->update(['status_produksi' => OrderItem::STATUS_SIAP_DIAMBIL]);
        });

        $item = $orderItem->fresh(['order']);
        OrderStatusChanged::dispatch(
            $item->order,
            $item,
            OrderItem::STATUS_SIAP_DIAMBIL,
            [User::ROLE_ADMIN, User::ROLE_FINISHING, User::ROLE_DESAINER, User::ROLE_OPERATOR_CETAK, User::ROLE_ADVERTISING, User::ROLE_OWNER],
            "Nota {$item->order?->no_nota} siap diambil.",
        );

        return back()->with('status', 'Produksi selesai. Item siap diambil.');
    }
}
