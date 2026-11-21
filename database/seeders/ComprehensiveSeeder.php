<?php

namespace Database\Seeders;

use App\Models\AdvertisingSpk;
use App\Models\Customer;
use App\Models\DesignRevision;
use App\Models\Finishing;
use App\Models\MaterialLog;
use App\Models\MaterialOffcut;
use App\Models\MaterialRoll;
use App\Models\OperationalExpense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\MediaStorage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ComprehensiveSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Status yang hanya boleh dicapai setelah desainer mengunggah
     * file cetak .tiff DAN file master arsip.
     */
    private const STATUS_PASCA_DESAIN = [
        OrderItem::STATUS_ANTREAN_CETAK,
        OrderItem::STATUS_PROSES_CETAK,
        OrderItem::STATUS_ANTREAN_FINISHING,
        OrderItem::STATUS_PROSES_FINISHING,
        OrderItem::STATUS_SIAP_DIAMBIL,
        OrderItem::STATUS_SELESAI,
    ];

    /**
     * Jalankan seeder komprehensif secara idempotent.
     */
    public function run(): void
    {
        // Master dasar tetap dipakai; pemanggilan ulang aman karena seluruh
        // seeder dasar memakai updateOrCreate.
        $this->call([
            UserSeeder::class,
            CustomerSeeder::class,
            ProductSeeder::class,
            FinishingSeeder::class,
            MaterialRollSeeder::class,
        ]);

        DB::transaction(function (): void {
            $customers = $this->seedCustomers();
            $products = $this->seedProducts();
            $finishings = Finishing::query()->get()->keyBy('id');
            $rolls = $this->seedMaterialRolls();
            $offcuts = $this->seedMaterialOffcuts($rolls);

            /** @var User $admin */
            $admin = User::query()
                ->where('role', User::ROLE_ADMIN)
                ->firstOrFail();

            $this->seedOrders($customers, $products, $finishings, $rolls, $offcuts, $admin);
            $this->seedOperationalExpenses();
        });
    }

    /**
     * 73 pelanggan tambahan: 55 pribadi + 18 PIC instansi (total 50+ realistis).
     */
    private function seedCustomers(): Collection
    {
        $pribadi = [
            'Rizky Pratama', 'Nadia Putri', 'Fajar Nugroho', 'Maya Sari',
            'Hendra Gunawan', 'Lina Marlina', 'Agus Setiawan', 'Dewi Anggraini',
            'Bambang Susilo', 'Sri Wahyuni', 'Eko Purnomo', 'Yanti Kusuma',
            'Arif Rahman', 'Fitri Handayani', 'Slamet Riyadi', 'Tuti Wulandari',
            'Deddy Kurniawan', 'Nina Karlina', 'Iwan Setiawan', 'Rina Susanti',
            'Toni Irawan', 'Wulan Aprilia', 'Aceng Hidayat', 'Mira Novita',
            'Joko Susanto', 'Yusuf Maulana', 'Ani Rohani', 'Reza Firmansyah',
            'Rani Kusumawati', 'Asep Saepudin', 'Diana Suryani', 'Heri Kuswanto',
            'Lala Amelia', 'Ujang Sunandar', 'Neng Eva Rahmawati', 'Rangga Aditya',
            'Intan Permata', 'Bagas Prakoso', 'Alya Rahma', 'Cahya Wijaya',
            'Gita Lestari', 'Farhan Akbar', 'Nabila Zahra', 'Rendi Saputra',
            'Salsa Amelia', 'Yoga Mahendra', 'Tika Aprilia', 'Raka Wiratama',
            'Mega Silalahi', 'Dion Saputra', 'Vina Oktaviani', 'Ilham Fauzi',
            'Putri Ayu', 'Rizal Hamzah', 'Siska Melati',
        ];

        $instansi = [
            'Telkomsel Area Bandung' => ['Bayu Prasetyo', 'Dian Pertiwi', 'Fani Oktaviani'],
            'RS Sehat Bersama' => ['Arif Wibowo', 'Rina Kartika', 'Slamet Hidayat'],
            'Universitas Langit Bangsa' => ['Sudirman Efendi', 'Retno Wulandari', 'Rizky Ananda'],
            'Bank Jabar Cabang Dago' => ['Asep Kurniawan', 'Tania Permata', 'Dedi Mulyadi'],
            'PTO Mutiara Motor' => ['Hadi Wijaya', 'Yuni Handayani', 'Fajar Ramadhan'],
            'Diskominfo Kota Bandung' => ['Nani Suryani', 'Adang Saputra', 'Idris Maulana'],
        ];

        $kategoriPribadi = [
            Customer::KATEGORI_UMUM,
            Customer::KATEGORI_UMUM,
            Customer::KATEGORI_RESELLER,
            Customer::KATEGORI_UMUM,
            Customer::KATEGORI_PRIORITAS,
            Customer::KATEGORI_PEGAWAI,
        ];

        $jalan = ['Merdeka', 'Cihampelas', 'Asia Afrika', 'Gatot Subroto', 'Dago', 'Riau', 'Sudirman', 'Soekarno-Hatta'];
        $index = 0;

        foreach ($pribadi as $nama) {
            $index++;
            $kunci = [
                'instansi' => Customer::INSTANSI_DEFAULT,
                'nama_pelanggan' => $nama,
            ];
            $atribut = [
                'no_wa' => sprintf('0857%08d', 20000001 + $index),
                'kategori' => $kategoriPribadi[$index % count($kategoriPribadi)],
                'alamat' => sprintf(
                    'Jl. %s No. %d, Bandung',
                    $jalan[$index % count($jalan)],
                    5 + ($index * 3),
                ),
            ];
            $ini = Customer::query()->where($kunci)->first();

            Customer::updateOrCreate(
                $kunci,
                $ini ? $ini->only(['instansi', 'nama_pelanggan', 'no_wa', 'kategori', 'alamat']) : $atribut,
            );
        }

        $index = 0;
        foreach ($instansi as $namaInstansi => $picList) {
            foreach ($picList as $pic) {
                $index++;
                $kunci = [
                    'instansi' => $namaInstansi,
                    'nama_pelanggan' => $pic,
                ];
                $atribut = [
                    'no_wa' => sprintf('0812%08d', 40000001 + $index),
                    'kategori' => $index % 3 === 0
                        ? Customer::KATEGORI_UMUM
                        : ($index % 3 === 1 ? Customer::KATEGORI_RESELLER : Customer::KATEGORI_PRIORITAS),
                    'alamat' => sprintf('%s, Jl. %s No. %d, Bandung', $namaInstansi, $jalan[$index % count($jalan)], 10 + $index),
                ];
                $ini = Customer::query()->where($kunci)->first();

                Customer::updateOrCreate(
                    $kunci,
                    $ini ? $ini->only(['instansi', 'nama_pelanggan', 'no_wa', 'kategori', 'alamat']) : $atribut,
                );
            }
        }

        return Customer::query()->orderBy('id')->get();
    }

    /**
     * Memakai katalog yang sudah ada, hanya memberi variasi stok sekali saja.
     *
     * @return Collection<string, Product>
     */
    private function seedProducts(): Collection
    {
        $stokVariasi = [
            'X-Banner' => 148,
            'Roll Banner' => 76,
            'Poster' => 320,
            'Stiker A3' => 215,
            'A-Board' => 39,
        ];

        foreach ($stokVariasi as $nama => $stok) {
            $produk = Product::query()->where('nama_produk', $nama)->first();

            if ($produk === null) {
                continue;
            }

            // 25 adalah stok awal ProductSeeder. Stok yang sudah diubah
            // owner/user tidak akan ditimpa saat seeder dijalankan ulang.
            if ((int) $produk->stok_pcs === 25) {
                $produk->update(['stok_pcs' => $stok]);
            }
        }

        return Product::query()->get()->keyBy('nama_produk');
    }

    /**
     * 48 roll tambahan (total 60 dengan seeder dasar), aktif dan habis.
     */
    private function seedMaterialRolls(): Collection
    {
        $bahan = [
            ['jenis' => 'MMT 280', 'kode' => 'MMT280'],
            ['jenis' => 'MMT 340', 'kode' => 'MMT340'],
            ['jenis' => 'MMT 440', 'kode' => 'MMT440'],
            ['jenis' => 'Backlit', 'kode' => 'BACKLIT'],
            ['jenis' => MaterialRoll::JENIS_STIKER_1M, 'kode' => 'STIKER1M'],
            ['jenis' => MaterialRoll::JENIS_STIKER_15M, 'kode' => 'STIKER15M'],
        ];

        $potongan = [0.00, 3.50, 12.25, 27.50, 5.75];

        foreach ($bahan as $item) {
            $ukuran = MaterialRoll::ukuranStandar($item['jenis']);
            $lebar = $ukuran['lebar_m'];
            $panjangAwal = $ukuran['panjang_m'];

            for ($nomor = 3; $nomor <= 10; $nomor++) {
                $kodeRoll = sprintf('%s-R%02d', $item['kode'], $nomor);
                $habis = in_array($nomor, [7, 10], true);
                $atribut = [
                    'jenis_bahan' => $item['jenis'],
                    'lebar_m' => $this->dec($lebar),
                    'panjang_awal_m' => $this->dec($panjangAwal),
                    'panjang_sisa_m' => $habis
                        ? $this->dec(0)
                        : $this->dec($panjangAwal - $potongan[$nomor % count($potongan)]),
                    'status' => $habis ? MaterialRoll::STATUS_HABIS : MaterialRoll::STATUS_AKTIF,
                ];
                $ini = MaterialRoll::query()->where('kode_roll', $kodeRoll)->first();

                MaterialRoll::updateOrCreate(
                    ['kode_roll' => $kodeRoll],
                    $ini ? $ini->only(['kode_roll', 'jenis_bahan', 'lebar_m', 'panjang_awal_m', 'panjang_sisa_m', 'status']) : $atribut,
                );
            }
        }

        return MaterialRoll::query()->orderBy('kode_roll')->get();
    }

    /**
     * 30 potongan roll: sebagian tersedia, sebagian sudah terpakai.
     */
    private function seedMaterialOffcuts(Collection $rolls): Collection
    {
        $aktif = $rolls->where('status', MaterialRoll::STATUS_AKTIF)->values();

        if ($aktif->isEmpty()) {
            return MaterialOffcut::query()->get();
        }

        for ($i = 0; $i < 30; $i++) {
            /** @var MaterialRoll $roll */
            $roll = $aktif[($i * 3 + 1) % $aktif->count()];
            $lebar = round(max(0.5, (float) $roll->lebar_m - 0.5 - ($i % 3) * 0.25), 2);
            $panjang = round(1.5 + ($i % 6) * 0.75, 2);
            $kunci = [
                'material_roll_id' => $roll->id,
                'jenis_bahan' => $roll->jenis_bahan,
                'lebar_m' => $this->dec($lebar),
                'panjang_m' => $this->dec($panjang),
            ];
            $atribut = [
                'luas_m2' => $this->dec($lebar * $panjang),
                'status' => $i < 20 ? MaterialOffcut::STATUS_TERSEDIA : MaterialOffcut::STATUS_TERPAKAI,
            ];
            $ini = MaterialOffcut::query()->where($kunci)->first();

            MaterialOffcut::updateOrCreate(
                $kunci,
                $ini ? $ini->only(['luas_m2', 'status']) : $atribut,
            );
        }

        return MaterialOffcut::query()->orderBy('id')->get();
    }

    /**
     * 55 nota realistis: setiap nota berisi 10 item cetak + 5 SPK advertising.
     * Order dikelompokkan berdasarkan instansi, lalu sub-grup nama pelanggan + tanggal.
     *
     * @param  Collection<int|string, Product>  $products
     * @param  Collection<int|string, Finishing>  $finishings
     */
    private function seedOrders(
        Collection $customers,
        Collection $products,
        Collection $finishings,
        Collection $rolls,
        Collection $offcuts,
        User $admin,
    ): void {
        $meteran = $products->where('kategori_produk', Product::KATEGORI_METERAN)->values();
        $satuan = $products->where('kategori_produk', Product::KATEGORI_SATUAN)->values();
        $kelompok = $customers
            ->groupBy('instansi')
            ->map(fn (Collection $group) => $group->sortBy('nama_pelanggan')->values())
            ->sortKeys();

        $ukuranMeteran = [
            ['2.00', '1.00'], ['1.50', '2.00'], ['0.80', '0.60'], ['3.00', '1.20'],
            ['1.00', '0.45'], ['2.50', '1.50'], ['1.20', '0.90'], ['4.00', '1.00'],
        ];

        $daftarStatus = [
            OrderItem::STATUS_MENUNGGU_DESAIN,
            OrderItem::STATUS_PROSES_DESAIN,
            OrderItem::STATUS_MENUNGGU_ACC,
            OrderItem::STATUS_ANTREAN_CETAK,
            OrderItem::STATUS_PROSES_CETAK,
            OrderItem::STATUS_ANTREAN_FINISHING,
            OrderItem::STATUS_PROSES_FINISHING,
            OrderItem::STATUS_SIAP_DIAMBIL,
            OrderItem::STATUS_SELESAI,
            OrderItem::STATUS_ANTREAN_CETAK,
        ];

        $catatanDesain = [
            'Mohon dicek kembali ejaan logo sebelum dicetak.',
            'Warna mengikuti katalog perusahaan.',
            'Ubah ukuran huruf utama menjadi 48 pt.',
            'Tambahkan QR code di sudut kanan bawah.',
            'Desain final disetujui PIC melalui WhatsApp.',
            'Gunakan font brand resmi instansi.',
        ];

        $target = 55;
        $i = 0;
        $tanggalDasar = Carbon::create(2026, 8, 1, 9, 0, 0);

        while ($i < $target) {
            foreach ($kelompok as $instansi => $pelangganInstansi) {
                foreach ($pelangganInstansi as $pelangganIdx => $customer) {
                    if ($i >= $target) {
                        break 2;
                    }
                    $i++;
                    $noNota = sprintf('INV-COMP-%04d', $i);
                    $createdAt = $tanggalDasar->copy()->addDays($i - 1)->addMinutes(($pelangganIdx % 6) * 20);

                    $itemData = $this->buatItemCampuran(
                        $i,
                        $customer,
                        $meteran,
                        $satuan,
                        $finishings,
                        $ukuranMeteran,
                        $daftarStatus,
                    );

                    // Total tagihan hanya dari item cetak. SPK advertising Fase 1 non-billing.
                    $total = round(collect($itemData)->sum('subtotal'), 2);
                    $semuaPascaDesain = collect($itemData)->every(
                        fn (array $barisItem): bool => in_array($barisItem['status_produksi'], self::STATUS_PASCA_DESAIN, true),
                    );
                    $statusDesain = $semuaPascaDesain
                        ? Order::STATUS_DESAIN_SIAP_CETAK_TIFF
                        : ($i % 3 === 0 ? Order::STATUS_DESAIN_CORETAN_KERTAS : Order::STATUS_DESAIN_FILE_MENTAH);

                    $statusBayar = match (true) {
                        $i % 5 === 0 => Order::STATUS_BAYAR_LUNAS,
                        $i % 3 === 0 => Order::STATUS_BAYAR_DP,
                        default => Order::STATUS_BAYAR_BELUM_LUNAS,
                    };
                    $jumlahDp = match ($statusBayar) {
                        Order::STATUS_BAYAR_LUNAS => $total,
                        Order::STATUS_BAYAR_DP => round($total * 0.4, 2),
                        default => 0.0,
                    };

                    $kunciNota = ['no_nota' => $noNota];
                    $atributNota = [
                        'customer_id' => $customer->id,
                        'user_id' => $admin->id,
                        'total_tagihan' => $this->dec($total),
                        'jumlah_dp' => $this->dec($jumlahDp),
                        'status_bayar' => $statusBayar,
                        'status_desain' => $statusDesain,
                        'catatan_desain' => $catatanDesain[$i % count($catatanDesain)],
                        'lampiran_briefing' => null,
                        'deadline' => $createdAt->copy()->addDays(2 + ($i % 14)),
                    ];
                    $notaLama = Order::query()->where($kunciNota)->first();
                    if ($notaLama !== null) {
                        // Idempotent: jangan timpa data nota yang sudah ada.
                        $atributNota = $notaLama->only([
                            'customer_id', 'user_id', 'total_tagihan', 'jumlah_dp',
                            'status_bayar', 'status_desain', 'catatan_desain',
                            'lampiran_briefing', 'deadline',
                        ]);
                    }

                    /** @var Order $order */
                    $order = Order::updateOrCreate($kunciNota, $atributNota);
                    if ($notaLama === null) {
                        Order::query()->whereKey($order->id)->update([
                            'created_at' => $createdAt,
                            'updated_at' => $createdAt,
                        ]);
                        $order->refresh();
                    }

                    $this->simpanSpkCampuran($order, $i, $instansi);

                    foreach ($itemData as $barisItem) {
                        $item = $this->simpanItem($order, $barisItem);
                        $this->simpanRevisiDesain($item, $i);
                        $this->simpanLogBahan($item, $i, $rolls, $offcuts, $finishings);
                    }
                }
            }
        }
    }

    /**
     * 10 item cetak per nota: 6 meteran + 4 satuan, dengan variasi status/finishing.
     *
     * @param  Collection<int, Product>  $meteran
     * @param  Collection<int, Product>  $satuan
     * @param  Collection<int|string, Finishing>  $finishings
     * @param  array<int, array{0:string,1:string}>  $ukuranMeteran
     * @param  array<int, string>  $daftarStatus
     * @return array<int, array<string, mixed>>
     */
    private function buatItemCampuran(
        int $i,
        Customer $customer,
        Collection $meteran,
        Collection $satuan,
        Collection $finishings,
        array $ukuranMeteran,
        array $daftarStatus,
    ): array {
        $items = [];

        for ($j = 0; $j < 6; $j++) {
            /** @var Product $produk */
            $produk = $meteran[($i + $j) % $meteran->count()];
            [$lebar, $tinggi] = $ukuranMeteran[($i + $j) % count($ukuranMeteran)];
            $qty = 1 + (($i + $j) % 3);
            $luasAsli = round((float) $lebar * (float) $tinggi * $qty, 2);
            $luasTagihan = max($luasAsli, 1.0); // pembulatan minimal 1 m2 hanya tagihan
            $harga = $produk->hargaUntukKategori($customer->kategori);
            $finishingId = ($j % 2 === 0 && $finishings->isNotEmpty())
                ? $finishings->values()[($i + $j) % $finishings->count()]->id
                : null;
            $status = $daftarStatus[($i + $j) % count($daftarStatus)];

            $items[] = $this->hitungItem($i, $j, $produk, $finishingId, $lebar, $tinggi, $qty, $luasTagihan, $harga, $status);
        }

        for ($j = 0; $j < 4; $j++) {
            /** @var Product $produk */
            $produk = $satuan[($i + $j) % $satuan->count()];
            $qty = 1 + (($i + $j) % 5);
            $harga = $produk->hargaUntukKategori($customer->kategori);
            $status = $daftarStatus[($i + 6 + $j) % count($daftarStatus)];

            // Produk satuan: tanpa finishing, tanpa luas meteran.
            $items[] = $this->hitungItem($i, 6 + $j, $produk, null, null, null, $qty, null, $harga, $status);
        }

        return $items;
    }

    /**
     * Hitung baris item sebelum nota dibuat.
     *
     * @return array{product_id: int, qty: int, lebar_m: string|null, tinggi_m: string|null, finishing_id: int|null, luas_tagihan_m2: string|null, harga_satuan_dipakai: string, subtotal: float, status_produksi: string, file_preview_acc: string|null, file_cetak_tiff: string|null, file_master_arsip: string|null}
     */
    private function hitungItem(
        int $i,
        int $j,
        Product $produk,
        ?int $finishingId,
        ?string $lebar,
        ?string $tinggi,
        int $qty,
        ?float $luasTagihan,
        float $harga,
        string $status,
    ): array {
        $pascaDesain = in_array($status, self::STATUS_PASCA_DESAIN, true);
        $ekstensiArsip = ['cdr', 'psd', 'ai', 'zip'][$i % 4];
        $awalFile = sprintf('COMP-%04d-%d', $i, $j + 1);
        $fileCetak = $pascaDesain
            ? MediaStorage::FOLDER_FILE_CETAK_TIFF.'/'.$awalFile.'.tiff'
            : null;
        $fileMaster = $pascaDesain
            ? MediaStorage::FOLDER_FILE_MASTER_ARSIP.'/'.$awalFile.'.'.$ekstensiArsip
            : null;
        $filePreview = $status === OrderItem::STATUS_MENUNGGU_ACC
            ? MediaStorage::FOLDER_PREVIEW_ACC.'/'.$awalFile.'.png'
            : null;

        if ($fileCetak !== null && $fileMaster !== null) {
            $this->letakkanPlaceholder($fileCetak, 'Placeholder file siap cetak '.$awalFile.'.');
            $this->letakkanPlaceholder($fileMaster, 'Placeholder file master arsip '.$awalFile.'.');
        }
        if ($filePreview !== null) {
            $this->letakkanPlaceholder($filePreview, 'Placeholder preview ACC '.$awalFile.'.');
        }

        $subtotal = $luasTagihan !== null
            ? round($harga * $luasTagihan, 2)
            : round($harga * $qty, 2);

        return [
            'product_id' => $produk->id,
            'qty' => $qty,
            'lebar_m' => $lebar === null ? null : $this->dec($lebar),
            'tinggi_m' => $tinggi === null ? null : $this->dec($tinggi),
            'finishing_id' => $finishingId,
            'luas_tagihan_m2' => $luasTagihan === null ? null : $this->dec($luasTagihan),
            'harga_satuan_dipakai' => $this->dec($harga),
            'subtotal' => $subtotal,
            'status_produksi' => $status,
            'file_preview_acc' => $filePreview,
            'file_cetak_tiff' => $fileCetak,
            'file_master_arsip' => $fileMaster,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function simpanItem(Order $order, array $data): OrderItem
    {
        $kunci = [
            'order_id' => $order->id,
            'product_id' => $data['product_id'],
            'qty' => $data['qty'],
            'lebar_m' => $data['lebar_m'],
            'tinggi_m' => $data['tinggi_m'],
        ];
        $atribut = [
            'finishing_id' => $data['finishing_id'],
            'luas_tagihan_m2' => $data['luas_tagihan_m2'],
            'harga_satuan_dipakai' => $data['harga_satuan_dipakai'],
            'subtotal' => $this->dec($data['subtotal']),
            'status_produksi' => $data['status_produksi'],
            'file_preview_acc' => $data['file_preview_acc'],
            'file_cetak_tiff' => $data['file_cetak_tiff'],
            'file_master_arsip' => $data['file_master_arsip'],
        ];
        $itemLama = OrderItem::query()->where($kunci)->first();
        if ($itemLama !== null) {
            $atribut = $itemLama->only([
                'finishing_id', 'luas_tagihan_m2', 'harga_satuan_dipakai',
                'subtotal', 'status_produksi', 'file_preview_acc',
                'file_cetak_tiff', 'file_master_arsip',
            ]);
        }

        return OrderItem::updateOrCreate($kunci, $atribut);
    }

    /**
     * 5 SPK advertising per nota. Non-billing Fase 1: tidak ada nominal harga.
     */
    private function simpanSpkCampuran(Order $order, int $i, string $instansi): void
    {
        $jenisList = [
            'Papan Nama',
            'Rangka hollow galvanis',
            'Pemasangan baliho',
            'Neon box',
            'Papan akrilik',
        ];
        $ukuran = ['200 x 100 cm', '300 x 150 cm', '120 x 240 cm', '400 x 200 cm', '100 x 80 cm'];
        $lokasiDasar = [
            'Depan gerbang utama',
            'Lobi gedung utama',
            'Area parkir timur',
            'Koridor lantai 2',
            'Fasad depan gedung',
        ];

        foreach ($jenisList as $idx => $jenis) {
            $status = AdvertisingSpk::STATUS_LAPANGAN_LIST[($i + $idx) % count(AdvertisingSpk::STATUS_LAPANGAN_LIST)];
            $kunci = [
                'order_id' => $order->id,
                'jenis_pekerjaan' => $jenis,
            ];
            $atribut = [
                'ukuran_konstruksi' => $ukuran[$idx % count($ukuran)],
                'lokasi_pasang' => $instansi !== Customer::INSTANSI_DEFAULT
                    ? $instansi.' - '.$lokasiDasar[$idx % count($lokasiDasar)]
                    : $lokasiDasar[$idx % count($lokasiDasar)],
                'catatan_teknis' => 'Rangka besi hollow, finishing cat anti karat. SPK non-billing Fase 1.',
                'status_lapangan' => $status,
            ];
            $spkLama = AdvertisingSpk::query()->where($kunci)->first();

            AdvertisingSpk::updateOrCreate(
                $kunci,
                $spkLama ? $spkLama->only(['ukuran_konstruksi', 'lokasi_pasang', 'catatan_teknis', 'status_lapangan']) : $atribut,
            );
        }
    }

    /**
     * SPK tunggal (kompatibilitas internal).
     */
    private function simpanSpk(Order $order, int $i): void
    {
        $this->simpanSpkCampuran($order, $i, $order->customer?->instansi ?? Customer::INSTANSI_DEFAULT);
    }

    private function simpanRevisiDesain(OrderItem $item, int $i): void
    {
        $perluRevisi = in_array($item->status_produksi, array_merge(
            [OrderItem::STATUS_MENUNGGU_ACC],
            self::STATUS_PASCA_DESAIN,
        ), true);

        if (! $perluRevisi) {
            return;
        }

        $jumlah = 1 + ($i % 2);
        $catatan = ['Perbesar judul utama', 'Ganti warna background', 'Rapikan jarak antar elemen'];

        for ($revisi = 1; $revisi <= $jumlah; $revisi++) {
            $kunci = [
                'order_item_id' => $item->id,
                'revisi_ke' => $revisi,
            ];
            $path = MediaStorage::FOLDER_PREVIEW_ACC.'/revision-'.$item->id.'-'.$revisi.'.png';
            $this->letakkanPlaceholder($path, 'Placeholder revisi '.$revisi.' item '.$item->id.'.');
            $atribut = [
                'catatan_revisi' => $catatan[($i + $revisi) % count($catatan)],
                'file_preview' => $path,
            ];
            $revisiLama = DesignRevision::query()->where($kunci)->first();

            DesignRevision::updateOrCreate(
                $kunci,
                $revisiLama ? $revisiLama->only(['catatan_revisi', 'file_preview']) : $atribut,
            );
        }
    }

    /**
     * Audit bahan memakai luas kotor; tagihan pelanggan tidak ikut bertambah.
     *
     * @param  Collection<int|string, Finishing>  $finishings
     */
    private function simpanLogBahan(
        OrderItem $item,
        int $i,
        Collection $rolls,
        Collection $offcuts,
        Collection $finishings,
    ): void {
        if (! in_array($item->status_produksi, self::STATUS_PASCA_DESAIN, true) || ! $item->isMeteran()) {
            return;
        }

        $lebar = (float) $item->lebar_m;
        $tinggi = (float) $item->tinggi_m;
        $qty = (int) $item->qty;
        $netto = round($lebar * $tinggi * $qty, 2);

        $finishing = $item->finishing_id ? $finishings->get($item->finishing_id) : null;
        $ekstraLebar = $finishing
            ? ((float) $finishing->ekstra_kiri_cm + (float) $finishing->ekstra_kanan_cm) / 100
            : 0.0;
        $ekstraTinggi = $finishing
            ? ((float) $finishing->ekstra_atas_cm + (float) $finishing->ekstra_bawah_cm) / 100
            : 0.0;
        $kotor = round(
            ($lebar + $ekstraLebar) * ($tinggi + $ekstraTinggi) * $qty,
            2,
        );
        $wosFinishing = round(max($kotor - $netto, 0), 2);
        $wosPotong = round([0.00, 0.15, 0.32][$i % 3], 2);
        $lebarKotor = round($lebar + $ekstraLebar, 2);
        $jenisBahan = $this->jenisBahan($item, $lebarKotor);
        $roll = $this->pilihRoll($rolls, $jenisBahan, $i + $item->id);
        $offcut = $roll === null ? $this->pilihOffcut($offcuts, $i + $item->id) : null;

        if ($roll === null && $offcut === null) {
            return;
        }

        $keterangan = 'Selesai cetak | '.$item->product?->nama_produk
            .' | kotor '.$this->dec($kotor).' m2';
        $kunci = [
            'order_item_id' => $item->id,
            'material_roll_id' => $roll?->id,
            'material_offcut_id' => $offcut?->id,
            'luas_netto_m2' => $this->dec($netto),
            'keterangan' => $keterangan,
        ];
        $atribut = [
            'wos_finishing_m2' => $this->dec($wosFinishing),
            'wos_potong_m2' => $this->dec($wosPotong),
            'gagal_cetak_m2' => $this->dec(0),
        ];
        $logLama = MaterialLog::query()->where($kunci)->first();

        MaterialLog::updateOrCreate(
            $kunci,
            $logLama ? $logLama->only(['wos_finishing_m2', 'wos_potong_m2', 'gagal_cetak_m2']) : $atribut,
        );

        // Sebagian kasus gagal cetak: tidak mengubah tagihan pelanggan.
        if ($i % 3 === 0 && $roll !== null) {
            $panjangRusak = round(0.5 + ($i % 3) * 0.25, 2);
            $luasRusak = round((float) $roll->lebar_m * $panjangRusak, 2);
            $kunciGagal = [
                'order_item_id' => $item->id,
                'material_roll_id' => $roll->id,
                'material_offcut_id' => null,
                'luas_netto_m2' => $this->dec(0),
                'keterangan' => 'Gagal cetak | Roll '.$roll->kode_roll.' | '.$this->dec($panjangRusak).' m',
            ];
            $atributGagal = [
                'wos_finishing_m2' => $this->dec(0),
                'wos_potong_m2' => $this->dec(0),
                'gagal_cetak_m2' => $this->dec($luasRusak),
            ];
            $gagalLama = MaterialLog::query()->where($kunciGagal)->first();

            MaterialLog::updateOrCreate(
                $kunciGagal,
                $gagalLama ? $gagalLama->only(['wos_finishing_m2', 'wos_potong_m2', 'gagal_cetak_m2']) : $atributGagal,
            );
        }
    }

    private function seedOperationalExpenses(): void
    {
        $kategori = OperationalExpense::KATEGORI_BIAYA;
        $tanggalDasar = Carbon::create(2026, 9, 1);

        for ($i = 0; $i < 60; $i++) {
            $jenis = $kategori[$i % count($kategori)];
            $keterangan = match ($jenis) {
                OperationalExpense::KATEGORI_GAJI => 'Gaji workshop periode '.($i + 1),
                OperationalExpense::KATEGORI_LISTRIK_AIR => 'Tagihan listrik dan air workshop '.($i + 1),
                OperationalExpense::KATEGORI_TINTA => 'Pembelian tinta printer '.($i + 1),
                OperationalExpense::KATEGORI_PERAWATAN => 'Servis dan perawatan mesin '.($i + 1),
                default => 'Biaya operasional lain '.($i + 1),
            };
            $nominal = match ($jenis) {
                OperationalExpense::KATEGORI_GAJI => 2500000 + ($i % 5) * 250000,
                OperationalExpense::KATEGORI_LISTRIK_AIR => 450000 + ($i % 7) * 75000,
                OperationalExpense::KATEGORI_TINTA => 850000 + ($i % 4) * 125000,
                OperationalExpense::KATEGORI_PERAWATAN => 300000 + ($i % 6) * 150000,
                default => 75000 + ($i % 8) * 25000,
            };
            $kunci = [
                'tanggal' => $tanggalDasar->copy()->addDays($i)->toDateString(),
                'kategori_biaya' => $jenis,
                'keterangan' => $keterangan,
            ];
            $ada = OperationalExpense::query()->where($kunci)->first();

            OperationalExpense::updateOrCreate(
                $kunci,
                ['nominal' => $ada ? $ada->nominal : $this->dec($nominal)],
            );
        }
    }

    private function jenisBahan(OrderItem $item, float $lebarKotor): string
    {
        $nama = strtolower((string) $item->product?->nama_produk);

        return match (true) {
            str_contains($nama, 'stiker') => $lebarKotor <= 1.0
                ? MaterialRoll::JENIS_STIKER_1M
                : MaterialRoll::JENIS_STIKER_15M,
            str_contains($nama, 'backlit') => 'Backlit',
            str_contains($nama, '440') => 'MMT 440',
            str_contains($nama, '340') => 'MMT 340',
            default => 'MMT 280',
        };
    }

    private function pilihRoll(Collection $rolls, string $jenisBahan, int $seed): ?MaterialRoll
    {
        $cocok = $rolls
            ->where('jenis_bahan', $jenisBahan)
            ->where('status', MaterialRoll::STATUS_AKTIF)
            ->values();

        if ($cocok->isEmpty()) {
            $cocok = $rolls->where('status', MaterialRoll::STATUS_AKTIF)->values();
        }

        return $cocok->isEmpty() ? null : $cocok[$seed % $cocok->count()];
    }

    private function pilihOffcut(Collection $offcuts, int $seed): ?MaterialOffcut
    {
        $cocok = $offcuts->where('status', MaterialOffcut::STATUS_TERSEDIA)->values();

        return $cocok->isEmpty() ? null : $cocok[$seed % $cocok->count()];
    }

    private function letakkanPlaceholder(string $path, string $isi): void
    {
        $disk = Storage::disk(MediaStorage::DISK);

        if (! $disk->exists($path)) {
            $disk->put($path, $isi);
        }
    }

    private function dec(float|int|string $nilai): string
    {
        return number_format((float) $nilai, 2, '.', '');
    }
}
