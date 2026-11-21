@extends('layouts.app')

@section('title', 'Dashboard Finishing')
@section('section', 'Divisi Finishing')
@section('page-title', 'Dashboard Finishing')

@section('content')
    <div class="space-y-6">
        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-teal-600 via-cyan-700 to-blue-800 px-6 py-7 text-white shadow-xl shadow-teal-900/10 sm:px-8">
            <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-white/10 blur-2xl"></div>
            <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-teal-100">Workspace Finishing</p>
                    <h2 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Kartu Kerja Finishing</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-cyan-100">
                        Periksa preview desain, jumlah cetak, dan jenis finishing sebelum pekerjaan dinyatakan selesai produksi.
                    </p>
                </div>
                <div class="shrink-0 rounded-xl bg-white/10 px-4 py-3 text-right ring-1 ring-white/15 backdrop-blur">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-teal-100">Antrean</p>
                    <p class="mt-1 text-2xl font-bold">{{ $items->total() }}</p>
                </div>
            </div>
        </section>

        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($items as $item)
                @php
                    $customer = $item->order?->customer;
                @endphp
                <article class="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex h-52 items-center justify-center overflow-hidden border-b border-slate-100 bg-slate-100">
                        @if ($item->previewUrl())
                            <img src="{{ $item->previewUrl() }}" alt="Preview desain {{ $item->product?->nama_produk }}"
                                class="h-full w-full object-contain">
                        @else
                            <div class="text-center text-slate-600">
                                <svg class="mx-auto h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5v10.5H3.75V6.75Zm0 0 3-3h10.5l3 3M8.25 12.75l2.25 2.25 5.25-5.25" />
                                </svg>
                                <p class="mt-2 text-xs font-semibold">Preview ACC belum tersedia</p>
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-1 flex-col p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-slate-500">{{ $item->order?->no_nota }}</p>
                                <h3 class="mt-1 truncate text-lg font-bold text-slate-900">{{ $item->product?->nama_produk }}</h3>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $customer?->nama_pelanggan }} — {{ $customer?->instansi }}</p>
                            </div>
                            <span class="shrink-0 rounded-lg bg-teal-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-teal-700 ring-1 ring-teal-100">
                                {{ $item->statusProduksiLabel() }}
                            </span>
                        </div>

                        <div class="mt-5 rounded-xl bg-teal-50 p-4 ring-1 ring-teal-100">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-teal-600">Jenis Finishing</p>
                            <p class="mt-1 break-words text-2xl font-black leading-tight text-teal-900">
                                {{ $item->finishing?->nama_finishing ?: 'Tanpa Finishing' }}
                            </p>
                        </div>

                        <div class="mt-3 grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Qty</p>
                                <p class="mt-1 text-2xl font-black text-slate-900">{{ $item->qty }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-3 ring-1 ring-slate-100">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Ukuran</p>
                                <p class="mt-1 text-sm font-bold text-slate-800">
                                    @if ($item->isMeteran())
                                        {{ number_format((float) $item->lebar_m, 2, ',', '.') }} x {{ number_format((float) $item->tinggi_m, 2, ',', '.') }} m
                                    @else
                                        Satuan
                                    @endif
                                </p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('finishing.items.konfirmasi', $item) }}" class="mt-5">
                            @csrf
                            <button type="submit" class="w-full rounded-xl bg-teal-600 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-teal-700">
                                Konfirmasi Selesai Produksi
                            </button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center md:col-span-2 xl:col-span-3">
                    <p class="text-sm font-semibold text-slate-700">Tidak ada kartu kerja finishing.</p>
                    <p class="mt-1 text-xs text-slate-500">Pekerjaan akan muncul setelah operator menyelesaikan proses cetak.</p>
                </div>
            @endforelse
        </div>

        {{ $items->links() }}
    </div>
@endsection
