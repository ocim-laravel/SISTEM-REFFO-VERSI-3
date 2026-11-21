<?php

namespace App\Http\Controllers;

use App\Events\SerahTerimaCompleted;
use App\Http\Requests\StoreSerahTerimaRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Support\MediaStorage;
use App\Support\RiwayatSelesai;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Serah terima / pengambilan barang dipakai bersama Admin dan empat divisi.
 * Akses dibatasi middleware role pada grup rute masing-masing.
 */
class SerahTerimaController extends Controller
{
    public function index(Request $request): View
    {
        $divisi = $this->divisi($request);
        $q = trim((string) $request->query('q', ''));

        $query = Order::query()
            ->with(['customer', 'items.product', 'advertisingSpks'])
            ->withCount('items')
            ->siapDiambil();

        // Admin melihat semua nota siap diambil; divisi hanya yang relevan.
        if ($divisi !== 'admin') {
            RiwayatSelesai::filterDivisi($query, $divisi);
        }

        if ($q !== '') {
            $query->where(function (Builder $query) use ($q): void {
                $query->where('no_nota', 'like', "%{$q}%")
                    ->orWhereHas('customer', function (Builder $customer) use ($q): void {
                        $customer->where('instansi', 'like', "%{$q}%")
                            ->orWhere('nama_pelanggan', 'like', "%{$q}%");
                    });
            });
        }

        $orders = (clone $query)->latest()->paginate(15)->withQueryString();

        return view('serah-terima.index', [
            'orders' => $orders,
            'q' => $q,
            'divisi' => $divisi,
            'judulDivisi' => $this->judulDivisi($divisi),
            'belumLunasCount' => (clone $query)->where('status_bayar', '!=', Order::STATUS_BAYAR_LUNAS)->count(),
        ]);
    }

    public function create(Request $request, Order $order): View|RedirectResponse
    {
        $divisi = $this->divisi($request);

        if (! $order->bolehDiserahkan()) {
            return $this->redirectPelunasanWajib($divisi);
        }

        $this->assertSiapDiambil($order);

        $order->load(['customer', 'items.product']);

        return view('serah-terima.form', [
            'order' => $order,
            'divisi' => $divisi,
            'judulDivisi' => $this->judulDivisi($divisi),
        ]);
    }

    public function store(StoreSerahTerimaRequest $request, Order $order): RedirectResponse
    {
        $divisi = $this->divisi($request);

        if (! $order->bolehDiserahkan()) {
            return $this->redirectPelunasanWajib($divisi);
        }

        $this->assertSiapDiambil($order);

        $data = $request->validated();

        $path = $request->hasFile('foto_serah_terima')
            ? $request->file('foto_serah_terima')->store(
                MediaStorage::FOLDER_FOTO_SERAH_TERIMA,
                MediaStorage::DISK,
            )
            : null;

        $catatan = trim((string) ($data['catatan_pengecualian'] ?? ''));

        DB::transaction(function () use ($order, $data, $path, $catatan): void {
            $update = [
                'nama_pengambil' => trim((string) $data['nama_pengambil']),
                'no_wa_pengambil' => trim((string) $data['no_wa_pengambil']),
                'waktu_serah_terima' => now(),
                // Catatan lama tetap dipertahankan untuk audit bila tidak diisi ulang.
                'catatan_pengecualian' => $catatan !== '' ? $catatan : $order->catatan_pengecualian,
            ];

            if ($path !== null) {
                $update['foto_serah_terima'] = $path;
            }

            $order->update($update);

            $order->items()
                ->where('status_produksi', OrderItem::STATUS_SIAP_DIAMBIL)
                ->update(['status_produksi' => OrderItem::STATUS_SELESAI]);
        });

        SerahTerimaCompleted::dispatch($order->fresh());

        $pesan = "Barang nota {$order->no_nota} sudah diserahkan atas nama {$order->nama_pengambil}.";

        if ($path === null) {
            $pesan .= ' Foto tidak diunggah.';
        }

        if ($catatan !== '') {
            $pesan .= ' Catatan pengecualian tersimpan untuk audit.';
        }

        return redirect()
            ->route($divisi.'.serah-terima.index')
            ->with('status', $pesan);
    }

    private function redirectPelunasanWajib(string $divisi): RedirectResponse
    {
        return redirect()
            ->route($divisi.'.serah-terima.index')
            ->with('error', 'Pelunasan wajib sebelum serah terima');
    }

    private function assertSiapDiambil(Order $order): void
    {
        abort_unless(
            Order::query()->siapDiambil()->whereKey($order->id)->exists(),
            403,
            'Order ini belum siap diambil.',
        );

        abort_if($order->waktu_serah_terima !== null, 403, 'Nota ini sudah diserahkan.');
    }

    private function divisi(Request $request): string
    {
        return match ($request->user()?->role) {
            User::ROLE_ADMIN => 'admin',
            User::ROLE_DESAINER => RiwayatSelesai::DESAINER,
            User::ROLE_OPERATOR_CETAK => RiwayatSelesai::OPERATOR,
            User::ROLE_FINISHING => RiwayatSelesai::FINISHING,
            User::ROLE_ADVERTISING => RiwayatSelesai::ADVERTISING,
            default => abort(403, 'Role tidak memiliki akses serah terima.'),
        };
    }

    private function judulDivisi(string $divisi): string
    {
        return $divisi === 'admin' ? 'Admin' : RiwayatSelesai::label($divisi);
    }
}
