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
use App\Support\MediaStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class Tahap6E2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(MediaStorage::DISK);
    }

    public function test_admin_mencetak_pdf_nota_dengan_spk_tanpa_nominal_harga(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $desainer = User::factory()->create(['role' => User::ROLE_DESAINER]);
        [$meteran, $satuan, $finishing] = $this->masterData();

        $order = $this->inputOrder($admin, $meteran, $satuan, $finishing);

        $this->actingAs($admin)
            ->get(route('admin.orders.summary', $order))
            ->assertOk()
            ->assertSee('Cetak PDF')
            ->assertSee('Non-Billing · Tanpa Nominal');

        $response = $this->actingAs($admin)->get(route('admin.orders.pdf', $order));
        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());

        $html = view('admin.orders.pdf', [
            'order' => $order->load(['customer', 'user', 'items.product', 'items.finishing', 'advertisingSpks']),
        ])->render();

        $this->assertStringContainsString('RINGKASAN NOTA', $html);
        $this->assertStringContainsString('Lembar SPK Advertising', $html);
        $this->assertStringContainsString('Tanpa nominal harga', $html);
        $this->assertStringContainsString('Rangka hollow galvanis', $html);

        // Bagian SPK tidak boleh memuat nominal harga apa pun.
        $bagianSpk = Str::after($html, 'Lembar SPK Advertising');
        $this->assertStringNotContainsString('Rp', $bagianSpk);
        $this->assertStringNotContainsString('150.000', $bagianSpk);

        $this->actingAs($desainer)
            ->get(route('admin.orders.pdf', $order))
            ->assertRedirect(route('desainer.dashboard'));
    }

    public function test_alur_lengkap_admin_desainer_operator_finishing_advertising_owner(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $desainer = User::factory()->create(['role' => User::ROLE_DESAINER]);
        $operator = User::factory()->create(['role' => User::ROLE_OPERATOR_CETAK]);
        $finishingUser = User::factory()->create(['role' => User::ROLE_FINISHING]);
        $advertising = User::factory()->create(['role' => User::ROLE_ADVERTISING]);
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);

        [$meteran, $satuan, $finishing, $roll] = $this->masterData();

        // 1. ADMIN — nota multi-item, pembulatan tagihan, dan SPK non-billing.
        $responsNota = $this->actingAs($admin)->post(
            route('admin.orders.store'),
            $this->orderPayload($meteran, $satuan, $finishing),
        );

        $order = Order::firstOrFail();
        $responsNota->assertRedirect(route('admin.orders.summary', $order));
        $this->assertSame(150000.0, (float) $order->total_tagihan);
        $this->assertSame(50000.0, (float) $order->jumlah_dp);

        $itemMeteran = $order->items()->where('product_id', $meteran->id)->firstOrFail();
        $itemSatuan = $order->items()->where('product_id', $satuan->id)->firstOrFail();

        $this->assertSame(1.0, (float) $itemMeteran->luas_tagihan_m2);
        $this->assertSame(100000.0, (float) $itemMeteran->subtotal);
        $this->assertSame($finishing->id, (int) $itemMeteran->finishing_id);
        $this->assertNull($itemSatuan->finishing_id);
        $this->assertSame(50000.0, (float) $itemSatuan->subtotal);

        $spk = $order->advertisingSpks()->firstOrFail();
        $this->assertSame(AdvertisingSpk::STATUS_ANTREAN, $spk->status_lapangan);

        // 2. DESAINER — revisi ACC, kunci dua file wajib, lalu kirim ke cetak.
        $this->actingAs($desainer)
            ->post(route('desainer.items.preview', $itemMeteran), [
                'catatan_revisi' => 'ACC tata letak',
                'file_preview' => UploadedFile::fake()->create('preview.png', 10, 'image/png'),
            ])
            ->assertRedirect();
        $this->assertSame(1, $itemMeteran->designRevisions()->count());

        $this->actingAs($desainer)
            ->post(route('desainer.items.finalisasi', $itemMeteran), [
                'file_cetak_tiff' => UploadedFile::fake()->create('meteran.tiff', 1, 'image/tiff'),
            ])
            ->assertSessionHasErrors('file_master_arsip');
        $this->assertSame(OrderItem::STATUS_MENUNGGU_ACC, $itemMeteran->fresh()->status_produksi);

        $this->finalisasiDesain($desainer, $itemMeteran, 'meteran');
        $this->finalisasiDesain($desainer, $itemSatuan, 'poster');
        $this->assertSame(OrderItem::STATUS_ANTREAN_CETAK, $itemMeteran->fresh()->status_produksi);
        $this->assertSame(OrderItem::STATUS_ANTREAN_CETAK, $itemSatuan->fresh()->status_produksi);
        Storage::disk(MediaStorage::DISK)->assertExists($itemMeteran->fresh()->file_cetak_tiff);
        Storage::disk(MediaStorage::DISK)->assertExists($itemMeteran->fresh()->file_master_arsip);

        // 3. OPERATOR — gagal cetak tercatat, lalu pemotongan menghasilkan offcut baru.
        $this->actingAs($operator)
            ->post(route('operator.items.gagal-cetak', $itemMeteran), [
                'material_roll_id' => $roll->id,
                'panjang_rusak_m' => 1,
                'keterangan' => 'Mesin tersendat',
            ])
            ->assertRedirect();
        $this->assertSame(9.0, (float) $roll->fresh()->panjang_sisa_m);

        $this->actingAs($operator)
            ->post(route('operator.items.selesai', $itemMeteran), [
                'sumber_bahan' => 'roll',
                'material_roll_id' => $roll->id,
                'orientasi_potong' => 'panjang',
            ])
            ->assertRedirect();

        $this->assertSame(8.0, (float) $roll->fresh()->panjang_sisa_m);
        $this->assertSame(OrderItem::STATUS_ANTREAN_FINISHING, $itemMeteran->fresh()->status_produksi);

        $offcut = MaterialOffcut::firstWhere('material_roll_id', $roll->id);
        $this->assertNotNull($offcut);
        $this->assertSame(1.95, (float) $offcut->lebar_m);
        $this->assertSame(1.0, (float) $offcut->panjang_m);
        $this->assertSame(1.95, (float) $offcut->luas_m2);
        $this->assertSame(MaterialOffcut::STATUS_TERSEDIA, $offcut->status);

        $logs = $itemMeteran->materialLogs()->orderBy('id')->get();
        $this->assertCount(2, $logs);
        $this->assertSame(3.2, (float) $logs[0]->gagal_cetak_m2);
        $this->assertSame(1.0, (float) $logs[1]->luas_netto_m2);
        $this->assertSame(0.25, (float) $logs[1]->wos_finishing_m2);
        $this->assertSame(0.0, (float) $logs[1]->wos_potong_m2);
        $this->assertSame(150000.0, (float) $order->fresh()->total_tagihan);

        $this->actingAs($operator)
            ->post(route('operator.items.selesai', $itemSatuan))
            ->assertRedirect();
        $this->assertSame(8, (int) $satuan->fresh()->stok_pcs);
        $this->assertSame(OrderItem::STATUS_SIAP_DIAMBIL, $itemSatuan->fresh()->status_produksi);
        $this->assertSame(0, MaterialLog::where('order_item_id', $itemSatuan->id)->count());

        // 4. FINISHING — konfirmasi item terakhir dan nota siap diambil.
        $this->actingAs($finishingUser)
            ->post(route('finishing.items.konfirmasi', $itemMeteran))
            ->assertRedirect();
        $this->assertSame(OrderItem::STATUS_SIAP_DIAMBIL, $itemMeteran->fresh()->status_produksi);
        $this->assertTrue(Order::siapDiambil()->whereKey($order->id)->exists());

        // 5. ADVERTISING — hanya melihat SPK tanpa nominal tagihan, progres maju satu tahap.
        $this->actingAs($advertising)
            ->get(route('advertising.dashboard'))
            ->assertOk()
            ->assertSee('Papan Nama')
            ->assertSee('Rangka hollow galvanis')
            ->assertDontSee('150.000');

        $this->actingAs($advertising)
            ->post(route('advertising.spks.status', $spk), ['status_lapangan' => AdvertisingSpk::STATUS_PEMASANGAN])
            ->assertSessionHasErrors('status_lapangan');

        foreach ([
            AdvertisingSpk::STATUS_PERAKITAN,
            AdvertisingSpk::STATUS_PEMASANGAN,
            AdvertisingSpk::STATUS_SELESAI,
        ] as $status) {
            $this->actingAs($advertising)
                ->post(route('advertising.spks.status', $spk), ['status_lapangan' => $status])
                ->assertRedirect();
            $this->assertSame($status, $spk->fresh()->status_lapangan);
        }

        // 6. OWNER — biaya operasional, audit bahan, dan laba bersih akhir.
        $this->actingAs($owner)
            ->post(route('owner.expenses.store'), [
                'tanggal' => now()->toDateString(),
                'kategori_biaya' => OperationalExpense::KATEGORI_GAJI,
                'keterangan' => 'Gaji workshop',
                'nominal' => 25000,
            ])
            ->assertRedirect();

        $periode = [
            'dari' => now()->subDay()->toDateString(),
            'sampai' => now()->addDay()->toDateString(),
        ];

        $labaRugi = $this->actingAs($owner)->get(route('owner.reports.profit-loss', $periode));
        $labaRugi->assertOk()
            ->assertSee('Wos Finishing')
            ->assertSee('70.500')
            ->assertViewHas('omzet.tagihan', 150000.0)
            ->assertViewHas('hppBahan', 44500.0)
            ->assertViewHas('hppSatuan', 10000.0)
            ->assertViewHas('hppProduksi', 54500.0)
            ->assertViewHas('totalOperasional', 25000.0)
            ->assertViewHas('labaBersih', 70500.0);

        $komponenHpp = $labaRugi->viewData('komponenHpp');
        $this->assertSame(1.0, $komponenHpp['netto']['luas']);
        $this->assertSame(0.25, $komponenHpp['finishing']['luas']);
        $this->assertSame(0.0, $komponenHpp['potong']['luas']);
        $this->assertSame(3.2, $komponenHpp['gagal']['luas']);

        $this->actingAs($owner)
            ->get(route('owner.reports.materials', $periode))
            ->assertOk()
            ->assertViewHas('ringkasan.netto', 1.0)
            ->assertViewHas('ringkasan.finishing', 0.25)
            ->assertViewHas('ringkasan.gagal', 3.2)
            ->assertSee('1,95');
    }

    /**
     * @return array{0: Product, 1: Product, 2: Finishing, 3: MaterialRoll}
     */
    private function masterData(): array
    {
        $finishing = Finishing::create([
            'nama_finishing' => 'Laminasi',
            'ekstra_kiri_cm' => 25,
            'ekstra_kanan_cm' => 0,
            'ekstra_atas_cm' => 0,
            'ekstra_bawah_cm' => 0,
        ]);

        $meteran = Product::create([
            'nama_produk' => 'Stiker Meteran E2E',
            'kategori_produk' => Product::KATEGORI_METERAN,
            'harga_umum' => 100000,
            'harga_reseller' => 95000,
            'harga_prioritas' => 90000,
            'harga_pegawai' => 85000,
            'harga_modal' => 10000,
        ]);

        $satuan = Product::create([
            'nama_produk' => 'Poster E2E',
            'kategori_produk' => Product::KATEGORI_SATUAN,
            'harga_umum' => 25000,
            'harga_reseller' => 23000,
            'harga_prioritas' => 22000,
            'harga_pegawai' => 20000,
            'harga_modal' => 5000,
            'stok_pcs' => 10,
        ]);

        $roll = MaterialRoll::create([
            'kode_roll' => 'R-E2E-001',
            'jenis_bahan' => MaterialRoll::JENIS_BAHAN[0],
            'lebar_m' => 3.2,
            'panjang_awal_m' => 10,
            'panjang_sisa_m' => 10,
            'status' => MaterialRoll::STATUS_AKTIF,
        ]);

        return [$meteran, $satuan, $finishing, $roll];
    }

    private function inputOrder(User $admin, Product $meteran, Product $satuan, Finishing $finishing): Order
    {
        $this->actingAs($admin)->post(route('admin.orders.store'), $this->orderPayload($meteran, $satuan, $finishing))
            ->assertRedirect();

        return Order::firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function orderPayload(Product $meteran, Product $satuan, Finishing $finishing): array
    {
        return [
            'instansi' => 'Instansi E2E',
            'nama_pelanggan' => 'PIC E2E',
            'no_wa' => '081234567890',
            'kategori' => Customer::KATEGORI_UMUM,
            'items' => [
                [
                    'product_id' => $meteran->id,
                    'qty' => 1,
                    'lebar_m' => 1,
                    'tinggi_m' => 1,
                    'finishing_id' => $finishing->id,
                ],
                [
                    'product_id' => $satuan->id,
                    'qty' => 2,
                ],
            ],
            'status_desain' => Order::STATUS_DESAIN_FILE_MENTAH,
            'catatan_desain' => 'Cetak satu stiker meteran dan dua poster.',
            'status_bayar' => Order::STATUS_BAYAR_DP,
            'jumlah_dp' => 50000,
            'spk_enabled' => 1,
            'spk_jenis_pekerjaan' => 'Papan Nama',
            'spk_ukuran_konstruksi' => '200 x 100 cm',
            'spk_lokasi_pasang' => 'Gedung A',
            'spk_catatan_teknis' => 'Rangka hollow galvanis',
        ];
    }

    private function finalisasiDesain(User $desainer, OrderItem $item, string $nama): void
    {
        $this->actingAs($desainer)
            ->post(route('desainer.items.finalisasi', $item), [
                'file_cetak_tiff' => UploadedFile::fake()->create($nama.'-siap.tiff', 1, 'image/tiff'),
                'file_master_arsip' => UploadedFile::fake()->create($nama.'-master.cdr', 1),
            ])
            ->assertRedirect();
    }
}
