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
                        Terima tugas desain per nota, unggah preview untuk ACC Admin, lalu kirim TIFF per item dan satu master desain.
                    </p>
                </div>
                <div class="shrink-0 rounded-xl bg-white/10 px-4 py-3 text-right ring-1 ring-white/15 backdrop-blur">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-fuchsia-200">Nota dalam antrean</p>
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
            <a href="{{ route('desainer.riwayat') }}" class="rounded-t-lg border-b-2 border-transparent px-4 py-3 text-sm font-semibold text-slate-500 transition hover:text-slate-800">
                Riwayat Desain
            </a>
        </div>

        {{-- TAB: Antrean Desain — satu kartu per nota --}}
        <div x-show="tab === 'antrean'" x-cloak class="space-y-4">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Antrean Desain</h3>
                    <p class="mt-0.5 text-xs text-slate-500">Satu kartu mewakili satu nota. Nota yang sudah selesai desain tidak ditampilkan.</p>
                </div>
                <span class="rounded-full bg-fuchsia-50 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-fuchsia-700 ring-1 ring-fuchsia-100">
                    {{ $queue->total() }} nota
                </span>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($queue as $nota)
                    @php
                        $progres = $nota->progresDesainPersen();
                        $diterima = $nota->diterimaDesainer();
                        $bisaTerima = blank($nota->designer_id)
                            && $nota->items->isNotEmpty()
                            && $nota->items->every(fn ($item) => $item->status_produksi === \App\Models\OrderItem::STATUS_MENUNGGU_DESAIN);
                        $jumlahItem = $nota->items->count();
                    @endphp
                    <article class="flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-fuchsia-200 hover:shadow-md">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">ID Nota</p>
                                <p class="mt-0.5 font-mono text-sm font-bold text-fuchsia-700">{{ $nota->no_nota }}</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-600">
                                {{ $nota->statusProduksiRingkas() }}
                            </span>
                        </div>

                        <div class="mt-4 min-w-0">
                            <p class="truncate text-base font-bold text-slate-900" title="{{ $nota->customer?->nama_pelanggan }}">
                                {{ $nota->customer?->nama_pelanggan ?: '-' }}
                            </p>
                            <p class="mt-0.5 truncate text-xs text-slate-500">{{ $nota->instansiLabel() }}</p>
                            <p class="mt-1 inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $diterima ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-100' }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $diterima ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                {{ $diterima ? 'Diterima Desainer' : 'Menunggu Diterima' }}
                            </p>
                        </div>

                        <dl class="mt-4 grid grid-cols-2 gap-3 rounded-xl bg-slate-50 p-3 ring-1 ring-slate-100">
                            <div>
                                <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Jumlah Item</dt>
                                <dd class="mt-0.5 text-sm font-bold text-slate-800">{{ $jumlahItem }} item</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Deadline</dt>
                                <dd class="mt-0.5 text-xs font-semibold text-slate-700">{{ $nota->deadline?->format('d/m/Y H:i') ?? '-' }}</dd>
                            </div>
                        </dl>

                        <div class="mt-4">
                            <div class="flex items-center justify-between text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                                <span>Progress Desain</span>
                                <span>{{ $progres }}%</span>
                            </div>
                            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-valuenow="{{ $progres }}" aria-valuemin="0" aria-valuemax="100">
                                <div class="h-full rounded-full bg-gradient-to-r from-fuchsia-500 to-indigo-500 transition-all" style="width: {{ $progres }}%"></div>
                            </div>
                        </div>

                        <div class="mt-5 flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
                            @if ($bisaTerima)
                                <form method="POST" action="{{ route('desainer.orders.terima', $nota) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-fuchsia-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-fuchsia-700">
                                        Terima Tugas
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('desainer.orders.items', $nota) }}"
                                    class="inline-flex items-center justify-center rounded-lg bg-fuchsia-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-fuchsia-700">
                                    Detail
                                </a>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="md:col-span-2 xl:col-span-3 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
                        <p class="text-sm font-semibold text-slate-700">Tidak ada nota yang menunggu desain.</p>
                        <p class="mt-1 text-xs text-slate-500">Nota yang sudah selesai desain otomatis masuk ke Riwayat Desain.</p>
                    </div>
                @endforelse
            </div>

            @if ($queue->hasPages())
                <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    {{ $queue->appends(['tab' => 'antrean'])->links() }}
                </div>
            @endif
        </div>

        {{-- TAB: Arsip Master Desain --}}
        <div x-show="tab === 'arsip'" x-cloak class="space-y-5">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Arsip Master Desain</h3>
                        <p class="mt-1 text-sm text-slate-500">Unduh ulang file master .cdr/.ai/.pdf/.psd/.zip berdasarkan pelanggan atau instansi.</p>
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
                            @forelse ($arsip as $arsipOrder)
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-4 py-3 font-medium text-slate-700">
                                        <p>{{ $arsipOrder->no_nota }}</p>
                                        <p class="mt-0.5 text-[10px] text-slate-400">{{ $arsipOrder->items->count() }} item</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-slate-800">{{ $arsipOrder->customer?->nama_pelanggan }}</p>
                                        <p class="text-xs text-slate-500">{{ $arsipOrder->customer?->instansi }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ $arsipOrder->items->first()?->product?->nama_produk ?: '-' }}</td>
                                    <td class="px-4 py-3 font-mono text-xs text-slate-500">
                                        <span class="block max-w-[16rem] truncate" title="{{ basename((string) $arsipOrder->file_master_arsip) }}">
                                            {{ basename((string) $arsipOrder->file_master_arsip) }}
                                        </span>
                                        <span class="mt-0.5 block text-[10px] text-slate-400">Satu master per nota</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('desainer.arsip.order.unduh', $arsipOrder) }}"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-fuchsia-200 bg-fuchsia-50 px-3 py-1.5 text-xs font-semibold text-fuchsia-700 hover:bg-fuchsia-100">
                                            Unduh Master
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">Tidak ada master desain yang cocok dengan pencarian.</td>
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
