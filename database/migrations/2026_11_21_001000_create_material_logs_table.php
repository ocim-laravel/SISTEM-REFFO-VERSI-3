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
        Schema::create('material_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('material_roll_id')->nullable()->constrained('material_rolls')->nullOnDelete();
            $table->foreignId('material_offcut_id')->nullable()->constrained('material_offcuts')->nullOnDelete();
            $table->decimal('luas_netto_m2', 8, 2)->default(0);
            $table->decimal('wos_finishing_m2', 8, 2)->default(0);
            $table->decimal('wos_potong_m2', 8, 2)->default(0);
            $table->decimal('gagal_cetak_m2', 8, 2)->default(0);
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_logs');
    }
};
