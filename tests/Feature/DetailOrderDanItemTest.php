<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Finishing;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DetailOrderDanItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_order_menampilkan_tabel_item_dengan_tombol_detail_dan_paginasi(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $order = $this->orderWithItems('DETAIL-ORDER-001', 16);

        $response = $this->actingAs($admin)->get(route('admin.orders.show', $order));
        $response->assertOk()
            ->assertSee('No')
            ->assertSee('Nama Item')
            ->assertSee('Produk')
            ->assertSee('Finishing')
            ->assertSee('Status')
            ->assertSee('Aksi')
            ->assertSee('Detail')
            ->assertSee('ITEM-01')
            ->assertSee('ITEM-15')
            ->assertDontSee('ITEM-16');

        $paginator = $response->viewData('items');
        $this->assertSame(15, $paginator->perPage());
        $this->assertSame(16, $paginator->total());

        $firstItem = $order->items()->orderBy('id')->firstOrFail();
        $response->assertSee(route('admin.orders.items.show', [$order, $firstItem]), false);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', [$order, 'item_page' => 2]))
            ->assertOk()
            ->assertSee('ITEM-16')
            ->assertDontSee('ITEM-01');
    }

    public function test_detail_item_menampilkan_rincian_dan_navigasi_item(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $order = $this->orderWithItems('DETAIL-ITEM-001', 3);

        $items = $order->items()->orderBy('id')->get();
        $kedua = $items->get(1);

        $this->actingAs($admin)
            ->get(route('admin.orders.items.show', [$order, $kedua]))
            ->assertOk()
            ->assertSee('Detail Item')
            ->assertSee('ITEM-02')
            ->assertSee('Stiker Meteran Detail')
            ->assertSee('Laminasi Detail')
            ->assertSee('Item 2 dari 3')
            ->assertSee(route('admin.orders.items.show', [$order, $items->first()]), false)
            ->assertSee(route('admin.orders.items.show', [$order, $items->last()]), false)
            ->assertSee('1,00 &times; 2,00 m', false);
    }

    public function test_detail_item_ditolak_jika_item_bukan_milik_nota(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $orderPertama = $this->orderWithItems('DETAIL-ITEM-002', 1);
        $orderKedua = $this->orderWithItems('DETAIL-ITEM-003', 1);

        $this->actingAs($admin)
            ->get(route('admin.orders.items.show', [$orderPertama, $orderKedua->items()->firstOrFail()]))
            ->assertNotFound();
    }

    public function test_semua_rute_detail_item_terisolasi_per_role(): void
    {
        $order = $this->orderWithItems('DETAIL-ITEM-RBAC-001', 1);
        $item = $order->items()->firstOrFail();

        $rute = [
            User::ROLE_ADMIN => 'admin.orders.items.show',
            User::ROLE_DESAINER => 'desainer.orders.items.show',
            User::ROLE_OPERATOR_CETAK => 'operator.orders.items.show',
            User::ROLE_FINISHING => 'finishing.orders.items.show',
            User::ROLE_ADVERTISING => 'advertising.orders.items.show',
            User::ROLE_OWNER => 'owner.orders.items.show',
        ];

        foreach ($rute as $role => $namaRute) {
            $pemilik = User::factory()->create(['role' => $role]);
            $this->actingAs($pemilik)
                ->get(route($namaRute, [$order, $item]))
                ->assertOk()
                ->assertSee('Detail Item');
        }

        foreach ($rute as $role => $namaRute) {
            foreach ($rute as $lain => $dashboard) {
                if ($lain === $role) {
                    continue;
                }

                $user = User::factory()->create(['role' => $lain]);
                $this->actingAs($user)
                    ->get(route($namaRute, [$order, $item]))
                    ->assertRedirect(route($user->dashboardRouteName()));
            }
        }
    }

    private function orderWithItems(string $noNota, int $jumlah): Order
    {
        $finishing = Finishing::query()->create([
            'nama_finishing' => 'Laminasi Detail',
            'ekstra_kiri_cm' => 0,
            'ekstra_kanan_cm' => 0,
            'ekstra_atas_cm' => 0,
            'ekstra_bawah_cm' => 0,
        ]);

        $product = Product::query()->create([
            'nama_produk' => 'Stiker Meteran Detail',
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
                'instansi' => 'Instansi Detail',
                'nama_pelanggan' => 'PIC Detail',
                'no_wa' => '081234567890',
                'kategori' => Customer::KATEGORI_UMUM,
            ])->id,
            'user_id' => User::factory()->create(['role' => User::ROLE_ADMIN])->id,
            'total_tagihan' => 100000,
            'status_bayar' => Order::STATUS_BAYAR_DP,
            'status_desain' => Order::STATUS_DESAIN_FILE_MENTAH,
        ]);

        for ($i = 1; $i <= $jumlah; $i++) {
            /** @var OrderItem $item */
            $item = $order->items()->create([
                'nama_item' => sprintf('ITEM-%02d', $i),
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
        }

        return $order;
    }
}
