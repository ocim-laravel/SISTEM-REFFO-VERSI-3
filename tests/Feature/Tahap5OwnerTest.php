<?php

namespace Tests\Feature;

use App\Models\AdvertisingSpk;
use App\Models\Customer;
use App\Models\Finishing;
use App\Models\MaterialLog;
use App\Models\MaterialOffcut;
use App\Models\MaterialRoll;
use App\Models\OperationalExpense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Tahap5OwnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_hanya_owner_yang_dapat_mengakses_fitur_tahap_lima(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $halaman = [
            route('owner.dashboard'),
            route('owner.reports.materials'),
            route('owner.reports.profit-loss'),
            route('owner.settings'),
        ];

        foreach ($halaman as $url) {
            $this->actingAs($owner)->get($url)->assertOk();
            $this->actingAs($admin)->get($url)->assertRedirect(route('admin.dashboard'));
        }

        $this->actingAs($admin)
            ->post(route('owner.expenses.store'), [])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertSame(0, OperationalExpense::count());
    }

    public function test_dashboard_menampilkan_antrean_deadline_dan_rekap_instansi(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $telkomsel = Customer::create([
            'instansi' => 'Telkomsel',
            'nama_pelanggan' => 'Sigit',
            'no_wa' => '081111111111',
            'kategori' => Customer::KATEGORI_UMUM,
        ]);
        $hendi = Customer::create([
            'instansi' => 'Telkomsel',
            'nama_pelanggan' => 'Hendi',
            'no_wa' => '081222222222',
            'kategori' => Customer::KATEGORI_UMUM,
        ]);
        $nurokhim = Customer::create([
            'instansi' => 'Telkomsel',
            'nama_pelanggan' => 'Nurokhim',
            'no_wa' => '081333333333',
            'kategori' => Customer::KATEGORI_UMUM,
        ]);

        $notaSigit = $this->nota($telkomsel, $admin, 1000000, 400000, Order::STATUS_BAYAR_DP);
        $notaSigit->update(['deadline' => now()->addDay()]);
        $this->nota($hendi, $admin, 500000, 500000, Order::STATUS_BAYAR_LUNAS);
        $this->nota($nurokhim, $admin, 300000, 0, Order::STATUS_BAYAR_BELUM_LUNAS);

        $produk = $this->produkMeteran();

        foreach ([
            OrderItem::STATUS_MENUNGGU_DESAIN,
            OrderItem::STATUS_ANTREAN_CETAK,
            OrderItem::STATUS_ANTREAN_FINISHING,
            OrderItem::STATUS_SIAP_DIAMBIL,
        ] as $status) {
            $this->item($notaSigit, $produk, $status);
        }

        AdvertisingSpk::create([
            'order_id' => $notaSigit->id,
            'jenis_pekerjaan' => 'Papan Nama',
            'ukuran_konstruksi' => '200 x 100 cm',
            'lokasi_pasang' => 'Gedung A',
            'status_lapangan' => AdvertisingSpk::STATUS_ANTREAN,
        ]);

        $response = $this->actingAs($owner)->get(route('owner.dashboard'));

        $response->assertOk()
            ->assertSee('Telkomsel')
            ->assertSee('Sigit')
            ->assertSee('Hendi')
            ->assertSee('Nurokhim')
            ->assertSee('1.800.000')
            ->assertSee('900.000')
            ->assertViewHas('statistik', [
                'desain' => 1,
                'cetak' => 1,
                'finishing' => 1,
                'advertising' => 1,
                'siap_diambil' => 1,
            ])
            ->assertViewHas('totalRekap.nota', 3)
            ->assertViewHas('totalRekap.omzet', 1800000.0)
            ->assertViewHas('totalRekap.piutang', 900000.0);

        $this->assertCount(1, $response->viewData('deadlines'));
    }

    public function test_laporan_bahan_menampilkan_stok_dan_empat_komponen_audit(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $produk = $this->produkMeteran();
        $nota = $this->nota(Customer::create([
            'instansi' => 'Instansi Audit',
            'nama_pelanggan' => 'PIC Audit',
            'no_wa' => '081444444444',
            'kategori' => Customer::KATEGORI_UMUM,
        ]), $admin);
        $item = $this->item($nota, $produk, OrderItem::STATUS_SIAP_DIAMBIL);

        $roll = MaterialRoll::create([
            'kode_roll' => 'MMT280-AUDIT',
            'jenis_bahan' => MaterialRoll::JENIS_BAHAN[0],
            'lebar_m' => 3.2,
            'panjang_awal_m' => 70,
            'panjang_sisa_m' => 35,
            'status' => MaterialRoll::STATUS_AKTIF,
        ]);
        MaterialOffcut::create([
            'material_roll_id' => $roll->id,
            'jenis_bahan' => $roll->jenis_bahan,
            'lebar_m' => 1.5,
            'panjang_m' => 4,
            'luas_m2' => 6,
            'status' => MaterialOffcut::STATUS_TERSEDIA,
        ]);

        $satuan = Product::create([
            'nama_produk' => 'X-Banner Audit',
            'kategori_produk' => Product::KATEGORI_SATUAN,
            'harga_umum' => 100000,
            'harga_reseller' => 95000,
            'harga_prioritas' => 90000,
            'harga_pegawai' => 85000,
            'harga_modal' => 60000,
            'stok_pcs' => 7,
        ]);

        MaterialLog::create([
            'order_item_id' => $item->id,
            'material_roll_id' => $roll->id,
            'luas_netto_m2' => 2,
            'wos_finishing_m2' => 0.5,
            'wos_potong_m2' => 0.25,
            'gagal_cetak_m2' => 1.25,
        ]);

        $response = $this->actingAs($owner)->get(route('owner.reports.materials'));

        $response->assertOk()
            ->assertSee('MMT280-AUDIT')
            ->assertSee('X-Banner Audit')
            ->assertSee('4,00')
            ->assertViewHas('ringkasan.netto', 2.0)
            ->assertViewHas('ringkasan.finishing', 0.5)
            ->assertViewHas('ringkasan.potong', 0.25)
            ->assertViewHas('ringkasan.gagal', 1.25)
            ->assertViewHas('ringkasan.total', 4.0);

        $rollData = collect($response->viewData('rolls'))->firstWhere('kode_roll', 'MMT280-AUDIT');
        $this->assertSame(50.0, $rollData['persen_sisa']);
        $this->assertTrue($response->viewData('stokPcs')->contains('id', $satuan->id));
    }

    public function test_laba_rugi_menghitung_seluruh_hpp_dan_biaya_operasional(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $dari = now()->subDays(2)->toDateString();
        $sampai = now()->toDateString();

        $meteran = Product::create([
            'nama_produk' => 'MMT 280 Meteran',
            'kategori_produk' => Product::KATEGORI_METERAN,
            'harga_umum' => 100000,
            'harga_reseller' => 90000,
            'harga_prioritas' => 90000,
            'harga_pegawai' => 80000,
            'harga_modal' => 10000,
        ]);
        $satuan = Product::create([
            'nama_produk' => 'Poster HPP',
            'kategori_produk' => Product::KATEGORI_SATUAN,
            'harga_umum' => 20000,
            'harga_reseller' => 19000,
            'harga_prioritas' => 18000,
            'harga_pegawai' => 17000,
            'harga_modal' => 7000,
            'stok_pcs' => 10,
        ]);

        $customer = Customer::create([
            'instansi' => 'Instansi HPP',
            'nama_pelanggan' => 'PIC HPP',
            'no_wa' => '081555555555',
            'kategori' => Customer::KATEGORI_UMUM,
        ]);
        $nota = $this->nota($customer, $admin, 2000000, 500000, Order::STATUS_BAYAR_DP);
        $itemMeteran = $this->item($nota, $meteran, OrderItem::STATUS_SIAP_DIAMBIL);
        $this->item($nota, $satuan, OrderItem::STATUS_SIAP_DIAMBIL, 3);

        MaterialLog::create([
            'order_item_id' => $itemMeteran->id,
            'luas_netto_m2' => 2,
            'wos_finishing_m2' => 0.5,
            'wos_potong_m2' => 0.25,
            'gagal_cetak_m2' => 1.25,
        ]);

        OperationalExpense::create([
            'tanggal' => $dari,
            'kategori_biaya' => OperationalExpense::KATEGORI_GAJI,
            'keterangan' => 'Gaji workshop',
            'nominal' => 250000,
        ]);
        OperationalExpense::create([
            'tanggal' => now()->subDays(30)->toDateString(),
            'kategori_biaya' => OperationalExpense::KATEGORI_LAIN,
            'keterangan' => 'Di luar periode',
            'nominal' => 999999,
        ]);

        $response = $this->actingAs($owner)->get(route('owner.reports.profit-loss', [
            'dari' => $dari,
            'sampai' => $sampai,
        ]));

        $response->assertOk()
            ->assertSee('Luas Netto')
            ->assertSee('Wos Finishing')
            ->assertSee('Wos Potong Roll')
            ->assertSee('Gagal Cetak')
            ->assertSee('1.689.000')
            ->assertViewHas('omzet.tagihan', 2000000.0)
            ->assertViewHas('omzet.diterima', 500000.0)
            ->assertViewHas('omzet.piutang', 1500000.0)
            ->assertViewHas('hppBahan', 40000.0)
            ->assertViewHas('hppSatuan', 21000.0)
            ->assertViewHas('hppProduksi', 61000.0)
            ->assertViewHas('totalOperasional', 250000.0)
            ->assertViewHas('labaBersih', 1689000.0);

        $komponen = $response->viewData('komponenHpp');
        $this->assertSame(20000.0, $komponen['netto']['biaya']);
        $this->assertSame(5000.0, $komponen['finishing']['biaya']);
        $this->assertSame(2500.0, $komponen['potong']['biaya']);
        $this->assertSame(12500.0, $komponen['gagal']['biaya']);
    }

    public function test_owner_dapat_mengubah_master_harga_stok_dan_rumus_finishing(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);
        $meteran = $this->produkMeteran();
        $satuan = Product::create([
            'nama_produk' => 'Roll Banner',
            'kategori_produk' => Product::KATEGORI_SATUAN,
            'harga_umum' => 150000,
            'harga_reseller' => 140000,
            'harga_prioritas' => 135000,
            'harga_pegawai' => 130000,
            'harga_modal' => 90000,
            'stok_pcs' => 4,
        ]);
        $finishing = Finishing::create([
            'nama_finishing' => 'Laminasi',
            'ekstra_kiri_cm' => 1,
            'ekstra_kanan_cm' => 1,
            'ekstra_atas_cm' => 1,
            'ekstra_bawah_cm' => 1,
        ]);

        $this->actingAs($owner)
            ->put(route('owner.settings.products'), [
                'products' => [
                    [
                        'id' => $meteran->id,
                        'harga_umum' => 111000,
                        'harga_reseller' => 112000,
                        'harga_prioritas' => 113000,
                        'harga_pegawai' => 114000,
                        'harga_modal' => 115000,
                    ],
                ],
            ])
            ->assertRedirect();

        $meteran->refresh();
        $this->assertSame(111000.0, (float) $meteran->harga_umum);
        $this->assertSame(115000.0, (float) $meteran->harga_modal);

        $this->actingAs($owner)
            ->post(route('owner.settings.rolls'), [
                'kode_roll' => 'STIKER15-001',
                'jenis_bahan' => MaterialRoll::JENIS_STIKER_15M,
            ])
            ->assertRedirect();

        $roll = MaterialRoll::where('kode_roll', 'STIKER15-001')->firstOrFail();
        $this->assertSame(1.5, (float) $roll->lebar_m);
        $this->assertSame(50.0, (float) $roll->panjang_sisa_m);

        $this->actingAs($owner)
            ->post(route('owner.settings.stok-pcs'), [
                'product_id' => $satuan->id,
                'jumlah' => 6,
            ])
            ->assertRedirect();

        $this->assertSame(10, (int) $satuan->fresh()->stok_pcs);

        $this->actingAs($owner)
            ->put(route('owner.settings.finishings'), [
                'finishings' => [
                    [
                        'id' => $finishing->id,
                        'ekstra_kiri_cm' => 2.5,
                        'ekstra_kanan_cm' => 3,
                        'ekstra_atas_cm' => 4,
                        'ekstra_bawah_cm' => 7,
                    ],
                ],
            ])
            ->assertRedirect();

        $finishing->refresh();
        $this->assertSame(2.5, (float) $finishing->ekstra_kiri_cm);
        $this->assertSame(7.0, (float) $finishing->ekstra_bawah_cm);
    }

    public function test_pengeluaran_operasional_dapat_disimpan_dan_dihapus(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);

        $this->actingAs($owner)
            ->post(route('owner.expenses.store'), [
                'tanggal' => now()->toDateString(),
                'kategori_biaya' => OperationalExpense::KATEGORI_LISTRIK_AIR,
                'keterangan' => 'Tagihan listrik workshop',
                'nominal' => 750000,
            ])
            ->assertRedirect();

        $expense = OperationalExpense::firstOrFail();
        $this->assertSame(750000.0, (float) $expense->nominal);

        $this->actingAs($owner)
            ->delete(route('owner.expenses.destroy', $expense))
            ->assertRedirect();

        $this->assertSame(0, OperationalExpense::count());
    }

    private function produkMeteran(): Product
    {
        return Product::create([
            'nama_produk' => 'Stiker Meteran '.uniqid(),
            'kategori_produk' => Product::KATEGORI_METERAN,
            'harga_umum' => 10000,
            'harga_reseller' => 9000,
            'harga_prioritas' => 9000,
            'harga_pegawai' => 8000,
            'harga_modal' => 5000,
        ]);
    }

    private function nota(Customer $customer, User $admin, float $total = 10000, float $dp = 0, string $statusBayar = Order::STATUS_BAYAR_BELUM_LUNAS): Order
    {
        return Order::create([
            'no_nota' => 'INV-TEST-'.strtoupper(uniqid()),
            'customer_id' => $customer->id,
            'user_id' => $admin->id,
            'total_tagihan' => $total,
            'jumlah_dp' => $dp,
            'status_bayar' => $statusBayar,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function item(Order $nota, Product $produk, string $status, int $qty = 1, array $overrides = []): OrderItem
    {
        return OrderItem::create(array_merge([
            'order_id' => $nota->id,
            'product_id' => $produk->id,
            'finishing_id' => null,
            'lebar_m' => $produk->kategori_produk === Product::KATEGORI_METERAN ? 1 : null,
            'tinggi_m' => $produk->kategori_produk === Product::KATEGORI_METERAN ? 1 : null,
            'qty' => $qty,
            'luas_tagihan_m2' => $produk->kategori_produk === Product::KATEGORI_METERAN ? $qty : null,
            'harga_satuan_dipakai' => $produk->harga_umum,
            'subtotal' => $produk->harga_umum * $qty,
            'status_produksi' => $status,
        ], $overrides));
    }
}
