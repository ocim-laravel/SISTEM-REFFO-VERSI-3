@extends('layouts.app')

@use('App\Support\RiwayatSelesai')

@section('title', 'Riwayat Selesai '.$judulDivisi)
@section('section', 'Audit Divisi')
@section('page-title', 'Riwayat Selesai '.$judulDivisi)

@section('content')
    <div class="space-y-6">
        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-800 via-slate-900 to-indigo-950 px-6 py-7 text-white shadow-xl sm:px-8">
            <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-indigo-500/20 blur-3xl"></div>
            <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-indigo-300">Audit {{ $judulDivisi }}</p>
                    <h2 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Riwayat Selesai</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">
                        Order yang sudah diproses divisi {{ $judulDivisi }}. Satu baris mewakili satu nota; klik Detail untuk melihat rincian item.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route($dashboardRoute) }}" class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2.5 text-xs font-semibold text-white ring-1 ring-white/15 transition hover:bg-white/20">
                        Kembali ke Dashboard
                    </a>
                    <span class="inline-flex items-center rounded-xl bg-indigo-500 px-4 py-2.5 text-xs font-bold text-white">
                        {{ $orders->total() }} nota
                    </span>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Daftar Order Selesai</h3>
                    <p class="mt-0.5 text-[11px] text-slate-500">Menampilkan instansi dan PIC pada setiap nota.</p>
                </div>
                <form method="GET" action="{{ route($divisi.'.riwayat') }}" class="flex flex-wrap gap-2">
                    <label for="q" class="sr-only">Cari nota</label>
                    <input
                        id="q"
                        name="q"
                        type="search"
                        value="{{ $q }}"
                        placeholder="Cari no. nota / instansi / PIC"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 sm:w-72"
                    >
                    <button type="submit" class="h-10 rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-800">Cari</button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-semibold">No. Nota</th>
                            <th class="px-3 py-3 font-semibold">Instansi / PIC</th>
                            @if ($divisi === RiwayatSelesai::ADVERTISING)
                                <th class="px-3 py-3 font-semibold">Pekerjaan Selesai</th>
                            @else
                                <th class="px-3 py-3 font-semibold">Item Diproses</th>
                            @endif
                            <th class="px-3 py-3 font-semibold">Status Produksi</th>
                            <th class="px-3 py-3 font-semibold">Tanggal</th>
                            <th class="px-5 py-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($orders as $order)
                            @php
                                $itemSelesai = $order->relationLoaded('riwayatItems')
                                    ? $order->getRelation('riwayatItems')
                                    : collect();
                                $spkSelesai = $divisi === RiwayatSelesai::ADVERTISING
                                    ? RiwayatSelesai::spkSelesai($order)
                                    : collect();
                            @endphp
                            <tr class="align-top transition hover:bg-slate-50/70">
                                <td class="px-5 py-4">
                                    <p class="font-mono text-xs font-bold text-indigo-600">{{ $order->no_nota }}</p>
                                    <p class="mt-0.5 text-[10px] text-slate-400">{{ $order->items_count }} item pada nota</p>
                                </td>
                                <td class="px-3 py-4">
                                    <p class="font-semibold text-slate-800">{{ $order->instansiLabel() }}</p>
                                    <p class="mt-0.5 text-[11px] text-slate-500">PIC: {{ $order->picLabel() }}</p>
                                </td>
                                @if ($divisi === RiwayatSelesai::ADVERTISING)
                                    <td class="px-3 py-4">
                                        @forelse ($spkSelesai as $spk)
                                            <p class="max-w-[16rem] text-xs font-semibold text-slate-700">{{ $spk->jenis_pekerjaan }}</p>
                                            <p class="mt-0.5 text-[11px] text-slate-500">{{ $spk->lokasi_pasang }}</p>
                                        @empty
                                            <span class="text-xs text-slate-400">-</span>
                                        @endforelse
                                    </td>
                                @else
                                    <td class="px-3 py-4">
                                        <p class="max-w-[18rem] text-xs text-slate-600">
                                            {{ $itemSelesai->map(fn ($item) => $item->product?->nama_produk)->filter()->implode(', ') ?: '-' }}
                                        </p>
                                        <p class="mt-0.5 text-[10px] font-semibold text-slate-400">{{ $itemSelesai->count() }} item selesai</p>
                                    </td>
                                @endif
                                <td class="px-3 py-4">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold text-slate-600">{{ $order->statusProduksiRingkas() }}</span>
                                </td>
                                <td class="px-3 py-4 text-xs text-slate-500">{{ $order->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route($divisi.'.orders.show', $order) }}" class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-1.5 text-[11px] font-semibold text-white transition hover:bg-slate-800">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500">
                                    Belum ada order yang selesai diproses divisi {{ $judulDivisi }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($orders->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">{{ $orders->links() }}</div>
            @endif
        </section>
    </div>
@endsection
