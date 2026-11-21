<?php

namespace Tests\Feature;

use App\Models\AdvertisingSpk;
use App\Models\Customer;
use App\Models\Finishing;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Tahap7AuditOrderTest extends TestCase
{
    use RefreshDatabase;

    private int $urutan = 0;

    private ?User $adminPembuat = null;

    public function test_admin_melihat_daftar_nota_per_order_dengan_pencarian_dan_paginasi_15(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        [$meteran, $satuan, $finishing] = $this->masterData();

        $orders = [];
        for ($i = 1; $i <= 16; $i++) {
            $orders[$i] = $this->buatOrderSiapAudit($meteran, $finishing, [
                'no_nota' => sprintf('AUDIT-%03d', $i),
                'instansi' => 'Instansi Audit '.$i,
                'pic' => 'PIC Audit '.$i,
                'created_at' => now()->subMinutes(16 - $i),
            ]);
        }

        $response = $this->actingAs($admin)->get(route('admin.orders.index'));
        $this->assertPaginasi15($response, 16);
        $response->assertSee('AUDIT-016')
            ->assertDontSee('AUDIT-001')
            ->assertSee('Instansi Audit 16')
            ->assertSee('PIC Audit 16');

        foreach (['AUDIT-010', 'Instansi Audit 10', 'PIC Audit 10'] as $kataKunci) {
            $this->actingAs($admin)
                ->get(route('admin.orders.index', ['q' => $kataKunci]))
                ->assertOk()
                ->assertSee('AUDIT-010')
                ->assertDontSee('AUDIT-016');
        }

        // Dua item pada satu nota tetap tampil sebagai satu baris di daftar.
        $this->tambahItem($orders[10], $satuan, null, [
            'qty' => 2,
            'harga_satuan_dipakai' => 25000,
            'subtotal' => 50000,
            'status_produksi' => OrderItem::STATUS_SIAP_DIAMBIL,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.index', ['q' => 'AUDIT-010']))
            ->assertOk()
            ->assertSee('AUDIT-010')
            ->assertSee('2 item pada nota');
    }

    public function test_admin_detail_order_menampilkan_item_instansi_dan_pic(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        [$meteran, $satuan, $finishing] = $this->masterData();

        $order = $this->buatOrder([
            'no_nota' => 'NOTA-DETAIL-001',
            'instansi' => 'Instansi Detail Audit',
            'pic' => 'PIC Detail Audit',
            'total_tagihan' => 175000,
            'jumlah_dp' => 50000,
        ]);

        $this->tambahItem($order, $meteran, $finishing, [
            'lebar_m' => 1.25,
            'tinggi_m' => 2,
            'harga_satuan_dipakai' => 100000,
            'subtotal' => 100000,
            'status_produksi' => OrderItem::STATUS_SIAP_DIAMBIL,
        ]);
        $this->tambahItem($order, $satuan, null, [
            'qty' => 3,
            'harga_satuan_dipakai' => 25000,
            'subtotal' => 75000,
            'status_produksi' => OrderItem::STATUS_SIAP_DIAMBIL,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('NOTA-DETAIL-001')
            ->assertSee('Instansi Detail Audit')
            ->assertSee('PIC Detail Audit')
            ->assertSee($meteran->nama_produk)
            ->assertSee($satuan->nama_produk)
            ->assertSee($finishing->nama_finishing)
            ->assertSee('1,25')
            ->assertSee('100.000')
            ->assertSee('175.000')
            ->assertSee('Rp');
    }

    public function test_riwayat_divisi_hanya_menampilkan_order_yang_sudah_diproses(): void
    {
        [$meteran, $satuan, $finishing] = $this->masterData();

        $lolos = $this->buatOrderSiapAudit($meteran, $finishing, [
            'no_nota' => 'DIV-LOLOS-001',
            'instansi' => 'Instansi Lolos Audit',
            'pic' => 'PIC Lolos Audit',
        ]);

        $belumDesainer = $this->buatOrderSiapAudit($meteran, $finishing, [
            'no_nota' => 'DIV-DESAIN-001',
        ], ['file_master_arsip' => null]);

        $belumOperator = $this->buatOrderSiapAudit($meteran, $finishing, [
            'no_nota' => 'DIV-OPERATOR-001',
        ], ['status_produksi' => OrderItem::STATUS_ANTREAN_CETAK]);

        $belumFinishing = $this->buatOrderSiapAudit($meteran, $finishing, [
            'no_nota' => 'DIV-FINISHING-001',
        ], ['status_produksi' => OrderItem::STATUS_ANTREAN_FINISHING]);

        $belumAdvertising = $this->buatOrderSiapAudit($meteran, $finishing, [
            'no_nota' => 'DIV-ADVERTISING-001',
        ], [], AdvertisingSpk::STATUS_PEMASANGAN);

        $kasus = [
            [User::ROLE_DESAINER, 'desainer.riwayat', $belumDesainer],
            [User::ROLE_OPERATOR_CETAK, 'operator.riwayat', $belumOperator],
            [User::ROLE_FINISHING, 'finishing.riwayat', $belumFinishing],
            [User::ROLE_ADVERTISING, 'advertising.riwayat', $belumAdvertising],
        ];

        foreach ($kasus as [$role, $rute, $ditolak]) {
            $this->actingAs($this->user($role))
                ->get(route($rute))
                ->assertOk()
                ->assertSee('DIV-LOLOS-001')
                ->assertSee('Instansi Lolos Audit')
                ->assertSee('PIC Lolos Audit')
                ->assertSee('Detail')
                ->assertDontSee($ditolak->no_nota);
        }
    }

    public function test_detail_order_divisi_tidak_menampilkan_nominal_tagihan(): void
    {
        [$meteran, $satuan, $finishing] = $this->masterData();

        $order = $this->buatOrderSiapAudit($meteran, $finishing, [
            'no_nota' => 'DIV-TANPA-HARGA-001',
            'instansi' => 'Instansi Tanpa Harga',
            'pic' => 'PIC Tanpa Harga',
            'total_tagihan' => 150000,
        ]);
        $this->tambahItem($order, $satuan, null, [
            'qty' => 1,
            'harga_satuan_dipakai' => 50000,
            'subtotal' => 50000,
            'status_produksi' => OrderItem::STATUS_SIAP_DIAMBIL,
        ]);

        foreach ([User::ROLE_ADMIN, User::ROLE_OWNER] as $role) {
            $rute = $role === User::ROLE_ADMIN ? 'admin.orders.show' : 'owner.orders.show';

            $this->actingAs($this->user($role))
                ->get(route($rute, $order))
                ->assertOk()
                ->assertSee('Rp')
                ->assertSee('150.000');
        }

        $ruteDivisi = [
            User::ROLE_DESAINER => 'desainer.orders.show',
            User::ROLE_OPERATOR_CETAK => 'operator.orders.show',
            User::ROLE_FINISHING => 'finishing.orders.show',
            User::ROLE_ADVERTISING => 'advertising.orders.show',
        ];

        foreach ($ruteDivisi as $role => $rute) {
            $this->actingAs($this->user($role))
                ->get(route($rute, $order))
                ->assertOk()
                ->assertSee('Instansi Tanpa Harga')
                ->assertSee('PIC Tanpa Harga')
                ->assertSee('Rangka hollow galvanis')
                ->assertDontSee('150.000');
        }
    }

    public function test_owner_melihat_rekap_order_per_nota_dengan_paginasi_15(): void
    {
        $owner = $this->user(User::ROLE_OWNER);
        [$meteran, $satuan, $finishing] = $this->masterData();

        $orders = [];
        for ($i = 1; $i <= 16; $i++) {
            $orders[$i] = $this->buatOrderSiapAudit($meteran, $finishing, [
                'no_nota' => sprintf('OWNER-%03d', $i),
                'created_at' => now()->subMinutes(16 - $i),
            ]);
        }

        $response = $this->actingAs($owner)->get(route('owner.rekap-orders'));
        $this->assertPaginasi15($response, 16);
        $response->assertSee('OWNER-016')
            ->assertDontSee('OWNER-001')
            ->assertSee(route('owner.orders.show', $orders[16]));
    }

    public function test_semua_rute_audit_diblokir_untuk_role_selain_pemiliknya(): void
    {
        [$meteran, $satuan, $finishing] = $this->masterData();
        $order = $this->buatOrderSiapAudit($meteran, $finishing);

        $roles = [
            User::ROLE_ADMIN => ['admin.dashboard', $this->user(User::ROLE_ADMIN)],
            User::ROLE_DESAINER => ['desainer.dashboard', $this->user(User::ROLE_DESAINER)],
            User::ROLE_OPERATOR_CETAK => ['operator.dashboard', $this->user(User::ROLE_OPERATOR_CETAK)],
            User::ROLE_FINISHING => ['finishing.dashboard', $this->user(User::ROLE_FINISHING)],
            User::ROLE_ADVERTISING => ['advertising.dashboard', $this->user(User::ROLE_ADVERTISING)],
            User::ROLE_OWNER => ['owner.dashboard', $this->user(User::ROLE_OWNER)],
        ];

        $ruteBaru = [
            ['admin.orders.show', User::ROLE_ADMIN, $order],
            ['desainer.riwayat', User::ROLE_DESAINER, null],
            ['desainer.orders.show', User::ROLE_DESAINER, $order],
            ['operator.riwayat', User::ROLE_OPERATOR_CETAK, null],
            ['operator.orders.show', User::ROLE_OPERATOR_CETAK, $order],
            ['finishing.riwayat', User::ROLE_FINISHING, null],
            ['finishing.orders.show', User::ROLE_FINISHING, $order],
            ['advertising.riwayat', User::ROLE_ADVERTISING, null],
            ['advertising.orders.show', User::ROLE_ADVERTISING, $order],
            ['owner.rekap-orders', User::ROLE_OWNER, null],
            ['owner.orders.show', User::ROLE_OWNER, $order],
        ];

        foreach ($ruteBaru as [$namaRute, $pemilik, $parameter]) {
            foreach ($roles as $role => [$dashboard, $user]) {
                if ($role === $pemilik) {
                    continue;
                }

                $this->actingAs($user)
                    ->get(route($namaRute, $parameter === null ? [] : $parameter))
                    ->assertRedirect(route($dashboard));
            }
        }
    }

    /**
     * @return array{0: Product, 1: Product, 2: Finishing}
     */
    private function masterData(): array
    {
        $finishing = Finishing::create([
            'nama_finishing' => 'Laminasi Audit '.str_pad((string) $this->urutan, 3, '0', STR_PAD_LEFT),
            'ekstra_kiri_cm' => 25,
            'ekstra_kanan_cm' => 0,
            'ekstra_atas_cm' => 0,
            'ekstra_bawah_cm' => 0,
        ]);

        $meteran = Product::create([
            'nama_produk' => 'Stiker Meteran Audit',
            'kategori_produk' => Product::KATEGORI_METERAN,
            'harga_umum' => 100000,
            'harga_reseller' => 95000,
            'harga_prioritas' => 90000,
            'harga_pegawai' => 85000,
            'harga_modal' => 10000,
        ]);

        $satuan = Product::create([
            'nama_produk' => 'Poster Audit',
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

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function buatOrder(array $overrides = []): Order
    {
        $this->urutan++;

        $data = array_merge([
            'no_nota' => 'AUDIT-'.str_pad((string) $this->urutan, 3, '0', STR_PAD_LEFT),
            'instansi' => 'Instansi Audit '.$this->urutan,
            'pic' => 'PIC Audit '.$this->urutan,
            'total_tagihan' => 100000,
            'jumlah_dp' => 0,
            'status_bayar' => Order::STATUS_BAYAR_DP,
            'created_at' => null,
        ], $overrides);

        $customer = Customer::create([
            'instansi' => $data['instansi'],
            'nama_pelanggan' => $data['pic'],
            'no_wa' => '0812'.str_pad((string) $this->urutan, 8, '0', STR_PAD_LEFT),
            'kategori' => Customer::KATEGORI_UMUM,
        ]);

        $this->adminPembuat ??= $this->user(User::ROLE_ADMIN);

        $order = Order::create([
            'no_nota' => $data['no_nota'],
            'customer_id' => $customer->id,
            'user_id' => $this->adminPembuat->id,
            'total_tagihan' => $data['total_tagihan'],
            'jumlah_dp' => $data['jumlah_dp'],
            'status_bayar' => $data['status_bayar'],
            'status_desain' => Order::STATUS_DESAIN_FILE_MENTAH,
        ]);

        if ($data['created_at'] !== null) {
            Order::query()->whereKey($order->id)->update([
                'created_at' => $data['created_at'],
                'updated_at' => $data['created_at'],
            ]);
            $order->refresh();
        }

        return $order;
    }

    /**
     * @param  array<string, mixed>  $orderOverrides
     * @param  array<string, mixed>  $itemOverrides
     */
    private function buatOrderSiapAudit(
        Product $meteran,
        Finishing $finishing,
        array $orderOverrides = [],
        array $itemOverrides = [],
        string $spkStatus = AdvertisingSpk::STATUS_SELESAI,
    ): Order {
        $order = $this->buatOrder($orderOverrides);

        $this->tambahItem($order, $meteran, $finishing, array_merge([
            'file_master_arsip' => 'MEDIA SISTEM/ARSIP MASTER/master-audit.cdr',
            'status_produksi' => OrderItem::STATUS_SIAP_DIAMBIL,
        ], $itemOverrides));

        AdvertisingSpk::create([
            'order_id' => $order->id,
            'jenis_pekerjaan' => 'Papan Nama',
            'ukuran_konstruksi' => '200 x 100 cm',
            'lokasi_pasang' => 'Gedung Audit',
            'catatan_teknis' => 'Rangka hollow galvanis',
            'status_lapangan' => $spkStatus,
        ]);

        return $order;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function tambahItem(Order $order, Product $product, ?Finishing $finishing, array $overrides = []): OrderItem
    {
        return OrderItem::create(array_merge([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'finishing_id' => $finishing?->id,
            'lebar_m' => 1,
            'tinggi_m' => 1,
            'qty' => 1,
            'luas_tagihan_m2' => 1,
            'harga_satuan_dipakai' => 100000,
            'subtotal' => 100000,
            'status_produksi' => OrderItem::STATUS_SIAP_DIAMBIL,
        ], $overrides));
    }

    private function assertPaginasi15($response, int $total): void
    {
        $response->assertOk();

        $paginator = $response->viewData('orders');

        $this->assertSame(15, $paginator->perPage());
        $this->assertSame($total, $paginator->total());
        $this->assertTrue($paginator->hasPages());
    }
}
