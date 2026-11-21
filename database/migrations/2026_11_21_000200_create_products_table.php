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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('nama_produk');
            $table->enum('kategori_produk', ['meteran', 'satuan']);
            $table->decimal('harga_umum', 12, 2);
            $table->decimal('harga_reseller', 12, 2);
            $table->decimal('harga_prioritas', 12, 2);
            $table->decimal('harga_pegawai', 12, 2);
            $table->decimal('harga_modal', 12, 2);
            $table->integer('stok_pcs')->nullable();
            $table->timestamps();

            $table->index('kategori_produk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
