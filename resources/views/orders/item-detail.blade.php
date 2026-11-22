@extends('layouts.app')

@use('App\Support\RiwayatSelesai')

@section('title', 'Detail Item '.$order->no_nota)
@section('section', 'Audit '.$judulDivisi)
@section('page-title', 'Detail Item')

@section('content')
    @php
        $tiffRoute = match ($divisi) {
            RiwayatSelesai::DESAINER => 'desainer.items.tiff',
            RiwayatSelesai::OPERATOR => 'operator.items.tiff',
            default => null,
        };
        $previewTerakhir = $order->designRevisions->first(fn ($revision) => filled($revision->file_preview));
    @endphp

    <div class="mx-auto max-w-6xl space-y-6">
        <div>
            <a href="{{ route($kembaliRoute, $order) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-700">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
                Kembali ke Detail Nota
            </a>
        </div>

        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-slate-900 to-indigo-950 px-6 py-7 text-white shadow-xl sm:px-8">
            <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-indigo-500/20 blur-3xl"></div>
            <div class="relative flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-indigo-300">Detail Item &middot; {{ $judulDivisi }}</p>
                    <h2 class="mt-2 truncate text-2xl font-bold tracking-tight sm:text-3xl">{{ $item->nama_item ?: 'Tanpa nama item' }}</h2>
                    <p class="mt-2 text-sm text-slate-300">
                        {{ $order->no_nota }} &middot; Item {{ $posisiItem }} dari {{ $totalItem }}
                    </p>
                </div>
                <span class="shrink-0 rounded-xl bg-white/10 px-4 py-3 text-right ring-1 ring-white/15">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-indigo-200">Status Produksi</p>
                    <p class="mt-1 text-sm font-bold">{{ $item->statusProduksiLabel() }}</p>
                </span>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-sm font-bold text-slate-900">Rincian Item</h3>
            </div>
            <dl class="grid gap-5 px-5 py-5 sm:grid-cols-2 lg:grid-cols-3 sm:px-6">
                <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">No</dt>
                    <dd class="mt-1 text-sm font-bold text-slate-900">{{ $posisiItem }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Nama Item</dt>
                    <dd class="mt-1 text-sm font-bold text-slate-900">{{ $item->nama_item ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Produk</dt>
                    <dd class="mt-1 text-sm font-bold text-slate-900">{{ $item->product?->nama_produk ?: '-' }}</dd>
                    <dd class="mt-0.5 text-[10px] uppercase tracking-wider text-slate-400">{{ $item->product?->kategori_produk ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Finishing</dt>
                    <dd class="mt-1 text-sm font-semibold text-slate-700">{{ $item->finishing?->nama_finishing ?: 'Tanpa finishing' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Ukuran &amp; Qty</dt>
                    <dd class="mt-1 text-sm font-semibold text-slate-700">
                        @if ($item->isMeteran())
                            {{ number_format((float) $item->lebar_m, 2, ',', '.') }} &times; {{ number_format((float) $item->tinggi_m, 2, ',', '.') }} m &middot; {{ $item->qty }} pcs
                        @else
                            Produk satuan &middot; {{ $item->qty }} pcs
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">ACC Admin</dt>
                    <dd class="mt-1 text-sm font-semibold text-slate-700">
                        {{ $item->accAdmin?->name ?: '-' }}
                        @if ($item->waktu_acc)
                            <span class="mt-0.5 block text-[10px] text-slate-400">{{ $item->waktu_acc->format('d/m/Y H:i') }}</span>
                        @endif
                    </dd>
                </div>
                @if ($tampilkanHarga)
                    <div>
                        <dt class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Subtotal</dt>
                        <dd class="mt-1 text-sm font-bold text-slate-900">Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}</dd>
                    </div>
                @endif
            </dl>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-sm font-bold text-slate-900">Berkas Desain</h3>
            </div>
            <div class="grid gap-4 px-5 py-5 sm:grid-cols-2 sm:px-6">
                <div class="rounded-xl border border-fuchsia-100 bg-fuchsia-50/40 p-4">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-fuchsia-600">Preview / Revisi</p>
                    @if ($previewTerakhir?->file_preview)
                        @if (preg_match('/\.(jpe?g|png)$/i', $previewTerakhir->file_preview))
                            <img src="{{ $previewTerakhir->fileUrl() }}"
                                alt="Preview revisi {{ $previewTerakhir->revisi_ke }}"
                                class="mt-3 max-h-64 w-full rounded-lg border border-fuchsia-100 bg-white object-contain p-2">
                        @endif
                        <p class="mt-2 text-xs text-slate-600">
                            Revisi {{ $previewTerakhir->revisi_ke }} &middot; {{ $previewTerakhir->statusLabel() }}
                        </p>
                        <p class="mt-1 whitespace-pre-line text-xs text-slate-500">{{ $previewTerakhir->catatan_revisi }}</p>
                        <a href="{{ $divisi === RiwayatSelesai::DESAINER ? route('desainer.orders.preview.download', [$order, $previewTerakhir]) : $previewTerakhir->fileUrl() }}"
                            class="mt-2 inline-block text-xs font-semibold text-fuchsia-600 hover:underline">Unduh preview</a>
                    @else
                        <p class="mt-3 text-xs text-slate-500">Belum ada preview pada item ini.</p>
                    @endif
                </div>

                <div class="space-y-4">
                    <div class="rounded-xl border border-emerald-100 bg-emerald-50/40 p-4">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-emerald-600">File Cetak TIFF</p>
                        @if ($item->file_cetak_tiff)
                            <p class="mt-2 truncate font-mono text-[11px] text-slate-600">{{ basename($item->file_cetak_tiff) }}</p>
                            <a href="{{ $tiffRoute ? route($tiffRoute, $item) : $item->fileUrl($item->file_cetak_tiff) }}"
                                class="mt-2 inline-block text-xs font-semibold text-emerald-700 hover:underline">Unduh TIFF</a>
                        @else
                            <p class="mt-2 text-xs text-slate-500">Belum diunggah.</p>
                        @endif
                    </div>

                    <div class="rounded-xl border border-fuchsia-100 bg-fuchsia-50/40 p-4">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-fuchsia-600">Master Desain (1 per Nota)</p>
                        @if ($order->file_master_arsip)
                            <p class="mt-2 truncate font-mono text-[11px] text-slate-600">{{ basename($order->file_master_arsip) }}</p>
                            <a href="{{ $divisi === RiwayatSelesai::DESAINER ? route('desainer.arsip.order.unduh', $order) : $item->fileUrl($order->file_master_arsip) }}"
                                class="mt-2 inline-block text-xs font-semibold text-fuchsia-700 hover:underline">Unduh master</a>
                        @else
                            <p class="mt-2 text-xs text-slate-500">Belum diunggah.</p>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <nav class="flex flex-wrap items-center justify-between gap-3" aria-label="Navigasi item">
            @if ($itemSebelumnya)
                <a href="{{ route($divisi.'.orders.items.show', [$order, $itemSebelumnya]) }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">
                    <span aria-hidden="true">&larr;</span> Item sebelumnya
                </a>
            @else
                <span class="text-xs text-slate-400">Item pertama</span>
            @endif

            <span class="text-xs font-semibold text-slate-500">{{ $posisiItem }} / {{ $totalItem }}</span>

            @if ($itemBerikutnya)
                <a href="{{ route($divisi.'.orders.items.show', [$order, $itemBerikutnya]) }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">
                    Item berikutnya <span aria-hidden="true">&rarr;</span>
                </a>
            @else
                <span class="text-xs text-slate-400">Item terakhir</span>
            @endif
        </nav>
    </div>
@endsection
