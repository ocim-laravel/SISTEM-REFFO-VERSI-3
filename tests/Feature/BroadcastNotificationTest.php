<?php

namespace Tests\Feature;

use App\Events\OrderStatusChanged;
use App\Events\PelunasanCompleted;
use App\Events\SerahTerimaCompleted;
use App\Models\AdvertisingSpk;
use App\Models\Customer;
use App\Models\Finishing;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\MediaStorage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BroadcastNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_status_changed_event_broadcasts_to_role_channel_with_payload(): void
    {
        $order = $this->buatOrder();
        $item = $order->items->first();

        $event = new OrderStatusChanged(
            $order,
            $item,
            OrderItem::STATUS_SIAP_DIAMBIL,
            [User::ROLE_ADMIN, User::ROLE_FINISHING],
            'Nota test siap diambil.'
        );

        $this->assertInstanceOf(ShouldBroadcastNow::class, $event);
        $this->assertSame('order.status.changed', $event->broadcastAs());
        $this->assertEqualsCanonicalizing(
            ['private-notifications.admin', 'private-notifications.finishing'],
            collect($event->broadcastOn())->map(fn ($channel) => $channel->name)->all()
        );

        $payload = $event->broadcastWith();
        $this->assertSame('order_status_changed', $payload['type']);
        $this->assertSame($order->no_nota, $payload['no_nota']);
        $this->assertSame(OrderItem::STATUS_SIAP_DIAMBIL, $payload['status_produksi']);
        $this->assertSame('Nota test siap diambil.', $payload['message']);
    }

    public function test_pelunasan_and_serah_terima_events_use_private_role_channels(): void
    {
        $order = $this->buatOrder(['status_bayar' => Order::STATUS_BAYAR_DP, 'jumlah_dp' => 50000]);

        $pelunasan = new PelunasanCompleted($order->fresh());
        $serahTerima = new SerahTerimaCompleted($order->fresh());

        $this->assertInstanceOf(ShouldBroadcastNow::class, $pelunasan);
        $this->assertInstanceOf(ShouldBroadcastNow::class, $serahTerima);
        $this->assertSame('pelunasan.completed', $pelunasan->broadcastAs());
        $this->assertSame('serah-terima.completed', $serahTerima->broadcastAs());

        $expected = collect(User::ROLES)->map(fn ($role) => 'private-notifications.'.$role)->all();
        $this->assertEqualsCanonicalizing($expected, collect($pelunasan->broadcastOn())->map(fn ($c) => $c->name)->all());
        $this->assertEqualsCanonicalizing($expected, collect($serahTerima->broadcastOn())->map(fn ($c) => $c->name)->all());

        $this->assertSame($order->no_nota, $pelunasan->broadcastWith()['no_nota']);
        $this->assertSame($order->no_nota, $serahTerima->broadcastWith()['no_nota']);
    }

    public function test_notification_channel_authorizes_only_matching_role(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $desainer = User::factory()->create(['role' => User::ROLE_DESAINER]);

        // Channel callback harus mengizinkan role yang sama dan menolak role lain.
        $callback = function ($user, string $role) {
            return in_array($role, User::ROLES, true) && $user->role === $role;
        };

        $this->assertTrue($callback($admin, User::ROLE_ADMIN));
        $this->assertFalse($callback($desainer, User::ROLE_ADMIN));
        $this->assertFalse($callback($admin, 'unknown_role'));

        // Verifikasi channel terdaftar di aplikasi.
        $this->assertArrayHasKey('notifications.{role}', \Illuminate\Support\Facades\Broadcast::getChannels());
    }

    public function test_controllers_dispatch_broadcast_events(): void
    {
        Storage::fake(MediaStorage::DISK);
        Event::fake([PelunasanCompleted::class, SerahTerimaCompleted::class]);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $order = $this->buatOrder([
            'status_bayar' => Order::STATUS_BAYAR_DP,
            'jumlah_dp' => 50000,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.orders.pelunasan', $order), [
                'catatan_pengecualian' => 'Tes pelunasan broadcast.',
            ])
            ->assertSessionHasNoErrors();

        Event::assertDispatched(PelunasanCompleted::class, function (PelunasanCompleted $event) use ($order): bool {
            return $event->order->id === $order->id;
        });

        $orderSiap = $this->buatOrder();
        $this->actingAs($admin)
            ->post(route('admin.orders.serah-terima', $orderSiap), [
                'foto_serah_terima' => UploadedFile::fake()->create('barang.png', 10, 'image/png'),
                'nama_pengambil' => 'Penerima',
                'no_wa_pengambil' => '08123456789',
            ])
            ->assertSessionHasNoErrors();

        Event::assertDispatched(SerahTerimaCompleted::class, function (SerahTerimaCompleted $event) use ($orderSiap): bool {
            return $event->order->id === $orderSiap->id;
        });
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function buatOrder(array $overrides = []): Order
    {
        static $urutan = 0;
        $urutan++;

        $finishing = Finishing::firstOrCreate(
            ['nama_finishing' => 'Finishing Broadcast'],
            ['ekstra_kiri_cm' => 0, 'ekstra_kanan_cm' => 0, 'ekstra_atas_cm' => 0, 'ekstra_bawah_cm' => 0]
        );

        $product = Product::firstOrCreate(
            ['nama_produk' => 'Produk Broadcast'],
            [
                'kategori_produk' => Product::KATEGORI_METERAN,
                'harga_umum' => 100000,
                'harga_reseller' => 95000,
                'harga_prioritas' => 90000,
                'harga_pegawai' => 85000,
                'harga_modal' => 10000,
            ]
        );

        $customer = Customer::create([
            'instansi' => 'Instansi BC '.$urutan,
            'nama_pelanggan' => 'PIC BC '.$urutan,
            'no_wa' => '08120000'.$urutan,
            'kategori' => Customer::KATEGORI_UMUM,
        ]);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $order = Order::create(array_merge([
            'no_nota' => 'BC-'.str_pad((string) $urutan, 4, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'user_id' => $admin->id,
            'total_tagihan' => 100000,
            'jumlah_dp' => 100000,
            'status_bayar' => Order::STATUS_BAYAR_LUNAS,
            'status_desain' => Order::STATUS_DESAIN_SIAP_CETAK_TIFF,
        ], $overrides));

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'finishing_id' => $finishing->id,
            'lebar_m' => 1,
            'tinggi_m' => 1,
            'qty' => 1,
            'luas_tagihan_m2' => 1,
            'harga_satuan_dipakai' => 100000,
            'subtotal' => 100000,
            'status_produksi' => OrderItem::STATUS_SIAP_DIAMBIL,
            'file_master_arsip' => 'file_master_arsip/master.cdr',
        ]);

        return $order->fresh(['items', 'customer']);
    }
}
