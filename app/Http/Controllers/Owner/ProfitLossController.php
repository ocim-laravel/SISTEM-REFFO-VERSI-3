<?php

namespace App\Http\Controllers\Owner;

use App\Http\Requests\Owner\StoreOperationalExpenseRequest;
use App\Models\MaterialLog;
use App\Models\OperationalExpense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\PeriodeLaporan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProfitLossController extends OwnerController
{
    public function index(Request $request): View
    {
        $this->pastikanOwner();

        $periode = PeriodeLaporan::dariRequest($request);

        $orders = Order::query()
            ->with('customer')
            ->tap(fn (Builder $query): Builder => $periode->whereCreatedAt($query))
            ->get();

        $omzet = [
            'tagihan' => (float) $orders->sum('total_tagihan'),
            'lunas' => (float) $orders->where('status_bayar', Order::STATUS_BAYAR_LUNAS)->sum('jumlah_dp'),
            'dp' => (float) $orders->where('status_bayar', Order::STATUS_BAYAR_DP)->sum('jumlah_dp'),
        ];
        $omzet['diterima'] = round($omzet['lunas'] + $omzet['dp'], 2);
        $omzet['piutang'] = round($orders->sum(fn (Order $order): float => max(
            (float) $order->total_tagihan - (float) $order->jumlah_dp,
            0.0,
        )), 2);

        $logQuery = MaterialLog::query()->with('orderItem.product');
        $periode->whereCreatedAt($logQuery);
        $logs = $logQuery->get();

        // HPP bahan memakai seluruh pemakaian riil, bukan hanya luas tagihan.
        $komponenHpp = [
            'netto' => $this->hppKomponen($logs, 'luas_netto_m2'),
            'finishing' => $this->hppKomponen($logs, 'wos_finishing_m2'),
            'potong' => $this->hppKomponen($logs, 'wos_potong_m2'),
            'gagal' => $this->hppKomponen($logs, 'gagal_cetak_m2'),
        ];

        $hppBahan = round(collect($komponenHpp)->sum('biaya'), 2);

        $itemSatuan = OrderItem::query()
            ->with('product')
            ->whereHas('product', fn (Builder $product): Builder => $product
                ->where('kategori_produk', Product::KATEGORI_SATUAN))
            ->whereHas('order', fn (Builder $order) => $periode->whereCreatedAt($order))
            ->get();

        $hppSatuan = round($itemSatuan->sum(fn (OrderItem $item): float => (int) $item->qty
            * (float) ($item->product?->harga_modal ?? 0)), 2);

        $hppProduksi = round($hppBahan + $hppSatuan, 2);

        $expenseQuery = OperationalExpense::query()
            ->whereBetween('tanggal', [$periode->dari, $periode->sampai]);
        $totalOperasional = round((float) (clone $expenseQuery)->sum('nominal'), 2);
        $expenses = (clone $expenseQuery)
            ->latest('tanggal')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('owner.reports.profit-loss', [
            'periode' => $periode,
            'omzet' => $omzet,
            'komponenHpp' => $komponenHpp,
            'hppBahan' => $hppBahan,
            'hppSatuan' => $hppSatuan,
            'hppProduksi' => $hppProduksi,
            'totalOperasional' => $totalOperasional,
            'labaBersih' => round($omzet['tagihan'] - $hppProduksi - $totalOperasional, 2),
            'expenses' => $expenses,
            'kategoriBiaya' => OperationalExpense::KATEGORI_BIAYA,
        ]);
    }

    public function storeExpense(StoreOperationalExpenseRequest $request): RedirectResponse
    {
        $this->pastikanOwner();

        DB::transaction(function () use ($request): void {
            OperationalExpense::create($request->validated());
        });

        return back()->with('status', 'Pengeluaran operasional berhasil disimpan.');
    }

    public function destroyExpense(OperationalExpense $operationalExpense): RedirectResponse
    {
        $this->pastikanOwner();

        DB::transaction(function () use ($operationalExpense): void {
            $operationalExpense->delete();
        });

        return back()->with('status', 'Pengeluaran operasional berhasil dihapus.');
    }

    /**
     * @param  Collection<int, MaterialLog>  $logs
     * @return array{luas: float, biaya: float}
     */
    private function hppKomponen(Collection $logs, string $kolom): array
    {
        $luas = (float) $logs->sum($kolom);
        $biaya = (float) $logs->sum(fn (MaterialLog $log): float => (float) $log->{$kolom}
            * (float) ($log->orderItem?->product?->harga_modal ?? 0));

        return [
            'luas' => round($luas, 2),
            'biaya' => round($biaya, 2),
        ];
    }
}
