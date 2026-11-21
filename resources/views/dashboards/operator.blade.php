@extends('layouts.app')

@section('title', 'Dashboard Operator Cetak')
@section('section', 'Divisi Produksi')
@section('page-title', 'Dashboard Operator Cetak')

@section('content')
    <div class="space-y-6" x-data="{ items: @js($itemData), active: null, mode: null, sumber: 'roll' }">
        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-amber-500 via-orange-600 to-rose-700 px-6 py-7 text-white shadow-xl shadow-orange-900/10 sm:px-8">
            <div class="pointer-events-none absolute -right-20 -top-24 h-64 w-64 rounded-full bg-white/10 blur-2xl"></div>
            <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-amber-100">Workspace Produksi Cetak</p>
                    <h2 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Antrean Cetak</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-orange-100">
                        Unduh file TIFF, potong stok roll/offcut dengan pencatatan log bahan, lalu teruskan ke finishing bila diperlukan.
                    </p>
                </div>
                <div class="shrink-0 rounded-xl bg-white/10 px-4 py-3 text-right ring-1 ring-white/15 backdrop-blur">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-amber-100">Item aktif</p>
                    <p class="mt-1 text-2xl font-bold">{{ $items->total() }}</p>
                </div>
            </div>
        </section>

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <p class="font-semibold">Periksa kembali input Anda:</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-5 lg:grid-cols-2">
            @forelse ($items as $item)
                @php
                    $customer = $item->order?->customer;
                    $kotor = $item->isMeteran() ? $item->ukuranKotor() : null;
                    $rekomendasi = $item->rekomendasiRollStiker();
                @endphp
                <article class="flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider ring-1 {{ $item->status_produksi === \App\Models\OrderItem::STATUS_PROSES_CETAK ? 'bg-amber-50 text-amber-700 ring-amber-100' : 'bg-sky-50 text-sky-700 ring-sky-100' }}">
                                    {{ $item->statusProduksiLabel() }}
                                </span>
                                <span class="text-xs font-semibold text-slate-500">{{ $item->order?->no_nota }}</span>
                            </div>
                            <h3 class="mt-2 text-lg font-bold text-slate-900">{{ $item->product?->nama_produk }}</h3>
                            <p class="mt-1 text-sm text-slate-600">{{ $customer?->nama_pelanggan }} — {{ $customer?->instansi }}</p>
                        </div>
                        <span class="shrink-0 rounded-lg bg-slate-100 px-3 py-2 text-center">
                            <span class="block text-[10px] font-semibold uppercase text-slate-400">Qty</span>
                            <span class="block text-lg font-bold text-slate-800">{{ $item->qty }}</span>
                        </span>
                    </div>

                    <div class="mt-4 rounded-xl bg-slate-50 p-4 text-sm ring-1 ring-slate-100">
                        @if ($kotor)
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Ukuran Netto</p>
                                    <p class="mt-1 font-semibold text-slate-700">
                                        {{ number_format((float) $item->lebar_m, 2, ',', '.') }} x {{ number_format((float) $item->tinggi_m, 2, ',', '.') }} m
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Ukuran Kotor (finishing)</p>
                                    <p class="mt-1 font-bold text-amber-700">
                                        {{ number_format($kotor['lebar'], 2, ',', '.') }} x {{ number_format($kotor['tinggi'], 2, ',', '.') }} m
                                    </p>
                                </div>
                            </div>
                            @if ($item->finishing)
                                <p class="mt-3 text-xs text-slate-600">Finishing: <span class="font-semibold">{{ $item->finishing->nama_finishing }}</span> (ekstra hanya memotong stok, tidak ditagihkan)</p>
                            @endif
                            @if ($rekomendasi)
                                <p class="mt-2 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                    Rekomendasi roll otomatis: {{ $rekomendasi }}
                                </p>
                            @endif
                        @else
                            <p class="text-sm text-slate-600">Produk satuan &mdash; tanpa pemotongan roll. Stok tersedia:
                                <span class="font-semibold">{{ $item->product?->stok_pcs ?? '-' }} pcs</span>
                            </p>
                        @endif
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($item->file_cetak_tiff)
                            <a href="{{ route('operator.items.tiff', $item) }}"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                                Unduh File .TIFF
                            </a>
                        @else
                            <span class="rounded-lg bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-600">File TIFF belum tersedia</span>
                        @endif

                        @if ($item->status_produksi === \App\Models\OrderItem::STATUS_ANTREAN_CETAK)
                            <form method="POST" action="{{ route('operator.items.mulai', $item) }}">
                                @csrf
                                <button type="submit" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Mulai Cetak</button>
                            </form>
                        @endif

                        <button type="button"
                            @click="active = items[{{ $item->id }}]; mode = 'selesai'; sumber = 'roll'"
                            class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                            Selesai Cetak
                        </button>
                        <button type="button"
                            @click="active = items[{{ $item->id }}]; mode = 'gagal'"
                            class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                            Lapor Gagal Cetak
                        </button>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center lg:col-span-2">
                    <p class="text-sm font-semibold text-slate-700">Tidak ada item pada antrean cetak.</p>
                    <p class="mt-1 text-xs text-slate-500">Item yang telah difinalisasi desainer akan muncul di sini.</p>
                </div>
            @endforelse
        </div>

        {{ $items->links() }}

        {{-- Modal aksi produksi --}}
        <div x-show="active" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" @click="active = null"></div>
            <div class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900" x-text="mode === 'selesai' ? 'Selesai Cetak' : 'Lapor Gagal Cetak'"></h3>
                        <p class="mt-1 text-sm text-slate-500" x-text="active ? active.no_nota + ' — ' + active.produk : ''"></p>
                    </div>
                    <button type="button" @click="active = null" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Tutup">&times;</button>
                </div>

                {{-- Form Selesai Cetak: meteran --}}
                <template x-if="active && mode === 'selesai' && active.kotor">
                    <form :action="active.url_selesai" method="POST" class="mt-5 space-y-4">
                        @csrf
                        <div class="grid grid-cols-2 gap-3 rounded-xl bg-amber-50 p-4 text-sm ring-1 ring-amber-100">
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-amber-700">Lebar Kotor</p>
                                <p class="mt-1 text-lg font-bold text-amber-900" x-text="active.kotor.lebar.toFixed(2) + ' m'"></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-amber-700">Tinggi Kotor</p>
                                <p class="mt-1 text-lg font-bold text-amber-900" x-text="active.kotor.tinggi.toFixed(2) + ' m'"></p>
                            </div>
                        </div>

                        <p x-show="active.rekomendasi" class="rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">
                            Rekomendasi roll: <span x-text="active.rekomendasi"></span>
                        </p>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700" for="sumber_bahan">Sumber Bahan</label>
                            <select id="sumber_bahan" name="sumber_bahan" x-model="sumber" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                <option value="roll">Roll Utama (material_rolls)</option>
                                <option value="offcut">Stok Bahan Potongan (material_offcuts)</option>
                            </select>
                        </div>

                        <div x-show="sumber === 'roll'">
                            <label class="block text-xs font-semibold text-slate-700" for="material_roll_id">Roll Aktif</label>
                            <select id="material_roll_id" name="material_roll_id" :required="sumber === 'roll'" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                <template x-for="roll in active.rolls" :key="roll.id">
                                    <option :value="roll.id"
                                        :selected="active.rekomendasi && roll.jenis === active.rekomendasi"
                                        x-text="roll.kode + ' — ' + roll.jenis + ' | lebar ' + roll.lebar.toFixed(2) + ' m | sisa ' + roll.sisa.toFixed(2) + ' m'"></option>
                                </template>
                            </select>
                            <p x-show="active.rolls.length === 0" class="mt-1 text-xs text-rose-600">Tidak ada roll aktif.</p>
                        </div>

                        <div x-show="sumber === 'offcut'">
                            <label class="block text-xs font-semibold text-slate-700" for="material_offcut_id">Stok Potongan Tersedia</label>
                            <select id="material_offcut_id" name="material_offcut_id" :required="sumber === 'offcut'" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                <template x-for="offcut in active.offcuts" :key="offcut.id">
                                    <option :value="offcut.id"
                                        x-text="offcut.jenis + ' | ' + offcut.lebar.toFixed(2) + ' x ' + offcut.panjang.toFixed(2) + ' m (' + offcut.luas.toFixed(2) + ' m2)'"></option>
                                </template>
                            </select>
                            <p x-show="active.offcuts.length === 0" class="mt-1 text-xs text-rose-600">Tidak ada stok potongan tersedia.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700" for="orientasi_potong">Orientasi Potong</label>
                            <select id="orientasi_potong" name="orientasi_potong" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                <option value="panjang">Tinggi kotor mengikuti panjang roll</option>
                                <option value="lebar">Lebar kotor mengikuti panjang roll (diputar)</option>
                            </select>
                        </div>

                        <p class="text-xs leading-5 text-slate-500">
                            Netto dan WOS finishing dicatat otomatis. Sisa lebar &ge; 0,50 m disimpan sebagai stok potongan; di bawah itu dicatat sebagai WOS potong.
                        </p>

                        <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
                            Simpan Selesai Cetak
                        </button>
                    </form>
                </template>

                {{-- Form Selesai Cetak: satuan --}}
                <template x-if="active && mode === 'selesai' && !active.kotor">
                    <form :action="active.url_selesai" method="POST" class="mt-5 space-y-4">
                        @csrf
                        <p class="rounded-xl bg-slate-50 p-4 text-sm leading-6 text-slate-600 ring-1 ring-slate-100">
                            Produk satuan tidak memotong roll. Stok akan dikurangi
                            <span class="font-bold text-slate-800" x-text="active.qty"></span> pcs dan status menjadi siap diambil.
                        </p>
                        <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
                            Konfirmasi Selesai Cetak
                        </button>
                    </form>
                </template>

                {{-- Form Lapor Gagal Cetak --}}
                <template x-if="active && mode === 'gagal'">
                    <form :action="active.url_gagal" method="POST" class="mt-5 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-slate-700" for="gagal_roll">Roll Aktif</label>
                            <select id="gagal_roll" name="material_roll_id" required class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                <template x-for="roll in active.rolls" :key="roll.id">
                                    <option :value="roll.id"
                                        x-text="roll.kode + ' — ' + roll.jenis + ' | sisa ' + roll.sisa.toFixed(2) + ' m'"></option>
                                </template>
                            </select>
                            <p x-show="active.rolls.length === 0" class="mt-1 text-xs text-rose-600">Tidak ada roll aktif.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700" for="panjang_rusak_m">Panjang Bahan Rusak (meter)</label>
                            <input id="panjang_rusak_m" type="number" name="panjang_rusak_m" step="0.01" min="0.01" required
                                placeholder="0.00" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700" for="keterangan_gagal">Keterangan (opsional)</label>
                            <input id="keterangan_gagal" type="text" name="keterangan" maxlength="255"
                                placeholder="Contoh: mesin tersendat" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                        </div>
                        <p class="text-xs leading-5 text-slate-500">Luas rusak dicatat ke <span class="font-mono">gagal_cetak_m2</span> dan tidak mengubah tagihan pelanggan.</p>
                        <button type="submit" class="w-full rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-700">
                            Simpan Laporan Gagal Cetak
                        </button>
                    </form>
                </template>
            </div>
        </div>
    </div>
@endsection
