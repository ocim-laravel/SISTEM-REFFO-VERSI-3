<?php

use App\Models\Instansi;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('instansi_id')
                ->nullable()
                ->after('id')
                ->constrained('instansis')
                ->nullOnDelete();
        });

        // Backfill: setiap instansi string lama mendapat satu baris instansis.
        $namaDefault = Instansi::DEFAULT;
        $this->pastikanInstansi($namaDefault);

        DB::table('customers')
            ->select('id', 'instansi')
            ->orderBy('id')
            ->each(function (object $customer): void {
                $nama = trim((string) $customer->instansi);
                $nama = $nama === '' ? Instansi::DEFAULT : $nama;
                $instansiId = $this->pastikanInstansi($nama);

                DB::table('customers')
                    ->where('id', $customer->id)
                    ->update(['instansi_id' => $instansiId, 'instansi' => $nama]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['instansi_id']);
            $table->dropColumn('instansi_id');
        });
    }

    private function pastikanInstansi(string $nama): int
    {
        $instansi = DB::table('instansis')->where('nama_instansi', $nama)->first();

        if ($instansi !== null) {
            return (int) $instansi->id;
        }

        return (int) DB::table('instansis')->insertGetId([
            'nama_instansi' => $nama,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
