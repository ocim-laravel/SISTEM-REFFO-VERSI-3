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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('no_nota', 30)->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('total_tagihan', 12, 2)->default(0);
            $table->decimal('jumlah_dp', 12, 2)->default(0);
            $table->enum('status_bayar', ['belum_lunas', 'dp', 'lunas'])->default('belum_lunas');
            $table->enum('status_desain', ['belum_ada_file', 'file_mentah', 'coretan_kertas', 'siap_cetak_tiff'])->default('belum_ada_file');
            $table->text('catatan_desain')->nullable();
            $table->string('lampiran_briefing')->nullable();
            $table->dateTime('deadline')->nullable();
            $table->timestamps();

            $table->index('status_bayar');
            $table->index('status_desain');
            $table->index('deadline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
