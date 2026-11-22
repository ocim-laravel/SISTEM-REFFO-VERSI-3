<?php

namespace App\Http\Controllers\Desainer;

use App\Events\OrderStatusChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\Desainer\FinalizeBatchDesignRequest;
use App\Http\Requests\Desainer\FinalizeDesignRequest;
use App\Http\Requests\Desainer\UploadMasterDesignRequest;
use App\Http\Requests\Desainer\UploadPreviewRequest;
use App\Models\DesignRevision;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DesignController extends Controller
{
    /**
     * Status yang masih berada di workspace desainer.
     *
     * @var array<int, string>
     */
    private const STATUS_ANTREAN = [
        OrderItem::STATUS_MENUNGGU_DESAIN,
        OrderItem::STATUS_PROSES_DESAIN,
        OrderItem::STATUS_MENUNGGU_ACC,
        OrderItem::STATUS_REVISI,
    ];

    /**
     * Antrean desain berupa kartu per nota + arsip master pada satu halaman dashboard.
     */
    public function dashboard(Request $request): View
    {
        $tab = $request->string('tab')->toString() === 'arsip' ? 'arsip' : 'antrean';
        $cari = trim((string) $request->query('q', ''));

        // Satu kartu mewakili satu nota. Nota yang sudah selesai desain tidak ditampilkan.
        $queue = Order::query()
            ->with(['customer', 'designer', 'items.product', 'items.finishing'])
            ->whereNull('file_master_arsip')
            ->whereHas('items', function (Builder $item): void {
                $item->whereIn('status_produksi', self::STATUS_ANTREAN);
            })
            ->latest()
            ->paginate(15, ['*'], 'queue_page')
            ->withQueryString();

        $arsip = Order::query()
            ->with(['customer', 'items.product'])
            ->whereNotNull('file_master_arsip')
            ->whereHas('items')
            ->when($cari !== '', function (Builder $query) use ($cari): void {
                $query->whereHas('customer', function (Builder $customer) use ($cari): void {
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
            'mediaFolders' => MediaStorage::foldersStatus(),
            'mediaReady' => MediaStorage::isReady(),
        ]);
    }

    /**
     * Detail item per nota: rincian item, preview TIFF, dan workspace desain terpaginasi.
     */
    public function detailItem(Request $request, Order $order): View
    {
        $order->load([
            'customer',
            'user',
            'designer',
            'designRevisions' => fn ($query) => $query
                ->with(['admin', 'orderItem'])
                ->orderBy('revisi_ke'),
        ]);

        $semuaItem = $order->items()
            ->with(['product', 'finishing', 'designRevisions.admin', 'accAdmin'])
            ->orderBy('id')
            ->get();

        // Gunakan koleksi yang sudah dimuat agar progress bar tidak memicu query ulang.
        $order->setRelation('items', $semuaItem);

        // Log preview/revisi tersimpan satu tingkat di atas item (level order).
        $semuaLog = $order->designRevisions;

        $halamanLog = max(1, (int) $request->query('log_page', 1));
        $logRevisi = new LengthAwarePaginator(
            $semuaLog->forPage($halamanLog, 15)->values(),
            $semuaLog->count(),
            15,
            $halamanLog,
            [
                'path' => $request->url(),
                'query' => $request->query(),
                'pageName' => 'log_page',
            ],
        );

        $items = $order->items()
            ->with(['product', 'finishing', 'designRevisions.admin', 'accAdmin'])
            ->orderBy('id')
            ->paginate(15, ['*'], 'item_page')
            ->withQueryString();

        $revisiPending = $semuaLog
            ->sortByDesc('revisi_ke')
            ->first(fn (DesignRevision $revision): bool => $revision->isPending());

        return view('dashboards.desainer-item-detail', [
            'order' => $order,
            'items' => $items,
            'semuaItem' => $semuaItem,
            'logRevisi' => $logRevisi,
            'nomorRevisiBerikutnya' => ((int) $semuaLog->max('revisi_ke')) + 1,
            'briefingItem' => $semuaItem->first(
                fn (OrderItem $item): bool => in_array($item->status_produksi, self::STATUS_ANTREAN, true),
            ),
            'masterSudahAda' => filled($order->file_master_arsip),
            'semuaTiffTerupload' => $semuaItem->isNotEmpty()
                && $semuaItem->every(fn (OrderItem $item): bool => filled($item->file_cetak_tiff)),
            'semuaMenunggu' => $semuaItem->isNotEmpty()
                && $semuaItem->every(fn (OrderItem $item): bool => $item->status_produksi === OrderItem::STATUS_MENUNGGU_DESAIN),
            'bisaTerima' => $semuaItem->isNotEmpty()
                && blank($order->designer_id)
                && $semuaItem->every(fn (OrderItem $item): bool => $item->status_produksi === OrderItem::STATUS_MENUNGGU_DESAIN),
            'bisaPreview' => $this->bisaUploadPreview($order, $semuaItem),
            'revisiPending' => $revisiPending,
            'progresDesain' => $order->progresDesainPersen(),
            'semuaLog' => $semuaLog,
            'jumlahRevisi' => $semuaLog->count(),
        ]);
    }

    /**
     * Riwayat Desain: nota dengan seluruh TIFF per item dan master terupload.
     */
    public function riwayat(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $orders = Order::query()
            ->with(['customer', 'designer', 'items.product', 'items.finishing'])
            ->whereNotNull('file_master_arsip')
            ->whereHas('items')
            ->whereDoesntHave('items', fn (Builder $item): mixed => $item->whereNull('file_cetak_tiff'))
            ->when($q !== '', function (Builder $query) use ($q): void {
                $query->where('no_nota', 'like', "%{$q}%")
                    ->orWhereHas('customer', function (Builder $customer) use ($q): void {
                        $customer->where('nama_pelanggan', 'like', "%{$q}%")
                            ->orWhere('instansi', 'like', "%{$q}%");
                    });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('dashboards.desainer-riwayat', [
            'orders' => $orders,
            'q' => $q,
            'dashboardRoute' => 'desainer.dashboard',
        ]);
    }

    /**
     * Terima job desain untuk satu nota (memulai timeline proses).
     */
    public function terimaJob(Order $order): RedirectResponse
    {
        abort_unless(auth()->user()?->role === User::ROLE_DESAINER, 403);

        DB::transaction(function () use ($order): void {
            /** @var Order $nota */
            $nota = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $items = $nota->items()->lockForUpdate()->get();

            abort_unless($items->isNotEmpty(), 422, 'Nota ini tidak memiliki item desain.');
            abort_if(
                filled($nota->designer_id) && (int) $nota->designer_id !== (int) auth()->id(),
                403,
                'Job ini sudah diterima desainer lain.',
            );

            $menunggu = $items->where('status_produksi', OrderItem::STATUS_MENUNGGU_DESAIN);

            abort_unless($menunggu->isNotEmpty(), 422, 'Tidak ada item yang masih menunggu desain pada nota ini.');

            $nota->items()
                ->whereIn('id', $menunggu->pluck('id'))
                ->update(['status_produksi' => OrderItem::STATUS_PROSES_DESAIN]);

            $nota->update([
                'designer_id' => $nota->designer_id ?: auth()->id(),
                'waktu_terima_desain' => $nota->waktu_terima_desain ?: now(),
                'waktu_selesai_desain' => null,
            ]);
        });

        return back()->with('status', 'Job diterima. Silakan unggah preview desain.');
    }

    /**
     * Unggah preview JPG/PNG sekali untuk seluruh order.
     * Saat menanggapi revisi Admin, semua catatan revisi pending ditutup.
     */
    public function uploadPreview(UploadPreviewRequest $request, Order $order): RedirectResponse
    {
        $this->pastikanBisaUploadPreview($order);

        $data = $request->validated();
        $revisiKe = 0;

        DB::transaction(function () use ($request, $order, $data, &$revisiKe): void {
            /** @var Order $nota */
            $nota = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $items = $nota->items()->lockForUpdate()->get();

            $this->pastikanBisaUploadPreview($nota, $items);

            // Satu preview menjawab seluruh catatan revisi Admin pada nota ini.
            $nota->designRevisions()
                ->where('status_revisi', DesignRevision::STATUS_MENUNGGU)
                ->update(['status_revisi' => DesignRevision::STATUS_SELESAI]);

            $revisiKe = ((int) $nota->designRevisions()->max('revisi_ke')) + 1;

            $file = $request->file('file_preview')->store(
                MediaStorage::FOLDER_PREVIEW_ACC,
                MediaStorage::DISK,
            );

            // Baris preview disimpan pada level order, bukan pada salah satu item.
            $nota->designRevisions()->create([
                'order_item_id' => null,
                'revisi_ke' => $revisiKe,
                'catatan_revisi' => $data['catatan_revisi'],
                'file_preview' => $file,
                'status_revisi' => DesignRevision::STATUS_SELESAI,
            ]);

            // Seluruh item aktif memakai preview yang sama dan menunggu ACC Admin.
            $nota->items()
                ->whereIn('status_produksi', [
                    OrderItem::STATUS_PROSES_DESAIN,
                    OrderItem::STATUS_REVISI,
                    OrderItem::STATUS_MENUNGGU_ACC,
                ])
                ->update([
                    'file_preview_acc' => $file,
                    'status_produksi' => OrderItem::STATUS_MENUNGGU_ACC,
                    'acc_admin_id' => null,
                    'waktu_acc' => null,
                ]);
        });

        return back()->with('status', "Preview revisi {$revisiKe} berhasil diunggah untuk seluruh order dan menunggu ACC Admin.");
    }

    /**
     * Finalisasi ACC: TIFF unik per item, satu file master untuk seluruh nota.
     * Hanya aktif jika status menunggu_acc, sudah ACC Admin, tanpa revisi pending.
     */
    public function finalisasi(FinalizeDesignRequest $request, OrderItem $orderItem): RedirectResponse
    {
        abort_unless($orderItem->bolehFinalisasi(), 403, 'Preview belum disetujui Admin atau masih ada revisi pending.');

        $selesai = collect();

        DB::transaction(function () use ($request, $orderItem, &$selesai): void {
            /** @var OrderItem $item */
            $item = OrderItem::query()
                ->whereKey($orderItem->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($item->bolehFinalisasi(), 403, 'Preview belum disetujui Admin atau masih ada revisi pending.');

            /** @var Order $order */
            $order = Order::query()
                ->whereKey($item->order_id)
                ->lockForUpdate()
                ->firstOrFail();

            $item->update([
                'file_cetak_tiff' => $request->file('file_cetak_tiff')->store(
                    MediaStorage::FOLDER_FILE_CETAK_TIFF,
                    MediaStorage::DISK,
                ),
            ]);

            $fileMaster = null;

            if ($request->hasFile('file_master_arsip')) {
                $fileMaster = $request->file('file_master_arsip')->store(
                    MediaStorage::FOLDER_FILE_MASTER_ARSIP,
                    MediaStorage::DISK,
                );
            }

            // Master hanya menutup proses bila seluruh TIFF per item sudah tersedia.
            if ($fileMaster || filled($order->file_master_arsip)) {
                $selesai = $this->selesaikanNota(
                    $order,
                    $fileMaster,
                    trim((string) $request->input('tanda_tangan', '')),
                );
            }
        });

        $this->kirimNotifikasiAntreanCetak($orderItem->order, $selesai);

        return back()->with('status', $selesai->isNotEmpty()
            ? 'Master desain tersimpan. Seluruh item telah dikirim ke antrean cetak.'
            : 'TIFF tersimpan. Master Desain dibuka setelah seluruh item memiliki TIFF.');
    }

    /**
     * Unggah TIFF per item sesuai urutan daftar item. Master belum menutup proses di sini.
     */
    public function finalisasiBatch(FinalizeBatchDesignRequest $request, Order $order): RedirectResponse
    {
        $data = $request->validated();
        $ids = array_map('intval', array_keys($data['items']));
        $selesai = collect();

        DB::transaction(function () use ($request, $order, $ids, &$selesai): void {
            /** @var Order $nota */
            $nota = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            /** @var Collection<int, OrderItem> $items */
            $items = $nota->items()
                ->with(['designRevisions', 'accAdmin'])
                ->whereIn('id', $ids)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            abort_unless($items->count() === count($ids), 422, 'Sebagian item tidak ditemukan pada nota ini.');

            foreach ($ids as $id) {
                /** @var OrderItem|null $item */
                $item = $items->get($id);

                abort_unless($item?->bolehFinalisasi(), 403, 'Preview belum disetujui Admin atau masih ada revisi pending.');
            }

            foreach ($ids as $id) {
                /** @var OrderItem $item */
                $item = $items->get($id);

                $item->update([
                    'file_cetak_tiff' => $request->file("items.{$id}.file_cetak_tiff")->store(
                        MediaStorage::FOLDER_FILE_CETAK_TIFF,
                        MediaStorage::DISK,
                    ),
                ]);
            }

            $fileMaster = $request->hasFile('file_master_arsip')
                ? $request->file('file_master_arsip')->store(
                    MediaStorage::FOLDER_FILE_MASTER_ARSIP,
                    MediaStorage::DISK,
                )
                : null;

            if ($fileMaster || filled($nota->file_master_arsip)) {
                $selesai = $this->selesaikanNota(
                    $nota,
                    $fileMaster,
                    trim((string) ($request->input('tanda_tangan') ?? '')),
                );
            }
        });

        $this->kirimNotifikasiAntreanCetak($order->fresh(), $selesai);

        return back()->with('status', $selesai->isNotEmpty()
            ? 'Master desain tersimpan. Seluruh item telah dikirim ke antrean cetak.'
            : count($ids).' TIFF tersimpan. Master Desain dibuka setelah seluruh item memiliki TIFF.');
    }

    /**
     * Master desain (satu per nota) hanya bisa diunggah setelah seluruh TIFF per item ada.
     */
    public function unggahMaster(UploadMasterDesignRequest $request, Order $order): RedirectResponse
    {
        $selesai = collect();

        DB::transaction(function () use ($request, $order, &$selesai): void {
            /** @var Order $nota */
            $nota = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $items = $nota->items()->with('designRevisions', 'accAdmin')->lockForUpdate()->get();

            abort_unless($items->isNotEmpty(), 422, 'Nota ini tidak memiliki item desain.');
            abort_unless(
                $items->every(fn (OrderItem $item): bool => filled($item->file_cetak_tiff)
                    && ($item->bolehFinalisasi() || $item->status_produksi === OrderItem::STATUS_ANTREAN_CETAK)),
                422,
                'Seluruh TIFF per item wajib diunggah lebih dulu.',
            );

            $fileMaster = $request->hasFile('file_master_arsip')
                ? $request->file('file_master_arsip')->store(
                    MediaStorage::FOLDER_FILE_MASTER_ARSIP,
                    MediaStorage::DISK,
                )
                : $nota->file_master_arsip;

            $selesai = $this->selesaikanNota(
                $nota,
                $fileMaster,
                trim((string) $request->input('tanda_tangan', '')),
            );
        });

        $this->kirimNotifikasiAntreanCetak($order->fresh(), $selesai);

        return back()->with('status', 'Master desain tersimpan. Proses desain selesai dan item masuk antrean cetak.');
    }

    /**
     * Tutup proses desain satu nota: satu master, tanda tangan, waktu selesai, dan antrean cetak.
     *
     * @return Collection<int, OrderItem>
     */
    private function selesaikanNota(Order $nota, ?string $fileMaster, string $tandaTangan): Collection
    {
        /** @var Collection<int, OrderItem> $items */
        $items = $nota->items()->with('accAdmin')->orderBy('id')->lockForUpdate()->get();

        abort_unless($items->isNotEmpty() && $items->every(fn (OrderItem $item): bool => filled($item->file_cetak_tiff)),
            422, 'Seluruh TIFF per item wajib diunggah sebelum master desain.');
        abort_unless((bool) $fileMaster, 422, 'Master desain wajib diunggah satu kali per nota.');

        $notaUpdate = ['file_master_arsip' => $fileMaster];

        if (! filled($nota->tanda_tangan_desainer)) {
            $notaUpdate['tanda_tangan_desainer'] = $tandaTangan ?: auth()->user()?->name;
        }

        if (! filled($nota->tanda_tangan_admin)) {
            $namaAdmin = $items->first(fn (OrderItem $item): bool => filled($item->acc_admin_id))?->accAdmin?->name;

            if ($namaAdmin) {
                $notaUpdate['tanda_tangan_admin'] = $namaAdmin;
            }
        }

        if (! $nota->waktu_selesai_desain) {
            $notaUpdate['waktu_selesai_desain'] = now();
        }

        $nota->update($notaUpdate);

        $baruSelesai = $items->filter(
            fn (OrderItem $item): bool => $item->status_produksi !== OrderItem::STATUS_ANTREAN_CETAK,
        );

        /** @var Collection<int, OrderItem> $items */
        $items->each(function (OrderItem $item) use ($fileMaster): void {
            $item->update([
                'file_master_arsip' => $fileMaster,
                'status_produksi' => OrderItem::STATUS_ANTREAN_CETAK,
            ]);
        });

        return $baruSelesai;
    }

    /**
     * @param  Collection<int, OrderItem>  $items
     */
    private function kirimNotifikasiAntreanCetak(Order $nota, Collection $items): void
    {
        foreach ($items as $item) {
            OrderStatusChanged::dispatch(
                $nota,
                $item,
                OrderItem::STATUS_ANTREAN_CETAK,
                [User::ROLE_ADMIN, User::ROLE_DESAINER, User::ROLE_OPERATOR_CETAK, User::ROLE_OWNER],
                "Nota {$nota->no_nota} masuk antrean cetak.",
            );
        }
    }

    public function unduhBriefing(OrderItem $orderItem): StreamedResponse
    {
        $this->pastikanDiAntreanDesain($orderItem);

        $path = $orderItem->order?->lampiran_briefing;

        abort_unless($path && Storage::disk(MediaStorage::DISK)->exists($path), 404, 'File briefing tidak ditemukan.');

        return Storage::disk(MediaStorage::DISK)->download($path, basename($path));
    }

    public function unduhPreview(Order $order, int $revisi): StreamedResponse
    {
        $revision = DesignRevision::query()
            ->whereKey($revisi)
            ->where('order_id', $order->id)
            ->firstOrFail();

        $path = $revision->file_preview;

        abort_unless($path && Storage::disk(MediaStorage::DISK)->exists($path), 404, 'File preview tidak ditemukan.');

        return Storage::disk(MediaStorage::DISK)->download($path, basename($path));
    }

    /**
     * Unduh TIFF yang sudah diunggah untuk keperluan popup pratinjau desainer.
     */
    public function unduhTiff(OrderItem $orderItem): StreamedResponse
    {
        $path = $orderItem->file_cetak_tiff;

        abort_unless($path && Storage::disk(MediaStorage::DISK)->exists($path), 404, 'File TIFF tidak ditemukan.');

        return Storage::disk(MediaStorage::DISK)->download($path, basename($path));
    }

    /**
     * Unduh master desain melalui nota (satu file untuk seluruh item).
     */
    public function unduhMaster(Order $order): StreamedResponse
    {
        $path = $order->file_master_arsip;

        abort_unless($path && Storage::disk(MediaStorage::DISK)->exists($path), 404, 'Master desain tidak ditemukan.');

        return Storage::disk(MediaStorage::DISK)->download($path, basename($path));
    }

    /**
     * Rute lama tetap didukung untuk gran / tautan yang tersimpan.
     */
    public function unduhMasterItem(OrderItem $orderItem): StreamedResponse
    {
        $path = $orderItem->order?->file_master_arsip ?: $orderItem->file_master_arsip;

        abort_unless($path && Storage::disk(MediaStorage::DISK)->exists($path), 404, 'Master desain tidak ditemukan.');

        return Storage::disk(MediaStorage::DISK)->download($path, basename($path));
    }

    private function pastikanDiAntreanDesain(OrderItem $item): void
    {
        abort_unless(in_array($item->status_produksi, self::STATUS_ANTREAN, true), 403, 'Item ini tidak berada di antrean desain.');
    }

    private function pastikanBisaUploadPreview(Order $order, ?Collection $items = null): void
    {
        $items ??= $order->items()->get();

        abort_unless(
            filled($order->designer_id) || filled($order->waktu_terima_desain),
            403,
            'Terima job terlebih dahulu sebelum mengunggah preview.',
        );

        abort_if(
            filled($order->file_master_arsip),
            403,
            'Proses desain nota ini sudah selesai.',
        );

        abort_unless(
            $items->contains(fn (OrderItem $item): bool => in_array($item->status_produksi, [
                OrderItem::STATUS_PROSES_DESAIN,
                OrderItem::STATUS_REVISI,
                OrderItem::STATUS_MENUNGGU_ACC,
            ], true)),
            403,
            'Preview hanya dapat diunggah saat job sedang berjalan atau menunggu ACC.',
        );
    }

    /**
     * Preview aktif setelah job diterima dan nota belum selesai desain.
     *
     * @param  Collection<int, OrderItem>  $items
     */
    private function bisaUploadPreview(Order $order, Collection $items): bool
    {
        if (blank($order->designer_id) && blank($order->waktu_terima_desain)) {
            return false;
        }

        if (filled($order->file_master_arsip)) {
            return false;
        }

        return $items->contains(fn (OrderItem $item): bool => in_array($item->status_produksi, [
            OrderItem::STATUS_PROSES_DESAIN,
            OrderItem::STATUS_REVISI,
            OrderItem::STATUS_MENUNGGU_ACC,
        ], true));
    }
}
