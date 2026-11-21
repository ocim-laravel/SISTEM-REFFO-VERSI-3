@extends('layouts.app')

@use('App\Support\FormatLaporan')

@section('title', 'Dashboard Owner')
@section('section', 'Divisi Owner')
@section('page-title', 'Dashboard Monitoring Produksi')

@section('content')
    <div class="space-y-6">
        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-slate-900 to-indigo-950 px-6 py-7 text-white shadow-xl shadow-slate-900/10 sm:px-8">
            <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-indigo-500/20 blur-3xl"></div>
            <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-indigo-300">Owner Workspace</p>
                    <h2 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Selamat datang, {{ auth()->user()->name }}</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">Pantau antrean produksi, deadline nota, serta rekap transaksi seluruh instansi secara real-time.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('owner.reports.materials') }}" class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2.5 text-xs font-semibold text-white ring-1 ring-white/15 transition hover:bg-white/20">
                        Laporan Bahan
                    </a>
                    <a href="{{ route('owner.reports.profit-loss') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-500 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-indigo-900/30 transition hover:bg-indigo-400">
                        Laba Rugi
                    </a>
                </div>
            </div>
        </section>

        {{-- Kartu statistik live --}}
        <section>
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">Antrean Produksi Live</h3>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold text-emerald-700 ring-1 ring-emerald-100">
                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                    Data terkini
                </span>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                @foreach ([
                    ['label' => 'Antrean Desain', 'key' => 'desain', 'warna' => 'bg-indigo-500', 'ring' => 'ring-indigo-100'],
                    ['label' => 'Antrean Cetak', 'key' => 'cetak', 'warna' => 'bg-sky-500', 'ring' => 'ring-sky-100'],
                    ['label' => 'Antrean Finishing', 'key' => 'finishing', 'warna' => 'bg-amber-500', 'ring' => 'ring-amber-100'],
                    ['label' => 'Tim Advertising', 'key' => 'advertising', 'warna' => 'bg-violet-500', 'ring' => 'ring-violet-100'],
                    ['label' => 'Siap Diambil', 'key' => 'siap_diambil', 'warna' => 'bg-emerald-500', 'ring' => 'ring-emerald-100'],
                ] as $kartu)
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">{{ $kartu['label'] }}</p>
                            <span class="h-2.5 w-2.5 rounded-full {{ $kartu['warna'] }}"></span>
                        </div>
                        <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ $statistik[$kartu['key']] }}</p>
                        <p class="mt-1 text-[11px] text-slate-500">item / SPK aktif</p>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-3">
            {{-- Peringatan deadline --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-1">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Peringatan Deadline</h3>
                        <p class="mt-0.5 text-[11px] text-slate-500">3 hari ke depan &amp; nota terlambat</p>
                    </div>
                    <span class="rounded-full bg-rose-50 px-2.5 py-1 text-[10px] font-bold text-rose-600 ring-1 ring-rose-100">{{ $deadlines->count() }}</span>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($deadlines as $deadline)
                        <div class="flex items-start justify-between gap-3 px-5 py-3.5">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $deadline['no_nota'] }}</p>
                                <p class="mt-0.5 truncate text-[11px] text-slate-500">{{ $deadline['instansi'] }} · {{ $deadline['pic'] }}</p>
                                <p class="mt-1 text-[11px] text-slate-400">{{ $deadline['deadline']?->format('d/m/Y H:i') }}</p>
                            </div>
                            <span class="shrink-0 rounded-lg px-2 py-1 text-[10px] font-bold {{ $deadline['terlambat'] ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700' }}">
                                @if ($deadline['terlambat'])
                                    Terlambat {{ abs($deadline['sisa_hari']) }}h
                                @elseif ($deadline['sisa_hari'] === 0)
                                    Hari ini
                                @else
                                    {{ $deadline['sisa_hari'] }} hari
                                @endif
                            </span>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-center">
                            <p class="text-sm font-medium text-slate-500">Tidak ada deadline mendesak.</p>
                            <p class="mt-1 text-[11px] text-slate-400">Seluruh nota dalam batas aman.</p>
                        </div>
                    @endforelse
                </div>
            </section>

            {{-- Ringkasan keuangan --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
                <h3 class="text-sm font-bold text-slate-900">Ringkasan Transaksi Keseluruhan</h3>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Total Nota</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900">{{ $totalRekap['nota'] }}</p>
                    </div>
                    <div class="rounded-xl bg-indigo-50 p-4 ring-1 ring-indigo-100">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-indigo-500">Total Omzet</p>
                        <p class="mt-2 text-lg font-bold text-indigo-900">{{ FormatLaporan::rupiah($totalRekap['omzet']) }}</p>
                    </div>
                    <div class="rounded-xl bg-rose-50 p-4 ring-1 ring-rose-100">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-rose-500">Total Piutang</p>
                        <p class="mt-2 text-lg font-bold text-rose-900">{{ FormatLaporan::rupiah($totalRekap['piutang']) }}</p>
                    </div>
                </div>
                <p class="mt-4 text-[11px] leading-5 text-slate-500">Omzet dan piutang dihitung dari seluruh nota, dikelompokkan menurut instansi pada tabel di bawah.</p>
            </section>
        </div>

        {{-- Rekap instansi --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Rekap Transaksi per Instansi</h3>
                    <p class="mt-0.5 text-[11px] text-slate-500">Total nota, omzet, dan piutang dengan rincian per PIC.</p>
                </div>
                <span class="text-[11px] text-slate-400">{{ $rekap->count() }} instansi</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Instansi / PIC</th>
                            <th class="px-5 py-3 text-center font-semibold">Total Nota</th>
                            <th class="px-5 py-3 text-right font-semibold">Total Omzet</th>
                            <th class="px-5 py-3 text-right font-semibold">Piutang</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rekap as $grup)
                            <tr class="bg-slate-50/80">
                                <td class="px-5 py-3 font-bold text-slate-900">{{ $grup['instansi'] }}</td>
                                <td class="px-5 py-3 text-center font-bold text-slate-900">{{ $grup['total_nota'] }}</td>
                                <td class="px-5 py-3 text-right font-bold text-slate-900">{{ FormatLaporan::rupiah($grup['omzet']) }}</td>
                                <td class="px-5 py-3 text-right font-bold text-rose-700">{{ FormatLaporan::rupiah($grup['piutang']) }}</td>
                            </tr>
                            @foreach ($grup['pics'] as $pic)
                                <tr class="text-slate-600">
                                    <td class="py-2.5 pl-10 pr-5">
                                        <span class="text-slate-300">↳</span> {{ $pic['nama'] }}
                                    </td>
                                    <td class="px-5 py-2.5 text-center">{{ $pic['total_nota'] }}</td>
                                    <td class="px-5 py-2.5 text-right">{{ FormatLaporan::rupiah($pic['omzet']) }}</td>
                                    <td class="px-5 py-2.5 text-right text-rose-600">{{ FormatLaporan::rupiah($pic['piutang']) }}</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada transaksi tercatat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
