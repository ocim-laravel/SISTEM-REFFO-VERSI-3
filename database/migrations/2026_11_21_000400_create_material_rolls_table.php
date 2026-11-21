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
        Schema::create('material_rolls', function (Blueprint $table) {
            $table->id();
            $table->string('kode_roll')->unique();
            $table->string('jenis_bahan', 50);
            $table->decimal('lebar_m', 5, 2);
            $table->decimal('panjang_awal_m', 8, 2);
            $table->decimal('panjang_sisa_m', 8, 2);
            $table->enum('status', ['aktif', 'habis']);
            $table->timestamps();

            $table->index('jenis_bahan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_rolls');
    }
};
