<?php

namespace App\Http\Controllers\Owner;

use App\Http\Requests\Owner\StoreMaterialRollRequest;
use App\Http\Requests\Owner\TambahStokPcsRequest;
use App\Http\Requests\Owner\UpdateFinishingRequest;
use App\Http\Requests\Owner\UpdateProductPricesRequest;
use App\Models\Finishing;
use App\Models\MaterialRoll;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SettingsController extends OwnerController
{
    public function index(): View
    {
        $this->pastikanOwner();

        $products = Product::query()
            ->orderBy('kategori_produk')
            ->orderBy('nama_produk')
            ->get();

        $finishings = Finishing::query()
            ->orderBy('nama_finishing')
            ->get();

        $jenisRoll = collect(MaterialRoll::JENIS_BAHAN)
            ->map(fn (string $jenis): array => [
                'jenis' => $jenis,
                'ukuran' => MaterialRoll::ukuranStandar($jenis),
            ])
            ->values();

        return view('owner.settings', [
            'products' => $products,
            'finishings' => $finishings,
            'jenisRoll' => $jenisRoll,
            'produkSatuan' => $products->where('kategori_produk', Product::KATEGORI_SATUAN)->values(),
        ]);
    }

    public function updateProducts(UpdateProductPricesRequest $request): RedirectResponse
    {
        $this->pastikanOwner();

        $data = collect($request->validated()['products'])->keyBy('id');

        DB::transaction(function () use ($data): void {
            $products = Product::query()
                ->whereIn('id', $data->keys())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($data as $id => $baris) {
                $product = $products->get($id);

                if (! $product) {
                    continue;
                }

                $product->update([
                    'harga_umum' => $baris['harga_umum'],
                    'harga_reseller' => $baris['harga_reseller'],
                    'harga_prioritas' => $baris['harga_prioritas'],
                    'harga_pegawai' => $baris['harga_pegawai'],
                    'harga_modal' => $baris['harga_modal'],
                ]);
            }
        });

        return back()->with('status', 'Master harga dan harga modal berhasil diperbarui.');
    }

    public function storeRoll(StoreMaterialRollRequest $request): RedirectResponse
    {
        $this->pastikanOwner();

        $data = $request->validated();
        $ukuran = MaterialRoll::ukuranStandar($data['jenis_bahan']);

        abort_unless($ukuran !== null, 422, 'Jenis roll tidak dikenal.');

        DB::transaction(function () use ($data, $ukuran): void {
            MaterialRoll::create([
                'kode_roll' => strtoupper(trim($data['kode_roll'])),
                'jenis_bahan' => $data['jenis_bahan'],
                'lebar_m' => $ukuran['lebar_m'],
                'panjang_awal_m' => $ukuran['panjang_m'],
                'panjang_sisa_m' => $ukuran['panjang_m'],
                'status' => MaterialRoll::STATUS_AKTIF,
            ]);
        });

        return back()->with('status', 'Stok roll baru berhasil ditambahkan.');
    }

    public function tambahStokPcs(TambahStokPcsRequest $request): RedirectResponse
    {
        $this->pastikanOwner();

        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            $product = Product::query()
                ->whereKey($data['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $product->kategori_produk === Product::KATEGORI_SATUAN,
                422,
                'Stok pcs hanya untuk produk kategori satuan.',
            );

            $product->update([
                'stok_pcs' => (int) $product->stok_pcs + (int) $data['jumlah'],
            ]);
        });

        return back()->with('status', 'Stok pcs produk satuan berhasil ditambah.');
    }

    public function updateFinishings(UpdateFinishingRequest $request): RedirectResponse
    {
        $this->pastikanOwner();

        $data = collect($request->validated()['finishings'])->keyBy('id');

        DB::transaction(function () use ($data): void {
            $finishings = Finishing::query()
                ->whereIn('id', $data->keys())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($data as $id => $baris) {
                $finishing = $finishings->get($id);

                if (! $finishing) {
                    continue;
                }

                $finishing->update([
                    'ekstra_kiri_cm' => $baris['ekstra_kiri_cm'],
                    'ekstra_kanan_cm' => $baris['ekstra_kanan_cm'],
                    'ekstra_atas_cm' => $baris['ekstra_atas_cm'],
                    'ekstra_bawah_cm' => $baris['ekstra_bawah_cm'],
                ]);
            }
        });

        return back()->with('status', 'Rumus ekstra finishing berhasil disesuaikan.');
    }
}
