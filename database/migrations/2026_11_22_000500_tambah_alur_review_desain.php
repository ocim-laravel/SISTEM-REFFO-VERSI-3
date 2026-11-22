<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alur desain dikendalikan Admin: terima job -> preview -> ACC/revisi -> TIFF + master.
     * Semua perubahan struktur dilakukan melalui migrasi baru, bukan mengubah migrasi lama.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('designer_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->timestamp('waktu_terima_desain')->nullable()->after('file_master_arsip');
            $table->timestamp('waktu_selesai_desain')->nullable()->after('waktu_terima_desain');
            $table->string('tanda_tangan_desainer')->nullable()->after('waktu_selesai_desain');
            $table->string('tanda_tangan_admin')->nullable()->after('tanda_tangan_desainer');
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->enum('status_produksi', [
                'menunggu_desain',
                'proses_desain',
                'menunggu_acc',
                'revisi',
                'antrean_cetak',
                'proses_cetak',
                'antrean_finishing',
                'proses_finishing',
                'siap_diambil',
                'selesai',
            ])->default('menunggu_desain')->change();

            $table->foreignId('acc_admin_id')->nullable()->after('status_produksi')->constrained('users')->nullOnDelete();
            $table->timestamp('waktu_acc')->nullable()->after('acc_admin_id');
        });

        Schema::table('design_revisions', function (Blueprint $table): void {
            $table->foreignId('admin_id')->nullable()->after('order_item_id')->constrained('users')->nullOnDelete();
            $table->enum('status_revisi', ['menunggu', 'selesai'])->nullable()->after('file_preview');
            $table->index(['order_item_id', 'status_revisi'], 'design_revisi_status_idx');
        });

        // Data lama yang sudah lolos desain dianggap telah disetujui agar tidak macet.
        $adminId = DB::table('users')->where('role', 'admin')->orderBy('id')->value('id');

        if ($adminId !== null) {
            DB::table('order_items')
                ->whereIn('status_produksi', [
                    'menunggu_acc', 'antrean_cetak', 'proses_cetak', 'antrean_finishing',
                    'proses_finishing', 'siap_diambil', 'selesai',
                ])
                ->whereNull('acc_admin_id')
                ->update([
                    'acc_admin_id' => $adminId,
                    'waktu_acc' => now(),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('design_revisions', function (Blueprint $table): void {
            $table->dropIndex('design_revisi_status_idx');
            $table->dropColumn(['status_revisi', 'admin_id']);
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn(['waktu_acc', 'acc_admin_id']);
            $table->enum('status_produksi', [
                'menunggu_desain',
                'proses_desain',
                'menunggu_acc',
                'antrean_cetak',
                'proses_cetak',
                'antrean_finishing',
                'proses_finishing',
                'siap_diambil',
                'selesai',
            ])->default('menunggu_desain')->change();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'tanda_tangan_admin',
                'tanda_tangan_desainer',
                'waktu_selesai_desain',
                'waktu_terima_desain',
                'designer_id',
            ]);
        });
    }
};
