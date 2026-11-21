@extends('layouts.app')

@section('title', 'Dashboard Admin')
@section('section', 'Divisi Admin')
@section('page-title', 'Dashboard Admin')

@section('content')
    <div class="mb-6 grid gap-4 sm:grid-cols-2">
        <a
            href="{{ route('admin.orders.create') }}"
            class="group flex items-center gap-4 rounded-2xl border border-indigo-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md"
        >
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/25">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
            </span>
            <span class="min-w-0">
                <span class="block text-sm font-bold text-slate-900">Input Order Baru</span>
                <span class="mt-0.5 block text-xs leading-5 text-slate-500">Buat nota baru dengan keranjang multi-item, brief desain, dan SPK advertising.</span>
            </span>
        </a>

        <a
            href="{{ route('admin.orders.index') }}"
            class="group flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md"
        >
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white shadow-lg shadow-slate-900/20">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12M8.25 17.25h12M3.75 6.75h.007v.008H3.75V6.75Zm0 5.25h.007v.008H3.75V12Zm0 5.25h.007v.008H3.75v-.008Z" />
                </svg>
            </span>
            <span class="min-w-0">
                <span class="block text-sm font-bold text-slate-900">Daftar Nota</span>
                <span class="mt-0.5 block text-xs leading-5 text-slate-500">Pantau nota per instansi, pelunasan, dan serah terima barang siap diambil.</span>
            </span>
        </a>
    </div>

    @include('dashboards.partials.foundation', [
        'roleLabel' => 'Administrator',
        'summary' => 'Kelola transaksi order Digital Printing dan SPK Advertising dari satu tempat. Setiap nota tersimpan dengan rincian biaya, status desain, DP, dan status produksi yang terisolasi per divisi.',
        'tasks' => [
            'Buat nota baru dengan data pelanggan, keranjang multi-item, dan SPK advertising non-billing.',
            'Pantau nota per instansi agar order Telkomsel dari PIC berbeda tetap terkelompok.',
            'Catat DP, pelunasan, dan serah terima barang dari tab Siap Diambil.',
            'Pastikan seluruh lampiran briefing desain tersimpan di media lokal media_sistem.',
        ],
    ])
@endsection
