@extends('layouts.app')

@section('title', 'Review Desain')
@section('section', 'Admin')
@section('page-title', 'Review Preview Desain')

@section('content')
    <div class="space-y-6">
        <section class="rounded-2xl bg-gradient-to-br from-fuchsia-600 via-purple-700 to-indigo-800 px-6 py-7 text-white shadow-xl sm:px-8">
            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-fuchsia-200">Kontrol Admin</p>
            <h2 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Review Preview Desain</h2>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-purple-100">
                Setujui preview untuk membuka finalisasi TIFF/master, atau ajukan revisi dengan catatan wajib.
                Revisi hanya dapat dikerjakan desainer setelah catatan Admin tersimpan.
            </p>
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

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Menunggu Review ({{ $items->total() }})</h3>
                    <p class="mt-0.5 text-[11px] text-slate-500">Satu baris mewakili satu item yang preview-nya sudah diunggah desainer.</p>
                </div>
                <form method="GET" action="{{ route('admin.design-reviews.index') }}" class="flex flex-wrap gap-2">
                    <input type="search" name="q" value="{{ $q }}" placeholder="Cari nota / pelanggan / instansi"
                        class="h-10 w-64 rounded-lg border border-slate-300 px-3 text-sm shadow-sm outline-none focus:border-fuchsia-500 focus:ring-2 focus:ring-fuchsia-500/20">
                    <button type="submit" class="h-10 rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800">Cari</button>
                </form>
            </div>

            <div class="space-y-5 p-5">
                @forelse ($items as $item)
                    @php
                        $preview = $item->order?->designRevisions->first(fn ($revision) => filled($revision->file_preview));
                        $bisaDireview = $item->status_produksi === \App\Models\OrderItem::STATUS_MENUNGGU_ACC
                            && ! $item->acc_admin_id
                            && ! $item->revisiPending();
                    @endphp
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-col gap-4 border-b border-slate-100 pb-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-fuchsia-50 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-fuchsia-700">
                                        {{ $item->statusProduksiLabel() }}
                                    </span>
                                    <span class="font-mono text-xs font-semibold text-slate-600">{{ $item->order?->no_nota }}</span>
                                    <span class="text-xs text-slate-500">Deadline {{ $item->order?->deadline?->format('d/m/Y H:i') ?? '-' }}</span>
                                </div>
                                <h4 class="mt-2 text-base font-bold text-slate-900">{{ $item->product?->nama_produk }}</h4>
                                <p class="mt-1 text-sm text-slate-600">
                                    {{ $item->order?->customer?->nama_pelanggan }} (ID {{ $item->order?->customer_id }} &middot; {{ $item->order?->instansiLabel() }})
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Finishing: {{ $item->finishing?->nama_finishing ?: 'Tanpa finishing' }}
                                    &middot; Catatan: {{ $item->order?->catatan_desain ?: 'Tidak ada' }}
                                </p>
                            </div>
                            <a href="{{ route('admin.orders.summary', $item->order) }}" class="shrink-0 text-xs font-semibold text-indigo-600 hover:underline">Lihat nota</a>
                        </div>

                        <div class="mt-4 grid gap-4 lg:grid-cols-2">
                            <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">Preview Revisi {{ $preview?->revisi_ke ?? '-' }}</p>
                                @if ($preview?->file_preview)
                                    <div class="mt-2 overflow-hidden rounded-lg border border-slate-200 bg-white">
                                        <img src="{{ $item->fileUrl($preview->file_preview) }}" alt="Preview desain" class="max-h-72 w-full object-contain">
                                    </div>
                                    <a href="{{ route('admin.orders.show', $item->order) }}" class="mt-2 inline-block text-xs font-semibold text-fuchsia-600 hover:underline">Buka detail order</a>
                                @else
                                    <p class="mt-2 text-sm text-slate-500">Preview belum tersedia.</p>
                                @endif
                                @if ($preview?->catatan_revisi)
                                    <p class="mt-2 whitespace-pre-line text-xs text-slate-600">Catatan desainer: {{ $preview->catatan_revisi }}</p>
                                @endif
                            </div>

                            <div class="space-y-3">
                                @if ($bisaDireview)
                                    <form method="POST" action="{{ route('admin.design-reviews.setujui', $item) }}" class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-4">
                                        @csrf
                                        <p class="text-sm font-bold text-emerald-900">Setujui preview ini</p>
                                        <p class="mt-1 text-xs text-emerald-800">Desainer baru dapat mengunggah TIFF dan master setelah persetujuan.</p>
                                        <button type="submit" class="mt-3 w-full rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                            Setujui
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.design-reviews.revisi', $item) }}" class="rounded-xl border border-amber-200 bg-amber-50/60 p-4">
                                        @csrf
                                        <label class="block text-sm font-bold text-amber-900" for="catatan_revisi_{{ $item->id }}">Ajukan Revisi</label>
                                        <p class="mt-1 text-xs text-amber-800">Catatan wajib diisi dan akan disimpan beserta ID Admin serta waktu pengajuan.</p>
                                        <textarea id="catatan_revisi_{{ $item->id }}" name="catatan_revisi" rows="2" required maxlength="1000"
                                            placeholder="Contoh:Perbesar logo dan sesuaikan warna background"
                                            class="mt-2 w-full rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm shadow-sm outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20">{{ old('catatan_revisi') }}</textarea>
                                        @error('catatan_revisi')
                                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                        @enderror
                                        <button type="submit" class="mt-2 w-full rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-700">
                                            Ajukan Revisi
                                        </button>
                                    </form>
                                @else
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                                        Item ini tidak dapat direview (sudah ACC, sedang revisi, atau keluar dari antrean desain).
                                    </div>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-300 px-6 py-12 text-center">
                        <p class="text-sm font-semibold text-slate-700">Tidak ada preview yang menunggu review.</p>
                    </div>
                @endforelse
            </div>

            @if ($items->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">{{ $items->links() }}</div>
            @endif
        </section>
    </div>
@endsection
