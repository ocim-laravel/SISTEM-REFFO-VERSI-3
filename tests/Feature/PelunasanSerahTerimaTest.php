<?php

namespace Tests\Feature;

use App\Models\AdvertisingSpk;
use App\Models\Customer;
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

class PelunasanSerahTerimaTest extends TestCase
{
    use RefreshDatabase;

    private int $urutan = 0;

    private ?User $admin = null;

    private Product $meteran;

    private Finishing $finishing;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(MediaStorage::DISK);

        $this->finishing = Finishing::create([
            'nama_finishing' => 'Laminasi Serah Terima',
            'ekstra_kiri_cm' => 0,
            'ekstra_kanan_cm' => 0,
            'ekstra_atas_cm' => 0,
            'ekstra_bawah_cm' => 0,
        ]);

        $this->meteran = Product::create([
            'nama_produk' => 'Stiker Meteran Serah Terima',
            'kategori_produk' => Product::KATEGORI_METERAN,
            'harga_umum' => 100000,
            'harga_reseller' => 95000,
            'harga_prioritas' => 90000,
            'harga_pegawai' => 85000,
            'harga_modal' => 10000,
        ]);
    }

    public function test_pelunasan_admin_menyimpan_bukti_di_media_sistem_dan_catatan_boleh_kosong(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $order = $this->buatOrder([
            'status_bayar' => Order::STATUS_BAYAR_DP,
            'jumlah_dp' => 50000,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.pelunasan.create', $order))
            ->assertOk()
            ->assertSee('Bukti Transfer / Nota Pembayaran')
            ->assertSee('Catatan Pengecualian');

        $this->actingAs($admin)
            ->post(route('admin.orders.pelunasan', $order), [])
            ->assertSessionHasErrors('catatan_pengecualian');

        $this->actingAs($admin)
            ->post(route('admin.orders.pelunasan', $order), [
                'bukti_pelunasan' => UploadedFile::fake()->create('bukti.txt', 10, 'text/plain'),
            ])
            ->assertSessionHasErrors('bukti_pelunasan');

        $this->actingAs($admin)
            ->post(route('admin.orders.pelunasan', $order), [
                'bukti_pelunasan' => UploadedFile::fake()->create('bukti.pdf', 10, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.orders.index', ['tab' => 'siap_diambil']))
            ->assertSessionHas('status');

        $order->refresh();

        $this->assertSame(Order::STATUS_BAYAR_LUNAS, $order->status_bayar);
        $this->assertSame(150000.0, (float) $order->jumlah_dp);
        $this->assertStringStartsWith(MediaStorage::FOLDER_BUKTI_PELUNASAN.'/', (string) $order->bukti_pelunasan);
        $this->assertNull($order->catatan_pengecualian);
        Storage::disk(MediaStorage::DISK)->assertExists($order->bukti_pelunasan);

        // Divisi tidak boleh mengakses pelunasan admin.
        $this->actingAs($this->user(User::ROLE_DESAINER))
            ->get(route('admin.orders.pelunasan.create', $order))
            ->assertRedirect(route('desainer.dashboard'));
    }

    public function test_serah_terima_admin_menyimpan_foto_dan_data_penerima_dan_catatan_boleh_kosong(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $order = $this->buatOrder();

        $this->actingAs($admin)
            ->get(route('admin.orders.serah-terima.create', $order))
            ->assertOk()
            ->assertSee('ambil sendiri')
            ->assertSee($order->customer->no_wa)
            ->assertSee('Catatan Pengecualian');

        $this->actingAs($admin)
            ->post(route('admin.orders.serah-terima', $order), [])
            ->assertSessionHasErrors(['catatan_pengecualian', 'nama_pengambil', 'no_wa_pengambil']);

        $this->actingAs($admin)
            ->post(route('admin.orders.serah-terima', $order), [
                'foto_serah_terima' => UploadedFile::fake()->create('barang.png', 10, 'image/png'),
                'nama_pengambil' => 'Siti Penerima',
                'no_wa_pengambil' => '081299998888',
            ])
            ->assertRedirect(route('admin.serah-terima.index'))
            ->assertSessionHas('status');

        $order->refresh();

        $this->assertStringStartsWith(MediaStorage::FOLDER_FOTO_SERAH_TERIMA.'/', (string) $order->foto_serah_terima);
        $this->assertSame('Siti Penerima', $order->nama_pengambil);
        $this->assertSame('081299998888', $order->no_wa_pengambil);
        $this->assertNull($order->catatan_pengecualian);
        $this->assertNotNull($order->waktu_serah_terima);
        $this->assertSame(0, $order->items()->where('status_produksi', OrderItem::STATUS_SIAP_DIAMBIL)->count());
        Storage::disk(MediaStorage::DISK)->assertExists($order->foto_serah_terima);
    }

    public function test_pelunasan_tanpa_bukti_menolak_jika_catatan_kosong_dan_menyimpan_catatan_jika_diisi(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $order = $this->buatOrder([
            'status_bayar' => Order::STATUS_BAYAR_DP,
            'jumlah_dp' => 50000,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.orders.pelunasan', $order), [
                'catatan_pengecualian' => '',
            ])
            ->assertSessionHasErrors('catatan_pengecualian');

        $this->assertSame(Order::STATUS_BAYAR_DP, $order->fresh()->status_bayar);
        $this->assertNull($order->fresh()->bukti_pelunasan);

        $catatan = 'Bukti transfer belum dikirim pelanggan; konfirmasi via WhatsApp 22/11.';

        $this->actingAs($admin)
            ->post(route('admin.orders.pelunasan', $order), [
                'catatan_pengecualian' => $catatan,
            ])
            ->assertRedirect(route('admin.orders.index', ['tab' => 'siap_diambil']))
            ->assertSessionHas('status', fn (string $pesan): bool => str_contains($pesan, 'Catatan pengecualian tersimpan untuk audit'));

        $order->refresh();

        $this->assertSame(Order::STATUS_BAYAR_LUNAS, $order->status_bayar);
        $this->assertSame(150000.0, (float) $order->jumlah_dp);
        $this->assertNull($order->bukti_pelunasan);
        $this->assertSame($catatan, $order->catatan_pengecualian);
    }

    public function test_serah_terima_tanpa_foto_menolak_jika_catatan_kosong_dan_menyimpan_catatan_jika_diisi(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $order = $this->buatOrder();

        $payload = [
            'nama_pengambil' => 'Penerima Tanpa Foto',
            'no_wa_pengambil' => '081255554444',
        ];

        $this->actingAs($admin)
            ->post(route('admin.orders.serah-terima', $order), $payload + ['catatan_pengecualian' => '   '])
            ->assertSessionHasErrors('catatan_pengecualian');

        $this->assertNull($order->fresh()->waktu_serah_terima);

        $catatan = 'Barang diambil langsung pelanggan, foto tidak sempat diambil.';

        $this->actingAs($admin)
            ->post(route('admin.orders.serah-terima', $order), $payload + ['catatan_pengecualian' => $catatan])
            ->assertRedirect(route('admin.serah-terima.index'))
            ->assertSessionHas('status', fn (string $pesan): bool => str_contains($pesan, 'Catatan pengecualian tersimpan untuk audit'));

        $order->refresh();

        $this->assertNull($order->foto_serah_terima);
        $this->assertSame($catatan, $order->catatan_pengecualian);
        $this->assertNotNull($order->waktu_serah_terima);
        $this->assertSame(0, $order->items()->where('status_produksi', OrderItem::STATUS_SIAP_DIAMBIL)->count());
    }

    public function test_catatan_pengecualian_hanya_ditampilkan_jika_ada(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $catatan = 'Pelunasan dicatat tanpa bukti transfer.';
        $order = $this->buatOrder([
            'no_nota' => 'ST-CATATAN-ADA',
            'catatan_pengecualian' => $catatan,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Catatan Pengecualian')
            ->assertSee($catatan);

        $this->actingAs($admin)
            ->get(route('admin.orders.summary', $order))
            ->assertOk()
            ->assertSee('Catatan Pengecualian')
            ->assertSee($catatan);

        $tanpaCatatan = $this->buatOrder(['no_nota' => 'ST-CATATAN-KOSONG']);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $tanpaCatatan))
            ->assertOk()
            ->assertDontSee('Catatan Pengecualian');

        $this->actingAs($admin)
            ->get(route('admin.orders.summary', $tanpaCatatan))
            ->assertOk()
            ->assertDontSee('Catatan Pengecualian');
    }

    public function test_catatan_pengecualian_tetap_tersimpan_saat_bukti_dilengkapi(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $catatan = 'Bukti menyusul, pelunasan dicatat lebih dulu.';
        $order = $this->buatOrder([
            'status_bayar' => Order::STATUS_BAYAR_DP,
            'jumlah_dp' => 50000,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.orders.pelunasan', $order), ['catatan_pengecualian' => $catatan])
            ->assertSessionHas('status');

        $this->actingAs($admin)
            ->post(route('admin.orders.pelunasan', $order), [
                'bukti_pelunasan' => UploadedFile::fake()->create('bukti-lengkap.pdf', 10, 'application/pdf'),
                'catatan_pengecualian' => '',
            ])
            ->assertSessionHas('status', fn (string $pesan): bool => str_contains($pesan, 'Bukti tersimpan di media_sistem'));

        $order->refresh();

        $this->assertNotNull($order->bukti_pelunasan);
        $this->assertSame($catatan, $order->catatan_pengecualian);
    }

    public function test_belum_lunas_menonaktifkan_tombol_dan_controller_menolak_serah_terima(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $order = $this->buatOrder([
            'status_bayar' => Order::STATUS_BAYAR_DP,
            'jumlah_dp' => 50000,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.index', ['tab' => 'siap_diambil']))
            ->assertOk()
            ->assertSee('Pelunasan wajib sebelum serah terima')
            ->assertSee('disabled');

        $this->actingAs($admin)
            ->get(route('admin.orders.serah-terima.create', $order))
            ->assertRedirect(route('admin.serah-terima.index'))
            ->assertSessionHas('error', 'Pelunasan wajib sebelum serah terima');

        $this->actingAs($admin)
            ->post(route('admin.orders.serah-terima', $order), $this->serahTerimaPayload())
            ->assertRedirect(route('admin.serah-terima.index'))
            ->assertSessionHas('error', 'Pelunasan wajib sebelum serah terima');

        $order->refresh();

        $this->assertNull($order->foto_serah_terima);
        $this->assertSame(OrderItem::STATUS_SIAP_DIAMBIL, $order->items()->first()->status_produksi);
    }

    public function test_divisi_melihat_order_relevan_dan_dapat_melakukan_pengambilan(): void
    {
        $kasus = [
            [User::ROLE_DESAINER, 'desainer', ['item' => ['file_master_arsip' => 'arsip/master.cdr']]],
            [User::ROLE_OPERATOR_CETAK, 'operator', []],
            [User::ROLE_FINISHING, 'finishing', []],
            [User::ROLE_ADVERTISING, 'advertising', ['spk_status' => AdvertisingSpk::STATUS_SELESAI]],
        ];

        foreach ($kasus as [$role, $prefix, $itemOverride]) {
            $relevan = $this->buatOrder(array_merge([
                'no_nota' => 'ST-RELEVAN-'.$prefix,
                'instansi' => 'Instansi Relevan '.ucfirst($prefix),
            ], $itemOverride));

            $tidakRelevan = $this->buatOrder([
                'no_nota' => 'ST-ASING-'.$prefix,
                'instansi' => 'Instansi Asing '.ucfirst($prefix),
                'item' => [
                    'status_produksi' => OrderItem::STATUS_ANTREAN_CETAK,
                    'file_master_arsip' => null,
                ],
                'spk_status' => AdvertisingSpk::STATUS_ANTREAN,
            ]);

            $user = $this->user($role);

            $this->actingAs($user)
                ->get(route($prefix.'.serah-terima.index'))
                ->assertOk()
                ->assertSee($relevan->no_nota)
                ->assertDontSee($tidakRelevan->no_nota)
                ->assertSee('Pengambilan');

            $this->actingAs($user)
                ->get(route($prefix.'.orders.serah-terima.create', $relevan))
                ->assertOk()
                ->assertSee('ambil sendiri');

            $this->actingAs($user)
                ->post(route($prefix.'.orders.serah-terima', $relevan), $this->serahTerimaPayload())
                ->assertRedirect(route($prefix.'.serah-terima.index'))
                ->assertSessionHas('status');

            $this->assertNotNull($relevan->fresh()->waktu_serah_terima);
            $this->assertSame('Panitia Divisi', $relevan->fresh()->nama_pengambil);
        }
    }

    public function test_paginasi_15_pada_semua_daftar_serah_terima(): void
    {
        $rute = [
            User::ROLE_ADMIN => 'admin.serah-terima.index',
            User::ROLE_DESAINER => 'desainer.serah-terima.index',
            User::ROLE_OPERATOR_CETAK => 'operator.serah-terima.index',
            User::ROLE_FINISHING => 'finishing.serah-terima.index',
            User::ROLE_ADVERTISING => 'advertising.serah-terima.index',
        ];

        for ($i = 1; $i <= 16; $i++) {
            $this->buatOrder([
                'no_nota' => sprintf('ST-HAL-%03d', $i),
                'created_at' => now()->subMinutes(16 - $i),
            ]);
        }

        foreach ($rute as $role => $namaRute) {
            $response = $this->actingAs($this->user($role))->get(route($namaRute));

            $response->assertOk();

            $paginator = $response->viewData('orders');
            $this->assertSame(15, $paginator->perPage());
            $this->assertSame(16, $paginator->total());
            $this->assertTrue($paginator->hasPages());
        }
    }

    public function test_rute_serah_terima_diblokir_untuk_role_selain_pemiliknya(): void
    {
        $order = $this->buatOrder();

        $pemilik = [
            'admin.serah-terima.index' => [User::ROLE_ADMIN, null],
            'admin.orders.serah-terima.create' => [User::ROLE_ADMIN, $order],
            'desainer.serah-terima.index' => [User::ROLE_DESAINER, null],
            'operator.serah-terima.index' => [User::ROLE_OPERATOR_CETAK, null],
            'finishing.serah-terima.index' => [User::ROLE_FINISHING, null],
            'advertising.serah-terima.index' => [User::ROLE_ADVERTISING, null],
        ];

        $dashboard = [
            User::ROLE_ADMIN => 'admin.dashboard',
            User::ROLE_DESAINER => 'desainer.dashboard',
            User::ROLE_OPERATOR_CETAK => 'operator.dashboard',
            User::ROLE_FINISHING => 'finishing.dashboard',
            User::ROLE_ADVERTISING => 'advertising.dashboard',
            User::ROLE_OWNER => 'owner.dashboard',
        ];

        foreach ($pemilik as $namaRute => [$rolePemilik, $parameter]) {
            foreach ($dashboard as $role => $ruteDashboard) {
                if ($role === $rolePemilik) {
                    continue;
                }

                $this->actingAs($this->user($role))
                    ->get(route($namaRute, $parameter === null ? [] : [$parameter]))
                    ->assertRedirect(route($ruteDashboard));
            }
        }
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    /**
     * @return array<string, string>
     */
    private function serahTerimaPayload(): array
    {
        return [
            'foto_serah_terima' => UploadedFile::fake()->create('barang.png', 10, 'image/png'),
            'nama_pengambil' => 'Panitia Divisi',
            'no_wa_pengambil' => '081234567890',
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function buatOrder(array $overrides = []): Order
    {
        $this->urutan++;

        $data = array_merge([
            'no_nota' => 'ST-'.str_pad((string) $this->urutan, 4, '0', STR_PAD_LEFT),
            'instansi' => 'Instansi ST '.$this->urutan,
            'pic' => 'PIC ST '.$this->urutan,
            'status_bayar' => Order::STATUS_BAYAR_LUNAS,
            'total_tagihan' => 150000,
            'jumlah_dp' => 150000,
            'catatan_pengecualian' => null,
            'created_at' => null,
            'item' => [],
            'spk_status' => AdvertisingSpk::STATUS_SELESAI,
        ], $overrides);

        $customer = Customer::create([
            'instansi' => $data['instansi'],
            'nama_pelanggan' => $data['pic'],
            'no_wa' => '0812'.str_pad((string) $this->urutan, 8, '0', STR_PAD_LEFT),
            'kategori' => Customer::KATEGORI_UMUM,
        ]);

        $this->admin ??= $this->user(User::ROLE_ADMIN);

        $order = Order::create([
            'no_nota' => $data['no_nota'],
            'customer_id' => $customer->id,
            'user_id' => $this->admin->id,
            'total_tagihan' => $data['total_tagihan'],
            'jumlah_dp' => $data['jumlah_dp'],
            'status_bayar' => $data['status_bayar'],
            'status_desain' => Order::STATUS_DESAIN_SIAP_CETAK_TIFF,
            'catatan_pengecualian' => $data['catatan_pengecualian'],
        ]);

        OrderItem::create(array_merge([
            'order_id' => $order->id,
            'product_id' => $this->meteran->id,
            'finishing_id' => $this->finishing->id,
            'lebar_m' => 1,
            'tinggi_m' => 1,
            'qty' => 1,
            'luas_tagihan_m2' => 1,
            'harga_satuan_dipakai' => 100000,
            'subtotal' => 100000,
            'status_produksi' => OrderItem::STATUS_SIAP_DIAMBIL,
            'file_master_arsip' => 'file_master_arsip/master.cdr',
        ], $data['item']));

        if ($data['spk_status'] !== null) {
            AdvertisingSpk::create([
                'order_id' => $order->id,
                'jenis_pekerjaan' => 'Papan Nama',
                'ukuran_konstruksi' => '200 x 100 cm',
                'lokasi_pasang' => 'Gedung Uji',
                'status_lapangan' => $data['spk_status'],
            ]);
        }

        if ($data['created_at'] !== null) {
            Order::query()->whereKey($order->id)->update([
                'created_at' => $data['created_at'],
                'updated_at' => $data['created_at'],
            ]);
            $order->refresh();
        }

        return $order;
    }
}
