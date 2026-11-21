@extends('layouts.app')

@section('title', 'Ringkasan Nota '.$order->no_nota)
@section('section', 'Ringkasan Nota')
@section('page-title', 'Ringkasan Nota')

@push('styles')
    <style>
        @media print {
            @page {
                margin: 12mm;
            }

            aside,
            header,
            .no-print {
                display: none !important;
            }

            .lg\:pl-72 {
                padding-left: 0 !important;
            }

            main {
                padding: 0 !important;
            }

            body {
                background: #fff !important;
            }

            .print-card {
                box-shadow: none !important;
            }
        }
    </style>
@endpush

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        {{-- Action bar --}}
        <div class="no-print flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-700">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
                Kembali ke Daftar Nota
            </a>

            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    onclick="window.print()"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 9.75V4.5h10.5v5.25M6.75 18h-1.5A2.25 2.25 0 0 1 3 15.75v-3A2.25 2.25 0 0 1 5.25 10.5h13.5A2.25 2.25 0 0 1 21 12.75v3A2.25 2.25 0 0 1 18.75 18h-1.5M6.75 14.25h10.5v6H6.75v-6Z" />
                    </svg>
                    Cetak Nota
                </button>

                <a
                    href="{{ route('admin.orders.pdf', $order) }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15h6m-6 3h4.5" />
                    </svg>
                    Cetak PDF
                </a>

                <a
                    href="{{ $waLink }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12.04 2c-5.5 0-9.96 4.46-9.96 9.96 0 1.76.46 3.45 1.32 4.94L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22c5.5 0 9.96-4.46 9.96-9.96S17.54 2 12.04 2Zm0 18.13c-1.5 0-2.97-.4-4.25-1.16l-.3-.18-3.12.82.83-3.04-.2-.31a8.15 8.15 0 0 1-1.26-4.3c0-4.54 3.7-8.24 8.24-8.24 4.54 0 8.24 3.7 8.24 8.24 0 4.54-3.7 8.17-8.18 8.17Zm4.52-6.16c-.25-.12-1.47-.72-1.7-.8-.23-.09-.4-.13-.56.12-.17.25-.64.8-.79.97-.14.16-.29.18-.54.06-.25-.12-1.05-.39-2-1.24-.74-.66-1.24-1.47-1.38-1.72-.15-.25-.02-.39.11-.51.11-.11.25-.29.37-.43.13-.15.17-.25.25-.41.08-.17.04-.31-.02-.43-.06-.12-.56-1.34-.76-1.83-.2-.48-.4-.42-.56-.42h-.48c-.17 0-.43.06-.66.31-.23.25-.87.85-.87 2.07 0 1.22.89 2.4 1.01 2.56.12.17 1.75 2.67 4.24 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.08.15-1.18-.06-.11-.23-.17-.48-.29Z" />
                    </svg>
                    Kirim Ringkasan ke WhatsApp
                </a>
            </div>
        </div>

        {{-- Nota header --}}
        <section class="print-card overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-slate-900 to-indigo-950 px-6 py-6 text-white sm:px-8">
                <div class="pointer-events-none absolute -right-12 -top-16 h-48 w-48 rounded-full bg-indigo-500/20 blur-2xl"></div>
                <div class="relative flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-indigo-300">Ringkasan Nota Digital Printing</p>
                        <p class="mt-1 font-mono text-xl font-bold tracking-tight sm:text-2xl">{{ $order->no_nota }}</p>
                        <p class="mt-2 text-sm text-slate-300">{{ $order->created_at?->format('d F Y, H:i') }} WIB</p>
                    </div>
                    <div class="flex flex-col items-start gap-2 sm:items-end">
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-[11px] font-bold uppercase tracking-wider ring-1
                            {{ $order->status_bayar === \App\Models\Order::STATUS_BAYAR_LUNAS
                                ? 'bg-emerald-400/15 text-emerald-300 ring-emerald-400/30'
                                : ($order->status_bayar === \App\Models\Order::STATUS_BAYAR_DP
                                    ? 'bg-amber-400/15 text-amber-300 ring-amber-400/30'
                                    : 'bg-rose-400/15 text-rose-300 ring-rose-400/30') }}">
                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                            {{ $order->statusBayarLabel() }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-[11px] font-semibold text-slate-200 ring-1 ring-white/15">
                            {{ $order->statusDesainLabel() }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid gap-5 border-b border-slate-100 px-6 py-5 sm:grid-cols-2 sm:px-8 lg:grid-cols-4">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Instansi</p>
                    <p class="mt-1 text-sm font-bold text-slate-900">{{ $order->customer?->instansi ?? \App\Models\Customer::INSTANSI_DEFAULT }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Nama Pelanggan / PIC</p>
                    <p class="mt-1 text-sm font-bold text-slate-900">{{ $order->customer?->nama_pelanggan }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">WhatsApp</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">{{ $order->customer?->no_wa }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Kategori Harga</p>
                    <p class="mt-1 text-sm font-semibold capitalize text-slate-700">{{ $order->customer?->kategori }}</p>
                </div>
                @if ($order->customer?->alamat)
                    <div class="sm:col-span-2 lg:col-span-3">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Alamat</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $order->customer->alamat }}</p>
                    </div>
                @endif
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Deadline</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">{{ $order->deadline?->format('d/m/Y H:i') ?? '—' }}</p>
                </div>
            </div>

            {{-- Rincian biaya Digital Printing --}}
            <div class="px-6 py-6 sm:px-8">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-base font-bold text-slate-900">Rincian Biaya Digital Printing</h3>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-slate-500">{{ $order->items->count() }} item</span>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-[10px] uppercase tracking-wider text-slate-400">
                                <th class="pb-2.5 pr-3 font-semibold">Produk</th>
                                <th class="pb-2.5 pr-3 font-semibold">Ukuran / Finishing</th>
                                <th class="pb-2.5 pr-3 text-center font-semibold">Qty</th>
                                <th class="pb-2.5 pr-3 text-right font-semibold">Luas Tagihan</th>
                                <th class="pb-2.5 pr-3 text-right font-semibold">Harga Satuan</th>
                                <th class="pb-2.5 text-right font-semibold">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($order->items as $item)
                                <tr class="align-top">
                                    <td class="py-3 pr-3">
                                        <p class="font-semibold text-slate-900">{{ $item->product?->nama_produk }}</p>
                                        <p class="mt-0.5 text-[11px] uppercase tracking-wider text-slate-400">{{ $item->product?->kategori_produk }}</p>
                                    </td>
                                    <td class="py-3 pr-3 text-slate-600">
                                        @if ($item->isMeteran())
                                            <p>{{ number_format((float) $item->lebar_m, 2, ',', '.') }} m × {{ number_format((float) $item->tinggi_m, 2, ',', '.') }} m</p>
                                            <p class="mt-0.5 text-[11px] text-slate-400">{{ $item->finishing?->nama_finishing ?? 'Tanpa finishing' }}</p>
                                        @else
                                            <p class="text-slate-500">Produk satuan</p>
                                        @endif
                                    </td>
                                    <td class="py-3 pr-3 text-center font-semibold text-slate-700">{{ $item->qty }}</td>
                                    <td class="py-3 pr-3 text-right text-slate-600">
                                        {{ $item->luas_tagihan_m2 !== null ? number_format((float) $item->luas_tagihan_m2, 2, ',', '.').' m²' : '—' }}
                                    </td>
                                    <td class="py-3 pr-3 text-right text-slate-600">Rp {{ number_format((float) $item->harga_satuan_dipakai, 0, ',', '.') }}</td>
                                    <td class="py-3 text-right font-bold text-slate-900">Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-5 flex justify-end">
                    <div class="w-full max-w-sm space-y-2 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-500">Total Tagihan</span>
                            <span class="font-bold text-slate-900">Rp {{ number_format((float) $order->total_tagihan, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-500">DP Dibayar</span>
                            <span class="font-semibold text-slate-700">Rp {{ number_format((float) $order->jumlah_dp, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-200 pt-2 text-sm">
                            <span class="font-semibold text-slate-600">Sisa Tagihan</span>
                            <span class="text-base font-bold {{ $order->sisaTagihan() > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                Rp {{ number_format($order->sisaTagihan(), 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Briefing desain --}}
        <section class="print-card rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Briefing Desain</h3>
                    <p class="mt-1 text-sm text-slate-500">Materi dan lampiran yang diteruskan ke divisi desainer.</p>
                </div>
                <span class="shrink-0 rounded-full bg-indigo-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-indigo-600 ring-1 ring-indigo-100">{{ $order->statusDesainLabel() }}</span>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Catatan / Materi Desain</p>
                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $order->catatan_desain ?: 'Tidak ada catatan desain.' }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Lampiran Briefing</p>
                    @if ($order->lampiranBriefingUrl())
                        <a href="{{ $order->lampiranBriefingUrl() }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                            </svg>
                            Buka Lampiran Briefing
                        </a>
                    @else
                        <p class="mt-2 text-sm text-slate-500">Tidak ada lampiran diunggah.</p>
                    @endif
                </div>
            </div>
        </section>

        {{-- Catatan pengecualian bukti (audit) --}}
        @if ($order->catatan_pengecualian)
            <section class="print-card rounded-2xl border border-amber-200 bg-amber-50/60 p-6 shadow-sm">
                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-amber-600">Audit &middot; Pengecualian Bukti</p>
                <h3 class="mt-1 text-base font-bold text-slate-900">Catatan Pengecualian</h3>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $order->catatan_pengecualian }}</p>
            </section>
        @endif

        {{-- SPK Advertising --}}
        @if ($order->advertisingSpks->isNotEmpty())
            <section class="print-card overflow-hidden rounded-2xl border-2 border-dashed border-amber-300 bg-amber-50/50 shadow-sm">
                <div class="flex flex-col gap-2 border-b border-amber-200/80 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-amber-600">Lembar SPK Divisi Advertising</p>
                        <h3 class="mt-1 text-base font-bold text-slate-900">Rangka / Pemasangan / Papan Nama</h3>
                    </div>
                    <span class="inline-flex shrink-0 items-center gap-1.5 self-start rounded-full bg-emerald-100 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-emerald-700 ring-1 ring-emerald-200 sm:self-auto">
                        Non-Billing · Tanpa Nominal
                    </span>
                </div>

                <div class="space-y-4 p-6">
                    @foreach ($order->advertisingSpks as $spk)
                        <div class="rounded-xl border border-amber-200 bg-white p-4">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Jenis Pekerjaan</p>
                                    <p class="mt-1 text-sm font-bold text-slate-900">{{ $spk->jenis_pekerjaan }}</p>
                                </div>
                                <span class="inline-flex shrink-0 items-center gap-1.5 self-start rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-amber-700">
                                    {{ $spk->statusLapanganLabel() }}
                                </span>
                            </div>

                            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <dt class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Ukuran Konstruksi</dt>
                                    <dd class="mt-1 text-sm font-medium text-slate-700">{{ $spk->ukuran_konstruksi }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Lokasi Pasang</dt>
                                    <dd class="mt-1 text-sm font-medium text-slate-700">{{ $spk->lokasi_pasang }}</dd>
                                </div>
                                <div class="sm:col-span-2">
                                    <dt class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Catatan Teknis</dt>
                                    <dd class="mt-1 whitespace-pre-line text-sm text-slate-600">{{ $spk->catatan_teknis ?: '—' }}</dd>
                                </div>
                            </dl>
                        </div>
                    @endforeach

                    <p class="text-[11px] leading-5 text-amber-700">
                        Lembar ini hanya menerbitkan pekerjaan untuk tim rangka &amp; pemasangan. Nominal jasa advertising tidak dihitung pada tagihan pelanggan.
                    </p>
                </div>
            </section>
        @endif

        <div class="no-print flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs leading-5 text-slate-500">
                Nota dibuat oleh <span class="font-semibold text-slate-700">{{ $order->user?->name }}</span>.
                Simpan atau cetak ringkasan ini untuk pelanggan.
            </p>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.orders.create') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
                    Input Order Baru
                </a>
                <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">
                    Daftar Nota
                </a>
            </div>
        </div>
    </div>
@endsection
