<?php

namespace Tests\Feature;

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

class DetailItemDesainWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(MediaStorage::DISK);
    }

    public function test_detail_item_memiliki_kartu_terpisah_tanpa_pesan_lama_dan_paginasi(): void
    {
        $desainer = User::factory()->create(['role' => User::ROLE_DESAINER]);
        [$order] = $this->orderWithItems('KARTU-001', 16);

        $response = $this->actingAs($desainer)->get(route('desainer.orders.items', $order));
        $response->assertOk()
            ->assertSee('Daftar Item Desain')
            ->assertSee('Log Preview Revisi')
            ->assertSee('Finalisasi')
            ->assertDontSee('Preview belum dapat diunggah')
            ->assertDontSee('Terima job terlebih dahulu')
            ->assertSee('ITEM-01')
            ->assertSee('ITEM-15')
            ->assertDontSee('ITEM-16');

        $this->assertSame(15, $response->viewData('items')->perPage());
        $this->assertSame(16, $response->viewData('items')->total());

        // Tombol Detail per item menuju halaman detail item, bukan halaman nota.
        $itemPertama = $order->items()->orderBy('id')->firstOrFail();
        $response->assertSee(route('desainer.orders.items.show', [$order, $itemPertama]), false);

        $this->actingAs($desainer)
            ->get(route('desainer.orders.items', [$order, 'item_page' => 2]))
            ->assertOk()
            ->assertSee('ITEM-16')
            ->assertDontSee('ITEM-01');
    }

    public function test_field_preview_revisi_dan_finalisasi_mengikuti_status(): void
    {
        $desainer = User::factory()->create(['role' => User::ROLE_DESAINER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        [$order, $itemPertama, $itemKedua] = $this->orderWithItems('KONDISI-001', 2);

        $this->actingAs($desainer)
            ->get(route('desainer.orders.items', $order))
            ->assertOk()
            ->assertDontSee('Upload Preview — Revisi')
            ->assertDontSee('Tanggapi Revisi Admin');

        // Preview aktif setelah job diterima.
        $this->actingAs($desainer)
            ->post(route('desainer.orders.terima', $order))
            ->assertRedirect();

        $this->actingAs($desainer)
            ->get(route('desainer.orders.items', $order))
            ->assertOk()
            ->assertSee('Upload Preview — Revisi')
            ->assertSee(route('desainer.orders.preview', $order), false);

        $this->unggahPreview($desainer, $itemPertama, 'Preview awal pertama');
        $this->actingAs($desainer)
            ->get(route('desainer.orders.items', $order))
            ->assertOk()
            ->assertSee('Menunggu review Admin')
            ->assertSee('Finalisasi terkunci')
            ->assertSee('Upload Preview — Revisi'); // item kedua masih proses desain

        // Revisi aktif setelah Admin mengajukan revisi.
        $this->actingAs($admin)
            ->post(route('admin.design-reviews.revisi', $itemPertama), [
                'catatan_revisi' => 'Perbesar logo dan perbaiki kontras',
            ])
            ->assertRedirect();

        $this->actingAs($desainer)
            ->get(route('desainer.orders.items', $order))
            ->assertOk()
            ->assertSee('Tanggapi Revisi Admin')
            ->assertSee('Catatan Admin:')
            ->assertSee('Perbesar logo dan perbaiki kontras')
            ->assertSee('Revisi dari Admin');

        $this->unggahPreview($desainer, $itemPertama, 'Logo sudah diperbesar');
        $this->actingAs($admin)
            ->post(route('admin.design-reviews.setujui', $itemPertama))
            ->assertRedirect();

        $this->actingAs($desainer)
            ->get(route('desainer.orders.items', $order))
            ->assertOk()
            ->assertSee(route('desainer.orders.finalisasi', $order), false)
            ->assertSee('name="items['.$itemPertama->id.'][file_cetak_tiff]"', false)
            ->assertDontSee('name="items['.$itemKedua->id.'][file_cetak_tiff]"', false);
    }

    public function test_log_preview_revisi_menampung_beberapa_revisi_preview_dan_catatan(): void
    {
        $desainer = User::factory()->create(['role' => User::ROLE_DESAINER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        [$order, $item] = $this->orderWithItems('LOG-001', 1);

        $this->actingAs($desainer)->post(route('desainer.orders.terima', $order))->assertRedirect();

        foreach ([
            ['Preview awal', null],
            [null, 'Perbesar tulisan judul'],
            ['Judul diperbesar', null],
            [null, 'Ganti warna background'],
            ['Warna diperbaiki', null],
        ] as [$catatanDesainer, $catatanAdmin]) {
            if ($catatanDesainer !== null) {
                $this->unggahPreview($desainer, $item, $catatanDesainer);
            } else {
                $this->actingAs($admin)
                    ->post(route('admin.design-reviews.revisi', $item), ['catatan_revisi' => $catatanAdmin])
                    ->assertRedirect();
            }
        }

        $this->assertSame([1, 2, 3, 4, 5], $order->designRevisions()->orderBy('revisi_ke')->pluck('revisi_ke')->all());

        $this->actingAs($desainer)
            ->get(route('desainer.orders.items', $order))
            ->assertOk()
            ->assertSee('Log Preview Revisi')
            ->assertSee('5 log')
            ->assertSee('Preview awal')
            ->assertSee('Perbesar tulisan judul')
            ->assertSee('Judul diperbesar')
            ->assertSee('Ganti warna background')
            ->assertSee('Warna diperbaiki')
            ->assertSee(route('desainer.orders.preview.download', [$order, $order->designRevisions()->firstOrFail()]));
    }

    public function test_log_preview_revisi_terpaginasi_dengan_parameter_log_page(): void
    {
        $desainer = User::factory()->create(['role' => User::ROLE_DESAINER]);
        [$order, $item] = $this->orderWithItems('LOG-PAGE-001', 1);

        for ($i = 1; $i <= 16; $i++) {
            $order->designRevisions()->create([
                'revisi_ke' => $i,
                'catatan_revisi' => sprintf('Catatan log %02d', $i),
                'status_revisi' => DesignRevision::STATUS_SELESAI,
            ]);
        }

        $response = $this->actingAs($desainer)->get(route('desainer.orders.items', $order));
        $response->assertOk()
            ->assertSee('Catatan log 01')
            ->assertSee('Catatan log 15');

        $log = $response->viewData('logRevisi');
        $this->assertSame(15, $log->perPage());
        $this->assertSame(16, $log->total());
        $this->assertSame('Catatan log 01', $log->first()->catatan_revisi);
        $this->assertSame('Catatan log 15', $log->last()->catatan_revisi);
        $this->assertStringContainsString('log_page=2', $log->url(2));

        $halamanDua = $this->actingAs($desainer)
            ->get(route('desainer.orders.items', [$order, 'log_page' => 2]));

        $halamanDua->assertOk();
        $logHalamanDua = $halamanDua->viewData('logRevisi');
        $this->assertSame(1, $logHalamanDua->count());
        $this->assertSame('Catatan log 16', $logHalamanDua->first()->catatan_revisi);
        $halamanDua->assertSee('Catatan log 16');
    }

    public function test_finalisasi_batch_menyimpan_tiff_per_item_dan_satu_master_per_nota(): void
    {
        $desainer = User::factory()->create(['role' => User::ROLE_DESAINER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        [$order, $itemPertama, $itemKedua] = $this->orderWithItems('FINAL-001', 2);

        $this->actingAs($desainer)->post(route('desainer.orders.terima', $order))->assertRedirect();

        $this->unggahPreview($desainer, $itemPertama, 'Preview untuk ACC');
        foreach ([$itemPertama, $itemKedua] as $item) {
            $this->actingAs($admin)->post(route('admin.design-reviews.setujui', $item))->assertRedirect();
        }

        // TIFF tersimpan per item; master belum aktif sebelum seluruh TIFF terupload.
        $this->actingAs($desainer)
            ->post(route('desainer.orders.finalisasi', $order), [
                'items' => [
                    $itemPertama->id => ['file_cetak_tiff' => UploadedFile::fake()->create('item-1.tiff', 1, 'image/tiff')],
                    $itemKedua->id => ['file_cetak_tiff' => UploadedFile::fake()->create('item-2.tiff', 1, 'image/tiff')],
                ],
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->assertNull($order->fresh()->file_master_arsip);
        $this->assertNull($order->fresh()->waktu_selesai_desain);
        $this->assertSame(OrderItem::STATUS_MENUNGGU_ACC, $itemPertama->fresh()->status_produksi);

        // Master (satu per nota) menutup proses setelah semua TIFF tersedia.
        $this->actingAs($desainer)
            ->post(route('desainer.orders.master', $order), [
                'file_master_arsip' => UploadedFile::fake()->create('master-nota.cdr', 1),
                'tanda_tangan' => 'Desainer Final',
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $order->refresh();
        $itemPertama->refresh();
        $itemKedua->refresh();

        $this->assertNotNull($order->file_master_arsip);
        $this->assertSame('Desainer Final', $order->tanda_tangan_desainer);
        $this->assertNotNull($order->waktu_selesai_desain);
        $this->assertSame(OrderItem::STATUS_ANTREAN_CETAK, $itemPertama->status_produksi);
        $this->assertSame(OrderItem::STATUS_ANTREAN_CETAK, $itemKedua->status_produksi);
        $this->assertSame($order->file_master_arsip, $itemPertama->file_master_arsip);
        $this->assertSame($order->file_master_arsip, $itemKedua->file_master_arsip);
        $this->assertNotSame($itemPertama->file_cetak_tiff, $itemKedua->file_cetak_tiff);
        $this->assertCount(1, Storage::disk(MediaStorage::DISK)->allFiles(MediaStorage::FOLDER_FILE_MASTER_ARSIP));
        $this->assertCount(2, Storage::disk(MediaStorage::DISK)->allFiles(MediaStorage::FOLDER_FILE_CETAK_TIFF));

        $this->actingAs($desainer)
            ->get(route('desainer.orders.items', $order))
            ->assertOk()
            ->assertSee('Finalisasi selesai.')
            ->assertDontSee('Lanjutkan ke Master Desain di bawah untuk menutup proses desain.');
    }

    public function test_finalisasi_batch_ditolak_sebelum_acc_dan_terisolasi_per_role(): void
    {
        $desainer = User::factory()->create(['role' => User::ROLE_DESAINER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        [$order, $item] = $this->orderWithItems('FINAL-002', 1);

        $data = [
            'items' => [
                $item->id => ['file_cetak_tiff' => UploadedFile::fake()->create('item.tiff', 1, 'image/tiff')],
            ],
            'file_master_arsip' => UploadedFile::fake()->create('master.cdr', 1),
        ];

        $this->actingAs($desainer)
            ->post(route('desainer.orders.finalisasi', $order), $data)
            ->assertSessionHasErrors("items.{$item->id}.file_cetak_tiff");

        $this->actingAs($admin)
            ->post(route('desainer.orders.finalisasi', $order), $data)
            ->assertRedirect(route('admin.dashboard'));

        $this->assertSame(OrderItem::STATUS_MENUNGGU_DESAIN, $item->fresh()->status_produksi);
        $this->assertNull($order->fresh()->file_master_arsip);
    }

    public function test_field_master_desain_hanya_aktif_setelah_semua_tiff_terupload(): void
    {
        $desainer = User::factory()->create(['role' => User::ROLE_DESAINER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        [$order, $itemPertama, $itemKedua] = $this->orderWithItems('MASTER-GATE-001', 2);

        $this->actingAs($desainer)
            ->get(route('desainer.orders.items', $order))
            ->assertOk()
            ->assertDontSee('name="file_master_arsip"', false)
            ->assertSee('Field Master Desain aktif setelah seluruh TIFF per item terupload.');

        $this->actingAs($desainer)->post(route('desainer.orders.terima', $order))->assertRedirect();

        $this->unggahPreview($desainer, $itemPertama, 'Preview untuk ACC');
        foreach ([$itemPertama, $itemKedua] as $item) {
            $this->actingAs($admin)->post(route('admin.design-reviews.setujui', $item))->assertRedirect();
        }

        $this->actingAs($desainer)
            ->get(route('desainer.orders.items', $order))
            ->assertOk()
            ->assertDontSee('name="file_master_arsip"', false)
            ->assertSee('Field Master Desain aktif setelah seluruh TIFF per item terupload.');

        $this->actingAs($desainer)
            ->post(route('desainer.items.finalisasi', $itemPertama), [
                'file_cetak_tiff' => UploadedFile::fake()->create('item-1.tiff', 1, 'image/tiff'),
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->actingAs($desainer)
            ->post(route('desainer.orders.master', $order), [
                'file_master_arsip' => UploadedFile::fake()->create('master-awal.cdr', 1),
            ])
            ->assertStatus(422);

        $this->assertNull($order->fresh()->file_master_arsip);

        $this->actingAs($desainer)
            ->get(route('desainer.orders.items', $order))
            ->assertOk()
            ->assertSee('TIFF tersimpan')
            ->assertDontSee('name="file_master_arsip"', false)
            ->assertSee('Field Master Desain aktif setelah seluruh TIFF per item terupload.');

        $this->actingAs($desainer)
            ->post(route('desainer.items.finalisasi', $itemKedua), [
                'file_cetak_tiff' => UploadedFile::fake()->create('item-2.tiff', 1, 'image/tiff'),
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->actingAs($desainer)
            ->get(route('desainer.orders.items', $order))
            ->assertOk()
            ->assertSee('Seluruh TIFF per item sudah terupload.')
            ->assertSee(route('desainer.orders.master', $order), false)
            ->assertSee('name="file_master_arsip"', false)
            ->assertDontSee('Field Master Desain aktif setelah seluruh TIFF per item terupload.');

        $this->assertNull($order->fresh()->file_master_arsip);
        $this->assertSame(OrderItem::STATUS_MENUNGGU_ACC, $itemPertama->fresh()->status_produksi);
        $this->assertSame(OrderItem::STATUS_MENUNGGU_ACC, $itemKedua->fresh()->status_produksi);
    }

    public function test_rute_master_desain_diblokir_untuk_role_selain_desainer(): void
    {
        [$order] = $this->orderWithItems('MASTER-RBAC-001', 1);

        foreach ([
            User::ROLE_ADMIN,
            User::ROLE_OPERATOR_CETAK,
            User::ROLE_FINISHING,
            User::ROLE_ADVERTISING,
            User::ROLE_OWNER,
        ] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->post(route('desainer.orders.master', $order), [
                    'file_master_arsip' => UploadedFile::fake()->create('master.cdr', 1),
                    'tanda_tangan' => 'Role lain',
                ])
                ->assertRedirect(route($user->dashboardRouteName()));
        }

        $this->assertNull($order->fresh()->file_master_arsip);
    }

    public function test_riwayat_menampilkan_tanda_tangan_dan_waktu_selesai_setelah_master_diunggah(): void
    {
        $desainer = User::factory()->create(['role' => User::ROLE_DESAINER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'name' => 'Admin Riwayat']);
        [$order, $item] = $this->orderWithItems('RIWAYAT-TANDA-001', 1);

        $this->actingAs($desainer)->post(route('desainer.orders.terima', $order))->assertRedirect();
        $this->unggahPreview($desainer, $item, 'Preview final');
        $this->actingAs($admin)->post(route('admin.design-reviews.setujui', $item))->assertRedirect();

        $this->actingAs($desainer)
            ->post(route('desainer.items.finalisasi', $item), [
                'file_cetak_tiff' => UploadedFile::fake()->create('final.tiff', 1, 'image/tiff'),
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->actingAs($desainer)
            ->post(route('desainer.orders.master', $order), [
                'file_master_arsip' => UploadedFile::fake()->create('master-riwayat.cdr', 1),
                'tanda_tangan' => 'Tanda Tangan Riwayat',
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $order->refresh();

        $this->assertSame('Tanda Tangan Riwayat', $order->tanda_tangan_desainer);
        $this->assertSame('Admin Riwayat', $order->tanda_tangan_admin);
        $this->assertNotNull($order->waktu_selesai_desain);

        $this->actingAs($desainer)
            ->get(route('desainer.riwayat'))
            ->assertOk()
            ->assertSee('Tanda Tangan Digital')
            ->assertSee('Waktu Proses')
            ->assertSee($order->no_nota)
            ->assertSee('Tanda Tangan Riwayat')
            ->assertSee('Admin: Admin Riwayat')
            ->assertSee($order->waktu_selesai_desain->format('d/m/Y H:i'))
            ->assertSee('1 TIFF terupload');
    }

    /**
     * @return array{0: Order, 1: OrderItem, 2?: OrderItem}
     */
    private function orderWithItems(string $noNota, int $jumlah): array
    {
        $finishing = Finishing::query()->create([
            'nama_finishing' => 'Laminasi '.$noNota,
            'ekstra_kiri_cm' => 0,
            'ekstra_kanan_cm' => 0,
            'ekstra_atas_cm' => 0,
            'ekstra_bawah_cm' => 0,
        ]);

        $product = Product::query()->create([
            'nama_produk' => 'Stiker Meteran '.$noNota,
            'kategori_produk' => Product::KATEGORI_METERAN,
            'harga_umum' => 100000,
            'harga_reseller' => 95000,
            'harga_prioritas' => 90000,
            'harga_pegawai' => 85000,
            'harga_modal' => 10000,
        ]);

        /** @var Order $order */
        $order = Order::query()->create([
            'no_nota' => $noNota,
            'customer_id' => Customer::query()->create([
                'instansi' => 'Instansi '.$noNota,
                'nama_pelanggan' => 'PIC '.$noNota,
                'no_wa' => '081234567890',
                'kategori' => Customer::KATEGORI_UMUM,
            ])->id,
            'user_id' => User::factory()->create(['role' => User::ROLE_ADMIN])->id,
            'total_tagihan' => 100000,
            'status_bayar' => Order::STATUS_BAYAR_DP,
            'status_desain' => Order::STATUS_DESAIN_FILE_MENTAH,
            'catatan_desain' => 'Perhatikan warna brand.',
            'deadline' => now()->addDay(),
        ]);

        $items = [];

        for ($i = 1; $i <= $jumlah; $i++) {
            /** @var OrderItem $item */
            $item = $order->items()->create([
                'product_id' => $product->id,
                'finishing_id' => $finishing->id,
                'lebar_m' => 1,
                'tinggi_m' => 2,
                'qty' => 1,
                'luas_tagihan_m2' => 2,
                'harga_satuan_dipakai' => 100000,
                'subtotal' => 200000,
                'status_produksi' => OrderItem::STATUS_MENUNGGU_DESAIN,
            ]);

            $item->update(['nama_item' => sprintf('ITEM-%02d', $i)]);
            $items[] = $item->fresh();
        }

        return [$order, ...$items];
    }

    private function unggahPreview(User $desainer, OrderItem $item, string $catatan): void
    {
        $this->actingAs($desainer)
            ->post(route('desainer.orders.preview', $item->order), [
                'catatan_revisi' => $catatan,
                'file_preview' => UploadedFile::fake()->create('preview-'.uniqid().'.png', 10, 'image/png'),
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();
    }
}
