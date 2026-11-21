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
                <p class="mt-1 text-sm text-slate-500">Nota dikelompokkan per instansi agar semua PIC dalam satu perusahaan tetap terlihat.</p>
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
                    <label for="instansi" class="sr-only">Filter instansi</label>
                    <select
                        id="instansi"
                        name="instansi"
                        onchange="this.form.submit()"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-700 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 sm:w-64"
                    >
                        <option value="">Semua Instansi</option>
                        @foreach ($instansiOptions as $option)
                            <option value="{{ $option->instansi }}" @selected($instansi === $option->instansi)>
                                {{ $option->instansi }} ({{ $orderCounts[$option->instansi] ?? 0 }} nota)
                            </option>
                        @endforeach
                    </select>
                    @if ($instansi !== '')
                        <a href="{{ route('admin.orders.index', array_filter(['tab' => $tab === 'siap_diambil' ? 'siap_diambil' : null])) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">
                            Reset
                        </a>
                    @endif
                </form>
            </div>

            @if ($instansi !== '')
                <p class="mt-3 text-xs text-slate-500">
                    Menampilkan nota untuk instansi <span class="font-semibold text-slate-700">{{ $instansi }}</span>.
                </p>
            @endif
        </div>

        {{-- Daftar nota per instansi --}}
        @forelse ($groups as $namaInstansi => $groupOrders)
            @php
                $pics = $groupOrders->map(fn ($order) => $order->customer?->nama_pelanggan)->filter()->unique()->values();
                $groupTotal = $groupOrders->sum(fn ($order) => (float) $order->total_tagihan);
            @endphp

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50/70 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-900 text-white">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <h3 class="truncate text-base font-bold text-slate-900">{{ $namaInstansi }}</h3>
                                <p class="mt-0.5 text-[11px] text-slate-500">{{ $groupOrders->count() }} nota pada halaman ini · Rp {{ number_format((float) $groupTotal, 0, ',', '.') }}</p>
                            </div>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach ($pics as $pic)
                                <span class="rounded-full bg-white px-2.5 py-1 text-[10px] font-semibold text-slate-600 ring-1 ring-slate-200">{{ $pic }}</span>
                            @endforeach
                        </div>
                    </div>

                    <a href="{{ route('admin.orders.index', array_filter(['instansi' => $namaInstansi, 'tab' => $tab === 'siap_diambil' ? 'siap_diambil' : null])) }}" class="inline-flex shrink-0 items-center gap-1.5 self-start rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-600 transition hover:bg-slate-50 sm:self-auto">
                        Fokus instansi ini
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[860px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 text-[10px] uppercase tracking-wider text-slate-400">
                                <th class="px-5 py-3 font-semibold">No. Nota</th>
                                <th class="px-3 py-3 font-semibold">Pelanggan / PIC</th>
                                <th class="px-3 py-3 font-semibold">Item</th>
                                <th class="px-3 py-3 text-right font-semibold">Total</th>
                                <th class="px-3 py-3 font-semibold">Bayar</th>
                                <th class="px-3 py-3 font-semibold">Produksi</th>
                                <th class="px-5 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($groupOrders as $order)
                                @php
                                    $bayarClass = match ($order->status_bayar) {
                                        \App\Models\Order::STATUS_BAYAR_LUNAS => 'bg-emerald-100 text-emerald-700',
                                        \App\Models\Order::STATUS_BAYAR_DP => 'bg-amber-100 text-amber-700',
                                        default => 'bg-rose-100 text-rose-700',
                                    };
                                    $statusUnik = $order->items->pluck('status_produksi')->unique();
                                    $produksiLabel = $statusUnik->count() === 1
                                        ? $order->items->first()?->statusProduksiLabel()
                                        : 'Beragam ('.$statusUnik->count().' status)';
                                    $itemRingkas = $order->items->take(2)->map(fn ($item) => $item->product?->nama_produk)->filter()->implode(', ');
                                    $sisaItem = max($order->items->count() - 2, 0);
                                @endphp
                                <tr class="align-top transition hover:bg-slate-50/70">
                                    <td class="px-5 py-4">
                                        <a href="{{ route('admin.orders.summary', $order) }}" class="font-mono text-xs font-bold text-indigo-600 hover:text-indigo-800">{{ $order->no_nota }}</a>
                                        <p class="mt-1 text-[11px] text-slate-400">{{ $order->created_at?->format('d/m/Y H:i') }}</p>
                                    </td>
                                    <td class="px-3 py-4">
                                        <p class="font-semibold text-slate-800">{{ $order->customer?->nama_pelanggan }}</p>
                                        <p class="mt-0.5 text-[11px] text-slate-400">{{ $order->customer?->no_wa }}</p>
                                    </td>
                                    <td class="px-3 py-4">
                                        <p class="max-w-[16rem] text-xs text-slate-600">{{ $itemRingkas ?: '—' }}@if ($sisaItem > 0)<span class="text-slate-400"> +{{ $sisaItem }} lainnya</span>@endif</p>
                                        @if ($order->advertisingSpks->isNotEmpty())
                                            <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-amber-700">SPK Advertising</span>
                                        @endif
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
                                        <span class="text-xs font-medium text-slate-600">{{ $produksiLabel }}</span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-col items-end gap-2">
                                            <a href="{{ route('admin.orders.summary', $order) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-1.5 text-[11px] font-semibold text-white transition hover:bg-slate-800">
                                                Lihat Nota
                                            </a>

                                            @if ($tab === 'siap_diambil')
                                                <div class="flex flex-wrap justify-end gap-1.5">
                                                    @if ($order->status_bayar !== \App\Models\Order::STATUS_BAYAR_LUNAS)
                                                        <form method="POST" action="{{ route('admin.orders.pelunasan', $order) }}" onsubmit="return confirm('Tandai nota {{ $order->no_nota }} sebagai LUNAS?')">
                                                            @csrf
                                                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-[11px] font-semibold text-white transition hover:bg-emerald-700">
                                                                Pelunasan
                                                            </button>
                                                        </form>
                                                    @endif

                                                    <form method="POST" action="{{ route('admin.orders.serah-terima', $order) }}" onsubmit="return confirm('Serah terima barang nota {{ $order->no_nota }}?')">
                                                        @csrf
                                                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-50">
                                                            Serah Terima
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5A3.375 3.375 0 0 0 10.125 2.25H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                </span>
                <h3 class="mt-3 text-base font-bold text-slate-900">
                    {{ $tab === 'siap_diambil' ? 'Belum ada nota yang siap diambil' : 'Belum ada nota tersimpan' }}
                </h3>
                <p class="mx-auto mt-1 max-w-md text-sm leading-6 text-slate-500">
                    {{ $tab === 'siap_diambil'
                        ? 'Tab ini terisi otomatis setelah seluruh item menyelesaikan proses finishing.'
                        : 'Mulai dengan membuat nota order baru untuk pelanggan.' }}
                </p>
                @if ($instansi !== '')
                    <a href="{{ route('admin.orders.index', array_filter(['tab' => $tab === 'siap_diambil' ? 'siap_diambil' : null])) }}" class="mt-4 inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-700">
                        Tampilkan semua instansi
                    </a>
                @else
                    <a href="{{ route('admin.orders.create') }}" class="mt-4 inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700">
                        Input Order Baru
                    </a>
                @endif
            </div>
        @endforelse

        @if ($orders->hasPages())
            <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
@endsection
