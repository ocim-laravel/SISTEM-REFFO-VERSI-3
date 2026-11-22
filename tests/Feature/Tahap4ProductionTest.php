<?php

namespace Tests\Feature;

use App\Models\AdvertisingSpk;
use App\Models\Customer;
use App\Models\Finishing;
use App\Models\MaterialLog;
use App\Models\MaterialOffcut;
use App\Models\MaterialRoll;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\MediaStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Tahap4ProductionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(MediaStorage::DISK);
    }

    public function test_revisi_dikendalikan_admin_dan_finalisasi_menbutuhkan_acc_plus_dua_file(): void
    {
        $desainer = User::factory()->create(['role' => User::ROLE_DESAINER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $item = $this->meteranItem();

        $this->actingAs($desainer)
            ->post(route('desainer.orders.terima', $item->order))
            ->assertRedirect();

        $this->actingAs($desainer)
            ->post(route('desainer.orders.preview', $item->order), [
                'catatan_revisi' => 'Perbesar judul',
                'file_preview' => UploadedFile::fake()->create('preview-1.png', 10, 'image/png'),
            ])
            ->assertRedirect();

        // Admin mengajukan revisi dengan catatan wajib.
        $this->actingAs($admin)
            ->post(route('admin.design-reviews.revisi', $item), [
                'catatan_revisi' => 'Perbesar judul dan perbaiki kontras',
            ])
            ->assertRedirect();

        $this->assertSame(OrderItem::STATUS_REVISI, $item->fresh()->status_produksi);
        $this->assertNull($item->fresh()->acc_admin_id);
        $this->assertSame($admin->id, (int) $item->order->designRevisions()->whereNotNull('admin_id')->firstOrFail()->admin_id);

        // Finalisasi tetap terkunci selama revisi pending.
        $this->actingAs($desainer)
            ->post(route('desainer.items.finalisasi', $item), [
                'file_cetak_tiff' => UploadedFile::fake()->create('siap.tiff', 1, 'image/tiff'),
                'file_master_arsip' => UploadedFile::fake()->create('master.cdr', 1),
            ])
            ->assertSessionHasErrors('file_cetak_tiff');

        $this->actingAs($desainer)
            ->post(route('desainer.orders.preview', $item->order), [
                'catatan_revisi' => 'Ganti warna',
                'file_preview' => UploadedFile::fake()->create('preview-2.png', 10, 'image/png'),
            ])
            ->assertRedirect();

        // Log: preview awal (1), revisi Admin (2), tanggapan desainer (3).
        $this->assertSame([1, 2, 3], $item->order->designRevisions()->orderBy('revisi_ke')->pluck('revisi_ke')->all());
        $this->assertFalse((bool) $item->order->designRevisions()->where('revisi_ke', 2)->firstOrFail()->isPending());

        $this->actingAs($admin)
            ->post(route('admin.design-reviews.setujui', $item))
            ->assertRedirect();

        $this->assertNotNull($item->fresh()->acc_admin_id);
        $this->assertNotNull($item->fresh()->waktu_acc);

        // TIFF tersimpan lebih dulu; master belum mengunci proses sebelum seluruh TIFF ada.
        $this->actingAs($desainer)
            ->post(route('desainer.items.finalisasi', $item), [
                'file_cetak_tiff' => UploadedFile::fake()->create('siap.tiff', 1, 'image/tiff'),
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->assertSame(OrderItem::STATUS_MENUNGGU_ACC, $item->fresh()->status_produksi);
        $this->assertNotNull($item->fresh()->file_cetak_tiff);
        $this->assertNull($item->fresh()->order->file_master_arsip);

        $this->actingAs($desainer)
            ->post(route('desainer.orders.master', $item->order), [
                'file_master_arsip' => UploadedFile::fake()->create('master.cdr', 1),
                'tanda_tangan' => 'Tanda Tangan Desainer',
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $item->refresh();
        $item->load('order');
        $this->assertSame(OrderItem::STATUS_ANTREAN_CETAK, $item->status_produksi);
        $this->assertNotNull($item->file_cetak_tiff);
        $this->assertNotNull($item->file_master_arsip);
        $this->assertSame('Tanda Tangan Desainer', $item->order->tanda_tangan_desainer);
        $this->assertNotNull($item->order->waktu_terima_desain);
        $this->assertNotNull($item->order->waktu_selesai_desain);
        Storage::disk(MediaStorage::DISK)->assertExists($item->file_master_arsip);
    }

    public function test_operator_memotong_roll_dan_mencatat_log_bahan(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OPERATOR_CETAK]);
        $finishing = Finishing::create([
            'nama_finishing' => 'Laminasi',
            'ekstra_kiri_cm' => 25,
            'ekstra_kanan_cm' => 0,
            'ekstra_atas_cm' => 0,
            'ekstra_bawah_cm' => 0,
        ]);
        $item = $this->meteranItem(['finishing_id' => $finishing->id, 'qty' => 2]);
        $item->update(['status_produksi' => OrderItem::STATUS_ANTREAN_CETAK]);

        $roll = MaterialRoll::create([
            'kode_roll' => 'R-TEST-'.uniqid(),
            'jenis_bahan' => MaterialRoll::JENIS_BAHAN[0],
            'lebar_m' => 1.5,
            'panjang_awal_m' => 10,
            'panjang_sisa_m' => 10,
            'status' => MaterialRoll::STATUS_AKTIF,
        ]);

        $this->actingAs($user)
            ->post(route('operator.items.selesai', $item), [
                'sumber_bahan' => 'roll',
                'material_roll_id' => $roll->id,
                'orientasi_potong' => 'panjang',
            ])
            ->assertRedirect();

        $this->assertSame(8.0, (float) $roll->fresh()->panjang_sisa_m);
        $this->assertSame(OrderItem::STATUS_ANTREAN_FINISHING, $item->fresh()->status_produksi);

        // Sisa lebar 0,25 m (< 0,50 m) harus masuk WOS potong.
        $log = MaterialLog::firstWhere('order_item_id', $item->id);
        $this->assertNotNull($log);
        $this->assertSame(2.0, (float) $log->luas_netto_m2);
        $this->assertSame(0.5, (float) $log->wos_finishing_m2);
        $this->assertSame(0.5, (float) $log->wos_potong_m2);
        $this->assertSame(0, MaterialOffcut::where('material_roll_id', $roll->id)->count());
    }

    public function test_sisa_lebar_minimal_05_m_disimpan_sebagai_offcut(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OPERATOR_CETAK]);
        $item = $this->meteranItem(['qty' => 1]);
        $item->update(['status_produksi' => OrderItem::STATUS_PROSES_CETAK]);

        $roll = MaterialRoll::create([
            'kode_roll' => 'R-TEST-'.uniqid(),
            'jenis_bahan' => MaterialRoll::JENIS_STIKER_1M,
            'lebar_m' => 1.5,
            'panjang_awal_m' => 5,
            'panjang_sisa_m' => 5,
            'status' => MaterialRoll::STATUS_AKTIF,
        ]);

        $this->actingAs($user)
            ->post(route('operator.items.selesai', $item), [
                'sumber_bahan' => 'roll',
                'material_roll_id' => $roll->id,
            ])
            ->assertRedirect();

        $this->assertSame(1, MaterialOffcut::where('material_roll_id', $roll->id)->count());
        $offcut = MaterialOffcut::firstWhere('material_roll_id', $roll->id);
        $this->assertSame(0.5, (float) $offcut->lebar_m);
        $this->assertSame(MaterialOffcut::STATUS_TERSEDIA, $offcut->status);
        $this->assertSame(OrderItem::STATUS_SIAP_DIAMBIL, $item->fresh()->status_produksi);
    }

    public function test_operator_dapat_memotong_dari_stok_offcut(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OPERATOR_CETAK]);
        $item = $this->meteranItem();
        $item->update(['status_produksi' => OrderItem::STATUS_PROSES_CETAK]);

        $offcut = MaterialOffcut::create([
            'material_roll_id' => null,
            'jenis_bahan' => MaterialRoll::JENIS_BAHAN[0],
            'lebar_m' => 1.2,
            'panjang_m' => 3,
            'luas_m2' => 3.6,
            'status' => MaterialOffcut::STATUS_TERSEDIA,
        ]);

        $this->actingAs($user)
            ->post(route('operator.items.selesai', $item), [
                'sumber_bahan' => 'offcut',
                'material_offcut_id' => $offcut->id,
            ])
            ->assertRedirect();

        $this->assertSame(MaterialOffcut::STATUS_TERPAKAI, $offcut->fresh()->status);
        $log = MaterialLog::firstWhere('order_item_id', $item->id);
        $this->assertSame($offcut->id, (int) $log->material_offcut_id);
        $this->assertNull($log->material_roll_id);
    }

    public function test_rekomendasi_roll_stiker_mengikuti_lebar_kotor(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OPERATOR_CETAK]);
        $finishing = Finishing::create([
            'nama_finishing' => 'Laminasi '.uniqid(),
            'ekstra_kiri_cm' => 40,
            'ekstra_kanan_cm' => 40,
            'ekstra_atas_cm' => 0,
            'ekstra_bawah_cm' => 0,
        ]);
        $item = $this->meteranItem(['finishing_id' => $finishing->id]);
        $item->update(['status_produksi' => OrderItem::STATUS_ANTREAN_CETAK]);

        $this->assertSame(1.8, $item->ukuranKotor()['lebar']);
        $this->assertNull($item->rekomendasiRollStiker());

        $finishing->update(['ekstra_kiri_cm' => 0, 'ekstra_kanan_cm' => 0]);
        $this->assertSame(MaterialRoll::JENIS_STIKER_1M, $item->fresh()->rekomendasiRollStiker());

        $this->actingAs($user)
            ->get(route('operator.dashboard'))
            ->assertOk()
            ->assertSee('Rekomendasi roll otomatis: '.MaterialRoll::JENIS_STIKER_1M, false);
    }

    public function test_lapor_gagal_cetak_tidak_mengubah_tagihan(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OPERATOR_CETAK]);
        $item = $this->meteranItem();
        $item->update(['status_produksi' => OrderItem::STATUS_PROSES_CETAK]);
        $tagihan = (float) $item->order->total_tagihan;

        $roll = MaterialRoll::create([
            'kode_roll' => 'R-TEST-'.uniqid(),
            'jenis_bahan' => MaterialRoll::JENIS_BAHAN[0],
            'lebar_m' => 1.5,
            'panjang_awal_m' => 5,
            'panjang_sisa_m' => 5,
            'status' => MaterialRoll::STATUS_AKTIF,
        ]);

        $this->actingAs($user)
            ->post(route('operator.items.gagal-cetak', $item), [
                'material_roll_id' => $roll->id,
                'panjang_rusak_m' => 1.5,
                'keterangan' => 'Mesin tersendat',
            ])
            ->assertRedirect();

        $this->assertSame(3.5, (float) $roll->fresh()->panjang_sisa_m);
        $this->assertSame(2.25, (float) MaterialLog::firstWhere('order_item_id', $item->id)->gagal_cetak_m2);
        $this->assertSame($tagihan, (float) $item->order->fresh()->total_tagihan);
    }

    public function test_produk_satuan_tanpa_finishing_mengurangi_stok_dan_langsung_siap(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OPERATOR_CETAK]);
        $produk = Product::create([
            'nama_produk' => 'Poster '.uniqid(),
            'kategori_produk' => Product::KATEGORI_SATUAN,
            'harga_umum' => 10000,
            'harga_reseller' => 9000,
            'harga_prioritas' => 9000,
            'harga_pegawai' => 8000,
            'harga_modal' => 5000,
            'stok_pcs' => 5,
        ]);
        $item = $this->satuanItem($produk, 2);
        $item->update(['status_produksi' => OrderItem::STATUS_ANTREAN_CETAK]);

        $this->actingAs($user)
            ->post(route('operator.items.selesai', $item))
            ->assertRedirect();

        $this->assertSame(3, (int) $produk->fresh()->stok_pcs);
        $this->assertSame(OrderItem::STATUS_SIAP_DIAMBIL, $item->fresh()->status_produksi);
        $this->assertSame(0, MaterialLog::where('order_item_id', $item->id)->count());
    }

    public function test_finishing_mengubah_status_menjadi_siap_diambil(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_FINISHING]);
        $finishing = Finishing::create([
            'nama_finishing' => 'Pemotongan '.uniqid(),
            'ekstra_kiri_cm' => 0,
            'ekstra_kanan_cm' => 0,
            'ekstra_atas_cm' => 0,
            'ekstra_bawah_cm' => 0,
        ]);
        $item = $this->meteranItem(['finishing_id' => $finishing->id]);
        $item->update(['status_produksi' => OrderItem::STATUS_ANTREAN_FINISHING]);

        $this->actingAs($user)
            ->post(route('finishing.items.konfirmasi', $item))
            ->assertRedirect();

        $this->assertSame(OrderItem::STATUS_SIAP_DIAMBIL, $item->fresh()->status_produksi);
    }

    public function test_advertising_hanya_melihat_spk_tanpa_nominal_tagihan(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADVERTISING]);
        $order = $this->order(123456.78);

        $spk = AdvertisingSpk::create([
            'order_id' => $order->id,
            'jenis_pekerjaan' => 'Papan Nama',
            'ukuran_konstruksi' => '200 x 100 cm',
            'lokasi_pasang' => 'Gedung A',
            'catatan_teknis' => 'Rangka besi hollow',
            'status_lapangan' => AdvertisingSpk::STATUS_ANTREAN,
        ]);

        $response = $this->actingAs($user)->get(route('advertising.dashboard'));
        $response->assertOk()
            ->assertSee('Papan Nama')
            ->assertSee('Rangka besi hollow')
            ->assertDontSee('123.456');

        $this->actingAs($user)
            ->post(route('advertising.spks.status', $spk), ['status_lapangan' => AdvertisingSpk::STATUS_PEMASANGAN])
            ->assertSessionHasErrors('status_lapangan');

        $this->actingAs($user)
            ->post(route('advertising.spks.status', $spk), ['status_lapangan' => AdvertisingSpk::STATUS_PERAKITAN])
            ->assertRedirect();

        $this->assertSame(AdvertisingSpk::STATUS_PERAKITAN, $spk->fresh()->status_lapangan);
    }

    public function test_role_divisi_lain_diblokir_mengakses_dashboard_operator(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_DESAINER]);

        $this->actingAs($user)
            ->get(route('operator.dashboard'))
            ->assertRedirect(route('desainer.dashboard'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function meteranItem(array $overrides = []): OrderItem
    {
        $produk = Product::create([
            'nama_produk' => 'Stiker Meteran '.uniqid(),
            'kategori_produk' => Product::KATEGORI_METERAN,
            'harga_umum' => 10000,
            'harga_reseller' => 9000,
            'harga_prioritas' => 9000,
            'harga_pegawai' => 8000,
            'harga_modal' => 5000,
            'stok_pcs' => null,
        ]);

        return OrderItem::create(array_merge([
            'order_id' => $this->order()->id,
            'product_id' => $produk->id,
            'finishing_id' => null,
            'lebar_m' => 1,
            'tinggi_m' => 1,
            'qty' => 1,
            'luas_tagihan_m2' => 1,
            'harga_satuan_dipakai' => 10000,
            'subtotal' => 10000,
            'status_produksi' => OrderItem::STATUS_MENUNGGU_DESAIN,
        ], $overrides));
    }

    private function satuanItem(Product $produk, int $qty): OrderItem
    {
        return OrderItem::create([
            'order_id' => $this->order()->id,
            'product_id' => $produk->id,
            'finishing_id' => null,
            'qty' => $qty,
            'harga_satuan_dipakai' => 10000,
            'subtotal' => 10000 * $qty,
            'status_produksi' => OrderItem::STATUS_MENUNGGU_DESAIN,
        ]);
    }

    private function order(float $total = 10000): Order
    {
        $customer = Customer::create([
            'instansi' => 'Instansi Test '.uniqid(),
            'nama_pelanggan' => 'Pelanggan Test',
            'no_wa' => '081234567890',
            'kategori' => Customer::KATEGORI_UMUM,
        ]);

        return Order::create([
            'no_nota' => 'TEST-'.strtoupper(uniqid()),
            'customer_id' => $customer->id,
            'user_id' => User::factory()->create(['role' => User::ROLE_ADMIN])->id,
            'total_tagihan' => $total,
        ]);
    }
}
