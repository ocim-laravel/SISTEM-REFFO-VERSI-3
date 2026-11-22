<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Log preview desain dipindahkan ke level order (satu preview per nota),
     * sementara catatan revisi Admin tetap tersimpan per revisi.
     */
    public function up(): void
    {
        Schema::table('design_revisions', function (Blueprint $table): void {
            $table->foreignId('order_id')->nullable()->after('id')->constrained('orders')->cascadeOnDelete();
            $table->index(['order_id', 'revisi_ke'], 'design_revisi_order_idx');
        });

        // Data lama tetap memiliki order_id agar riwayat tidak hilang.
        DB::statement(
            'UPDATE design_revisions
             INNER JOIN order_items ON order_items.id = design_revisions.order_item_id
             SET design_revisions.order_id = order_items.order_id
             WHERE design_revisions.order_id IS NULL'
        );

        // Preview level order tidak lagi terikat ke satu item.
        Schema::table('design_revisions', function (Blueprint $table): void {
            $table->foreignId('order_item_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Hapus log level order agar kolom order_item_id bisa kembali wajib.
        DB::table('design_revisions')->whereNull('order_item_id')->delete();

        Schema::table('design_revisions', function (Blueprint $table): void {
            $table->dropIndex('design_revisi_order_idx');
            $table->dropConstrainedForeignId('order_id');
        });

        Schema::table('design_revisions', function (Blueprint $table): void {
            $table->foreignId('order_item_id')->nullable(false)->change();
        });
    }
};
