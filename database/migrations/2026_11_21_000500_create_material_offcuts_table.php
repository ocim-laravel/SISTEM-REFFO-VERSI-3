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
        Schema::create('material_offcuts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_roll_id')->nullable()->constrained('material_rolls')->nullOnDelete();
            $table->string('jenis_bahan', 50);
            $table->decimal('lebar_m', 5, 2);
            $table->decimal('panjang_m', 8, 2);
            $table->decimal('luas_m2', 8, 2);
            $table->enum('status', ['tersedia', 'terpakai']);
            $table->timestamps();

            $table->index(['jenis_bahan', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_offcuts');
    }
};
