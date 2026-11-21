<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('finishing_id')->nullable()->constrained('finishings')->nullOnDelete();
            $table->decimal('lebar_m', 6, 2)->nullable();
            $table->decimal('tinggi_m', 6, 2)->nullable();
            $table->integer('qty');
            $table->decimal('luas_tagihan_m2', 8, 2)->nullable();
            $table->decimal('harga_satuan_dipakai', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->enum('status_produksi', [
                'menunggu_desain',
                'proses_desain',
                'menunggu_acc',
                'antrean_cetak',
                'proses_cetak',
                'antrean_finishing',
                'proses_finishing',
                'siap_diambil',
                'selesai',
            ])->default('menunggu_desain');
            $table->string('file_preview_acc')->nullable();
            $table->string('file_cetak_tiff')->nullable();
            $table->string('file_master_arsip')->nullable();
            $table->timestamps();

            $table->index('status_produksi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
