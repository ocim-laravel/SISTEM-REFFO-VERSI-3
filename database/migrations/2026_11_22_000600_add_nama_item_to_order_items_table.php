<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nama item bebas dari Admin, dipakai sebagai identitas baris pada antrean desain.
     * Penambahan kolom selalu melalui migrasi baru, bukan mengubah migrasi lama.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->string('nama_item')->nullable()->after('order_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn('nama_item');
        });
    }
};
