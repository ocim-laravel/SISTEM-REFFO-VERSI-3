<?php

namespace App\Http\Controllers\Finishing;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Support\MediaStorage;
use Illuminate\Http\RedirectResponse;
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

    public function konfirmasi(OrderItem $orderItem): RedirectResponse
    {
        abort_unless(in_array($orderItem->status_produksi, [
            OrderItem::STATUS_ANTREAN_FINISHING,
            OrderItem::STATUS_PROSES_FINISHING,
        ], true), 403, 'Item ini tidak berada di antrean finishing.');

        DB::transaction(function () use ($orderItem): void {
            $orderItem->update(['status_produksi' => OrderItem::STATUS_SIAP_DIAMBIL]);
        });

        return back()->with('status', 'Produksi selesai. Item siap diambil.');
    }
}
