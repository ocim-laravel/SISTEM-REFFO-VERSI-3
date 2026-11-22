<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Instansi;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\MediaStorage;
use Database\Seeders\InstansiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstansiDanMasterDesainTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(MediaStorage::DISK);
    }

    public function test_seeder_instansi_default_idempoten(): void
    {
        $this->seed(InstansiSeeder::class);
        $this->seed(InstansiSeeder::class);

        $this->assertSame(
            1,
            Instansi::query()->where('nama_instansi', Instansi::DEFAULT)->count(),
        );
        $this->assertDatabaseHas('instansis', ['nama_instansi' => 'Telkomsel']);
    }

    public function test_form_order_menyediakan_dropdown_dan_tambah_instansi_baru(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $instansi = Instansi::query()->create(['nama_instansi' => 'PT Form Order']);

        $this->actingAs($admin)
            ->get(route('admin.orders.create'))
            ->assertOk()
            ->assertSee('name="instansi_id"', false)
            ->assertSee('Tambah instansi baru', false)
            ->assertSee('PT Form Order');

        $respons = $this->actingAs($admin)
            ->postJson(route('admin.instansis.store'), ['nama_instansi' => 'Instansi Baru Uji'])
            ->assertCreated()
            ->assertJsonPath('nama_instansi', 'Instansi Baru Uji');

        $this->assertDatabaseHas('instansis', ['nama_instansi' => 'Instansi Baru Uji']);
        $this->assertIsInt($respons->json('id'));

        $this->actingAs($admin)
            ->postJson(route('admin.instansis.store'), ['nama_instansi' => 'Instansi Baru Uji'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('nama_instansi');

        $this->assertNotNull($instansi);
    }

    public function test_auto_complete_pelanggan_dibatasi_instansi_id_dan_nama(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $instansiA = Instansi::query()->create(['nama_instansi' => 'Instansi Autocomplete A']);
        $instansiB = Instansi::query()->create(['nama_instansi' => 'Instansi Autocomplete B']);

        $picA = Customer::query()->create([
            'instansi_id' => $instansiA->id,
            'instansi' => $instansiA->nama_instansi,
            'nama_pelanggan' => 'PIC Sama',
            'no_wa' => '081111111111',
            'kategori' => Customer::KATEGORI_UMUM,
        ]);
        Customer::query()->create([
            'instansi_id' => $instansiB->id,
            'instansi' => $instansiB->nama_instansi,
            'nama_pelanggan' => 'PIC Sama',
            'no_wa' => '082222222222',
            'kategori' => Customer::KATEGORI_UMUM,
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.customers.search', [
                'q' => 'PIC Sama',
                'instansi_id' => $instansiA->id,
            ]))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $picA->id)
            ->assertJsonPath('0.instansi_id', $instansiA->id)
            ->assertJsonPath('0.no_wa', '081111111111');

        $this->actingAs($admin)
            ->getJson(route('admin.customers.search', ['q' => 'PIC Sama']))
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_order_baru_menyimpan_instansi_id_dan_string_instansi_sinkron(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $instansi = Instansi::query()->create(['nama_instansi' => 'PT Sinkron']);
        $produk = $this->meteranProduct();

        $this->actingAs($admin)
            ->post(route('admin.orders.store'), [
                'instansi_id' => $instansi->id,
                // String sengaja dibedakan untuk membuktikan sumber kebenaran ada di instansi_id.
                'instansi' => 'String Lama',
                'nama_pelanggan' => 'PIC Baru Sinkron',
                'no_wa' => '081234567899',
                'kategori' => Customer::KATEGORI_UMUM,
                'items' => [[
                    'product_id' => $produk->id,
                    'qty' => 1,
                    'lebar_m' => 1,
                    'tinggi_m' => 1,
                ]],
                'status_desain' => Order::STATUS_DESAIN_FILE_MENTAH,
                'status_bayar' => Order::STATUS_BAYAR_BELUM_LUNAS,
            ])
            ->assertRedirect();

        $customer = Customer::query()->where('nama_pelanggan', 'PIC Baru Sinkron')->firstOrFail();

        $this->assertSame($instansi->id, (int) $customer->instansi_id);
        $this->assertSame('PT Sinkron', $customer->instansi);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'instansi_id' => $instansi->id,
            'instansi' => 'PT Sinkron',
        ]);
    }

    public function test_satu_master_desain_per_nota_dan_file_tiff_per_item(): void
    {
        $desainer = User::factory()->create(['role' => User::ROLE_DESAINER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $order = $this->orderDuaItem();
        $itemPertama = $order->items()->firstOrFail();
        $itemKedua = $order->items()->orderByDesc('id')->firstOrFail();

        $this->actingAs($desainer)
            ->post(route('desainer.orders.terima', $order))
            ->assertRedirect();

        $this->actingAs($desainer)
            ->post(route('desainer.orders.preview', $order), [
                'catatan_revisi' => 'Preview awal untuk ACC.',
                'file_preview' => UploadedFile::fake()->create('preview-order.png', 10, 'image/png'),
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        foreach ([$itemPertama, $itemKedua] as $item) {
            $this->actingAs($admin)
                ->post(route('admin.design-reviews.setujui', $item))
                ->assertRedirect()
                ->assertSessionDoesntHaveErrors();
        }

        // Dua TIFF per item dan satu master per nota dikirim bersamaan.
        $this->actingAs($desainer)
            ->post(route('desainer.orders.finalisasi', $order), [
                'items' => [
                    $itemPertama->id => ['file_cetak_tiff' => UploadedFile::fake()->create('item-1.tiff', 1, 'image/tiff')],
                    $itemKedua->id => ['file_cetak_tiff' => UploadedFile::fake()->create('item-2.tiff', 1, 'image/tiff')],
                ],
                'file_master_arsip' => UploadedFile::fake()->create('master-nota.cdr', 1),
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $order->refresh();
        $itemPertama->refresh();
        $itemKedua->refresh();

        $this->assertNotNull($order->file_master_arsip);
        $this->assertSame($order->file_master_arsip, $itemPertama->file_master_arsip);
        $this->assertSame($order->file_master_arsip, $itemKedua->file_master_arsip);
        $this->assertNotSame($itemPertama->file_cetak_tiff, $itemKedua->file_cetak_tiff);
        $this->assertSame(OrderItem::STATUS_ANTREAN_CETAK, $itemKedua->status_produksi);
        $this->assertCount(1, Storage::disk(MediaStorage::DISK)->allFiles(MediaStorage::FOLDER_FILE_MASTER_ARSIP));
        $this->assertCount(2, Storage::disk(MediaStorage::DISK)->allFiles(MediaStorage::FOLDER_FILE_CETAK_TIFF));

        $this->actingAs($desainer)
            ->get(route('desainer.dashboard', ['tab' => 'arsip']))
            ->assertOk()
            ->assertSee('Satu master per nota')
            ->assertSee(basename((string) $order->file_master_arsip));
    }

    private function meteranProduct(): Product
    {
        return Product::query()->create([
            'nama_produk' => 'Stiker Meteran Test '.uniqid(),
            'kategori_produk' => Product::KATEGORI_METERAN,
            'harga_umum' => 10000,
            'harga_reseller' => 9000,
            'harga_prioritas' => 9000,
            'harga_pegawai' => 8000,
            'harga_modal' => 5000,
            'stok_pcs' => null,
        ]);
    }

    private function orderDuaItem(): Order
    {
        $customer = Customer::query()->create([
            'instansi' => Instansi::DEFAULT,
            'nama_pelanggan' => 'PIC Master Desain',
            'no_wa' => '081234567890',
            'kategori' => Customer::KATEGORI_UMUM,
        ]);

        /** @var Order $order */
        $order = Order::query()->create([
            'no_nota' => 'TEST-'.strtoupper(uniqid()),
            'customer_id' => $customer->id,
            'user_id' => User::factory()->create(['role' => User::ROLE_ADMIN])->id,
            'total_tagihan' => 20000,
        ]);

        foreach (range(1, 2) as $nomor) {
            OrderItem::query()->create([
                'order_id' => $order->id,
                'product_id' => $this->meteranProduct()->id,
                'qty' => 1,
                'lebar_m' => 1,
                'tinggi_m' => 1,
                'luas_tagihan_m2' => 1,
                'harga_satuan_dipakai' => 10000,
                'subtotal' => 10000,
                'status_produksi' => OrderItem::STATUS_MENUNGGU_DESAIN,
            ]);
        }

        return $order;
    }
}
