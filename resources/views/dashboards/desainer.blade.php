@extends('layouts.app')

@section('title', 'Dashboard Desainer')
@section('section', 'Divisi Desain')
@section('page-title', 'Dashboard Desainer')

@section('content')
    <div class="space-y-6" x-data="{ tab: @js($tab) }">
        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-fuchsia-600 via-purple-700 to-indigo-800 px-6 py-7 text-white shadow-xl shadow-purple-900/10 sm:px-8">
            <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-white/10 blur-2xl"></div>
            <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-fuchsia-200">Workspace Desain</p>
                    <h2 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Halo, {{ auth()->user()->name }}</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-purple-100">
                        Proses briefing, catat revisi ACC, lalu kirim ke antrean cetak setelah file TIFF dan master arsip terunggah.
                    </p>
                </div>
                <div class="shrink-0 rounded-xl bg-white/10 px-4 py-3 text-right ring-1 ring-white/15 backdrop-blur">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-fuchsia-200">Menunggu desain</p>
                    <p class="mt-1 text-2xl font-bold">{{ $queue->total() }}</p>
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

        <div class="flex flex-wrap gap-2 border-b border-slate-200">
            <button type="button"
                @click="tab = 'antrean'"
                class="rounded-t-lg border-b-2 px-4 py-3 text-sm font-semibold transition"
                :class="tab === 'antrean' ? 'border-fuchsia-600 text-fuchsia-700' : 'border-transparent text-slate-500 hover:text-slate-800'">
                Antrean Desain ({{ $queue->total() }})
            </button>
            <button type="button"
                @click="tab = 'arsip'"
                class="rounded-t-lg border-b-2 px-4 py-3 text-sm font-semibold transition"
                :class="tab === 'arsip' ? 'border-fuchsia-600 text-fuchsia-700' : 'border-transparent text-slate-500 hover:text-slate-800'">
                Arsip Master Desain ({{ $arsip->total() }})
            </button>
        </div>

        {{-- TAB: Antrean Desain --}}
        <div x-show="tab === 'antrean'" x-cloak class="space-y-5">
            @forelse ($queue as $item)
                @php
                    $customer = $item->order?->customer;
                    $ukuran = $item->isMeteran()
                        ? number_format((float) $item->lebar_m, 2, ',', '.').' x '.number_format((float) $item->tinggi_m, 2, ',', '.').' m'
                        : 'Produk satuan';
                @endphp
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex flex-col gap-4 border-b border-slate-100 pb-5 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-fuchsia-50 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-fuchsia-700 ring-1 ring-fuchsia-100">
                                    {{ $item->statusProduksiLabel() }}
                                </span>
                                <span class="text-xs font-semibold text-slate-500">{{ $item->order?->no_nota }}</span>
                                @if ($item->order?->deadline)
                                    <span class="text-xs text-slate-500">Deadline {{ $item->order->deadline->format('d/m/Y H:i') }}</span>
                                @endif
                            </div>
                            <h3 class="mt-2 text-lg font-bold text-slate-900">{{ $item->product?->nama_produk }}</h3>
                            <p class="mt-1 text-sm text-slate-600">
                                {{ $customer?->nama_pelanggan }} — <span class="text-slate-500">{{ $customer?->instansi }}</span>
                            </p>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ $ukuran }} &middot; Qty <span class="font-semibold text-slate-700">{{ $item->qty }}</span>
                                @if ($item->finishing)
                                    &middot; Finishing: <span class="font-semibold text-slate-700">{{ $item->finishing->nama_finishing }}</span>
                                @endif
                            </p>
                        </div>
                        @if ($item->order?->lampiran_briefing)
                            @php
                                $briefingPath = $item->order->lampiran_briefing;
                                $briefingIsImage = $briefingPath && preg_match('/\.(jpe?g|png|gif|webp|bmp|svg|avif)$/i', $briefingPath);
                            @endphp
                            <div class="flex shrink-0 flex-col items-end gap-2">
                                @if ($briefingIsImage)
                                    <div class="h-24 w-36 overflow-hidden rounded-xl border border-fuchsia-100 bg-white shadow-sm">
                                        <img src="{{ $item->fileUrl($briefingPath) }}"
                                            alt="Briefing {{ $item->order?->no_nota }}"
                                            class="h-full w-full object-contain">
                                    </div>
                                @endif
                                <a href="{{ route('desainer.items.briefing', $item) }}"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-fuchsia-200 bg-fuchsia-50 px-4 py-2.5 text-sm font-semibold text-fuchsia-700 transition hover:bg-fuchsia-100">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M4.5 16.5v3h15v-3" />
                                    </svg>
                                    Unduh Briefing Admin
                                </a>
                            </div>
                        @else
                            <span class="shrink-0 rounded-lg bg-slate-100 px-3 py-2 text-xs text-slate-500">Tidak ada lampiran briefing</span>
                        @endif
                    </div>

                    <div class="mt-5 grid gap-4 lg:grid-cols-2">
                        <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">Catatan Materi dari Admin</p>
                            <p class="mt-2 text-sm font-semibold text-slate-700">{{ $item->order?->statusDesainLabel() }}</p>
                            <p class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-600">
                                {{ $item->order?->catatan_desain ?: 'Tidak ada catatan khusus.' }}
                            </p>

                            @if ($item->designRevisions->isNotEmpty())
                                <div class="mt-4 border-t border-slate-200 pt-4">
                                    <p class="text-xs font-bold text-slate-700">Log Revisi</p>
                                    <ul class="mt-2 space-y-2">
                                        @foreach ($item->designRevisions->sortByDesc('revisi_ke') as $revision)
                                            @php
                                                $revisionIsImage = $revision->file_preview
                                                    && preg_match('/\.(jpe?g|png|gif|webp|bmp|svg|avif)$/i', $revision->file_preview);
                                            @endphp
                                            <li class="flex items-center justify-between gap-3 rounded-lg bg-white px-3 py-2 ring-1 ring-slate-200">
                                                <div class="flex min-w-0 items-center gap-3">
                                                    @if ($revisionIsImage)
                                                        <img src="{{ $item->fileUrl($revision->file_preview) }}"
                                                            alt="Preview Revisi {{ $revision->revisi_ke }}"
                                                            class="h-11 w-14 shrink-0 rounded-md object-contain ring-1 ring-slate-200">
                                                    @endif
                                                    <div class="min-w-0">
                                                        <p class="text-xs font-bold text-fuchsia-700">Revisi {{ $revision->revisi_ke }}</p>
                                                        <p class="truncate text-xs text-slate-500">{{ $revision->catatan_revisi }}</p>
                                                    </div>
                                                </div>
                                                @if ($revision->file_preview)
                                                    <a href="{{ route('desainer.items.preview.download', [$item, $revision]) }}"
                                                        class="shrink-0 text-xs font-semibold text-fuchsia-600 hover:underline">Preview</a>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>

                        <div class="space-y-4">
                            <form method="POST" action="{{ route('desainer.items.preview', $item) }}" enctype="multipart/form-data" class="rounded-xl border border-slate-200 p-4">
                                @csrf
                                <p class="text-sm font-bold text-slate-800">
                                    Upload Preview ACC — Revisi {{ $nomorRevisiBerikutnya[$item->id] ?? 1 }}
                                </p>
                                <div class="mt-3 space-y-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600" for="catatan_revisi_{{ $item->id }}">Catatan Revisi</label>
                                        <textarea id="catatan_revisi_{{ $item->id }}" name="catatan_revisi" rows="2" required
                                            placeholder="Contoh: Perbesar tulisan judul, ganti warna background..."
                                            class="mt-1 w-full rounded-lg border border-slate-300 text-sm shadow-sm focus:border-fuchsia-500 focus:ring-fuchsia-500">{{ old('catatan_revisi') }}</textarea>
                                    </div>
                                    <div x-data="imagePreview()" class="rounded-lg border border-slate-200 bg-slate-50/70 p-3">
                                        <label class="block text-xs font-semibold text-slate-700" for="file_preview_{{ $item->id }}">Gambar Preview (JPG/PNG/WebP/GIF)</label>
                                        <input id="file_preview_{{ $item->id }}" type="file" name="file_preview" required
                                            x-ref="file"
                                            @change="pick($event)"
                                            accept="image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif"
                                            class="mt-1 block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-fuchsia-100 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-fuchsia-800 hover:file:bg-fuchsia-200">
                                        @error('file_preview')
                                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                        @enderror

                                        <template x-if="url">
                                            <div class="mt-2 flex items-start gap-2.5 rounded-lg border border-fuchsia-100 bg-white p-2">
                                                <div class="h-16 w-20 shrink-0 overflow-hidden rounded-md bg-slate-100 ring-1 ring-fuchsia-100">
                                                    <img :src="url" :alt="'Pratinjau ' + name" x-on:error="failed = true" x-show="!failed"
                                                        class="h-full w-full object-contain">
                                                    <div x-show="failed" x-cloak class="flex h-full items-center justify-center px-1 text-center text-[10px] leading-4 text-slate-500">
                                                        Pratinjau tidak didukung
                                                    </div>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-fuchsia-500">Pratinjau berkas</p>
                                                    <p class="mt-0.5 truncate text-[11px] font-semibold text-slate-700" x-text="name"></p>
                                                    <button type="button" @click="clear()" class="mt-0.5 text-[10px] font-semibold text-rose-600 hover:underline">Hapus</button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    <button type="submit" class="w-full rounded-lg bg-fuchsia-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-fuchsia-700 focus:outline-none focus:ring-2 focus:ring-fuchsia-500 focus:ring-offset-2">
                                        Simpan Log Revisi
                                    </button>
                                </div>
                            </form>

                            <form method="POST" action="{{ route('desainer.items.finalisasi', $item) }}" enctype="multipart/form-data" class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-4">
                                @csrf
                                <p class="text-sm font-bold text-emerald-900">Finalisasi ACC</p>
                                <p class="mt-1 text-xs leading-5 text-emerald-800">
                                    Kedua file wajib diunggah sebelum item dikirim ke antrean cetak.
                                </p>
                                <div class="mt-3 space-y-3">
                                    <div x-data="imagePreview()" class="rounded-lg border border-dashed border-emerald-200 bg-white p-3">
                                        <label class="block text-xs font-semibold text-emerald-900" for="tiff_{{ $item->id }}">File Siap Cetak (.tif/.tiff)</label>
                                        <input id="tiff_{{ $item->id }}" type="file" name="file_cetak_tiff" required accept=".tif,.tiff"
                                            x-ref="file"
                                            @change="pick($event)"
                                            class="mt-1 block w-full rounded-lg border border-emerald-100 p-2 text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-100 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-emerald-800 hover:file:bg-emerald-200">
                                        @error('file_cetak_tiff')
                                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                        @enderror
                                        <template x-if="url">
                                            <div class="mt-2 flex items-center gap-2 rounded-lg bg-emerald-50 px-2 py-1.5">
                                                <div class="h-10 w-12 shrink-0 overflow-hidden rounded bg-white ring-1 ring-emerald-100">
                                                    <img :src="url" :alt="'Pratinjau ' + name" x-on:error="failed = true" x-show="!failed"
                                                        class="h-full w-full object-contain">
                                                    <div x-show="failed" x-cloak class="flex h-full items-center justify-center px-0.5 text-center text-[9px] leading-3 text-slate-500">
                                                        Tidak dapat ditampilkan
                                                    </div>
                                                </div>
                                                <p class="min-w-0 flex-1 truncate text-[11px] font-semibold text-emerald-900" x-text="name"></p>
                                                <button type="button" @click="clear()" class="shrink-0 text-[10px] font-semibold text-rose-600 hover:underline">Hapus</button>
                                            </div>
                                        </template>
                                        <div x-show="name && !url" x-cloak class="mt-2 flex items-center gap-2 rounded-lg bg-emerald-50 px-2 py-1.5">
                                            <p class="min-w-0 flex-1 truncate text-[11px] font-semibold text-emerald-900" x-text="name"></p>
                                            <button type="button" @click="clear()" class="shrink-0 text-[10px] font-semibold text-rose-600 hover:underline">Hapus</button>
                                        </div>
                                    </div>
                                    <div x-data="imagePreview()" class="rounded-lg border border-dashed border-emerald-200 bg-white p-3">
                                        <label class="block text-xs font-semibold text-emerald-900" for="master_{{ $item->id }}">File Master Arsip (.cdr/.psd/.ai/.zip)</label>
                                        <input id="master_{{ $item->id }}" type="file" name="file_master_arsip" required accept=".cdr,.psd,.ai,.zip"
                                            x-ref="file"
                                            @change="pick($event)"
                                            class="mt-1 block w-full rounded-lg border border-emerald-100 p-2 text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-100 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-emerald-800 hover:file:bg-emerald-200">
                                        @error('file_master_arsip')
                                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                        @enderror
                                        <div x-show="name && !url" x-cloak class="mt-2 flex items-center gap-2 rounded-lg bg-emerald-50 px-2 py-1.5">
                                            <p class="min-w-0 flex-1 truncate text-[11px] font-semibold text-emerald-900" x-text="name"></p>
                                            <button type="button" @click="clear()" class="shrink-0 text-[10px] font-semibold text-rose-600 hover:underline">Hapus</button>
                                        </div>
                                    </div>
                                    <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                                        Kirim ke Antrean Cetak
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center">
                    <p class="text-sm font-semibold text-slate-700">Tidak ada item yang perlu diproses desain.</p>
                    <p class="mt-1 text-xs text-slate-500">Item baru dari Admin akan muncul di sini.</p>
                </div>
            @endforelse

            {{ $queue->appends(['tab' => 'antrean'])->links() }}
        </div>

        {{-- TAB: Arsip Master Desain --}}
        <div x-show="tab === 'arsip'" x-cloak class="space-y-5">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Arsip Master Desain</h3>
                        <p class="mt-1 text-sm text-slate-500">Unduh ulang file master .cdr/.psd/.ai berdasarkan pelanggan atau instansi.</p>
                    </div>
                    <form method="GET" action="{{ route('desainer.dashboard') }}" class="flex flex-wrap gap-2">
                        <input type="hidden" name="tab" value="arsip">
                        <input type="text" name="q" value="{{ $cari }}" placeholder="Nama pelanggan / instansi"
                            class="h-10 w-56 rounded-lg border border-slate-300 bg-white px-3 text-sm shadow-sm outline-none focus:border-fuchsia-500 focus:ring-2 focus:ring-fuchsia-500/20">
                        <button type="submit" class="h-10 rounded-lg bg-fuchsia-600 px-4 text-sm font-semibold text-white transition hover:bg-fuchsia-700">Cari</button>
                        @if ($cari !== '')
                            <a href="{{ route('desainer.dashboard', ['tab' => 'arsip']) }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Reset</a>
                        @endif
                    </form>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-[11px] uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Nota</th>
                                <th class="px-4 py-3 font-semibold">Pelanggan / Instansi</th>
                                <th class="px-4 py-3 font-semibold">Produk</th>
                                <th class="px-4 py-3 font-semibold">File Master</th>
                                <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($arsip as $arsipItem)
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-4 py-3 font-medium text-slate-700">{{ $arsipItem->order?->no_nota }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-slate-800">{{ $arsipItem->order?->customer?->nama_pelanggan }}</p>
                                        <p class="text-xs text-slate-500">{{ $arsipItem->order?->customer?->instansi }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ $arsipItem->product?->nama_produk }}</td>
                                    <td class="px-4 py-3 font-mono text-xs text-slate-500">
                                        <span class="block max-w-[16rem] truncate" title="{{ basename((string) $arsipItem->file_master_arsip) }}">
                                            {{ basename((string) $arsipItem->file_master_arsip) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('desainer.arsip.unduh', $arsipItem) }}"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-fuchsia-200 bg-fuchsia-50 px-3 py-1.5 text-xs font-semibold text-fuchsia-700 hover:bg-fuchsia-100">
                                            Unduh Master
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">
                                        Tidak ada master desain yang cocok dengan pencarian.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $arsip->appends(['tab' => 'arsip'])->links() }}
            </section>
        </div>
    </div>
@endsection
