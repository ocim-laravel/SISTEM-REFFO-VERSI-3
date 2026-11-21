<?php

namespace App\Http\Controllers\Owner;

use App\Models\MaterialLog;
use App\Models\MaterialOffcut;
use App\Models\MaterialRoll;
use App\Models\Product;
use App\Support\PeriodeLaporan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaterialReportController extends OwnerController
{
    public function index(Request $request): View
    {
        $this->pastikanOwner();

        $periode = PeriodeLaporan::dariRequest($request);

        $rolls = MaterialRoll::query()
            ->where('status', MaterialRoll::STATUS_AKTIF)
            ->orderBy('jenis_bahan')
            ->orderBy('kode_roll')
            ->get()
            ->map(fn (MaterialRoll $roll): array => [
                'id' => $roll->id,
                'kode_roll' => $roll->kode_roll,
                'jenis_bahan' => $roll->jenis_bahan,
                'lebar_m' => (float) $roll->lebar_m,
                'panjang_awal_m' => (float) $roll->panjang_awal_m,
                'panjang_sisa_m' => (float) $roll->panjang_sisa_m,
                'luas_sisa_m2' => round((float) $roll->lebar_m * (float) $roll->panjang_sisa_m, 2),
                'persen_sisa' => (float) $roll->panjang_awal_m > 0
                    ? round(((float) $roll->panjang_sisa_m / (float) $roll->panjang_awal_m) * 100, 1)
                    : 0.0,
            ]);

        $offcuts = MaterialOffcut::query()
            ->with('materialRoll')
            ->where('status', MaterialOffcut::STATUS_TERSEDIA)
            ->orderBy('jenis_bahan')
            ->orderBy('lebar_m')
            ->get();

        $stokPcs = Product::query()
            ->where('kategori_produk', Product::KATEGORI_SATUAN)
            ->orderBy('nama_produk')
            ->get(['id', 'nama_produk', 'stok_pcs', 'harga_modal']);

        $logQuery = MaterialLog::query()
            ->with(['orderItem.order', 'orderItem.product', 'materialRoll', 'materialOffcut']);
        $periode->whereCreatedAt($logQuery);

        $ringkasan = $this->ringkasanAudit(clone $logQuery);

        $logs = $logQuery->latest()->paginate(15)->withQueryString();

        return view('owner.reports.materials', [
            'periode' => $periode,
            'rolls' => $rolls,
            'offcuts' => $offcuts,
            'stokPcs' => $stokPcs,
            'ringkasan' => $ringkasan,
            'logs' => $logs,
        ]);
    }

    /**
     * @return array<string, float|int>
     */
    private function ringkasanAudit(Builder $query): array
    {
        $logs = $query->get();

        $netto = (float) $logs->sum('luas_netto_m2');
        $finishing = (float) $logs->sum('wos_finishing_m2');
        $potong = (float) $logs->sum('wos_potong_m2');
        $gagal = (float) $logs->sum('gagal_cetak_m2');

        return [
            'jumlah_log' => $logs->count(),
            'netto' => $netto,
            'finishing' => $finishing,
            'potong' => $potong,
            'gagal' => $gagal,
            'total' => round($netto + $finishing + $potong + $gagal, 2),
        ];
    }
}
