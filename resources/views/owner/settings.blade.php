@extends('layouts.app')

@use('App\Support\FormatLaporan')
@use('App\Models\MaterialRoll')

@section('title', 'Pengaturan Master')
@section('section', 'Pengaturan Owner')
@section('page-title', 'Master Harga, Stok Roll & Finishing')

@section('content')
    <div class="space-y-6">
        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <p class="font-semibold">Periksa kembali input Anda:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Master harga --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Master Harga &amp; Harga Modal Produk</h3>
                    <p class="mt-0.5 text-[11px] text-slate-500">Perubahan langsung dipakai transaksi berikutnya. Harga modal menjadi dasar perhitungan HPP.</p>
                </div>
                <span class="text-[11px] text-slate-400">{{ $products->count() }} produk</span>
            </div>
            <form method="POST" action="{{ route('owner.settings.products') }}">
                @csrf
                @method('PUT')
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1000px] text-left text-sm">
                        <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Produk</th>
                                <th class="px-3 py-3 text-right font-semibold">Harga Umum</th>
                                <th class="px-3 py-3 text-right font-semibold">Reseller</th>
                                <th class="px-3 py-3 text-right font-semibold">Prioritas</th>
                                <th class="px-3 py-3 text-right font-semibold">Pegawai</th>
                                <th class="px-3 py-3 text-right font-semibold">Harga Modal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($products as $product)
                                @php
                                    $prefix = 'products.'.$product->id;
                                @endphp
                                <tr>
                                    <td class="px-4 py-2.5">
                                        <input type="hidden" name="products[{{ $product->id }}][id]" value="{{ $product->id }}">
                                        <p class="font-semibold text-slate-900">{{ $product->nama_produk }}</p>
                                        <p class="mt-0.5 text-[10px] uppercase tracking-wider text-slate-400">
                                            {{ $product->kategori_produk }}
                                            @if ($product->kategori_produk === \App\Models\Product::KATEGORI_SATUAN)
                                                · stok {{ (int) $product->stok_pcs }} pcs
                                            @endif
                                        </p>
                                    </td>
                                    @foreach (['harga_umum', 'harga_reseller', 'harga_prioritas', 'harga_pegawai', 'harga_modal'] as $kolom)
                                        <td class="px-3 py-2.5">
                                            <input
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                name="products[{{ $product->id }}][{{ $kolom }}]"
                                                value="{{ old($prefix.'.'.$kolom, $product->{$kolom}) }}"
                                                class="w-32 rounded-lg border border-slate-200 px-2.5 py-1.5 text-right text-xs focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                            >
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">Belum ada produk.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($products->isNotEmpty())
                    <div class="flex justify-end border-t border-slate-100 px-5 py-4">
                        <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700">
                            Simpan Semua Harga
                        </button>
                    </div>
                @endif
            </form>
        </section>

        <div class="grid gap-6 xl:grid-cols-2">
            {{-- Tambah roll --}}
            <section
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                x-data="{ jenis: '{{ old('jenis_bahan', MaterialRoll::JENIS_BAHAN[0]) }}' }"
            >
                <h3 class="text-sm font-bold text-slate-900">Tambah Stok Roll Baru</h3>
                <p class="mt-1 text-[11px] text-slate-500">Ukuran panjang &amp; lebar ditentukan otomatis sesuai jenis bahan.</p>
                <form method="POST" action="{{ route('owner.settings.rolls') }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label for="kode_roll" class="block text-[10px] font-semibold uppercase tracking-wider text-slate-400">Kode Roll</label>
                        <input type="text" id="kode_roll" name="kode_roll" value="{{ old('kode_roll') }}" required maxlength="50" placeholder="MMT280-001"
                            class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm uppercase focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    </div>
                    <div>
                        <label for="jenis_bahan" class="block text-[10px] font-semibold uppercase tracking-wider text-slate-400">Jenis Bahan</label>
                        <select id="jenis_bahan" name="jenis_bahan" x-model="jenis" required
                            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                            @foreach (MaterialRoll::JENIS_BAHAN as $jenisBahan)
                                <option value="{{ $jenisBahan }}" @selected(old('jenis_bahan', MaterialRoll::JENIS_BAHAN[0]) === $jenisBahan)>{{ $jenisBahan }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="rounded-xl bg-indigo-50 px-3 py-2.5 text-xs text-indigo-800 ring-1 ring-indigo-100">
                        Ukuran standar:
                        <span class="font-bold" x-text="jenis === 'Stiker 1m' ? '1 m × 50 m' : (jenis === 'Stiker 1.5m' ? '1,5 m × 50 m' : '3,2 m × 70 m')"></span>
                    </div>
                    <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800">
                        Tambah Roll
                    </button>
                </form>
            </section>

            {{-- Tambah stok pcs --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-bold text-slate-900">Tambah Stok Pcs Alat Satuan</h3>
                <p class="mt-1 text-[11px] text-slate-500">Menambah stok produk kategori satuan yang sudah terdaftar.</p>
                <form method="POST" action="{{ route('owner.settings.stok-pcs') }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label for="product_id" class="block text-[10px] font-semibold uppercase tracking-wider text-slate-400">Produk Satuan</label>
                        <select id="product_id" name="product_id" required
                            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                            @forelse ($produkSatuan as $produk)
                                <option value="{{ $produk->id }}" @selected(old('product_id') == $produk->id)>
                                    {{ $produk->nama_produk }} (stok {{ (int) $produk->stok_pcs }} pcs)
                                </option>
                            @empty
                                <option value="" disabled>Belum ada produk satuan</option>
                            @endforelse
                        </select>
                    </div>
                    <div>
                        <label for="jumlah" class="block text-[10px] font-semibold uppercase tracking-wider text-slate-400">Jumlah Tambahan (pcs)</label>
                        <input type="number" id="jumlah" name="jumlah" value="{{ old('jumlah') }}" required min="1" max="100000"
                            class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    </div>
                    <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800" @if ($produkSatuan->isEmpty()) disabled @endif>
                        Tambah Stok
                    </button>
                </form>
            </section>
        </div>

        {{-- Rumus finishing --}}
        <section class="overflow-hidden rounded-2xl border border-amber-200 bg-amber-50/40 shadow-sm">
            <div class="flex flex-col gap-2 border-b border-amber-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Rumus Ekstra Finishing (Internal Workshop)</h3>
                    <p class="mt-0.5 text-[11px] text-amber-700">Nilai ini tidak ditampilkan pada form input order Admin dan tidak dibebankan ke tagihan pelanggan.</p>
                </div>
                @php
                    $totalEkstra = $finishings->count();
                @endphp
                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-semibold text-amber-700">{{ $totalEkstra }} jenis finishing</span>
            </div>
            <form method="POST" action="{{ route('owner.settings.finishings') }}">
                @csrf
                @method('PUT')
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead class="bg-amber-100/60 text-[10px] uppercase tracking-wider text-amber-800">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Nama Finishing</th>
                                <th class="px-3 py-3 text-right font-semibold">Kiri (cm)</th>
                                <th class="px-3 py-3 text-right font-semibold">Kanan (cm)</th>
                                <th class="px-3 py-3 text-right font-semibold">Atas (cm)</th>
                                <th class="px-3 py-3 text-right font-semibold">Bawah (cm)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-amber-100">
                            @forelse ($finishings as $finishing)
                                @php
                                    $prefix = 'finishings.'.$finishing->id;
                                @endphp
                                <tr>
                                    <td class="px-4 py-2.5">
                                        <input type="hidden" name="finishings[{{ $finishing->id }}][id]" value="{{ $finishing->id }}">
                                        <p class="font-semibold text-slate-900">{{ $finishing->nama_finishing }}</p>
                                    </td>
                                    @foreach (['ekstra_kiri_cm', 'ekstra_kanan_cm', 'ekstra_atas_cm', 'ekstra_bawah_cm'] as $kolom)
                                        <td class="px-3 py-2.5">
                                            <input
                                                type="number"
                                                min="0"
                                                max="999.99"
                                                step="0.01"
                                                name="finishings[{{ $finishing->id }}][{{ $kolom }}]"
                                                value="{{ old($prefix.'.'.$kolom, $finishing->{$kolom}) }}"
                                                class="w-24 rounded-lg border border-amber-200 bg-white px-2.5 py-1.5 text-right text-xs focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100"
                                            >
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">Belum ada data finishing.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($finishings->isNotEmpty())
                    <div class="flex justify-end border-t border-amber-200 px-5 py-4">
                        <button type="submit" class="rounded-xl bg-amber-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-amber-700">
                            Simpan Rumus Finishing
                        </button>
                    </div>
                @endif
            </form>
        </section>
    </div>
@endsection
