@extends('layouts.app')

@section('title', 'Serah Terima '.$judulDivisi)
@section('section', 'Serah Terima')
@section('page-title', 'Serah Terima / Pengambilan')

@section('content')
    <div class="space-y-6">
        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-600 via-teal-700 to-slate-900 px-6 py-7 text-white shadow-xl sm:px-8">
            <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-white/10 blur-2xl"></div>
            <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-emerald-100">Serah Terima · {{ $judulDivisi }}</p>
                    <h2 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Order Siap Diambil</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-emerald-50">
                        Satu baris mewakili satu nota. Catat foto barang, atas nama pengambil, dan nomor WhatsApp sebelum barang diserahkan.
                    </p>
                </div>
                <span class="shrink-0 rounded-xl bg-white/10 px-4 py-3 text-right ring-1 ring-white/15">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-emerald-100">Belum Lunas</p>
                    <p class="mt-1 text-2xl font-bold">{{ $belumLunasCount }}</p>
                </span>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Daftar Order Siap Diambil</h3>
                    <p class="mt-0.5 text-[11px] text-slate-500">{{ $orders->total() }} nota pada filter ini &middot; 15 nota per halaman.</p>
                </div>
                <form method="GET" action="{{ route($divisi.'.serah-terima.index') }}" class="flex flex-wrap gap-2">
                    <label for="q" class="sr-only">Cari nota</label>
                    <input
                        id="q"
                        name="q"
                        type="search"
                        value="{{ $q }}"
                        placeholder="Cari no. nota / instansi / PIC"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 sm:w-72"
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
                            <th class="px-3 py-3 font-semibold">Status Bayar</th>
                            <th class="px-3 py-3 font-semibold">Status Produksi</th>
                            @if ($divisi === 'admin')
                                <th class="px-3 py-3 font-semibold">Bukti Pelunasan</th>
                            @endif
                            <th class="px-5 py-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($orders as $order)
                            @php
                                $bolehSerahTerima = $order->bolehDiserahkan();
                            @endphp
                            <tr class="align-top transition hover:bg-slate-50/70">
                                <td class="px-5 py-4">
                                    <p class="font-mono text-xs font-bold text-indigo-600">{{ $order->no_nota }}</p>
                                    <p class="mt-1 text-[11px] text-slate-400">{{ $order->created_at?->format('d/m/Y H:i') }}</p>
                                </td>
                                <td class="px-3 py-4">
                                    <p class="font-semibold text-slate-800">{{ $order->instansiLabel() }}</p>
                                    <p class="mt-0.5 text-[11px] text-slate-500">{{ $order->picLabel() }}</p>
                                </td>
                                <td class="px-3 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider {{ $bolehSerahTerima ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                        {{ $order->statusBayarLabel() }}
                                    </span>
                                </td>
                                <td class="px-3 py-4 text-xs font-medium text-slate-600">{{ $order->statusProduksiRingkas() }}</td>
                                @if ($divisi === 'admin')
                                    <td class="px-3 py-4 text-xs">
                                        @if ($order->buktiPelunasanUrl())
                                            <a href="{{ $order->buktiPelunasanUrl() }}" target="_blank" rel="noopener" class="font-semibold text-indigo-600 hover:text-indigo-700">Lihat Bukti</a>
                                        @else
                                            <span class="text-slate-400">Belum ada</span>
                                        @endif
                                    </td>
                                @endif
                                <td class="px-5 py-4">
                                    <div class="flex flex-col items-end gap-1.5">
                                        @if ($bolehSerahTerima)
                                            <a
                                                href="{{ route($divisi.'.orders.serah-terima.create', $order) }}"
                                                class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-3 py-1.5 text-[11px] font-bold text-white transition hover:bg-emerald-700"
                                            >
                                                {{ $divisi === 'admin' ? 'Serah Terima' : 'Pengambilan' }}
                                            </a>
                                        @else
                                            <button type="button" disabled class="cursor-not-allowed rounded-lg bg-slate-100 px-3 py-1.5 text-[11px] font-bold text-slate-400">
                                                {{ $divisi === 'admin' ? 'Serah Terima' : 'Pengambilan' }}
                                            </button>
                                            <p class="text-[10px] font-semibold text-rose-600">Pelunasan wajib sebelum serah terima</p>
                                            @if ($divisi === 'admin')
                                                <a href="{{ route('admin.orders.pelunasan.create', $order) }}" class="text-[10px] font-semibold text-indigo-600 hover:text-indigo-700">
                                                    Catat Pelunasan
                                                </a>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $divisi === 'admin' ? 6 : 5 }}" class="px-5 py-12 text-center">
                                    <h3 class="text-base font-bold text-slate-900">Belum ada order siap diambil</h3>
                                    <p class="mx-auto mt-1 max-w-md text-sm leading-6 text-slate-500">
                                        Daftar ini terisi otomatis setelah seluruh item pada nota selesai produksi.
                                    </p>
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
