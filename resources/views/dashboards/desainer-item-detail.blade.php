@extends('layouts.app')

@section('title', 'Detail Order Desain')
@section('section', 'Divisi Desain')
@section('page-title', 'Detail Order Desain')

@section('content')
    <div class="space-y-6" x-data="{ tiffUrl: null, tiffDownload: null, tiffNama: '', gagal: false }">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('desainer.dashboard', ['tab' => 'antrean']) }}"
                class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                <span aria-hidden="true">&larr;</span> Kembali ke Antrean
            </a>
            <a href="{{ route('desainer.orders.show', $order) }}"
                class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                Detail Nota
            </a>
        </div>

        {{-- Header ringkas nota --}}
        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-fuchsia-600 via-purple-700 to-indigo-800 px-6 py-7 text-white shadow-xl shadow-purple-900/10 sm:px-8">
            <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-white/10 blur-2xl"></div>
            <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-white/10 px-2.5 py-1 font-mono text-[11px] font-bold text-white ring-1 ring-white/20">{{ $order->no_nota }}</span>
                        <span class="rounded-full bg-white/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-fuchsia-100 ring-1 ring-white/20">
                            {{ $order->statusProduksiRingkas() }}
                        </span>
                        @if ($order->deadline)
                            <span class="text-[11px] text-purple-100">Deadline {{ $order->deadline->format('d/m/Y H:i') }}</span>
                        @endif
                    </div>
                    <h2 class="mt-2 truncate text-2xl font-bold tracking-tight sm:text-3xl">{{ $order->customer?->nama_pelanggan ?: '-' }}</h2>
                    <p class="mt-1 text-sm text-purple-100">{{ $order->instansiLabel() }} &middot; {{ $order->items->count() }} item</p>
                </div>
                <div class="w-full shrink-0 rounded-xl bg-white/10 px-4 py-3 ring-1 ring-white/15 backdrop-blur sm:w-64">
                    <div class="flex items-center justify-between text-[10px] font-semibold uppercase tracking-[0.16em] text-fuchsia-200">
                        <span>Progress Desain</span>
                        <span>{{ $progresDesain }}%</span>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-white/20">
                        <div class="h-full rounded-full bg-white transition-all" style="width: {{ $progresDesain }}%"></div>
                    </div>
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

        {{-- CARD 1: Info pelanggan + catatan --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-fuchsia-600">Card 1</p>
                    <h2 class="mt-0.5 text-base font-bold text-slate-900">Info Pelanggan &amp; Catatan</h2>
                </div>
                @if ($order->lampiran_briefing && $briefingItem)
                    <a href="{{ route('desainer.items.briefing', $briefingItem) }}"
                        class="inline-flex items-center justify-center rounded-lg border border-fuchsia-200 bg-fuchsia-50 px-3 py-2 text-xs font-semibold text-fuchsia-700 transition hover:bg-fuchsia-100">
                        Unduh Briefing Admin
                    </a>
                @else
                    <span class="rounded-lg bg-slate-100 px-3 py-2 text-[11px] text-slate-500">Tidak ada lampiran briefing</span>
                @endif
            </div>

            <div class="grid gap-5 px-5 py-5 sm:grid-cols-2 lg:grid-cols-4 sm:px-6">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Instansi</p>
                    <p class="mt-1 text-sm font-bold text-slate-900">{{ $order->instansiLabel() }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Nama Pelanggan / PIC</p>
                    <p class="mt-1 text-sm font-bold text-slate-900">{{ $order->customer?->nama_pelanggan ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">WhatsApp</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">{{ $order->customer?->no_wa ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Diterima Desainer</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">{{ $order->designer?->name ?: ($order->diterimaDesainer() ? 'Sudah diterima' : 'Belum diterima') }}</p>
                    <p class="mt-0.5 text-[10px] text-slate-400">{{ $order->waktu_terima_desain?->format('d/m/Y H:i') ?? '-' }}</p>
                </div>
                @if ($order->customer?->alamat)
                    <div class="sm:col-span-2 lg:col-span-4">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Alamat</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $order->customer->alamat }}</p>
                    </div>
                @endif
                <div class="sm:col-span-2 lg:col-span-4">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Catatan Desain</p>
                    <p class="mt-1 whitespace-pre-line rounded-xl bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-700 ring-1 ring-slate-100">
                        Catatan: {{ $order->catatan_desain ?: 'Tidak ada catatan khusus.' }}
                    </p>
                </div>
            </div>

            @if ($bisaTerima)
                <form method="POST" action="{{ route('desainer.orders.terima', $order) }}" class="mx-5 mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-indigo-200 bg-indigo-50/70 p-4 sm:mx-6">
                    @csrf
                    <div>
                        <p class="text-sm font-bold text-indigo-900">Job belum diterima</p>
                        <p class="mt-0.5 text-xs text-indigo-700">Terima tugas untuk mencatat waktu mulai proses desain.</p>
                    </div>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                        Terima Tugas
                    </button>
                </form>
            @endif
        </section>

        {{-- CARD 2: Log revisi (timeline) + unggah preview --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-fuchsia-600">Card 2</p>
                    <h2 class="mt-0.5 text-base font-bold text-slate-900">Log Revisi <span class="text-xs font-medium text-slate-500">(Timeline)</span></h2>
                    <p class="mt-0.5 text-[11px] text-slate-500">Log Preview Revisi: catatan Admin dan tanggapan desainer tersimpan berurutan.</p>
                </div>
                <span class="rounded-full bg-fuchsia-50 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-fuchsia-700 ring-1 ring-fuchsia-100">
                    {{ $logRevisi->total() }} log
                </span>
            </div>

            <div class="px-5 py-5 sm:px-6">
                @if ($logRevisi->isNotEmpty())
                    <ol class="relative ml-2 space-y-4 border-l border-slate-200 pl-5">
                        @foreach ($logRevisi as $revision)
                            @php
                                $dariAdmin = filled($revision->admin_id);
                                $gambar = $revision->file_preview && preg_match('/\.(jpe?g|png)$/i', $revision->file_preview);
                                $warnaDot = $dariAdmin
                                    ? ($revision->isPending() ? 'bg-amber-500 ring-amber-100' : 'bg-slate-400 ring-slate-100')
                                    : 'bg-fuchsia-500 ring-fuchsia-100';
                            @endphp
                            <li class="relative">
                                <span class="absolute -left-[27px] top-1.5 h-3 w-3 rounded-full ring-4 {{ $warnaDot }}"></span>
                                <div class="rounded-xl border {{ $dariAdmin ? 'border-amber-200 bg-amber-50/50' : 'border-slate-200 bg-slate-50/70' }} px-4 py-3">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="text-xs font-bold {{ $dariAdmin ? 'text-amber-800' : 'text-fuchsia-700' }}">
                                            {{ $dariAdmin ? 'Admin mengajukan revisi' : 'Desainer mengunggah preview' }}
                                            &middot; {{ $revision->orderItem?->nama_item ?: 'Seluruh order' }} &middot; Revisi {{ $revision->revisi_ke }}
                                        </p>
                                        <span class="rounded-full bg-white px-2 py-0.5 text-[10px] font-semibold text-slate-500 ring-1 ring-slate-200">{{ $revision->statusLabel() }}</span>
                                    </div>
                                    <p class="mt-1 text-[10px] text-slate-500">
                                        {{ $dariAdmin ? ($revision->admin?->name ?? 'Admin') : 'Desainer' }}
                                        &middot; {{ $revision->created_at?->format('d/m/Y H:i') ?? '-' }}
                                    </p>
                                    <p class="mt-1.5 whitespace-pre-line text-xs leading-5 text-slate-700">{{ $revision->catatan_revisi }}</p>
                                    @if ($gambar)
                                        <div class="mt-2 flex items-center gap-3">
                                            <img src="{{ $revision->fileUrl() }}"
                                                alt="Preview Revisi {{ $revision->revisi_ke }}"
                                                class="h-14 w-20 rounded-md object-contain ring-1 ring-slate-200">
                                            <a href="{{ route('desainer.orders.preview.download', [$order, $revision]) }}"
                                                class="text-[11px] font-semibold text-fuchsia-600 hover:underline">Unduh preview</a>
                                        </div>
                                    @elseif ($revision->file_preview)
                                        <a href="{{ route('desainer.orders.preview.download', [$order, $revision]) }}"
                                            class="mt-2 inline-block text-[11px] font-semibold text-fuchsia-600 hover:underline">Preview</a>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-xs text-slate-500">
                        Belum ada preview atau revisi.
                    </p>
                @endif

                @if ($logRevisi->hasPages())
                    <div class="mt-4">{{ $logRevisi->links() }}</div>
                @endif

                {{-- Form preview/tanggapan revisi sekali untuk seluruh order --}}
                @if ($bisaPreview)
                    <form method="POST" action="{{ route('desainer.orders.preview', $order) }}" enctype="multipart/form-data"
                        class="mt-4 rounded-xl border border-fuchsia-200 bg-fuchsia-50/40 p-4">
                        @csrf
                        <p class="text-sm font-bold text-slate-800">
                            @if ($revisiPending)
                                Tanggapi Revisi Admin — Revisi {{ $nomorRevisiBerikutnya }}
                            @else
                                Upload Preview — Revisi {{ $nomorRevisiBerikutnya }}
                            @endif
                        </p>
                        <p class="mt-1 text-[11px] text-slate-500">
                            {{ $revisiPending
                                ? 'Jawab catatan Admin berikut dengan satu preview JPG/PNG untuk seluruh order.'
                                : 'Satu preview JPG/PNG berlaku untuk seluruh item pada nota ini.' }}
                        </p>
                        @if ($revisiPending)
                            <div class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                Catatan Admin{{ $revisiPending->orderItem?->nama_item ? ' ('.$revisiPending->orderItem->nama_item.')' : '' }}:
                                {{ $revisiPending->catatan_revisi }}
                            </div>
                        @endif
                        <div class="mt-3 grid gap-3 lg:grid-cols-2">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600" for="catatan_revisi_order">Catatan Revisi</label>
                                <textarea id="catatan_revisi_order" name="catatan_revisi" rows="2" required
                                    placeholder="Contoh: Perbesar tulisan judul, ganti warna background..."
                                    class="mt-1 w-full rounded-lg border border-slate-300 text-sm shadow-sm focus:border-fuchsia-500 focus:ring-fuchsia-500">{{ old('catatan_revisi') }}</textarea>
                            </div>
                            <div x-data="imagePreview()" class="rounded-lg border border-slate-200 bg-white p-3">
                                <label class="block text-xs font-semibold text-slate-700" for="file_preview_order">Gambar Preview (JPG/PNG)</label>
                                <input id="file_preview_order" type="file" name="file_preview" required
                                    x-ref="file" @change="pick($event)" accept="image/jpeg,image/png,.jpg,.jpeg,.png"
                                    class="mt-1 block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-fuchsia-100 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-fuchsia-800 hover:file:bg-fuchsia-200">
                                @error('file_preview')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                                <template x-if="url">
                                    <div class="mt-2 flex items-start gap-2.5 rounded-lg border border-fuchsia-100 bg-slate-50 p-2">
                                        <div class="h-16 w-20 shrink-0 overflow-hidden rounded-md bg-slate-100 ring-1 ring-fuchsia-100">
                                            <img :src="url" :alt="'Pratinjau ' + name" x-on:error="failed = true" x-show="!failed" class="h-full w-full object-contain">
                                            <div x-show="failed" x-cloak class="flex h-full items-center justify-center px-1 text-center text-[10px] leading-4 text-slate-500">Pratinjau tidak didukung</div>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[10px] font-semibold uppercase tracking-wider text-fuchsia-500">Pratinjau berkas</p>
                                            <p class="mt-0.5 truncate text-[11px] font-semibold text-slate-700" x-text="name"></p>
                                            <button type="button" @click="clear()" class="mt-0.5 text-[10px] font-semibold text-rose-600 hover:underline">Hapus</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <button type="submit" class="mt-3 w-full rounded-lg bg-fuchsia-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-fuchsia-700">
                            Simpan Preview
                        </button>
                    </form>
                @elseif (! $order->diterimaDesainer())
                    <p class="mt-4 rounded-xl border border-dashed border-slate-300 px-4 py-4 text-center text-xs text-slate-500">
                        Preview aktif setelah job diterima desainer.
                    </p>
                @endif
            </div>
        </section>

        {{-- CARD 3: Daftar item desain --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-fuchsia-600">Card 3</p>
                    <h2 class="mt-0.5 text-base font-bold text-slate-900">Daftar Item Desain</h2>
                    <p class="mt-0.5 text-[11px] text-slate-500">Upload TIFF aktif hanya setelah preview disetujui Admin.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-slate-600">
                    {{ $items->total() }} item
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[960px] text-left text-sm">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-semibold">No</th>
                            <th class="px-4 py-3 font-semibold">Nama Item</th>
                            <th class="px-4 py-3 font-semibold">Produk</th>
                            <th class="px-4 py-3 font-semibold">Finishing</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($items as $item)
                            @php
                                $revisiTerakhir = $item->designRevisions->sortByDesc('revisi_ke')->first();
                                $revisiPending = $item->designRevisions->first(fn ($revision) => $revision->isPending())
                                    ?? $logRevisi->getCollection()->first(fn ($revision) => $revision->isPending());
                            @endphp
                            <tr class="align-top transition hover:bg-slate-50/70">
                                <td class="px-4 py-3 text-xs font-semibold text-slate-500">
                                    {{ $items->firstItem() + $loop->index }}
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('desainer.orders.items.show', [$order, $item]) }}" class="font-semibold text-slate-800 hover:text-fuchsia-700">
                                        {{ $item->nama_item ?: 'Tanpa nama item' }}
                                    </a>
                                    <p class="mt-0.5 font-mono text-[11px] text-fuchsia-600">{{ $order->no_nota }}</p>
                                    <p class="mt-0.5 text-[11px] text-slate-500">
                                        {{ $order->customer?->nama_pelanggan }} &middot; {{ $order->instansiLabel() }}
                                    </p>
                                    <p class="mt-0.5 text-[11px] text-slate-600">
                                        @if ($item->isMeteran())
                                            {{ number_format((float) $item->lebar_m, 2, ',', '.') }} x {{ number_format((float) $item->tinggi_m, 2, ',', '.') }} m
                                        @else
                                            Produk satuan
                                        @endif
                                        &middot; Qty {{ $item->qty }}
                                    </p>
                                    @if ($revisiPending)
                                        <p class="mt-1 rounded-lg border border-amber-200 bg-amber-50 px-2 py-1.5 text-[11px] text-amber-800">
                                            Revisi dari Admin ({{ $revisiPending->admin?->name ?? 'Admin' }} &middot; {{ $revisiPending->created_at?->format('d/m/Y H:i') }}): {{ $revisiPending->catatan_revisi }}
                                        </p>
                                    @elseif ($revisiTerakhir?->catatan_revisi)
                                        <p class="mt-0.5 text-[11px] text-fuchsia-700">Catatan revisi: {{ $revisiTerakhir->catatan_revisi }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $item->product?->nama_produk ?: '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $item->finishing?->nama_finishing ?: 'Tanpa finishing' }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-700">
                                        {{ $item->statusProduksiLabel() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if (filled($item->file_cetak_tiff))
                                        <div class="flex flex-col items-end gap-1.5">
                                            <p class="max-w-[16rem] truncate font-mono text-[11px] font-semibold text-emerald-700" title="{{ basename($item->file_cetak_tiff) }}">
                                                {{ basename($item->file_cetak_tiff) }}
                                            </p>
                                            <div class="flex flex-wrap justify-end gap-2">
                                                <button type="button"
                                                    @click="tiffUrl = @js($item->fileUrl($item->file_cetak_tiff)); tiffDownload = '{{ route('desainer.items.tiff', $item) }}'; tiffNama = @js(basename($item->file_cetak_tiff)); gagal = false"
                                                    class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">
                                                    Pratinjau TIFF
                                                </button>
                                                <a href="{{ route('desainer.items.tiff', $item) }}"
                                                    class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">
                                                    Unduh
                                                </a>
                                            </div>
                                            <span class="text-[10px] font-semibold text-emerald-700">TIFF tersimpan</span>
                                        </div>
                                    @elseif ($item->bolehFinalisasi())
                                        <form method="POST" action="{{ route('desainer.items.finalisasi', $item) }}" enctype="multipart/form-data" class="ml-auto max-w-xs space-y-2">
                                            @csrf
                                            <input type="file" name="file_cetak_tiff" required accept=".tif,.tiff"
                                                class="block w-full text-[11px] text-slate-600 file:mr-2 file:rounded-lg file:border-0 file:bg-emerald-100 file:px-2.5 file:py-1.5 file:text-[11px] file:font-semibold file:text-emerald-800 hover:file:bg-emerald-200">
                                            @error('file_cetak_tiff')
                                                <p class="text-left text-[10px] text-rose-600">{{ $message }}</p>
                                            @enderror
                                            <button type="submit" class="w-full rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700">
                                                Simpan
                                            </button>
                                        </form>
                                    @else
                                        <span class="inline-flex rounded-lg bg-slate-100 px-3 py-2 text-[11px] font-semibold text-slate-500">
                                            Proses ACC Desain
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">Nota ini tidak memiliki item.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($items->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">{{ $items->links() }}</div>
            @endif

            @php
                $finalisasiItems = $semuaItem->filter(
                    fn ($item) => $item->bolehFinalisasi() && ! filled($item->file_cetak_tiff),
                );
                $menungguAcc = $semuaItem->first(
                    fn ($item) => $item->status_produksi === \App\Models\OrderItem::STATUS_MENUNGGU_ACC && ! $item->acc_admin_id,
                );
            @endphp

            <div class="border-t border-slate-100 bg-slate-50/60 px-5 py-5 sm:px-6">
                @if ($finalisasiItems->isNotEmpty())
                    <details class="rounded-xl border border-emerald-200 bg-white" @if ($finalisasiItems->count() === 1) open @endif>
                        <summary class="cursor-pointer select-none px-4 py-3 text-sm font-bold text-slate-800">
                            Simpan Semua Sekaligus ({{ $finalisasiItems->count() }} item siap)
                        </summary>
                        <form method="POST" action="{{ route('desainer.orders.finalisasi', $order) }}" enctype="multipart/form-data" class="space-y-3 border-t border-slate-100 px-4 py-4">
                            @csrf
                            @foreach ($finalisasiItems as $item)
                                <div class="grid gap-2 sm:grid-cols-[1fr_minmax(0,20rem)] sm:items-center">
                                    <div class="min-w-0">
                                        <p class="truncate text-xs font-semibold text-slate-800">{{ $item->nama_item ?: 'Tanpa nama item' }}</p>
                                        <p class="text-[10px] text-slate-500">{{ $item->product?->nama_produk ?: '-' }} &middot; {{ $item->statusProduksiLabel() }}</p>
                                    </div>
                                    <div>
                                        <input type="file" name="items[{{ $item->id }}][file_cetak_tiff]" required accept=".tif,.tiff"
                                            class="block w-full text-[11px] text-slate-600 file:mr-2 file:rounded-lg file:border-0 file:bg-emerald-100 file:px-2.5 file:py-1.5 file:text-[11px] file:font-semibold file:text-emerald-800 hover:file:bg-emerald-200">
                                        @error("items.{$item->id}.file_cetak_tiff")
                                            <p class="mt-1 text-[10px] text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            @endforeach
                            <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                                Simpan Semua
                            </button>
                        </form>
                    </details>
                @elseif ($semuaTiffTerupload)
                    <p class="rounded-xl border border-emerald-200 bg-emerald-50/70 px-4 py-3 text-sm font-semibold text-emerald-800">
                        Seluruh TIFF per item sudah terupload.
                    </p>
                @elseif ($menungguAcc)
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <p class="text-sm font-bold text-slate-800">Menunggu review Admin</p>
                        <p class="mt-0.5 text-xs text-slate-500">Form finalisasi dibuka setelah Admin menyetujui preview.</p>
                        <button type="button" disabled class="mt-2 w-full cursor-not-allowed rounded-lg bg-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-500">
                            Finalisasi terkunci
                        </button>
                    </div>
                @else
                    <p class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
                        Belum ada item yang siap difinalisasi. Unggah preview dan tunggu ACC Admin terlebih dahulu.
                    </p>
                @endif
            </div>
        </section>

        {{-- CARD 4: Upload master desain --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-fuchsia-600">Card 4 &middot; Finalisasi</p>
                    <h2 class="mt-0.5 text-base font-bold text-slate-900">Upload Master Desain</h2>
                    <p class="mt-0.5 text-[11px] text-slate-500">Satu file master per nota. Format .cdr, .ai, .pdf, .psd, atau .zip.</p>
                </div>
                <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider {{ $masterSudahAda ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-100' }}">
                    Master: {{ $masterSudahAda ? 'Tersedia' : 'Belum diunggah' }}
                </span>
            </div>

            <div class="px-5 py-5 sm:px-6">
                @if ($masterSudahAda)
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-4">
                        <p class="text-sm font-bold text-emerald-900">Finalisasi selesai.</p>
                        <p class="mt-1 text-xs text-emerald-800">Seluruh TIFF per item dan Master Desain tersimpan. Item telah masuk antrean cetak.</p>
                        <div class="mt-3 flex flex-wrap items-center gap-3">
                            <span class="max-w-full truncate font-mono text-[11px] font-semibold text-emerald-800" title="{{ basename((string) $order->file_master_arsip) }}">
                                {{ basename((string) $order->file_master_arsip) }}
                            </span>
                            <a href="{{ route('desainer.arsip.order.unduh', $order) }}" class="text-xs font-semibold text-fuchsia-700 hover:underline">Unduh master</a>
                        </div>
                    </div>
                @elseif ($semuaTiffTerupload)
                    <form id="form-master-desain" method="POST" action="{{ route('desainer.orders.master', $order) }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <div x-data="imagePreview()" class="rounded-xl border border-dashed border-fuchsia-200 bg-fuchsia-50/40 p-4">
                            <label class="block text-xs font-semibold text-slate-700" for="master_design_file">File Master (.cdr/.ai/.pdf/.psd/.zip)</label>
                            <input id="master_design_file" type="file" name="file_master_arsip" required accept=".cdr,.ai,.pdf,.psd,.zip"
                                x-ref="file" @change="pick($event)"
                                class="mt-2 block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-fuchsia-100 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-fuchsia-800 hover:file:bg-fuchsia-200">
                            @error('file_master_arsip')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                            <template x-if="name">
                                <p class="mt-2 truncate text-[11px] font-semibold text-slate-700" x-text="name"></p>
                            </template>
                        </div>
                        <button type="submit" class="w-full rounded-lg bg-fuchsia-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-fuchsia-700">
                            Simpan Master &amp; Selesaikan Tugas
                        </button>
                    </form>
                @else
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                        <input type="file" disabled class="block w-full text-xs text-slate-400">
                        <p class="mt-2 text-xs text-slate-500">Field Master Desain aktif setelah seluruh TIFF per item terupload.</p>
                    </div>
                @endif
            </div>
        </section>

        {{-- CARD 5: Tanda tangan & laporan --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-fuchsia-600">Card 5</p>
                    <h2 class="mt-0.5 text-base font-bold text-slate-900">Tanda Tangan &amp; Laporan</h2>
                    <p class="mt-0.5 text-[11px] text-slate-500">Tanda tangan digital, durasi proses, dan rincian log revisi.</p>
                </div>
                @if ($masterSudahAda)
                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-emerald-700 ring-1 ring-emerald-100">
                        Tugas Desainer Selesai
                    </span>
                @endif
            </div>

            <div class="grid gap-5 px-5 py-5 lg:grid-cols-2 sm:px-6">
                <div class="space-y-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                        <h3 class="text-sm font-bold text-slate-900">Tanda Tangan Digital</h3>
                        @if ($masterSudahAda)
                            <dl class="mt-3 space-y-3 text-sm">
                                <div>
                                    <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Desainer</dt>
                                    <dd class="mt-0.5 font-semibold text-slate-800">{{ $order->tanda_tangan_desainer ?: '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Admin ACC</dt>
                                    <dd class="mt-0.5 font-semibold text-slate-800">{{ $order->tanda_tangan_admin ?: '-' }}</dd>
                                </div>
                            </dl>
                        @elseif ($semuaTiffTerupload)
                            <label class="mt-2 block text-xs font-semibold text-slate-700" for="tanda_tangan_desainer">Nama / Tanda Tangan Desainer</label>
                            <input id="tanda_tangan_desainer" type="text" name="tanda_tangan" form="form-master-desain" maxlength="255"
                                value="{{ old('tanda_tangan', $order->tanda_tangan_desainer ?: auth()->user()->name) }}"
                                class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm outline-none focus:border-fuchsia-500 focus:ring-2 focus:ring-fuchsia-500/20">
                            <p class="mt-1 text-[10px] text-slate-500">Tanda tangan akan tersimpan bersama master desain saat tugas diselesaikan.</p>
                            <button type="submit" form="form-master-desain" class="mt-3 w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                                Simpan Tanda Tangan &amp; Selesaikan Tugas
                            </button>
                        @else
                            <input type="text" disabled placeholder="Aktif setelah seluruh TIFF dan master siap"
                                class="mt-2 w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-sm text-slate-400">
                            <p class="mt-1 text-[10px] text-slate-500">Tanda tangan aktif setelah seluruh TIFF per item terupload.</p>
                        @endif
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <h3 class="text-sm font-bold text-slate-900">Durasi Proses</h3>
                        <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Mulai</dt>
                                <dd class="mt-0.5 font-semibold text-slate-800">{{ $order->waktu_terima_desain?->format('d/m/Y H:i') ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Selesai</dt>
                                <dd class="mt-0.5 font-semibold text-slate-800">{{ $order->waktu_selesai_desain?->format('d/m/Y H:i') ?? '-' }}</dd>
                            </div>
                            <div class="col-span-2">
                                <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Total Durasi</dt>
                                <dd class="mt-0.5 text-base font-bold text-fuchsia-700">
                                    @if ($order->waktu_selesai_desain)
                                        {{ $order->durasiProsesDesain() ?: '-' }}
                                    @elseif ($order->waktu_terima_desain)
                                        {{ $order->durasiProsesDesain(true) }} <span class="text-[10px] font-medium text-slate-500">(berjalan)</span>
                                    @else
                                        -
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-sm font-bold text-slate-900">Rincian Log Revisi</h3>
                        <span class="rounded-full bg-fuchsia-50 px-2.5 py-1 text-[10px] font-bold text-fuchsia-700">{{ $jumlahRevisi }} entri</span>
                    </div>
                    <div class="mt-3 max-h-80 space-y-2 overflow-y-auto pr-1">
                        @forelse ($semuaLog as $revision)
                            <div class="rounded-lg border border-slate-200 bg-slate-50/70 px-3 py-2">
                                <div class="flex flex-wrap items-center justify-between gap-1">
                                    <p class="text-[11px] font-bold text-slate-700">
                                        {{ $revision->orderItem?->nama_item ?: 'Seluruh order' }} &middot; Revisi {{ $revision->revisi_ke }}
                                    </p>
                                    <span class="text-[10px] font-semibold {{ filled($revision->admin_id) ? 'text-amber-700' : 'text-fuchsia-700' }}">
                                        {{ filled($revision->admin_id) ? 'Admin' : 'Desainer' }}
                                    </span>
                                </div>
                                <p class="mt-0.5 whitespace-pre-line text-[11px] leading-5 text-slate-600">{{ $revision->catatan_revisi }}</p>
                                <p class="mt-0.5 text-[10px] text-slate-400">{{ $revision->created_at?->format('d/m/Y H:i') ?? '-' }} &middot; {{ $revision->statusLabel() }}</p>
                            </div>
                        @empty
                            <p class="rounded-lg border border-dashed border-slate-300 px-3 py-6 text-center text-xs text-slate-500">Belum ada log revisi.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        {{-- Popup pratinjau TIFF --}}
        <div x-show="tiffUrl" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4" @click.self="tiffUrl = null">
            <div class="w-full max-w-4xl rounded-2xl bg-white p-5 shadow-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-emerald-600">Pratinjau File Cetak</p>
                        <p class="mt-1 truncate text-sm font-bold text-slate-900" x-text="tiffNama"></p>
                    </div>
                    <button type="button" @click="tiffUrl = null" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-200">Tutup</button>
                </div>
                <div class="mt-4 flex min-h-64 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <img :src="tiffUrl" :alt="tiffNama" x-on:error="gagal = true" x-show="!gagal" class="max-h-[60vh] max-w-full object-contain">
                    <p x-show="gagal" x-cloak class="text-center text-sm text-slate-500">
                        Browser tidak dapat merender TIFF. Unduh file untuk melihat isinya.
                    </p>
                </div>
                <a :href="tiffDownload" class="mt-4 inline-flex rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Unduh TIFF</a>
            </div>
        </div>
    </div>
@endsection
