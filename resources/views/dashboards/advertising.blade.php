@extends('layouts.app')

@section('title', 'Dashboard Advertising')
@section('section', 'Divisi Advertising')
@section('page-title', 'Dashboard Advertising')

@section('content')
    <div class="space-y-6">
        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-lime-600 via-emerald-700 to-teal-800 px-6 py-7 text-white shadow-xl shadow-emerald-900/10 sm:px-8">
            <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-white/10 blur-2xl"></div>
            <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-lime-100">Workspace Advertising</p>
                    <h2 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">SPK Lapangan</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-emerald-100">
                        Terima dan update progres pekerjaan rangka, pemasangan, dan papan nama. Data tagihan tidak ditampilkan di divisi ini.
                    </p>
                </div>
                <div class="shrink-0 rounded-xl bg-white/10 px-4 py-3 text-right ring-1 ring-white/15 backdrop-blur">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-lime-100">Total SPK</p>
                    <p class="mt-1 text-2xl font-bold">{{ $spks->total() }}</p>
                    <p class="mt-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-emerald-100">Non-Billing · Rp 0</p>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Daftar SPK Advertising</h3>
                    <p class="mt-1 text-sm text-slate-500">Progres: antrean &rarr; perakitan &rarr; pemasangan &rarr; selesai.</p>
                </div>
                <form method="GET" action="{{ route('advertising.dashboard') }}" class="flex flex-wrap items-end gap-2">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500" for="status">Filter Status</label>
                        <select id="status" name="status" class="mt-1 h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm shadow-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-500/20">
                            <option value="">Semua status</option>
                            @foreach ($statusOptions as $key => $label)
                                <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="h-10 rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white transition hover:bg-emerald-800">Terapkan</button>
                </form>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                @forelse ($spks as $spk)
                    @php
                        $berikutnya = $nextStatus[$spk->status_lapangan] ?? null;
                        $statusClass = match ($spk->status_lapangan) {
                            'antrean' => 'bg-slate-100 text-slate-700 ring-slate-200',
                            'perakitan' => 'bg-sky-50 text-sky-700 ring-sky-100',
                            'pemasangan' => 'bg-indigo-50 text-indigo-700 ring-indigo-100',
                            default => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                        };
                    @endphp
                    <article class="rounded-2xl border border-slate-200 bg-slate-50/60 p-5 ring-1 ring-slate-100">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-slate-500">SPK #{{ $spk->id }} &middot; {{ $spk->order?->no_nota }}</p>
                                <h4 class="mt-1 text-lg font-bold text-slate-900">{{ $spk->jenis_pekerjaan }}</h4>
                                <p class="mt-0.5 text-sm text-slate-600">
                                    {{ $spk->order?->customer?->nama_pelanggan }} — {{ $spk->order?->customer?->instansi }}
                                </p>
                            </div>
                            <span class="shrink-0 rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-wider ring-1 {{ $statusClass }}">
                                {{ $spk->statusLapanganLabel() }}
                            </span>
                        </div>

                        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                            <div class="rounded-xl bg-white p-3 ring-1 ring-slate-200">
                                <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Ukuran Konstruksi</dt>
                                <dd class="mt-1 font-semibold text-slate-800">{{ $spk->ukuran_konstruksi }}</dd>
                            </div>
                            <div class="rounded-xl bg-white p-3 ring-1 ring-slate-200">
                                <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Lokasi Pasang</dt>
                                <dd class="mt-1 font-semibold text-slate-800">{{ $spk->lokasi_pasang }}</dd>
                            </div>
                        </dl>

                        <div class="mt-3 rounded-xl bg-white p-3 ring-1 ring-slate-200">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Catatan Teknis</p>
                            <p class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-600">
                                {{ $spk->catatan_teknis ?: 'Tidak ada catatan teknis.' }}
                            </p>
                        </div>

                        @if ($berikutnya)
                            <form method="POST" action="{{ route('advertising.spks.status', $spk) }}" class="mt-4">
                                @csrf
                                <input type="hidden" name="status_lapangan" value="{{ $berikutnya }}">
                                <button type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700">
                                    Lanjut ke {{ $statusOptions[$berikutnya] }}
                                </button>
                            </form>
                        @else
                            <div class="mt-4 rounded-xl bg-slate-800 px-4 py-2.5 text-center text-sm font-bold text-white">
                                Pekerjaan Selesai
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center lg:col-span-2">
                        <p class="text-sm font-semibold text-slate-700">Tidak ada SPK yang ditampilkan.</p>
                        <p class="mt-1 text-xs text-slate-500">SPK baru dari Admin akan muncul di sini.</p>
                    </div>
                @endforelse
            </div>

            {{ $spks->links() }}
        </section>
    </div>
@endsection
