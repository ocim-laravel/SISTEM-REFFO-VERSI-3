<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\LaporGagalRequest;
use App\Http\Requests\Operator\SelesaiCetakRequest;
use App\Models\MaterialLog;
use App\Models\MaterialOffcut;
use App\Models\MaterialRoll;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\MediaStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductionController extends Controller
{
    public function dashboard(Request $request): View
    {
        $items = OrderItem::query()
            ->with(['order.customer', 'product', 'finishing'])
            ->whereIn('status_produksi', [
                OrderItem::STATUS_ANTREAN_CETAK,
                OrderItem::STATUS_PROSES_CETAK,
            ])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $rollOptions = MaterialRoll::query()
            ->where('status', MaterialRoll::STATUS_AKTIF)
            ->orderBy('jenis_bahan')
            ->orderBy('kode_roll')
            ->get()
            ->map(fn (MaterialRoll $roll): array => [
                'id' => $roll->id,
                'kode' => $roll->kode_roll,
                'jenis' => $roll->jenis_bahan,
                'lebar' => (float) $roll->lebar_m,
                'sisa' => (float) $roll->panjang_sisa_m,
            ])
            ->values()
            ->all();

        $offcutOptions = MaterialOffcut::query()
            ->where('status', MaterialOffcut::STATUS_TERSEDIA)
            ->orderBy('jenis_bahan')
            ->orderBy('lebar_m')
            ->get()
            ->map(fn (MaterialOffcut $offcut): array => [
                'id' => $offcut->id,
                'jenis' => $offcut->jenis_bahan,
                'lebar' => (float) $offcut->lebar_m,
                'panjang' => (float) $offcut->panjang_m,
                'luas' => (float) $offcut->luas_m2,
            ])
            ->values()
            ->all();

        $itemData = $items->getCollection()
            ->mapWithKeys(function (OrderItem $item) use ($rollOptions, $offcutOptions): array {
                return [$item->id => [
                    'id' => $item->id,
                    'no_nota' => $item->order?->no_nota,
                    'produk' => $item->product?->nama_produk,
                    'finishing' => $item->finishing?->nama_finishing,
                    'lebar' => (float) $item->lebar_m,
                    'tinggi' => (float) $item->tinggi_m,
                    'qty' => (int) $item->qty,
                    'kotor' => $item->isMeteran() ? $item->ukuranKotor() : null,
                    'rekomendasi' => $item->rekomendasiRollStiker(),
                    'stok' => $item->product?->stok_pcs,
                    'rolls' => $rollOptions,
                    'offcuts' => $offcutOptions,
                    'url_selesai' => route('operator.items.selesai', $item),
                    'url_gagal' => route('operator.items.gagal-cetak', $item),
                ]];
            })
            ->all();

        return view('dashboards.operator', [
            'items' => $items,
            'itemData' => $itemData,
            'mediaFolders' => MediaStorage::foldersStatus(),
            'mediaReady' => MediaStorage::isReady(),
        ]);
    }

    public function mulaiCetak(OrderItem $orderItem): RedirectResponse
    {
        abort_unless($orderItem->status_produksi === OrderItem::STATUS_ANTREAN_CETAK, 403);

        DB::transaction(function () use ($orderItem): void {
            $orderItem->update(['status_produksi' => OrderItem::STATUS_PROSES_CETAK]);
        });

        return back()->with('status', "Item {$orderItem->id} mulai diproses cetak.");
    }

    /**
     * Selesai cetak: potong stok (meteran) atau kurangi stok pcs (satuan),
     * lalu tentukan status produksi berikutnya.
     */
    public function selesaiCetak(SelesaiCetakRequest $request, OrderItem $orderItem): RedirectResponse
    {
        $this->pastikanDiAntreanCetak($orderItem);

        $data = $request->validated();

        DB::transaction(function () use ($request, $orderItem, $data): void {
            $item = OrderItem::query()
                ->with(['product', 'finishing'])
                ->whereKey($orderItem->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->pastikanDiAntreanCetak($item);

            $qty = (int) $item->qty;

            if ($item->isMeteran()) {
                $this->potongBahan($item, $data);
            } elseif ($item->product?->kategori_produk === Product::KATEGORI_SATUAN) {
                $this->kurangiStokSatuan($item, $qty);
            }

            $statusBerikutnya = $item->finishing_id
                ? OrderItem::STATUS_ANTREAN_FINISHING
                : OrderItem::STATUS_SIAP_DIAMBIL;

            $item->update(['status_produksi' => $statusBerikutnya]);
        });

        return back()->with('status', 'Cetak selesai. Status produksi telah diperbarui.');
    }

    /**
     * Lapor gagal cetak tanpa mengubah tagihan pelanggan.
     */
    public function laporGagalCetak(LaporGagalRequest $request, OrderItem $orderItem): RedirectResponse
    {
        $this->pastikanDiAntreanCetak($orderItem);

        $data = $request->validated();

        DB::transaction(function () use ($orderItem, $data): void {
            $item = OrderItem::query()
                ->whereKey($orderItem->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->pastikanDiAntreanCetak($item);

            $roll = MaterialRoll::query()
                ->whereKey($data['material_roll_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $panjangRusak = round((float) $data['panjang_rusak_m'], 2);
            $sisa = round((float) $roll->panjang_sisa_m - $panjangRusak, 2);

            if ($sisa < 0) {
                throw ValidationException::withMessages([
                    'panjang_rusak_m' => 'Panjang rusak melebihi sisa roll yang tersedia.',
                ]);
            }

            $luasRusak = round((float) $roll->lebar_m * $panjangRusak, 2);

            $roll->update([
                'panjang_sisa_m' => $sisa,
                'status' => $sisa <= 0 ? MaterialRoll::STATUS_HABIS : MaterialRoll::STATUS_AKTIF,
            ]);

            $keterangan = 'Gagal cetak | Roll '.$roll->kode_roll.' | '.$panjangRusak.' m';
            if (filled($data['keterangan'] ?? null)) {
                $keterangan .= ' | '.$data['keterangan'];
            }

            MaterialLog::create([
                'order_item_id' => $item->id,
                'material_roll_id' => $roll->id,
                'luas_netto_m2' => 0,
                'wos_finishing_m2' => 0,
                'wos_potong_m2' => 0,
                'gagal_cetak_m2' => $luasRusak,
                'keterangan' => mb_substr($keterangan, 0, 255),
            ]);
        });

        return back()->with('status', 'Gagal cetak dicatat. Stok roll dan log bahan telah disesuaikan.');
    }

    public function unduhTiff(OrderItem $orderItem): StreamedResponse
    {
        $this->pastikanDiAntreanCetak($orderItem);

        $path = $orderItem->file_cetak_tiff;

        abort_unless($path && Storage::disk(MediaStorage::DISK)->exists($path), 404, 'File TIFF tidak ditemukan.');

        return Storage::disk(MediaStorage::DISK)->download($path, basename($path));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function potongBahan(OrderItem $item, array $data): void
    {
        $ukuranKotor = $item->ukuranKotor();
        $qty = (int) $item->qty;
        $orientasi = $data['orientasi_potong'] ?? 'panjang';

        // Orientasi "lebar" memutar gambar: sisi lebar kotor yang memanjang.
        $panjangPakai = $orientasi === 'lebar'
            ? $ukuranKotor['lebar'] * $qty
            : $ukuranKotor['tinggi'] * $qty;
        $lebarPakai = $orientasi === 'lebar'
            ? $ukuranKotor['tinggi']
            : $ukuranKotor['lebar'];

        $panjangPakai = round($panjangPakai, 2);
        $lebarPakai = round($lebarPakai, 2);

        $luasNetto = round((float) $item->lebar_m * (float) $item->tinggi_m * $qty, 2);
        $luasKotor = $ukuranKotor['luas'];
        $wosFinishing = round(max($luasKotor - $luasNetto, 0), 2);

        $rollId = null;
        $offcutId = null;
        $wosPotong = 0.0;

        if (($data['sumber_bahan'] ?? null) === 'roll') {
            $roll = MaterialRoll::query()
                ->whereKey($data['material_roll_id'])
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($roll->status === MaterialRoll::STATUS_AKTIF, 403, 'Roll tidak aktif.');

            if ((float) $roll->lebar_m + 0.001 < $lebarPakai) {
                throw ValidationException::withMessages([
                    'material_roll_id' => 'Lebar roll kurang dari lebar bahan yang dibutuhkan ('.$lebarPakai.' m).',
                ]);
            }

            if ((float) $roll->panjang_sisa_m + 0.001 < $panjangPakai) {
                throw ValidationException::withMessages([
                    'material_roll_id' => 'Sisa panjang roll tidak mencukupi ('.$panjangPakai.' m).',
                ]);
            }

            $sisaRoll = round((float) $roll->panjang_sisa_m - $panjangPakai, 2);
            $roll->update([
                'panjang_sisa_m' => $sisaRoll,
                'status' => $sisaRoll <= 0 ? MaterialRoll::STATUS_HABIS : MaterialRoll::STATUS_AKTIF,
            ]);

            $sisaLebar = round((float) $roll->lebar_m - $lebarPakai, 2);

            if ($sisaLebar >= 0.50) {
                // Sisa lebar masih layak disimpan sebagai stok potongan.
                $roll->offcuts()->create([
                    'jenis_bahan' => $roll->jenis_bahan,
                    'lebar_m' => $sisaLebar,
                    'panjang_m' => $panjangPakai,
                    'luas_m2' => round($sisaLebar * $panjangPakai, 2),
                    'status' => MaterialOffcut::STATUS_TERSEDIA,
                ]);
            } else {
                $wosPotong = round(max($sisaLebar, 0) * $panjangPakai, 2);
            }

            $rollId = $roll->id;
        } else {
            $offcut = MaterialOffcut::query()
                ->whereKey($data['material_offcut_id'])
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $offcut->status === MaterialOffcut::STATUS_TERSEDIA,
                403,
                'Stok potongan sudah terpakai.',
            );

            if ((float) $offcut->lebar_m + 0.001 < $lebarPakai) {
                throw ValidationException::withMessages([
                    'material_offcut_id' => 'Lebar stok potongan kurang dari lebar bahan yang dibutuhkan ('.$lebarPakai.' m).',
                ]);
            }

            if ((float) $offcut->panjang_m + 0.001 < $panjangPakai) {
                throw ValidationException::withMessages([
                    'material_offcut_id' => 'Panjang stok potongan tidak mencukupi ('.$panjangPakai.' m).',
                ]);
            }

            $offcut->update(['status' => MaterialOffcut::STATUS_TERPAKAI]);
            $offcutId = $offcut->id;
        }

        MaterialLog::create([
            'order_item_id' => $item->id,
            'material_roll_id' => $rollId,
            'material_offcut_id' => $offcutId,
            'luas_netto_m2' => $luasNetto,
            'wos_finishing_m2' => $wosFinishing,
            'wos_potong_m2' => $wosPotong,
            'gagal_cetak_m2' => 0,
            'keterangan' => mb_substr('Selesai cetak | '.$item->product?->nama_produk.' | '.$panjangPakai.' m', 0, 255),
        ]);
    }

    private function kurangiStokSatuan(OrderItem $item, int $qty): void
    {
        if (! $item->product_id) {
            return;
        }

        $product = Product::query()
            ->whereKey($item->product_id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($product->stok_pcs === null) {
            return;
        }

        if ((int) $product->stok_pcs < $qty) {
            throw ValidationException::withMessages([
                'qty' => 'Stok produk satuan tidak mencukupi.',
            ]);
        }

        $product->decrement('stok_pcs', $qty);
    }

    private function pastikanDiAntreanCetak(OrderItem $item): void
    {
        abort_unless(in_array($item->status_produksi, [
            OrderItem::STATUS_ANTREAN_CETAK,
            OrderItem::STATUS_PROSES_CETAK,
        ], true), 403, 'Item ini tidak berada di antrean cetak.');
    }
}
