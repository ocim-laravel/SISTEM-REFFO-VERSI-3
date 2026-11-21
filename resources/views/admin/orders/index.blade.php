@extends('layouts.app')

@section('title', 'Daftar Nota')
@section('section', 'Transaksi Admin')
@section('page-title', 'Daftar Nota')

@section('content')
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <h2 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Daftar Nota</h2>
                <p class="mt-1 text-sm text-slate-500">Satu baris mewakili satu nota. Buka Detail untuk melihat rincian item, instansi, dan PIC.</p>
            </div>
            <a
                href="{{ route('admin.orders.create') }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-700"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Input Order Baru
            </a>
        </div>

        {{-- Ringkasan angka --}}
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Total Nota</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($totalNotaCount, 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-slate-500">Seluruh nota yang tersimpan.</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Belum Lunas</p>
                <p class="mt-2 text-2xl font-bold text-amber-600">{{ number_format($belumLunasCount, 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-slate-500">Termasuk nota berstatus DP.</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Siap Diambil</p>
                <p class="mt-2 text-2xl font-bold text-emerald-600">{{ number_format($siapDiambilCount, 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-slate-500">Seluruh item selesai finishing.</p>
            </div>
        </div>

        {{-- Tab & filter --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="inline-flex w-full rounded-xl bg-slate-100 p-1 sm:w-auto">
                    <a
                        href="{{ route('admin.orders.index', array_filter(['instansi' => $instansi ?: null])) }}"
                        class="flex flex-1 items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition sm:flex-none {{ $tab === 'semua' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}"
                    >
                        Semua Nota
                        <span class="rounded-full bg-slate-200/80 px-2 py-0.5 text-[10px] font-bold text-slate-600">{{ $totalNotaCount }}</span>
                    </a>
                    <a
                        href="{{ route('admin.orders.index', array_filter(['tab' => 'siap_diambil', 'instansi' => $instansi ?: null])) }}"
                        class="flex flex-1 items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition sm:flex-none {{ $tab === 'siap_diambil' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}"
                    >
                        Siap Diambil
                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700">{{ $siapDiambilCount }}</span>
                    </a>
                </div>

                <form method="GET" action="{{ route('admin.orders.index') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <label for="q" class="sr-only">Cari nota</label>
                    <input
                        id="q"
                        name="q"
                        type="search"
                        value="{{ $q ?? '' }}"
                        placeholder="Cari no. nota / instansi / pelanggan"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-700 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 sm:w-64"
                    >
                    <label for="instansi" class="sr-only">Filter instansi</label>
                    <select
                        id="instansi"
                        name="instansi"
                        onchange="this.form.submit()"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-700 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 sm:w-56"
                    >
                        <option value="">Semua Instansi</option>
                        @foreach ($instansiOptions as $option)
                            <option value="{{ $option->instansi }}" @selected($instansi === $option->instansi)>
                                {{ $option->instansi }} ({{ $orderCounts[$option->instansi] ?? 0 }} nota)
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="h-10 rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-800">Cari</button>
                    @if ($instansi !== '' || $q !== '')
                        <a href="{{ route('admin.orders.index', ['tab' => $tab === 'siap_diambil' ? 'siap_diambil' : null]) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">
                            Reset
                        </a>
                    @endif
                </form>
            </div>
        </div>

        {{-- Daftar nota: satu baris per nota --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-4">
                <h3 class="text-sm font-bold text-slate-900">Daftar Nota</h3>
                <p class="mt-0.5 text-[11px] text-slate-500">{{ $orders->total() }} nota pada filter ini &middot; 15 nota per halaman.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px] text-left text-sm">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-semibold">No. Nota</th>
                            <th class="px-3 py-3 font-semibold">Instansi</th>
                            <th class="px-3 py-3 font-semibold">Pelanggan / PIC</th>
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
                                    <p class="mt-1 text-[11px] text-slate-400">{{ $order->items_count }} item pada nota &middot; {{ $order->created_at?->format('d/m/Y H:i') }}</p>
                                    @if ($order->advertisingSpks->isNotEmpty())
                                        <span class="mt-1 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-amber-700">SPK Advertising</span>
                                    @endif
                                </td>
                                <td class="px-3 py-4 font-semibold text-slate-800">{{ $order->instansiLabel() }}</td>
                                <td class="px-3 py-4">
                                    <p class="font-semibold text-slate-800">{{ $order->picLabel() }}</p>
                                    <p class="mt-0.5 text-[11px] text-slate-400">{{ $order->customer?->no_wa ?: '-' }}</p>
                                </td>
                                <td class="px-3 py-4 text-right">
                                    <p class="font-bold text-slate-900">Rp {{ number_format((float) $order->total_tagihan, 0, ',', '.') }}</p>
                                    @if ($order->sisaTagihan() > 0)
                                        <p class="mt-0.5 text-[10px] text-rose-500">Sisa Rp {{ number_format($order->sisaTagihan(), 0, ',', '.') }}</p>
                                    @endif
                                </td>
                                <td class="px-3 py-4">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider {{ $bayarClass }}">
                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                        {{ $order->statusBayarLabel() }}
                                    </span>
                                </td>
                                <td class="px-3 py-4">
                                    <span class="text-xs font-medium text-slate-600">{{ $order->statusProduksiRingkas() }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-col items-end gap-2">
                                        <div class="flex flex-wrap justify-end gap-1.5">
                                            <a href="{{ route('admin.orders.show', $order) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-1.5 text-[11px] font-semibold text-white transition hover:bg-slate-800">
                                                Detail
                                            </a>
                                            <a href="{{ route('admin.orders.summary', $order) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-50">
                                                Lihat Nota
                                            </a>
                                        </div>

                                        @if ($tab === 'siap_diambil')
                                            <div class="flex flex-col items-end gap-1.5">
                                                @if (! $order->bolehDiserahkan())
                                                    <a href="{{ route('admin.orders.pelunasan.create', $order) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-amber-500 px-3 py-1.5 text-[11px] font-bold text-white transition hover:bg-amber-600">
                                                        Pelunasan
                                                    </a>
                                                    <button type="button" disabled class="inline-flex cursor-not-allowed items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-[11px] font-bold text-slate-400">
                                                        Serah Terima
                                                    </button>
                                                    <p class="text-[10px] font-semibold text-rose-600">Pelunasan wajib sebelum serah terima</p>
                                                @else
                                                    <a href="{{ route('admin.orders.serah-terima.create', $order) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-[11px] font-bold text-emerald-700 transition hover:bg-emerald-100">
                                                        Serah Terima
                                                    </a>
                                                    @if ($order->buktiPelunasanUrl())
                                                        <a href="{{ $order->buktiPelunasanUrl() }}" target="_blank" rel="noopener" class="text-[10px] font-semibold text-indigo-600 hover:text-indigo-700">Lihat Bukti Pelunasan</a>
                                                    @endif
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center">
                                    <h3 class="text-base font-bold text-slate-900">
                                        {{ $tab === 'siap_diambil' ? 'Belum ada nota yang siap diambil' : 'Belum ada nota tersimpan' }}
                                    </h3>
                                    <p class="mx-auto mt-1 max-w-md text-sm leading-6 text-slate-500">
                                        {{ $tab === 'siap_diambil'
                                            ? 'Tab ini terisi otomatis setelah seluruh item menyelesaikan proses finishing.'
                                            : 'Mulai dengan membuat nota order baru untuk pelanggan.' }}
                                    </p>
                                    @if ($instansi !== '' || $q !== '')
                                        <a href="{{ route('admin.orders.index') }}" class="mt-4 inline-flex items-center text-xs font-semibold text-indigo-600 hover:text-indigo-700">Reset pencarian</a>
                                    @else
                                        <a href="{{ route('admin.orders.create') }}" class="mt-4 inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700">Input Order Baru</a>
                                    @endif
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
