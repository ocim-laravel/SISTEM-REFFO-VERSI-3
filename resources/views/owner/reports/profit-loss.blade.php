@extends('layouts.app')

@use('App\Support\FormatLaporan')

@section('title', 'Laporan Laba Rugi')
@section('section', 'Laporan Owner')
@section('page-title', 'Laporan Laba Rugi & Biaya Operasional')

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

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Filter Periode Laporan</h3>
                    <p class="mt-1 text-[11px] text-slate-500">Omzet mengikuti tanggal nota, HPP mengikuti tanggal log/stok terjual, biaya operasional mengikuti tanggal pengeluaran.</p>
                </div>
                @include('owner.partials.periode', ['periode' => $periode])
            </div>
        </section>

        {{-- Kartu laba rugi --}}
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Total Omzet Penjualan</p>
                <p class="mt-3 text-2xl font-bold text-slate-900">{{ FormatLaporan::rupiah($omzet['tagihan']) }}</p>
                <p class="mt-2 text-[11px] text-slate-500">Diterima: <span class="font-semibold text-emerald-600">{{ FormatLaporan::rupiah($omzet['diterima']) }}</span></p>
                <p class="mt-1 text-[11px] text-slate-500">Piutang: <span class="font-semibold text-rose-600">{{ FormatLaporan::rupiah($omzet['piutang']) }}</span></p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Total HPP Produksi</p>
                <p class="mt-3 text-2xl font-bold text-slate-900">{{ FormatLaporan::rupiah($hppProduksi) }}</p>
                <p class="mt-2 text-[11px] text-slate-500">Bahan roll: <span class="font-semibold text-slate-700">{{ FormatLaporan::rupiah($hppBahan) }}</span></p>
                <p class="mt-1 text-[11px] text-slate-500">Produk satuan: <span class="font-semibold text-slate-700">{{ FormatLaporan::rupiah($hppSatuan) }}</span></p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Total Biaya Operasional</p>
                <p class="mt-3 text-2xl font-bold text-slate-900">{{ FormatLaporan::rupiah($totalOperasional) }}</p>
                <p class="mt-2 text-[11px] text-slate-500">{{ $expenses->total() }} transaksi pengeluaran pada periode ini.</p>
            </div>
            <div class="rounded-2xl border p-5 shadow-sm {{ $labaBersih >= 0 ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }} sm:col-span-2 xl:col-span-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-wider {{ $labaBersih >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">Laba Bersih</p>
                        <p class="mt-2 text-3xl font-bold tracking-tight {{ $labaBersih >= 0 ? 'text-emerald-900' : 'text-rose-900' }}">{{ FormatLaporan::rupiah($labaBersih) }}</p>
                    </div>
                    <p class="text-xs {{ $labaBersih >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">Omzet − HPP Produksi − Biaya Operasional</p>
                </div>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-2">
            {{-- Rincian omzet --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-bold text-slate-900">Rincian Omzet &amp; Piutang</h3>
                <div class="mt-4 divide-y divide-slate-100">
                    <div class="flex items-center justify-between py-3 text-sm">
                        <span class="text-slate-500">Pembayaran lunas masuk</span>
                        <span class="font-semibold text-slate-900">{{ FormatLaporan::rupiah($omzet['lunas']) }}</span>
                    </div>
                    <div class="flex items-center justify-between py-3 text-sm">
                        <span class="text-slate-500">DP masuk</span>
                        <span class="font-semibold text-slate-900">{{ FormatLaporan::rupiah($omzet['dp']) }}</span>
                    </div>
                    <div class="flex items-center justify-between py-3 text-sm">
                        <span class="font-semibold text-slate-700">Total pembayaran diterima</span>
                        <span class="font-bold text-emerald-700">{{ FormatLaporan::rupiah($omzet['diterima']) }}</span>
                    </div>
                    <div class="flex items-center justify-between py-3 text-sm">
                        <span class="text-slate-500">Piutang belum lunas</span>
                        <span class="font-bold text-rose-700">{{ FormatLaporan::rupiah($omzet['piutang']) }}</span>
                    </div>
                </div>
            </section>

            {{-- Rincian HPP --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-sm font-bold text-slate-900">Rincian HPP Produksi</h3>
                    <p class="mt-0.5 text-[11px] text-slate-500">Seluruh pemakaian riil dikalikan harga modal produk terkait.</p>
                </div>
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-2.5 font-semibold">Komponen Bahan</th>
                            <th class="px-5 py-2.5 text-right font-semibold">Luas (m²)</th>
                            <th class="px-5 py-2.5 text-right font-semibold">HPP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ([
                            ['label' => 'Luas Netto', 'key' => 'netto'],
                            ['label' => 'Wos Finishing', 'key' => 'finishing'],
                            ['label' => 'Wos Potong Roll', 'key' => 'potong'],
                            ['label' => 'Gagal Cetak', 'key' => 'gagal'],
                        ] as $komponen)
                            <tr class="text-slate-600">
                                <td class="px-5 py-2.5">{{ $komponen['label'] }}</td>
                                <td class="px-5 py-2.5 text-right">{{ FormatLaporan::meter($komponenHpp[$komponen['key']]['luas']) }}</td>
                                <td class="px-5 py-2.5 text-right">{{ FormatLaporan::rupiah($komponenHpp[$komponen['key']]['biaya']) }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-slate-50 font-semibold text-slate-900">
                            <td class="px-5 py-3">Subtotal HPP Bahan</td>
                            <td class="px-5 py-3 text-right">—</td>
                            <td class="px-5 py-3 text-right">{{ FormatLaporan::rupiah($hppBahan) }}</td>
                        </tr>
                        <tr class="text-slate-600">
                            <td class="px-5 py-2.5">Produk satuan terjual (qty × modal)</td>
                            <td class="px-5 py-2.5 text-right">—</td>
                            <td class="px-5 py-2.5 text-right">{{ FormatLaporan::rupiah($hppSatuan) }}</td>
                        </tr>
                        <tr class="bg-indigo-50 font-bold text-indigo-900">
                            <td class="px-5 py-3">Total HPP Produksi</td>
                            <td class="px-5 py-3 text-right">—</td>
                            <td class="px-5 py-3 text-right">{{ FormatLaporan::rupiah($hppProduksi) }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            {{-- Form pengeluaran --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-1">
                <h3 class="text-sm font-bold text-slate-900">Input Pengeluaran Operasional</h3>
                <p class="mt-1 text-[11px] text-slate-500">Catat pengeluaran harian/bulanan workshop.</p>
                <form method="POST" action="{{ route('owner.expenses.store') }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label for="tanggal" class="block text-[10px] font-semibold uppercase tracking-wider text-slate-400">Tanggal</label>
                        <input type="date" id="tanggal" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}" required
                            class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    </div>
                    <div>
                        <label for="kategori_biaya" class="block text-[10px] font-semibold uppercase tracking-wider text-slate-400">Kategori Biaya</label>
                        <select id="kategori_biaya" name="kategori_biaya" required
                            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                            @foreach ($kategoriBiaya as $kategori)
                                <option value="{{ $kategori }}" @selected(old('kategori_biaya') === $kategori)>{{ $kategori }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="keterangan" class="block text-[10px] font-semibold uppercase tracking-wider text-slate-400">Keterangan</label>
                        <input type="text" id="keterangan" name="keterangan" value="{{ old('keterangan') }}" required maxlength="255" placeholder="Contoh: Gaji operator Maret"
                            class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    </div>
                    <div>
                        <label for="nominal" class="block text-[10px] font-semibold uppercase tracking-wider text-slate-400">Nominal (Rp)</label>
                        <input type="number" id="nominal" name="nominal" value="{{ old('nominal') }}" required min="0.01" step="0.01" placeholder="0.00"
                            class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    </div>
                    <button type="submit" class="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700">
                        Simpan Pengeluaran
                    </button>
                </form>
            </section>

            {{-- Riwayat pengeluaran --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Riwayat Pengeluaran</h3>
                        <p class="mt-0.5 text-[11px] text-slate-500">Periode {{ $periode->dari }} s/d {{ $periode->sampai }}.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold text-slate-600">{{ FormatLaporan::rupiah($totalOperasional) }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[620px] text-left text-sm">
                        <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Tanggal</th>
                                <th class="px-5 py-3 font-semibold">Kategori</th>
                                <th class="px-5 py-3 font-semibold">Keterangan</th>
                                <th class="px-5 py-3 text-right font-semibold">Nominal</th>
                                <th class="px-5 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($expenses as $expense)
                                <tr class="text-slate-700">
                                    <td class="px-5 py-3 whitespace-nowrap">{{ $expense->tanggal?->format('d/m/Y') }}</td>
                                    <td class="px-5 py-3"><span class="rounded-lg bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600">{{ $expense->kategori_biaya }}</span></td>
                                    <td class="px-5 py-3">{{ $expense->keterangan }}</td>
                                    <td class="px-5 py-3 text-right font-semibold">{{ FormatLaporan::rupiah($expense->nominal) }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <form method="POST" action="{{ route('owner.expenses.destroy', $expense) }}" onsubmit="return confirm('Hapus pengeluaran ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg px-2 py-1 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada pengeluaran pada periode ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($expenses->hasPages())
                    <div class="border-t border-slate-100 px-5 py-4">{{ $expenses->links() }}</div>
                @endif
            </section>
        </div>
    </div>
@endsection
