@extends('layouts.app')

@use('App\Support\FormatLaporan')

@section('title', 'Laporan Stok & Audit Bahan')
@section('section', 'Laporan Owner')
@section('page-title', 'Laporan Stok & Audit Penggunaan Bahan')

@section('content')
    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Filter Periode Audit</h3>
                    <p class="mt-1 text-[11px] text-slate-500">Rekap pemakaian bahan dihitung dari tanggal pencatatan log bahan.</p>
                </div>
                @include('owner.partials.periode', ['periode' => $periode])
            </div>
        </section>

        {{-- Rekap audit 4 komponen --}}
        <section>
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">Rekapitulasi Audit Bahan</h3>
                <span class="text-[11px] text-slate-500">{{ $ringkasan['jumlah_log'] }} catatan log</span>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                @foreach ([
                    ['label' => 'Bahan Terpakai Netto', 'key' => 'netto', 'warna' => 'bg-indigo-500'],
                    ['label' => 'Wos Finishing', 'key' => 'finishing', 'warna' => 'bg-amber-500'],
                    ['label' => 'Wos Potong Roll', 'key' => 'potong', 'warna' => 'bg-violet-500'],
                    ['label' => 'Bahan Gagal Cetak', 'key' => 'gagal', 'warna' => 'bg-rose-500'],
                ] as $item)
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full {{ $item['warna'] }}"></span>
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ $item['label'] }}</p>
                        </div>
                        <p class="mt-3 text-2xl font-bold text-slate-900">{{ FormatLaporan::meter($ringkasan[$item['key']]) }}</p>
                        <p class="mt-1 text-[11px] text-slate-500">m²</p>
                    </div>
                @endforeach
                <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-indigo-500">Total Luas Fisik</p>
                    <p class="mt-3 text-2xl font-bold text-indigo-900">{{ FormatLaporan::meter($ringkasan['total']) }}</p>
                    <p class="mt-1 text-[11px] text-indigo-600">m² (netto + wos + gagal)</p>
                </div>
            </div>
        </section>

        {{-- Stok roll aktif --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-sm font-bold text-slate-900">Stok Roll Aktif</h3>
                <p class="mt-0.5 text-[11px] text-slate-500">Sisa meter dan persentase sisa terhadap panjang awal roll.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-left text-sm">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Kode Roll</th>
                            <th class="px-5 py-3 font-semibold">Jenis Bahan</th>
                            <th class="px-5 py-3 text-right font-semibold">Lebar</th>
                            <th class="px-5 py-3 text-right font-semibold">Panjang Awal</th>
                            <th class="px-5 py-3 text-right font-semibold">Sisa Meter</th>
                            <th class="px-5 py-3 text-right font-semibold">Sisa Luas</th>
                            <th class="px-5 py-3 text-right font-semibold">% Sisa Roll</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rolls as $roll)
                            <tr class="text-slate-700">
                                <td class="px-5 py-3 font-mono text-xs font-semibold text-slate-900">{{ $roll['kode_roll'] }}</td>
                                <td class="px-5 py-3">{{ $roll['jenis_bahan'] }}</td>
                                <td class="px-5 py-3 text-right">{{ FormatLaporan::meter($roll['lebar_m']) }} m</td>
                                <td class="px-5 py-3 text-right">{{ FormatLaporan::meter($roll['panjang_awal_m']) }} m</td>
                                <td class="px-5 py-3 text-right font-semibold">{{ FormatLaporan::meter($roll['panjang_sisa_m']) }} m</td>
                                <td class="px-5 py-3 text-right">{{ FormatLaporan::meter($roll['luas_sisa_m2']) }} m²</td>
                                <td class="px-5 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="h-1.5 w-16 overflow-hidden rounded-full bg-slate-100">
                                            <div class="h-full rounded-full {{ $roll['persen_sisa'] <= 20 ? 'bg-rose-500' : 'bg-emerald-500' }}" style="width: {{ min($roll['persen_sisa'], 100) }}%"></div>
                                        </div>
                                        <span class="w-12 text-right text-xs font-semibold">{{ FormatLaporan::persen($roll['persen_sisa']) }}</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada roll aktif.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            {{-- Offcut siap pakai --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-sm font-bold text-slate-900">Stok Potongan Baru</h3>
                    <p class="mt-0.5 text-[11px] text-slate-500">Material offcut baru dengan status tersedia dan siap pakai.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[520px] text-left text-sm">
                        <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Jenis Bahan</th>
                                <th class="px-5 py-3 text-right font-semibold">Lebar</th>
                                <th class="px-5 py-3 text-right font-semibold">Panjang</th>
                                <th class="px-5 py-3 text-right font-semibold">Luas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($offcuts as $offcut)
                                <tr class="text-slate-700">
                                    <td class="px-5 py-3">
                                        <p class="font-semibold">{{ $offcut->jenis_bahan }}</p>
                                        <p class="mt-0.5 font-mono text-[10px] text-slate-400">{{ $offcut->materialRoll?->kode_roll ?? 'Tanpa roll induk' }}</p>
                                    </td>
                                    <td class="px-5 py-3 text-right">{{ FormatLaporan::meter($offcut->lebar_m) }} m</td>
                                    <td class="px-5 py-3 text-right">{{ FormatLaporan::meter($offcut->panjang_m) }} m</td>
                                    <td class="px-5 py-3 text-right font-semibold">{{ FormatLaporan::meter($offcut->luas_m2) }} m²</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-5 py-10 text-center text-sm text-slate-500">Tidak ada stok potongan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Stok pcs satuan --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-sm font-bold text-slate-900">Stok Pcs Produk Satuan</h3>
                    <p class="mt-0.5 text-[11px] text-slate-500">X-Banner, Roll Banner, Poster, Stiker A3, A-Board, dll.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[420px] text-left text-sm">
                        <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Produk</th>
                                <th class="px-5 py-3 text-right font-semibold">Sisa Stok</th>
                                <th class="px-5 py-3 text-right font-semibold">Harga Modal /pcs</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($stokPcs as $produk)
                                <tr class="text-slate-700">
                                    <td class="px-5 py-3 font-semibold">{{ $produk->nama_produk }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <span class="rounded-lg px-2 py-1 text-xs font-bold {{ (int) $produk->stok_pcs <= 5 ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                                            {{ (int) $produk->stok_pcs }} pcs
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-right">{{ FormatLaporan::rupiah($produk->harga_modal) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada produk satuan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        {{-- Detail log audit --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-sm font-bold text-slate-900">Detail Log Penggunaan Bahan</h3>
                <p class="mt-0.5 text-[11px] text-slate-500">Periode {{ $periode->dari }} s/d {{ $periode->sampai }}.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-xs">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Waktu</th>
                            <th class="px-5 py-3 font-semibold">Nota / Produk</th>
                            <th class="px-5 py-3 font-semibold">Sumber</th>
                            <th class="px-5 py-3 text-right font-semibold">Netto</th>
                            <th class="px-5 py-3 text-right font-semibold">Wos Finishing</th>
                            <th class="px-5 py-3 text-right font-semibold">Wos Potong</th>
                            <th class="px-5 py-3 text-right font-semibold">Gagal Cetak</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($logs as $log)
                            <tr class="text-slate-600">
                                <td class="px-5 py-3 whitespace-nowrap">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-5 py-3">
                                    <p class="font-semibold text-slate-800">{{ $log->orderItem?->order?->no_nota ?? '-' }}</p>
                                    <p class="mt-0.5 text-[10px] text-slate-400">{{ $log->orderItem?->product?->nama_produk ?? '-' }}</p>
                                </td>
                                <td class="px-5 py-3">{{ $log->materialRoll?->kode_roll ?? ($log->materialOffcut ? 'Potongan #'.$log->materialOffcut->id : '-') }}</td>
                                <td class="px-5 py-3 text-right">{{ FormatLaporan::meter($log->luas_netto_m2) }}</td>
                                <td class="px-5 py-3 text-right">{{ FormatLaporan::meter($log->wos_finishing_m2) }}</td>
                                <td class="px-5 py-3 text-right">{{ FormatLaporan::meter($log->wos_potong_m2) }}</td>
                                <td class="px-5 py-3 text-right">{{ FormatLaporan::meter($log->gagal_cetak_m2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">Tidak ada log pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($logs->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">{{ $logs->links() }}</div>
            @endif
        </section>
    </div>
@endsection
