<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Finishing;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TenOrderSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $customers = Customer::query()->orderBy('id')->get();
        $products = Product::query()->orderBy('id')->get();
        $finishings = Finishing::query()->orderBy('id')->get();
        $admin = User::query()->where('role', User::ROLE_ADMIN)->orderBy('id')->first();

        if ($customers->isEmpty() || $products->isEmpty() || ! $admin) {
            $this->command?->warn('TenOrderSeeder: butuh minimal customer, product, dan user admin.');
            return;
        }

        $meteran = $products->where('kategori_produk', Product::KATEGORI_METERAN)->values();
        $satuan = $products->where('kategori_produk', Product::KATEGORI_SATUAN)->values();
        $statusBayar = [
            Order::STATUS_BAYAR_BELUM_LUNAS,
            Order::STATUS_BAYAR_DP,
            Order::STATUS_BAYAR_LUNAS,
        ];
        $statusDesain = [
            Order::STATUS_DESAIN_FILE_MENTAH,
            Order::STATUS_DESAIN_CORETAN_KERTAS,
        ];

        DB::transaction(function () use ($customers, $meteran, $satuan, $finishings, $admin, $statusBayar, $statusDesain): void {
            for ($i = 1; $i <= 10; $i++) {
                $customer = $customers[($i - 1) % $customers->count()];
                $createdAt = Carbon::now()->subDays(10 - $i)->setTime(9 + ($i % 8), ($i * 7) % 60);
                $jumlahItem = random_int(5, 10);
                $itemCount = 0;
                $total = 0.0;
                $items = [];

                for ($j = 1; $j <= $jumlahItem; $j++) {
                    $pakaiMeteran = $meteran->isNotEmpty() && ($satuan->isEmpty() || $j % 2 === 1);
                    $product = $pakaiMeteran
                        ? $meteran[($i + $j) % $meteran->count()]
                        : $satuan[($i + $j) % max($satuan->count(), 1)];

                    $qty = random_int(1, 3);
                    $harga = (float) $product->hargaUntukKategori($customer->kategori);

                    if ($product->kategori_produk === Product::KATEGORI_METERAN) {
                        $lebar = round(random_int(50, 300) / 100, 2);  // 0.50 - 3.00 m
                        $tinggi = round(random_int(50, 200) / 100, 2); // 0.50 - 2.00 m
                        $luasAsli = round($lebar * $tinggi * $qty, 2);
                        $luasTagihan = max($luasAsli, 1.0); // pembulatan minimal 1 m2 hanya tagihan
                        $subtotal = round($harga * $luasTagihan, 2);
                        $finishingId = $finishings->isNotEmpty() && $j % 2 === 0
                            ? $finishings[($i + $j) % $finishings->count()]->id
                            : null;

                        $items[] = [
                            'nama_item' => sprintf('Item %02d - %s', $j, $product->nama_produk),
                            'product_id' => $product->id,
                            'finishing_id' => $finishingId,
                            'lebar_m' => $this->dec($lebar),
                            'tinggi_m' => $this->dec($tinggi),
                            'qty' => $qty,
                            'luas_tagihan_m2' => $this->dec($luasTagihan),
                            'harga_satuan_dipakai' => $this->dec($harga),
                            'subtotal' => $this->dec($subtotal),
                            'status_produksi' => OrderItem::STATUS_MENUNGGU_DESAIN,
                        ];
                    } else {
                        $subtotal = round($harga * $qty, 2);
                        $items[] = [
                            'nama_item' => sprintf('Item %02d - %s', $j, $product->nama_produk),
                            'product_id' => $product->id,
                            'finishing_id' => null, // satuan: tanpa finishing
                            'lebar_m' => null,
                            'tinggi_m' => null,
                            'qty' => $qty,
                            'luas_tagihan_m2' => null,
                            'harga_satuan_dipakai' => $this->dec($harga),
                            'subtotal' => $this->dec($subtotal),
                            'status_produksi' => OrderItem::STATUS_MENUNGGU_DESAIN,
                        ];
                    }

                    $total += $subtotal;
                    $itemCount++;
                }

                $statusBayarOrder = $statusBayar[$i % count($statusBayar)];
                $jumlahDp = match ($statusBayarOrder) {
                    Order::STATUS_BAYAR_LUNAS => $total,
                    Order::STATUS_BAYAR_DP => round($total * 0.3, 2),
                    default => 0.0,
                };

                $order = Order::query()->create([
                    'no_nota' => sprintf('INV-SEED-%03d', $i),
                    'customer_id' => $customer->id,
                    'user_id' => $admin->id,
                    'total_tagihan' => $this->dec($total),
                    'jumlah_dp' => $this->dec($jumlahDp),
                    'status_bayar' => $statusBayarOrder,
                    'status_desain' => $statusDesain[$i % count($statusDesain)],
                    'catatan_desain' => 'Order seed uji - '.$customer->nama_pelanggan,
                    'deadline' => $createdAt->copy()->addDays(3 + ($i % 5)),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                foreach ($items as $item) {
                    $order->items()->create($item);
                }

                $this->command?->info(sprintf('Order %s: %s (%d item)', $order->no_nota, $customer->nama_pelanggan, $itemCount));
            }
        });

        $this->command?->info('TenOrderSeeder: 10 order berhasil dibuat.');
    }

    private function dec(float|int|string $nilai): string
    {
        return number_format((float) $nilai, 2, '.', '');
    }
}
