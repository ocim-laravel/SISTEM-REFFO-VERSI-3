<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOrderRequest;
use App\Models\AdvertisingSpk;
use App\Models\Customer;
use App\Models\Finishing;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->string('tab')->toString() === 'siap_diambil' ? 'siap_diambil' : 'semua';
        $instansi = trim((string) $request->query('instansi', ''));

        $query = Order::query()
            ->with(['customer', 'items.product', 'items.finishing', 'advertisingSpks'])
            ->withCount('items');

        if ($instansi !== '') {
            $query->whereHas('customer', fn (Builder $customer) => $customer->where('instansi', $instansi));
        }

        if ($tab === 'siap_diambil') {
            $query->siapDiambil();
        }

        $orders = $query->latest()->paginate(20)->withQueryString();

        $groups = $orders->getCollection()
            ->groupBy(fn (Order $order): string => $order->customer?->instansi ?: Customer::INSTANSI_DEFAULT)
            ->sortKeys();

        $instansiOptions = Customer::query()
            ->select('instansi')
            ->selectRaw('COUNT(*) as total_pelanggan')
            ->groupBy('instansi')
            ->orderBy('instansi')
            ->get();

        $orderCounts = Order::query()
            ->join('customers', 'customers.id', '=', 'orders.customer_id')
            ->selectRaw('customers.instansi as instansi, COUNT(*) as total')
            ->groupBy('customers.instansi')
            ->pluck('total', 'instansi');

        return view('admin.orders.index', [
            'orders' => $orders,
            'groups' => $groups,
            'tab' => $tab,
            'instansi' => $instansi,
            'instansiOptions' => $instansiOptions,
            'orderCounts' => $orderCounts,
            'totalNotaCount' => Order::query()->count(),
            'belumLunasCount' => Order::query()->where('status_bayar', '!=', Order::STATUS_BAYAR_LUNAS)->count(),
            'siapDiambilCount' => Order::query()->siapDiambil()->count(),
        ]);
    }

    public function create(): View
    {
        $products = Product::query()
            ->orderBy('kategori_produk')
            ->orderBy('nama_produk')
            ->get();

        // Hanya id dan nama finishing yang dikirim ke browser.
        // Rumus tambahan cm tidak boleh bocor ke halaman Admin.
        $finishings = Finishing::query()
            ->orderBy('nama_finishing')
            ->get(['id', 'nama_finishing']);

        $oldItems = old('items');

        if (! is_array($oldItems) || $oldItems === []) {
            $oldItems = [[
                'product_id' => '',
                'qty' => 1,
                'lebar_m' => '',
                'tinggi_m' => '',
                'finishing_id' => '',
            ]];
        }

        $formData = [
            'products' => $products->map(fn (Product $product): array => [
                'id' => $product->id,
                'nama_produk' => $product->nama_produk,
                'kategori_produk' => $product->kategori_produk,
                'harga_umum' => (float) $product->harga_umum,
                'harga_reseller' => (float) $product->harga_reseller,
                'harga_prioritas' => (float) $product->harga_prioritas,
                'harga_pegawai' => (float) $product->harga_pegawai,
            ])->values(),
            'finishings' => $finishings->map(fn ($finishing): array => [
                'id' => $finishing->id,
                'nama_finishing' => $finishing->nama_finishing,
            ])->values(),
            'items' => $oldItems,
            'customerId' => old('customer_id'),
            'customer' => [
                'instansi' => old('instansi', ''),
                'nama_pelanggan' => old('nama_pelanggan', ''),
                'no_wa' => old('no_wa', ''),
                'kategori' => old('kategori', Customer::KATEGORI_UMUM),
                'alamat' => old('alamat', ''),
            ],
            'statusDesain' => old('status_desain', Order::STATUS_DESAIN_BELUM_ADA_FILE),
            'statusBayar' => old('status_bayar', Order::STATUS_BAYAR_BELUM_LUNAS),
            'jumlahDp' => old('jumlah_dp', ''),
            'spkEnabled' => (bool) old('spk_enabled', false),
            'spk' => [
                'jenis_pekerjaan' => old('spk_jenis_pekerjaan', ''),
                'ukuran_konstruksi' => old('spk_ukuran_konstruksi', ''),
                'lokasi_pasang' => old('spk_lokasi_pasang', ''),
                'catatan_teknis' => old('spk_catatan_teknis', ''),
            ],
            'searchUrl' => route('admin.customers.search'),
        ];

        return view('admin.orders.create', [
            'formData' => $formData,
            'statusDesainOptions' => Order::LABEL_STATUS_DESAIN,
            'statusBayarOptions' => Order::LABEL_STATUS_BAYAR,
        ]);
    }

    public function searchCustomers(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $customers = Customer::query()
            ->where(function (Builder $query) use ($term): void {
                $query
                    ->where('nama_pelanggan', 'like', "%{$term}%")
                    ->orWhere('instansi', 'like', "%{$term}%");
            })
            ->orderBy('instansi')
            ->orderBy('nama_pelanggan')
            ->limit(10)
            ->get();

        return response()->json(
            $customers->map(fn (Customer $customer): array => [
                'id' => $customer->id,
                'instansi' => $customer->instansi,
                'nama_pelanggan' => $customer->nama_pelanggan,
                'no_wa' => $customer->no_wa,
                'kategori' => $customer->kategori,
                'alamat' => $customer->alamat,
            ])->all(),
        );
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $data = $request->validated();
        /** @var User $admin */
        $admin = $request->user();

        $order = DB::transaction(function () use ($data, $request, $admin): Order {
            $customer = $this->resolveCustomer($data);

            $products = Product::query()
                ->whereIn('id', collect($data['items'])->pluck('product_id')->unique())
                ->get()
                ->keyBy('id');

            $totalTagihan = 0.0;
            $baris = [];

            foreach ($data['items'] as $item) {
                /** @var Product $product */
                $product = $products->get((int) $item['product_id']);
                $qty = (int) $item['qty'];
                $hargaSatuan = $product->hargaUntukKategori($customer->kategori);

                $lebar = null;
                $tinggi = null;
                $luasTagihan = null;
                $finishingId = null;

                if ($product->kategori_produk === Product::KATEGORI_METERAN) {
                    $lebar = round((float) $item['lebar_m'], 2);
                    $tinggi = round((float) $item['tinggi_m'], 2);

                    // Pembulatan minimal 1 m2 hanya berlaku pada tagihan pelanggan.
                    $luasTagihan = max(round($lebar * $tinggi * $qty, 2), 1.0);
                    $finishingId = $item['finishing_id'] ?? null;
                    $subtotal = round($hargaSatuan * $luasTagihan, 2);
                } else {
                    // Produk satuan tidak pernah memakai finishing.
                    $finishingId = null;
                    $subtotal = round($hargaSatuan * $qty, 2);
                }

                $totalTagihan += $subtotal;

                $baris[] = [
                    'product_id' => $product->id,
                    'finishing_id' => $finishingId,
                    'lebar_m' => $lebar,
                    'tinggi_m' => $tinggi,
                    'qty' => $qty,
                    'luas_tagihan_m2' => $luasTagihan,
                    'harga_satuan_dipakai' => $hargaSatuan,
                    'subtotal' => $subtotal,
                    'status_produksi' => OrderItem::STATUS_MENUNGGU_DESAIN,
                ];
            }

            $totalTagihan = round($totalTagihan, 2);

            // Validasi pembayaran dilakukan sebelum file disimpan agar tidak
            // meninggalkan lampiran yatim saat transaksi dibatalkan.
            [$statusBayar, $jumlahDp] = $this->resolvePayment($data, $totalTagihan);

            $lampiran = $request->hasFile('lampiran_briefing')
                ? $request->file('lampiran_briefing')->store(MediaStorage::FOLDER_BRIEFING_DESAIN, MediaStorage::DISK)
                : null;

            $order = Order::create([
                'no_nota' => $this->generateNoNota(),
                'customer_id' => $customer->id,
                'user_id' => $admin->id,
                'total_tagihan' => $totalTagihan,
                'jumlah_dp' => $jumlahDp,
                'status_bayar' => $statusBayar,
                'status_desain' => $data['status_desain'],
                'catatan_desain' => $data['catatan_desain'] ?? null,
                'lampiran_briefing' => $lampiran,
                'deadline' => $data['deadline'] ?? null,
            ]);

            $order->items()->createMany($baris);

            if (! empty($data['spk_enabled'])) {
                $order->advertisingSpks()->create([
                    'jenis_pekerjaan' => $data['spk_jenis_pekerjaan'],
                    'ukuran_konstruksi' => $data['spk_ukuran_konstruksi'],
                    'lokasi_pasang' => $data['spk_lokasi_pasang'],
                    'catatan_teknis' => $data['spk_catatan_teknis'] ?? null,
                    'status_lapangan' => AdvertisingSpk::STATUS_ANTREAN,
                ]);
            }

            return $order;
        });

        return redirect()
            ->route('admin.orders.summary', $order)
            ->with('status', "Nota {$order->no_nota} berhasil disimpan.");
    }

    public function summary(Order $order): View
    {
        $order->load([
            'customer',
            'user',
            'items.product',
            'items.finishing',
            'advertisingSpks',
        ]);

        return view('admin.orders.summary', [
            'order' => $order,
            'waLink' => $this->whatsAppLink($order),
        ]);
    }

    public function pelunasan(Order $order): RedirectResponse
    {
        DB::transaction(function () use ($order): void {
            $order->update([
                'jumlah_dp' => $order->total_tagihan,
                'status_bayar' => Order::STATUS_BAYAR_LUNAS,
            ]);
        });

        return back()->with('status', "Nota {$order->no_nota} sudah ditandai lunas.");
    }

    public function serahTerima(Order $order): RedirectResponse
    {
        DB::transaction(function () use ($order): void {
            $order->items()
                ->where('status_produksi', OrderItem::STATUS_SIAP_DIAMBIL)
                ->update(['status_produksi' => OrderItem::STATUS_SELESAI]);
        });

        return back()->with('status', "Barang nota {$order->no_nota} sudah diserahkan ke pelanggan.");
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveCustomer(array $data): Customer
    {
        $instansi = trim((string) ($data['instansi'] ?? ''));

        if ($instansi === '') {
            $instansi = Customer::INSTANSI_DEFAULT;
        }

        $atribut = [
            'instansi' => $instansi,
            'nama_pelanggan' => trim($data['nama_pelanggan']),
            'no_wa' => trim($data['no_wa']),
            'kategori' => $data['kategori'],
            'alamat' => filled($data['alamat'] ?? null) ? trim($data['alamat']) : null,
        ];

        if (! empty($data['customer_id'])) {
            $customer = Customer::query()->findOrFail($data['customer_id']);
            $customer->update($atribut);

            return $customer;
        }

        return Customer::query()->updateOrCreate(
            [
                'instansi' => $atribut['instansi'],
                'nama_pelanggan' => $atribut['nama_pelanggan'],
            ],
            $atribut,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: string, 1: float}
     */
    private function resolvePayment(array $data, float $total): array
    {
        $status = $data['status_bayar'];
        $dp = round((float) ($data['jumlah_dp'] ?? 0), 2);

        if ($status === Order::STATUS_BAYAR_LUNAS) {
            return [$status, $total];
        }

        if ($status === Order::STATUS_BAYAR_BELUM_LUNAS) {
            return [$status, 0.0];
        }

        if ($dp <= 0) {
            throw ValidationException::withMessages([
                'jumlah_dp' => 'Jumlah DP wajib diisi dan lebih besar dari 0 saat status bayar DP.',
            ]);
        }

        if ($dp > $total) {
            throw ValidationException::withMessages([
                'jumlah_dp' => 'Jumlah DP tidak boleh melebihi total tagihan.',
            ]);
        }

        if ($dp >= $total) {
            return [Order::STATUS_BAYAR_LUNAS, $total];
        }

        return [$status, $dp];
    }

    private function generateNoNota(): string
    {
        $prefix = 'INV-'.now()->format('Ymd').'-';

        $terakhir = Order::query()
            ->where('no_nota', 'like', $prefix.'%')
            ->orderByDesc('no_nota')
            ->lockForUpdate()
            ->value('no_nota');

        $urutan = $terakhir ? ((int) substr($terakhir, -4)) + 1 : 1;

        return $prefix.str_pad((string) $urutan, 4, '0', STR_PAD_LEFT);
    }

    private function whatsAppLink(Order $order): string
    {
        $nomor = preg_replace('/\D+/', '', (string) $order->customer->no_wa);

        if (str_starts_with($nomor, '0')) {
            $nomor = '62'.substr($nomor, 1);
        } elseif (str_starts_with($nomor, '8')) {
            $nomor = '62'.$nomor;
        }

        $baris = [
            '*RINGKASAN NOTA '.$order->no_nota.'*',
            'Instansi: '.$order->customer->instansi,
            'Pelanggan: '.$order->customer->nama_pelanggan,
            'Tanggal: '.$order->created_at?->format('d/m/Y H:i'),
            '',
            '*Rincian Digital Printing*',
        ];

        foreach ($order->items as $index => $item) {
            $ukuran = $item->isMeteran()
                ? number_format((float) $item->lebar_m, 2, ',', '.').' x '.number_format((float) $item->tinggi_m, 2, ',', '.').' m'
                : 'satuan';

            $baris[] = ($index + 1).'. '.$item->product?->nama_produk.' ('.$ukuran.') x'.$item->qty
                .' = Rp '.number_format((float) $item->subtotal, 0, ',', '.');
        }

        $baris[] = '';
        $baris[] = 'Total Tagihan: Rp '.number_format((float) $order->total_tagihan, 0, ',', '.');
        $baris[] = 'DP: Rp '.number_format((float) $order->jumlah_dp, 0, ',', '.');
        $baris[] = 'Sisa: Rp '.number_format($order->sisaTagihan(), 0, ',', '.');
        $baris[] = 'Status Bayar: '.$order->statusBayarLabel();

        if ($order->advertisingSpks->isNotEmpty()) {
            $baris[] = '';
            $baris[] = '*SPK Advertising (Non-Billing)*';

            foreach ($order->advertisingSpks as $spk) {
                $baris[] = '- '.$spk->jenis_pekerjaan.' | '.$spk->ukuran_konstruksi.' | '.$spk->lokasi_pasang;
            }
        }

        $baris[] = '';
        $baris[] = 'Terima kasih.';

        return 'https://wa.me/'.$nomor.'?text='.rawurlencode(implode("\n", $baris));
    }
}
