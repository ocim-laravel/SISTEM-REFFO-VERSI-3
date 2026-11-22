<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu file master desain disimpan di level nota, bukan per item.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('file_master_arsip')->nullable()->after('lampiran_briefing');
        });

        // Data lama menyimpan master di item; pindahkan sebagai master nota.
        DB::table('orders')
            ->whereNull('file_master_arsip')
            ->update([
                'file_master_arsip' => DB::raw(
                    '(select order_items.file_master_arsip from order_items '
                    .'where order_items.order_id = orders.id and order_items.file_master_arsip is not null '
                    .'order by order_items.id limit 1)'
                ),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('file_master_arsip');
        });
    }
};
