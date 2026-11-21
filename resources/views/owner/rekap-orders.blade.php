@extends('layouts.app')

@use('App\Support\FormatLaporan')

@section('title', 'Rekap Order')
@section('section', 'Divisi Owner')
@section('page-title', 'Rekap Order per Nota')

@section('content')
    <div class="space-y-6">
        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-slate-900 to-indigo-950 px-6 py-7 text-white shadow-xl sm:px-8">
            <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-indigo-500/20 blur-3xl"></div>
            <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-indigo-300">Owner Workspace</p>
                    <h2 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Rekap Order</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">Satu baris untuk satu nota. Buka Detail untuk melihat rincian item beserta instansi dan PIC.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('owner.dashboard') }}" class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2.5 text-xs font-semibold text-white ring-1 ring-white/15 transition hover:bg-white/20">
                        Dashboard
                    </a>
                    <a href="{{ route('owner.reports.profit-loss') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-500 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-indigo-400">
                        Laba Rugi
                    </a>
                </div>
            </div>
        </section>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Total Nota</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($totalNota, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Total Omzet</p>
                <p class="mt-2 text-lg font-bold text-indigo-900">{{ FormatLaporan::rupiah($totalOmzet) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Total Piutang</p>
                <p class="mt-2 text-lg font-bold text-rose-700">{{ FormatLaporan::rupiah($totalPiutang) }}</p>
            </div>
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Daftar Nota</h3>
                    <p class="mt-0.5 text-[11px] text-slate-500">Rekap transaksi berbasis order, bukan per item.</p>
                </div>
                <form method="GET" action="{{ route('owner.rekap-orders') }}" class="flex flex-wrap gap-2">
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
                <table class="w-full min-w-[960px] text-left text-sm">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-semibold">No. Nota</th>
                            <th class="px-3 py-3 font-semibold">Instansi</th>
                            <th class="px-3 py-3 font-semibold">PIC</th>
                            <th class="px-3 py-3 text-right font-semibold">Total</th>
                            <th class="px-3 py-3 font-semibold">Status Bayar</th>
                            <th class="px-3 py-3 font-semibold">Status Produksi</th>
                            <th class="px-5 py-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($orders as $order)
                            @php
                                $bayarClass = match ($order->status_bayar) {
                                    \App\Models\Order::STATUS_BAYAR_LUNAS => 'bg-emerald-100 text-emerald-700',
                                    \App\Models\Order::STATUS_BAYAR_DP => 'bg-amber-100 text-amber-700',
                                    default => 'bg-rose-100 text-rose-700',
                                };
                            @endphp
                            <tr class="align-top transition hover:bg-slate-50/70">
                                <td class="px-5 py-4">
                                    <p class="font-mono text-xs font-bold text-indigo-600">{{ $order->no_nota }}</p>
                                    <p class="mt-0.5 text-[10px] text-slate-400">{{ $order->created_at?->format('d/m/Y H:i') ?? '-' }}</p>
                                </td>
                                <td class="px-3 py-4 font-semibold text-slate-800">{{ $order->instansiLabel() }}</td>
                                <td class="px-3 py-4 text-slate-600">{{ $order->picLabel() }}</td>
                                <td class="px-3 py-4 text-right font-bold text-slate-900">{{ FormatLaporan::rupiah($order->total_tagihan) }}</td>
                                <td class="px-3 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider {{ $bayarClass }}">{{ $order->statusBayarLabel() }}</span>
                                </td>
                                <td class="px-3 py-4 text-xs text-slate-600">{{ $order->statusProduksiRingkas() }}</td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('owner.orders.show', $order) }}" class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-1.5 text-[11px] font-semibold text-white transition hover:bg-slate-800">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada nota yang cocok dengan pencarian.</td>
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
