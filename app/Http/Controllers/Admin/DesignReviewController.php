<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AjukanRevisiRequest;
use App\Models\DesignRevision;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Review preview desain dikendalikan penuh oleh Admin.
 */
class DesignReviewController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $items = OrderItem::query()
            ->with([
                'order.customer',
                'order.designRevisions' => fn ($revisi) => $revisi->latest('revisi_ke'),
                'product',
                'finishing',
                'designRevisions' => fn ($revisi) => $revisi->latest('revisi_ke'),
            ])
            ->where('status_produksi', OrderItem::STATUS_MENUNGGU_ACC)
            ->whereNull('acc_admin_id')
            ->whereNotNull('file_preview_acc')
            ->when($q !== '', function ($query) use ($q): void {
                $query->whereHas('order', function ($order) use ($q): void {
                    $order->where('no_nota', 'like', "%{$q}%")
                        ->orWhereHas('customer', function ($customer) use ($q): void {
                            $customer->where('nama_pelanggan', 'like', "%{$q}%")
                                ->orWhere('instansi', 'like', "%{$q}%");
                        });
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.design-reviews.index', [
            'items' => $items,
            'q' => $q,
        ]);
    }

    /**
     * Setujui preview terakhir: membuka form finalisasi TIFF + master untuk desainer.
     */
    public function setujui(OrderItem $orderItem): RedirectResponse
    {
        abort_unless(auth()->user()?->role === User::ROLE_ADMIN, 403);

        DB::transaction(function () use ($orderItem): void {
            /** @var OrderItem $item */
            $item = OrderItem::query()
                ->whereKey($orderItem->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($item->status_produksi === OrderItem::STATUS_MENUNGGU_ACC, 422, 'Preview tidak sedang menunggu ACC.');
            abort_unless(filled($item->file_preview_acc), 422, 'Preview belum tersedia.');
            abort_unless(! ($item->order?->revisiPending() ?? $item->revisiPending()), 422, 'Masih ada revisi pending.');

            $item->update([
                'acc_admin_id' => auth()->id(),
                'waktu_acc' => now(),
            ]);

            Order::query()->whereKey($item->order_id)->update([
                'tanda_tangan_admin' => auth()->user()?->name,
            ]);
        });

        return back()->with('status', 'Preview disetujui. Desainer dapat mengunggah TIFF dan master desain.');
    }

    /**
     * Ajukan revisi: catatan wajib, admin_id dan waktu tersimpan pada log revisi.
     */
    public function ajukanRevisi(AjukanRevisiRequest $request, OrderItem $orderItem): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($orderItem, $data): void {
            /** @var OrderItem $item */
            $item = OrderItem::query()
                ->whereKey($orderItem->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($item->status_produksi === OrderItem::STATUS_MENUNGGU_ACC, 422, 'Preview tidak sedang menunggu ACC.');

            $revisiKe = ((int) DesignRevision::query()->where('order_id', $item->order_id)->max('revisi_ke')) + 1;

            $item->order->designRevisions()->create([
                'order_id' => $item->order_id,
                'order_item_id' => null,
                'revisi_ke' => $revisiKe,
                'catatan_revisi' => $data['catatan_revisi'],
                'admin_id' => auth()->id(),
                'status_revisi' => DesignRevision::STATUS_MENUNGGU,
            ]);

            $item->update([
                'status_produksi' => OrderItem::STATUS_REVISI,
                'acc_admin_id' => null,
                'waktu_acc' => null,
            ]);
        });

        return back()->with('status', 'Revisi diajukan ke desainer beserta catatan Admin.');
    }
}
