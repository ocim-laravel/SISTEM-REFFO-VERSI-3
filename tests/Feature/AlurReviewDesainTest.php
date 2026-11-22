<?php

namespace Tests\Feature;

use App\Models\AdvertisingSpk;
use App\Models\Customer;
use App\Models\DesignRevision;
use App\Models\Finishing;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\MediaStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AlurReviewDesainTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(MediaStorage::DISK);
    }

    public function test_review_admin_menampilkan_preview_dan_catatan_revisi_wajib(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $desainer = User::factory()->create(['role' => User::ROLE_DESAINER]);
        [$order, $item] = $this->orderAntrean('REVIEW-001');

        $this->actingAs($desainer)
            ->post(route('desainer.orders.terima', $order))
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->actingAs($desainer)
            ->post(route('desainer.orders.preview', $order), [
                'catatan_revisi' => 'Preview pertama',
                'file_preview' => UploadedFile::fake()->create('preview-pertama.png', 10, 'image/png'),
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->actingAs($admin)
            ->get(route('admin.design-reviews.index'))
            ->assertOk()
            ->assertSee('REVIEW-001')
            ->assertSee('PIC Review')
            ->assertSee('Instansi Review')
            ->assertSee('Menunggu ACC')
            ->assertSee('Setujui')
            ->assertSee('Ajukan Revisi');

        $this->actingAs($admin)
            ->get(route('admin.design-reviews.index', ['q' => 'PIC Review']))
            ->assertOk()
            ->assertSee('REVIEW-001');

        $this->actingAs($admin)
            ->post(route('admin.design-reviews.revisi', $item), [])
            ->assertSessionHasErrors('catatan_revisi');

        $this->actingAs($admin)
            ->post(route('admin.design-reviews.revisi', $item), [
                'catatan_revisi' => 'Perbesar logo dan sesuaikan warna background',
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $item->refresh();
        $this->assertSame(OrderItem::STATUS_REVISI, $item->status_produksi);
        $this->assertNull($item->acc_admin_id);

        /** @var DesignRevision $revisi */
        $revisi = $item->order->designRevisions()->whereNotNull('admin_id')->firstOrFail();
        $this->assertSame($admin->id, (int) $revisi->admin_id);
        $this->assertSame('menunggu', $revisi->status_revisi);
        $this->assertTrue($revisi->isPending());
        $this->assertSame('Perbesar logo dan sesuaikan warna background', $revisi->catatan_revisi);

        $this->actingAs($admin)
            ->get(route('admin.design-reviews.index'))
            ->assertOk()
            ->assertDontSee('REVIEW-001');

        $this->actingAs($desainer)
            ->get(route('desainer.orders.items', $order))
            ->assertOk()
            ->assertSee('Revisi dari Admin')
            ->assertSee('Perbesar logo dan sesuaikan warna background');

        // Finalisasi tetap ditolak sampai revisi ditanggapi dan Admin menyetujui.
        $this->actingAs($desainer)
            ->post(route('desainer.items.finalisasi', $item), [
                'file_cetak_tiff' => UploadedFile::fake()->create('siap.tiff', 1, 'image/tiff'),
                'file_master_arsip' => UploadedFile::fake()->create('master.cdr', 1),
            ])
            ->assertSessionHasErrors('file_cetak_tiff');

        $this->actingAs($desainer)
            ->post(route('desainer.orders.preview', $order), [
                'catatan_revisi' => 'Logo diperbesar',
                'file_preview' => UploadedFile::fake()->create('preview-revisi.jpg', 10, 'image/jpeg'),
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->assertFalse((bool) $revisi->fresh()->isPending());
        $this->assertSame(OrderItem::STATUS_MENUNGGU_ACC, $item->fresh()->status_produksi);

        $this->actingAs($desainer)
            ->get(route('desainer.orders.items', $order))
            ->assertOk()
            ->assertSee('Menunggu review Admin')
            ->assertSee('Finalisasi terkunci');

        $this->actingAs($admin)
            ->post(route('admin.design-reviews.setujui', $item))
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->assertNotNull($item->fresh()->acc_admin_id);
        $this->assertNotNull($item->fresh()->waktu_acc);
    }

    public function test_preview_hanya_jpg_png_dan_file_tiff_bisa_dipratinjau(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $desainer = User::factory()->create(['role' => User::ROLE_DESAINER]);
        [$order, $item] = $this->orderAntrean('PREVIEW-002');

        $this->actingAs($desainer)
            ->post(route('desainer.orders.terima', $order))
            ->assertRedirect();

        $this->actingAs($desainer)
            ->post(route('desainer.orders.preview', $order), [
                'catatan_revisi' => 'Salah format',
                'file_preview' => UploadedFile::fake()->create('bukan-gambar.tiff', 1, 'image/tiff'),
            ])
            ->assertSessionHasErrors('file_preview');
        $this->assertSame(0, $order->designRevisions()->count());

        $this->actingAs($desainer)
            ->post(route('desainer.orders.preview', $order), [
                'catatan_revisi' => 'Preview JPG',
                'file_preview' => UploadedFile::fake()->create('preview-final.jpg', 10, 'image/jpeg'),
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();
        $this->assertSame(1, $order->designRevisions()->count());

        $this->actingAs($admin)
            ->post(route('admin.design-reviews.setujui', $item))
            ->assertRedirect();

        $this->actingAs($desainer)
            ->post(route('desainer.items.finalisasi', $item), [
                'file_cetak_tiff' => UploadedFile::fake()->create('siap-cetak.tiff', 1, 'image/tiff'),
                'file_master_arsip' => UploadedFile::fake()->create('master-desain.psd', 1),
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $item->refresh();
        Storage::disk(MediaStorage::DISK)->assertExists($item->file_cetak_tiff);

        $this->actingAs($desainer)
            ->get(route('desainer.items.tiff', $item))
            ->assertOk()
            ->assertHeader('Content-Disposition');

        // Item yang sudah final keluar dari antrean, tapi master tetap masuk arsip desainer.
        $this->actingAs($desainer)
            ->get(route('desainer.dashboard'))
            ->assertOk()
            ->assertSee('Arsip Master Desain')
            ->assertSee(basename((string) $order->fresh()->file_master_arsip));

        // Pratinjau TIFF tersedia pada detail audit order.
        $this->actingAs($desainer)
            ->get(route('desainer.orders.show', $order))
            ->assertOk()
            ->assertSee('Pratinjau TIFF')
            ->assertSee(route('desainer.items.tiff', $item))
            ->assertSee('Lihat preview ACC');
    }

    public function test_riwayat_desain_terpagINASI_dan_detail_memuat_audit_tanda_tangan(): void
    {
        $designer = User::factory()->create(['role' => User::ROLE_DESAINER, 'name' => 'Desainer Audit']);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'name' => 'Admin ACC']);
        [$meteran, , $finishing] = $this->masterData();

        $orders = [];
        for ($i = 1; $i <= 16; $i++) {
            $orders[$i] = $this->orderSelesaiDesain(
                sprintf('RIWAYAT-%03d', $i),
                $meteran,
                $finishing,
                $designer,
                $admin,
            );

            $waktu = now()->subMinutes(16 - $i);
            Order::query()->whereKey($orders[$i]->id)->update([
                'created_at' => $waktu,
                'updated_at' => $waktu,
            ]);
            $orders[$i]->refresh();
        }

        $response = $this->actingAs($designer)->get(route('desainer.riwayat'));
        $response->assertOk();
        $paginator = $response->viewData('orders');
        $this->assertSame(15, $paginator->perPage());
        $this->assertSame(16, $paginator->total());
        $response->assertSee('RIWAYAT-016')
            ->assertDontSee('RIWAYAT-001')
            ->assertSee('Desainer Audit')
            ->assertSee('Admin: Admin ACC')
            ->assertSee('2 TIFF terupload');

        $this->actingAs($designer)
            ->get(route('desainer.riwayat', ['q' => 'RIWAYAT-010']))
            ->assertOk()
            ->assertSee('RIWAYAT-010')
            ->assertDontSee('RIWAYAT-016');

        $this->actingAs($designer)
            ->get(route('desainer.orders.show', $orders[16]))
            ->assertOk()
            ->assertSee('Audit Desain')
            ->assertSee('Tanda Tangan Desainer')
            ->assertSee('Tanda Tangan Admin ACC')
            ->assertSee('Desainer Audit')
            ->assertSee('Admin ACC')
            ->assertSee('Waktu Proses')
            ->assertSee('Berhasil upload')
            ->assertSee('Lihat preview ACC')
            ->assertDontSee('SPK Advertising')
            ->assertDontSee('Rangka hollow galvanis');

        // Data SPK tidak dihapus, hanya disembunyikan dari UI desainer.
        $this->assertDatabaseHas('advertising_spks', ['order_id' => $orders[16]->id]);
        $this->actingAs($admin)
            ->get(route('admin.orders.show', $orders[16]))
            ->assertOk()
            ->assertSee('SPK Advertising')
            ->assertSee('Rangka hollow galvanis');
    }

    public function test_rute_review_dan_alur_desain_terisolasi_per_role(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $desainer = User::factory()->create(['role' => User::ROLE_DESAINER]);
        $operator = User::factory()->create(['role' => User::ROLE_OPERATOR_CETAK]);
        [$order, $item] = $this->orderAntrean('RBAC-004');

        $this->actingAs($desainer)
            ->get(route('admin.design-reviews.index'))
            ->assertRedirect(route('desainer.dashboard'));

        $this->actingAs($operator)
            ->post(route('admin.design-reviews.setujui', $item))
            ->assertRedirect(route('operator.dashboard'));

        $this->actingAs($admin)
            ->post(route('desainer.orders.preview', $order), [
                'catatan_revisi' => 'Bukan desainer',
                'file_preview' => UploadedFile::fake()->create('preview.png', 10, 'image/png'),
            ])
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($admin)
            ->get(route('desainer.riwayat'))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertSame(OrderItem::STATUS_MENUNGGU_DESAIN, $item->fresh()->status_produksi);
        $this->assertNull($order->fresh()->waktu_terima_desain);
    }

    /**
     * @return array{0: Order, 1: OrderItem}
     */
    private function orderAntrean(string $noNota): array
    {
        [$meteran, , $finishing] = $this->masterData();

        $customer = Customer::query()->create([
            'instansi' => 'Instansi Review',
            'nama_pelanggan' => 'PIC Review',
            'no_wa' => '081234567890',
            'kategori' => Customer::KATEGORI_UMUM,
        ]);

        /** @var Order $order */
        $order = Order::query()->create([
            'no_nota' => $noNota,
            'customer_id' => $customer->id,
            'user_id' => User::factory()->create(['role' => User::ROLE_ADMIN])->id,
            'total_tagihan' => 100000,
            'status_bayar' => Order::STATUS_BAYAR_DP,
            'status_desain' => Order::STATUS_DESAIN_FILE_MENTAH,
            'catatan_desain' => 'Perhatikan warna brand.',
            'deadline' => now()->addDay(),
        ]);

        /** @var OrderItem $item */
        $item = OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $meteran->id,
            'finishing_id' => $finishing->id,
            'qty' => 1,
            'lebar_m' => 1,
            'tinggi_m' => 1,
            'luas_tagihan_m2' => 1,
            'harga_satuan_dipakai' => 100000,
            'subtotal' => 100000,
            'status_produksi' => OrderItem::STATUS_MENUNGGU_DESAIN,
        ]);

        return [$order, $item];
    }

    private function orderSelesaiDesain(
        string $noNota,
        Product $product,
        Finishing $finishing,
        User $designer,
        User $admin,
    ): Order {
        $customer = Customer::query()->create([
            'instansi' => 'Instansi Riwayat',
            'nama_pelanggan' => 'PIC Riwayat',
            'no_wa' => '081234567890',
            'kategori' => Customer::KATEGORI_UMUM,
        ]);

        $masterPath = MediaStorage::FOLDER_FILE_MASTER_ARSIP.'/master-'.$noNota.'.cdr';
        $tiffPathSatu = MediaStorage::FOLDER_FILE_CETAK_TIFF.'/tiff-1-'.$noNota.'.tiff';
        $tiffPathDua = MediaStorage::FOLDER_FILE_CETAK_TIFF.'/tiff-2-'.$noNota.'.tiff';
        $previewPath = MediaStorage::FOLDER_PREVIEW_ACC.'/preview-'.$noNota.'.png';

        foreach ([$masterPath, $tiffPathSatu, $tiffPathDua, $previewPath] as $path) {
            Storage::disk(MediaStorage::DISK)->put($path, 'dummy');
        }

        /** @var Order $order */
        $order = Order::query()->create([
            'no_nota' => $noNota,
            'customer_id' => $customer->id,
            'user_id' => $admin->id,
            'total_tagihan' => 200000,
            'status_bayar' => Order::STATUS_BAYAR_DP,
            'status_desain' => Order::STATUS_DESAIN_SIAP_CETAK_TIFF,
            'file_master_arsip' => $masterPath,
            'designer_id' => $designer->id,
            'waktu_terima_desain' => now()->subMinutes(90),
            'waktu_selesai_desain' => now()->subMinutes(30),
            'tanda_tangan_desainer' => $designer->name,
            'tanda_tangan_admin' => $admin->name,
            'deadline' => now()->addDay(),
        ]);

        foreach ([$tiffPathSatu, $tiffPathDua] as $index => $tiffPath) {
            /** @var OrderItem $item */
            $item = OrderItem::query()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'finishing_id' => $finishing->id,
                'qty' => 1,
                'lebar_m' => 1,
                'tinggi_m' => 1,
                'luas_tagihan_m2' => 1,
                'harga_satuan_dipakai' => 100000,
                'subtotal' => 100000,
                'status_produksi' => OrderItem::STATUS_ANTREAN_CETAK,
                'file_cetak_tiff' => $tiffPath,
                'file_master_arsip' => $masterPath,
                'file_preview_acc' => $previewPath,
                'acc_admin_id' => $admin->id,
                'waktu_acc' => now()->subMinutes(45),
            ]);

            $order->designRevisions()->create([
                'order_item_id' => null,
                'revisi_ke' => $index + 1,
                'catatan_revisi' => 'Preview akhir',
                'file_preview' => $previewPath,
                'status_revisi' => DesignRevision::STATUS_SELESAI,
            ]);
        }

        AdvertisingSpk::query()->create([
            'order_id' => $order->id,
            'jenis_pekerjaan' => 'Papan Nama',
            'ukuran_konstruksi' => '200 x 100 cm',
            'lokasi_pasang' => 'Gedung Audit',
            'catatan_teknis' => 'Rangka hollow galvanis',
            'status_lapangan' => AdvertisingSpk::STATUS_SELESAI,
        ]);

        return $order;
    }

    /**
     * @return array{0: Product, 1: Product, 2: Finishing}
     */
    private function masterData(): array
    {
        $finishing = Finishing::query()->create([
            'nama_finishing' => 'Laminasi Review',
            'ekstra_kiri_cm' => 25,
            'ekstra_kanan_cm' => 0,
            'ekstra_atas_cm' => 0,
            'ekstra_bawah_cm' => 0,
        ]);

        $meteran = Product::query()->create([
            'nama_produk' => 'Stiker Meteran Review',
            'kategori_produk' => Product::KATEGORI_METERAN,
            'harga_umum' => 100000,
            'harga_reseller' => 95000,
            'harga_prioritas' => 90000,
            'harga_pegawai' => 85000,
            'harga_modal' => 10000,
        ]);

        $satuan = Product::query()->create([
            'nama_produk' => 'Poster Review',
            'kategori_produk' => Product::KATEGORI_SATUAN,
            'harga_umum' => 25000,
            'harga_reseller' => 23000,
            'harga_prioritas' => 22000,
            'harga_pegawai' => 20000,
            'harga_modal' => 5000,
            'stok_pcs' => 100,
        ]);

        return [$meteran, $satuan, $finishing];
    }
}
