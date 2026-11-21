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
        Schema::create('finishings', function (Blueprint $table) {
            $table->id();
            $table->string('nama_finishing');
            $table->decimal('ekstra_kiri_cm', 5, 2);
            $table->decimal('ekstra_kanan_cm', 5, 2);
            $table->decimal('ekstra_atas_cm', 5, 2);
            $table->decimal('ekstra_bawah_cm', 5, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finishings');
    }
};
