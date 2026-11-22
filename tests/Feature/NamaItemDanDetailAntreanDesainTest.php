<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Finishing;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\MediaStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NamaItemDanDetailAntreanDesainTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(MediaStorage::DISK);
    }

    public function test_nama_item_tersedia_sebelum_produk_dan_tersimpan_per_item(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        [$meteran, $satuan, $finishing] = $this->masterData();

        $this->actingAs($admin)
            ->get(route('admin.orders.create'))
            ->assertOk()
            ->assertSee('Nama Item')
            ->assertSee('items[${index}][nama_item]', false);

        $this->actingAs($admin)
            ->post(route('admin.orders.store'), [
                'instansi' => 'Instansi Nama Item',
                'nama_pelanggan' => 'PIC Nama Item',
                'no_wa' => '081234567890',
                'kategori' => Customer::KATEGORI_UMUM,
                'items' => [
                    [
                        'nama_item' => 'Spanduk Promo Minggu Belanja',
                        'product_id' => $meteran->id,
                        'qty' => 1,
                        'lebar_m' => 1,
                        'tinggi_m' => 2,
                        'finishing_id' => $finishing->id,
                    ],
                    [
                        'nama_item' => '   ',
                        'product_id' => $satuan->id,
                        'qty' => 2,
                    ],
                ],
                'status_desain' => Order::STATUS_DESAIN_FILE_MENTAH,
                'catatan_desain' => 'Perhatikan warna brand.',
                'status_bayar' => Order::STATUS_BAYAR_BELUM_LUNAS,
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $order = Order::query()->firstOrFail();

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'nama_item' => 'Spanduk Promo Minggu Belanja',
            'product_id' => $meteran->id,
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'nama_item' => null,
            'product_id' => $satuan->id,
            'finishing_id' => null,
        ]);

        // Nilai lama tetap tampil saat validasi gagal.
        $this->actingAs($admin)
            ->post(route('admin.orders.store'), [
                'nama_pelanggan' => 'PIC Validasi',
                'items' => [
                    [
                        'nama_item' => 'Nama Item Lama',
                        'qty' => 1,
                    ],
                ],
            ])
            ->assertSessionHasErrors('items.0.product_id');

        $this->actingAs($admin)
            ->get(route('admin.orders.create'))
            ->assertOk()
            ->assertSee('Nama Item Lama');
    }

    public function test_antrean_desain_berupa_kartu_per_nota_dan_paginasi_15_nota(): void
    {
        $desainer = User::factory()->create(['role' => User::ROLE_DESAINER]);
        [$meteran, , $finishing] = $this->masterData();

        for ($i = 1; $i <= 17; $i++) {
            $order = $this->orderAntrean(sprintf('ANTREAN-%03d', $i), $meteran, $finishing, $desainer);

            if ($i === 17) {
                // Nota yang sudah selesai desain tidak boleh muncul di antrean.
                $order->update(['file_master_arsip' => MediaStorage::FOLDER_FILE_MASTER_ARSIP.'/master-lama.cdr']);
            }

            $waktu = now()->subMinutes(18 - $i);
            Order::query()->whereKey($order->id)->update(['created_at' => $waktu, 'updated_at' => $waktu]);
        }

        $response = $this->actingAs($desainer)->get(route('desainer.dashboard'));
        $response->assertOk();

        $paginator = $response->viewData('queue');
        $this->assertSame(15, $paginator->perPage());
        $this->assertSame(16, $paginator->total());

        $this->assertFalse($paginator->contains(fn (Order $order): bool => $order->no_nota === 'ANTREAN-001'));
        $this->assertFalse($paginator->contains(fn (Order $order): bool => $order->no_nota === 'ANTREAN-017'));

        $response->assertOk()
            ->assertSee('Antrean Desain')
            ->assertSee('ID Nota')
            ->assertSee('Jumlah Item')
            ->assertSee('Progress Desain')
            ->assertSee('Terima Tugas')
            ->assertSee('Menunggu Diterima')
            ->assertSee('ANTREAN-016')
            ->assertSee('PIC Antrean')
            ->assertSee('Instansi Antrean')
            ->assertSee('1 item')
            ->assertSee(route('desainer.orders.terima', $paginator->first()), false);

        $halamanDua = $this->actingAs($desainer)
            ->get(route('desainer.dashboard', ['queue_page' => 2]));

        $halamanDua->assertOk()
            ->assertSee('ANTREAN-001')
            ->assertDontSee('ANTREAN-016');

        $this->assertFalse($halamanDua->viewData('queue')->contains(fn (Order $order): bool => $order->no_nota === 'ANTREAN-016'));
    }

    public function test_terima_tugas_dari_kartu_mengubah_status_dan_tombol_menjadi_detail(): void
    {
        $desainer = User::factory()->create(['role' => User::ROLE_DESAINER]);
        [$meteran, , $finishing] = $this->masterData();
        $order = $this->orderAntrean('TERIMA-001', $meteran, $finishing, $desainer);

        $this->actingAs($desainer)
            ->get(route('desainer.dashboard'))
            ->assertOk()
            ->assertSee('Terima Tugas')
            ->assertSee('Menunggu Diterima')
            ->assertSee(route('desainer.orders.terima', $order), false);

        $this->actingAs($desainer)
            ->post(route('desainer.orders.terima', $order))
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $order->refresh();
        $this->assertSame($desainer->id, (int) $order->designer_id);
        $this->assertNotNull($order->waktu_terima_desain);
        $this->assertSame(
            OrderItem::STATUS_PROSES_DESAIN,
            $order->items()->firstOrFail()->status_produksi,
        );

        $this->actingAs($desainer)
            ->get(route('desainer.dashboard'))
            ->assertOk()
            ->assertSee('Diterima Desainer')
            ->assertSee(route('desainer.orders.items', $order), false)
            ->assertDontSee('Terima Tugas');
    }

    public function test_detail_item_menampilkan_rincian_preview_tiff_dan_paginasi(): void
    {
        $desainer = User::factory()->create(['role' => User::ROLE_DESAINER]);
        [$meteran, , $finishing] = $this->masterData();

        $order = $this->orderAntrean('DETAIL-001', $meteran, $finishing, $desainer);
        $tiffPath = MediaStorage::FOLDER_FILE_CETAK_TIFF.'/detail-siap-cetak.tiff';
        Storage::disk(MediaStorage::DISK)->put($tiffPath, 'dummy-tiff');

        for ($i = 1; $i <= 16; $i++) {
            /** @var OrderItem $item */
            $item = $i === 1 ? $order->items()->firstOrFail() : $order->items()->create([
                'product_id' => $meteran->id,
                'finishing_id' => $finishing->id,
                'lebar_m' => 1,
                'tinggi_m' => 2,
                'qty' => $i,
                'luas_tagihan_m2' => 2,
                'harga_satuan_dipakai' => 100000,
                'subtotal' => 200000,
                'status_produksi' => OrderItem::STATUS_MENUNGGU_DESAIN,
            ]);

            $item->update(['nama_item' => sprintf('ITEM-%02d', $i)]);
            if ($i === 1) {
                $item->update(['file_cetak_tiff' => $tiffPath]);
            }
        }

        $response = $this->actingAs($desainer)->get(route('desainer.orders.items', $order));
        $response->assertOk();

        $paginator = $response->viewData('items');
        $this->assertSame(15, $paginator->perPage());
        $this->assertSame(16, $paginator->total());

        $response->assertSee('ITEM-01')
            ->assertSee('ITEM-15')
            ->assertDontSee('ITEM-16')
            ->assertSee('PIC Antrean')
            ->assertSee('Instansi Antrean')
            ->assertSee('Stiker Meteran Detail')
            ->assertSee('Laminasi Detail')
            ->assertSee('1,00 x 2,00 m')
            ->assertSee('Catatan: Perhatikan warna brand.')
            ->assertSee('Pratinjau TIFF')
            ->assertSee(route('desainer.items.tiff', $order->items()->firstOrFail()));

        $this->actingAs($desainer)
            ->get(route('desainer.orders.items', [$order, 'item_page' => 2]))
            ->assertOk()
            ->assertSee('ITEM-16')
            ->assertDontSee('ITEM-01');
    }

    public function test_halaman_detail_item_terisolasi_per_role(): void
    {
        $desainer = User::factory()->create(['role' => User::ROLE_DESAINER]);
        [$meteran, , $finishing] = $this->masterData();
        $order = $this->orderAntrean('RBAC-ITEM-001', $meteran, $finishing, $desainer);

        $this->actingAs($desainer)
            ->get(route('desainer.orders.items', $order))
            ->assertOk();

        foreach ([User::ROLE_ADMIN, User::ROLE_OPERATOR_CETAK, User::ROLE_FINISHING, User::ROLE_ADVERTISING, User::ROLE_OWNER] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get(route('desainer.orders.items', $order))
                ->assertRedirect(route($user->dashboardRouteName()));
        }

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get(route('desainer.dashboard'))
            ->assertRedirect(route('admin.dashboard'));
    }

    private function orderAntrean(string $noNota, Product $meteran, Finishing $finishing, User $admin): Order
    {
        $customer = Customer::query()->create([
            'instansi' => 'Instansi Antrean',
            'nama_pelanggan' => 'PIC Antrean',
            'no_wa' => '081234567890',
            'kategori' => Customer::KATEGORI_UMUM,
        ]);

        /** @var Order $order */
        $order = Order::query()->create([
            'no_nota' => $noNota,
            'customer_id' => $customer->id,
            'user_id' => $admin->id,
            'total_tagihan' => 100000,
            'status_bayar' => Order::STATUS_BAYAR_DP,
            'status_desain' => Order::STATUS_DESAIN_FILE_MENTAH,
            'catatan_desain' => 'Perhatikan warna brand.',
            'deadline' => now()->addDay(),
        ]);

        $order->items()->create([
            'nama_item' => 'Spanduk Utama',
            'product_id' => $meteran->id,
            'finishing_id' => $finishing->id,
            'lebar_m' => 1,
            'tinggi_m' => 2,
            'qty' => 1,
            'luas_tagihan_m2' => 2,
            'harga_satuan_dipakai' => 100000,
            'subtotal' => 200000,
            'status_produksi' => OrderItem::STATUS_MENUNGGU_DESAIN,
        ]);

        return $order;
    }

    /**
     * @return array{0: Product, 1: Product, 2: Finishing}
     */
    private function masterData(): array
    {
        $finishing = Finishing::query()->create([
            'nama_finishing' => 'Laminasi Detail',
            'ekstra_kiri_cm' => 0,
            'ekstra_kanan_cm' => 0,
            'ekstra_atas_cm' => 0,
            'ekstra_bawah_cm' => 0,
        ]);

        $meteran = Product::query()->create([
            'nama_produk' => 'Stiker Meteran Detail',
            'kategori_produk' => Product::KATEGORI_METERAN,
            'harga_umum' => 100000,
            'harga_reseller' => 95000,
            'harga_prioritas' => 90000,
            'harga_pegawai' => 85000,
            'harga_modal' => 10000,
        ]);

        $satuan = Product::query()->create([
            'nama_produk' => 'Poster Detail',
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
