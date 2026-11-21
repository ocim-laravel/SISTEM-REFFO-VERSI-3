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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('instansi')->default('Order Pribadi');
            $table->string('nama_pelanggan');
            $table->string('no_wa', 30);
            $table->enum('kategori', ['umum', 'reseller', 'prioritas', 'pegawai']);
            $table->text('alamat')->nullable();
            $table->timestamps();

            $table->index('instansi');
            $table->index('nama_pelanggan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
