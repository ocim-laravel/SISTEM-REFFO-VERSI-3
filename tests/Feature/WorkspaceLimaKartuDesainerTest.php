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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Workspace desainer terdiri dari lima kartu: info pelanggan, log revisi,
 * daftar item + TIFF, master desain, serta tanda tangan & laporan.
 */
class WorkspaceLimaKartuDesainerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(MediaStorage::DISK);
    }

    public function test_workspace_menampilkan_lima_kartu_dan_info_pelanggan(): void
    {
        $desainer = User::factory()->create(['role' => User::ROLE_DESAINER]);
        [$order] = $this->orderWithItems('KARTU-LIMA-001', 2);

        $response = $this->actingAs($desainer)->get(route('desainer.orders.items', $order));

        $response->assertOk()
            ->assertSee('Card 1')
            ->assertSee('Info Pelanggan &amp; Catatan', false)
            ->assertSee('Card 2')
            ->assertSee('Log Revisi')
            ->assertSee('Card 3')
            ->assertSee('Daftar Item Desain')
            ->assertSee('Card 4')
            ->assertSee('Upload Master Desain')
            ->assertSee('Card 5')
            ->assertSee('Tanda Tangan &amp; Laporan', false)
            // Info pelanggan dan catatan Admin tampil pada kartu pertama.
            ->assertSee('PIC KARTU-LIMA-001')
            ->assertSee('Instansi KARTU-LIMA-001')
            ->assertSee('Perhatikan warna brand.')
            // Header nota menampilkan jumlah item dan progres desain.
            ->assertSee('2 item')
            ->assertSee('Progress Desain');

        $this->assertSame(0, $response->viewData('progresDesain'));
    }

    public function test_tiff_terkunci_placeholder_proses_acc_desain_sampai_admin_acc(): void
    {
        $desainer = User::factory()->create(['role' => User::ROLE_DESAINER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        [$order, $item] = $this->orderWithItems('TIFF-GATE-001', 1);

        // Sebelum job diterima: belum ada input TIFF, placeholder ditampilkan.
        $this->actingAs($desainer)
            ->get(route('desainer.orders.items', $order))
            ->assertOk()
            ->assertSee('Proses ACC Desain')
            ->assertDontSee('name="file_cetak_tiff"', false);

        $this->actingAs($desainer)->post(route('desainer.orders.terima', $order))->assertRedirect();

        // Preview sudah diunggah tetapi belum di-ACC: TIFF tetap terkunci.
        $this->unggahPreview($desainer, $item, 'Preview awal');
        $this->actingAs($desainer)
            ->get(route('desainer.orders.items', $order))
            ->assertOk()
            ->assertSee('Proses ACC Desain')
            ->assertDontSee('name="file_cetak_tiff"', false)
            ->assertSee('Menunggu review Admin');

        // Setelah ACC Admin, input TIFF per item terbuka.
        $this->actingAs($admin)->post(route('admin.design-reviews.setujui', $item))->assertRedirect();

        $this->actingAs($desainer)
            ->get(route('desainer.orders.items', $order))
            ->assertOk()
            ->assertDontSee('Proses ACC Desain')
            ->assertSee('name="file_cetak_tiff"', false)
            ->assertSee('Simpan Semua Sekaligus');
    }

    public function test_master_pdf_menyelesaikan_tugas_dengan_tanda_tangan_durasi_dan_laporan_revisi(): void
    {
        $desainer = User::factory()->create(['role' => User::ROLE_DESAINER, 'name' => 'Desainer Laporan']);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'name' => 'Admin Laporan']);
        [$order, $item] = $this->orderWithItems('LAPORAN-001', 1);

        $this->actingAs($desainer)->post(route('desainer.orders.terima', $order))->assertRedirect();

        // Satu putaran revisi: Admin meminta revisi, desainer mengunggah ulang preview.
        $this->unggahPreview($desainer, $item, 'Preview awal laporan');
        $this->actingAs($admin)
            ->post(route('admin.design-reviews.revisi', $item), ['catatan_revisi' => 'Perbaiki margin logo'])
            ->assertRedirect();
        $this->unggahPreview($desainer, $item, 'Preview revisi laporan');
        $this->actingAs($admin)->post(route('admin.design-reviews.setujui', $item))->assertRedirect();

        $this->actingAs($desainer)
            ->post(route('desainer.items.finalisasi', $item), [
                'file_cetak_tiff' => UploadedFile::fake()->create('cetak-laporan.tiff', 1, 'image/tiff'),
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        // Master berformat PDF diterima dan menutup tugas desain.
        $this->actingAs($desainer)
            ->post(route('desainer.orders.master', $order), [
                'file_master_arsip' => UploadedFile::fake()->create('master-laporan.pdf', 1, 'application/pdf'),
                'tanda_tangan' => 'Tanda Tangan Laporan',
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $order->refresh();
        $item->refresh();

        $this->assertStringEndsWith('.pdf', (string) $order->file_master_arsip);
        $this->assertSame('Tanda Tangan Laporan', $order->tanda_tangan_desainer);
        $this->assertSame('Admin Laporan', $order->tanda_tangan_admin);
        $this->assertNotNull($order->waktu_selesai_desain);
        $this->assertSame(OrderItem::STATUS_ANTREAN_CETAK, $item->status_produksi);

        $response = $this->actingAs($desainer)->get(route('desainer.orders.items', $order));

        $response->assertOk()
            ->assertSee('Finalisasi selesai.')
            ->assertSee('Tugas Desainer Selesai')
            // Card 5: tanda tangan digital.
            ->assertSee('Tanda Tangan Digital')
            ->assertSee('Tanda Tangan Laporan')
            ->assertSee('Admin ACC')
            ->assertSee('Admin Laporan')
            // Card 5: durasi proses dari penerimaan sampai selesai.
            ->assertSee('Durasi Proses')
            ->assertSee('Total Durasi')
            ->assertSee($order->waktu_terima_desain->format('d/m/Y H:i'))
            ->assertSee($order->waktu_selesai_desain->format('d/m/Y H:i'))
            ->assertSee($order->durasiProsesDesain())
            // Card 5: rincian log revisi Admin dan desainer.
            ->assertSee('Rincian Log Revisi')
            ->assertSee('3 entri')
            ->assertSee('Preview awal laporan')
            ->assertSee('Perbaiki margin logo')
            ->assertSee('Preview revisi laporan');

        $this->assertStringEndsWith('.pdf', (string) $response->viewData('order')->file_master_arsip);
        $this->assertSame(100, $response->viewData('progresDesain'));
    }

    public function test_master_menolak_format_di_luar_daftar_dan_tidak_menutup_tugas(): void
    {
        $desainer = User::factory()->create(['role' => User::ROLE_DESAINER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        [$order, $item] = $this->orderWithItems('FORMAT-001', 1);

        $this->actingAs($desainer)->post(route('desainer.orders.terima', $order))->assertRedirect();
        $this->unggahPreview($desainer, $item, 'Preview format');
        $this->actingAs($admin)->post(route('admin.design-reviews.setujui', $item))->assertRedirect();

        $this->actingAs($desainer)
            ->post(route('desainer.items.finalisasi', $item), [
                'file_cetak_tiff' => UploadedFile::fake()->create('format.tiff', 1, 'image/tiff'),
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->actingAs($desainer)
            ->post(route('desainer.orders.master', $order), [
                'file_master_arsip' => UploadedFile::fake()->create('master.exe', 1),
                'tanda_tangan' => 'Format Salah',
            ])
            ->assertSessionHasErrors('file_master_arsip');

        $this->assertNull($order->fresh()->file_master_arsip);
        $this->assertNull($order->fresh()->waktu_selesai_desain);
        $this->assertSame(OrderItem::STATUS_MENUNGGU_ACC, $item->fresh()->status_produksi);
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
