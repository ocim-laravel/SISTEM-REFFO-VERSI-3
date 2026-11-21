<?php

namespace App\Http\Controllers\Desainer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Desainer\FinalizeDesignRequest;
use App\Http\Requests\Desainer\UploadPreviewRequest;
use App\Models\DesignRevision;
use App\Models\OrderItem;
use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DesignController extends Controller
{
    /**
     * Antrean desain + arsip master pada satu halaman dashboard.
     */
    public function dashboard(Request $request): View
    {
        $tab = $request->string('tab')->toString() === 'arsip' ? 'arsip' : 'antrean';
        $cari = trim((string) $request->query('q', ''));

        $queue = OrderItem::query()
            ->with(['order.customer', 'product', 'finishing', 'designRevisions'])
            ->whereIn('status_produksi', [
                OrderItem::STATUS_MENUNGGU_DESAIN,
                OrderItem::STATUS_PROSES_DESAIN,
                OrderItem::STATUS_MENUNGGU_ACC,
            ])
            ->latest()
            ->paginate(15, ['*'], 'queue_page')
            ->withQueryString();

        $nomorRevisiBerikutnya = [];
        foreach ($queue->items() as $item) {
            $nomorRevisiBerikutnya[$item->id] = ((int) $item->designRevisions->max('revisi_ke')) + 1;
        }

        $arsip = OrderItem::query()
            ->with(['order.customer', 'product'])
            ->whereNotNull('file_master_arsip')
            ->when($cari !== '', function (Builder $query) use ($cari): void {
                $query->whereHas('order.customer', function (Builder $customer) use ($cari): void {
                    $customer
                        ->where('nama_pelanggan', 'like', "%{$cari}%")
                        ->orWhere('instansi', 'like', "%{$cari}%");
                });
            })
            ->latest()
            ->paginate(15, ['*'], 'arsip_page')
            ->withQueryString();

        return view('dashboards.desainer', [
            'tab' => $tab,
            'cari' => $cari,
            'queue' => $queue,
            'arsip' => $arsip,
            'nomorRevisiBerikutnya' => $nomorRevisiBerikutnya,
            'mediaFolders' => MediaStorage::foldersStatus(),
            'mediaReady' => MediaStorage::isReady(),
        ]);
    }

    /**
     * Unggah preview ACC dan catat Log Revisi beruntun.
     */
    public function uploadPreview(UploadPreviewRequest $request, OrderItem $orderItem): RedirectResponse
    {
        $this->pastikanDiAntreanDesain($orderItem);

        $data = $request->validated();
        $revisiKe = 0;

        DB::transaction(function () use ($request, $orderItem, $data, &$revisiKe): void {
            $item = OrderItem::query()
                ->whereKey($orderItem->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->pastikanDiAntreanDesain($item);

            $revisiKe = ((int) $item->designRevisions()->max('revisi_ke')) + 1;

            $file = $request->file('file_preview')->store(
                MediaStorage::FOLDER_PREVIEW_ACC,
                MediaStorage::DISK,
            );

            $item->designRevisions()->create([
                'revisi_ke' => $revisiKe,
                'catatan_revisi' => $data['catatan_revisi'],
                'file_preview' => $file,
            ]);

            $item->update([
                'file_preview_acc' => $file,
                'status_produksi' => OrderItem::STATUS_MENUNGGU_ACC,
            ]);
        });

        return back()->with('status', "Revisi {$revisiKe} berhasil disimpan dan menunggu ACC.");
    }

    /**
     * Finalisasi ACC: wajib menyimpan file TIFF dan master arsip.
     */
    public function finalisasi(FinalizeDesignRequest $request, OrderItem $orderItem): RedirectResponse
    {
        $this->pastikanDiAntreanDesain($orderItem);

        DB::transaction(function () use ($request, $orderItem): void {
            $item = OrderItem::query()
                ->whereKey($orderItem->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->pastikanDiAntreanDesain($item);

            $fileCetak = $request->file('file_cetak_tiff')->store(
                MediaStorage::FOLDER_FILE_CETAK_TIFF,
                MediaStorage::DISK,
            );

            $fileMaster = $request->file('file_master_arsip')->store(
                MediaStorage::FOLDER_FILE_MASTER_ARSIP,
                MediaStorage::DISK,
            );

            $item->update([
                'file_cetak_tiff' => $fileCetak,
                'file_master_arsip' => $fileMaster,
                'status_produksi' => OrderItem::STATUS_ANTREAN_CETAK,
            ]);
        });

        return back()->with('status', 'Desain final. Item telah dikirim ke antrean cetak.');
    }

    public function unduhBriefing(OrderItem $orderItem): StreamedResponse
    {
        $this->pastikanDiAntreanDesain($orderItem);

        $path = $orderItem->order?->lampiran_briefing;

        abort_unless($path && Storage::disk(MediaStorage::DISK)->exists($path), 404, 'File briefing tidak ditemukan.');

        return Storage::disk(MediaStorage::DISK)->download($path, basename($path));
    }

    public function unduhPreview(OrderItem $orderItem, int $revisi): StreamedResponse
    {
        $revision = DesignRevision::query()
            ->whereKey($revisi)
            ->where('order_item_id', $orderItem->id)
            ->firstOrFail();

        $path = $revision->file_preview;

        abort_unless($path && Storage::disk(MediaStorage::DISK)->exists($path), 404, 'File preview tidak ditemukan.');

        return Storage::disk(MediaStorage::DISK)->download($path, basename($path));
    }

    public function unduhMaster(OrderItem $orderItem): StreamedResponse
    {
        $path = $orderItem->file_master_arsip;

        abort_unless($path && Storage::disk(MediaStorage::DISK)->exists($path), 404, 'Master desain tidak ditemukan.');

        return Storage::disk(MediaStorage::DISK)->download($path, basename($path));
    }

    private function pastikanDiAntreanDesain(OrderItem $item): void
    {
        abort_unless(in_array($item->status_produksi, [
            OrderItem::STATUS_MENUNGGU_DESAIN,
            OrderItem::STATUS_PROSES_DESAIN,
            OrderItem::STATUS_MENUNGGU_ACC,
        ], true), 403, 'Item ini tidak berada di antrean desain.');
    }
}
