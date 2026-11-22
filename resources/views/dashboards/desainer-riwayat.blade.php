@extends('layouts.app')

@section('title', 'Riwayat Desain')
@section('section', 'Audit Divisi')
@section('page-title', 'Riwayat Desain')

@section('content')
    <div class="space-y-6">
        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-800 via-slate-900 to-indigo-950 px-6 py-7 text-white shadow-xl sm:px-8">
            <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-fuchsia-500/20 blur-3xl"></div>
            <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-fuchsia-300">Audit Desainer</p>
                    <h2 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Riwayat Desain</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">
                        Nota yang desainnya selesai: seluruh file TIFF per item dan satu master desain sudah terunggah.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route($dashboardRoute) }}" class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2.5 text-xs font-semibold text-white ring-1 ring-white/15 transition hover:bg-white/20">
                        Kembali ke Dashboard
                    </a>
                    <span class="inline-flex items-center rounded-xl bg-fuchsia-500 px-4 py-2.5 text-xs font-bold text-white">{{ $orders->total() }} nota</span>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Daftar Order Selesai Desain</h3>
                    <p class="mt-0.5 text-[11px] text-slate-500">Tanda tangan digital dan lama proses dihitung sejak job diterima.</p>
                </div>
                <form method="GET" action="{{ route('desainer.riwayat') }}" class="flex flex-wrap gap-2">
                    <input type="search" name="q" value="{{ $q }}" placeholder="Cari nota / instansi / PIC"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm outline-none focus:border-fuchsia-500 focus:ring-2 focus:ring-fuchsia-500/20 sm:w-72">
                    <button type="submit" class="h-10 rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-800">Cari</button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px] text-left text-sm">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-semibold">No. Nota</th>
                            <th class="px-3 py-3 font-semibold">Instansi / PIC</th>
                            <th class="px-3 py-3 font-semibold">Item &amp; Master</th>
                            <th class="px-3 py-3 font-semibold">Tanda Tangan Digital</th>
                            <th class="px-3 py-3 font-semibold">Waktu Proses</th>
                            <th class="px-5 py-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($orders as $order)
                            <tr class="align-top transition hover:bg-slate-50/70">
                                <td class="px-5 py-4">
                                    <p class="font-mono text-xs font-bold text-fuchsia-600">{{ $order->no_nota }}</p>
                                    <p class="mt-0.5 text-[10px] text-slate-400">{{ $order->items->count() }} item pada nota</p>
                                </td>
                                <td class="px-3 py-4">
                                    <p class="font-semibold text-slate-800">{{ $order->instansiLabel() }}</p>
                                    <p class="mt-0.5 text-[11px] text-slate-500">PIC: {{ $order->picLabel() }}</p>
                                    <p class="mt-0.5 text-[10px] text-slate-400">Deadline {{ $order->deadline?->format('d/m/Y H:i') ?? '-' }}</p>
                                </td>
                                <td class="px-3 py-4">
                                    <p class="max-w-[18rem] text-xs text-slate-600">
                                        {{ $order->items->map(fn ($item) => $item->product?->nama_produk)->filter()->implode(', ') }}
                                    </p>
                                    <p class="mt-0.5 text-[10px] font-semibold text-emerald-600">{{ $order->items->count() }} TIFF terupload</p>
                                    <a href="{{ route('desainer.arsip.order.unduh', $order) }}" class="mt-1 inline-block text-[11px] font-semibold text-fuchsia-600 hover:underline">Unduh master desain</a>
                                </td>
                                <td class="px-3 py-4 text-xs text-slate-600">
                                    <p class="font-semibold text-slate-800">{{ $order->tanda_tangan_desainer ?: '-' }}</p>
                                    <p class="mt-0.5 text-[10px] text-slate-400">Admin: {{ $order->tanda_tangan_admin ?: '-' }}</p>
                                </td>
                                <td class="px-3 py-4 text-xs text-slate-600">
                                    <p class="font-semibold">{{ $order->durasiProsesDesain() ?: '-' }}</p>
                                    <p class="mt-0.5 text-[10px] text-slate-400">
                                        {{ $order->waktu_terima_desain?->format('d/m/Y H:i') ?? '-' }}
                                        &rarr; {{ $order->waktu_selesai_desain?->format('d/m/Y H:i') ?? '-' }}
                                    </p>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('desainer.orders.show', $order) }}" class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-1.5 text-[11px] font-semibold text-white transition hover:bg-slate-800">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada order yang selesai desain.</td>
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
