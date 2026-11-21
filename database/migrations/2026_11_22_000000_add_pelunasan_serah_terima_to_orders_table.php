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
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('bukti_pelunasan')->nullable()->after('lampiran_briefing');
            $table->string('foto_serah_terima')->nullable()->after('bukti_pelunasan');
            $table->string('nama_pengambil')->nullable()->after('foto_serah_terima');
            $table->string('no_wa_pengambil', 30)->nullable()->after('nama_pengambil');
            $table->timestamp('waktu_serah_terima')->nullable()->after('no_wa_pengambil');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'waktu_serah_terima',
                'no_wa_pengambil',
                'nama_pengambil',
                'foto_serah_terima',
                'bukti_pelunasan',
            ]);
        });
    }
};
