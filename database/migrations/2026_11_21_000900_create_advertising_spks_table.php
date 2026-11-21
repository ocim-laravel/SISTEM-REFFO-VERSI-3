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
        Schema::create('advertising_spks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('jenis_pekerjaan');
            $table->string('ukuran_konstruksi');
            $table->text('lokasi_pasang');
            $table->text('catatan_teknis')->nullable();
            $table->enum('status_lapangan', ['antrean', 'perakitan', 'pemasangan', 'selesai'])->default('antrean');
            $table->timestamps();

            $table->index('status_lapangan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advertising_spks');
    }
};
