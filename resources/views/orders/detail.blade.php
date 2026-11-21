@extends('layouts.app')

@section('title', 'Detail Order '.$order->no_nota)
@section('section', 'Audit '.$judulDivisi)
@section('page-title', 'Detail Order')

@section('content')
    <div class="mx-auto max-w-6xl space-y-6">
        <div>
            <a href="{{ route($kembaliRoute) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-700">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
                Kembali ke daftar sebelumnya
            </a>
        </div>

        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-slate-900 to-indigo-950 px-6 py-7 text-white shadow-xl sm:px-8">
            <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-indigo-500/20 blur-3xl"></div>
            <div class="relative flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-indigo-300">Detail Order · {{ $judulDivisi }}</p>
                    <h2 class="mt-2 font-mono text-2xl font-bold tracking-tight sm:text-3xl">{{ $order->no_nota }}</h2>
                    <p class="mt-2 text-sm text-slate-300">Dibuat {{ $order->created_at?->format('d/m/Y H:i') ?? '-' }} &middot; {{ $order->items->count() }} item</p>
                </div>
                <span class="shrink-0 rounded-xl bg-white/10 px-4 py-3 text-right ring-1 ring-white/15">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-indigo-200">Status Produksi</p>
                    <p class="mt-1 text-sm font-bold">{{ $order->statusProduksiRingkas() }}</p>
                </span>
            </div>
        </section>

        {{-- Identitas pelanggan --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-sm font-bold text-slate-900">Instansi &amp; PIC</h3>
            </div>
            <div class="grid gap-5 px-5 py-5 sm:grid-cols-2 lg:grid-cols-4 sm:px-6">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Instansi</p>
                    <p class="mt-1 text-sm font-bold text-slate-900">{{ $order->instansiLabel() }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Pelanggan / PIC</p>
                    <p class="mt-1 text-sm font-bold text-slate-900">{{ $order->picLabel() }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">WhatsApp</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">{{ $order->customer?->no_wa ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Deadline</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">{{ $order->deadline?->format('d/m/Y H:i') ?? '-' }}</p>
                </div>
                @if ($order->customer?->alamat)
                    <div class="sm:col-span-2 lg:col-span-4">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Alamat</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $order->customer->alamat }}</p>
                    </div>
                @endif
            </div>
        </section>

        {{-- Rincian item --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h3 class="text-sm font-bold text-slate-900">Rincian Item Order</h3>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold text-slate-500">{{ $order->items->count() }} item</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Produk</th>
                            <th class="px-3 py-3 font-semibold">Ukuran</th>
                            <th class="px-3 py-3 text-center font-semibold">Qty</th>
                            <th class="px-3 py-3 font-semibold">Finishing</th>
                            <th class="px-3 py-3 font-semibold">Status</th>
                            @if ($tampilkanHarga)
                                <th class="px-5 py-3 text-right font-semibold">Subtotal</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($order->items as $item)
                            <tr class="align-top">
                                <td class="px-5 py-3.5">
                                    <p class="font-semibold text-slate-900">{{ $item->product?->nama_produk ?: '-' }}</p>
                                    <p class="mt-0.5 text-[10px] uppercase tracking-wider text-slate-400">{{ $item->product?->kategori_produk ?: '-' }}</p>
                                </td>
                                <td class="px-3 py-3.5 text-slate-600">
                                    @if ($item->isMeteran())
                                        {{ number_format((float) $item->lebar_m, 2, ',', '.') }} &times; {{ number_format((float) $item->tinggi_m, 2, ',', '.') }} m
                                    @else
                                        <span class="text-slate-500">Produk satuan</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3.5 text-center font-semibold text-slate-700">{{ $item->qty }}</td>
                                <td class="px-3 py-3.5 text-slate-600">{{ $item->finishing?->nama_finishing ?: 'Tanpa finishing' }}</td>
                                <td class="px-3 py-3.5">
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">{{ $item->statusProduksiLabel() }}</span>
                                </td>
                                @if ($tampilkanHarga)
                                    <td class="px-5 py-3.5 text-right font-bold text-slate-900">Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $tampilkanHarga ? 6 : 5 }}" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada item pada nota ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($tampilkanHarga)
                <div class="flex justify-end border-t border-slate-100 px-5 py-5">
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
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500">Status Bayar</span>
                            <span class="font-semibold text-slate-700">{{ $order->statusBayarLabel() }}</span>
                        </div>
                    </div>
                </div>
            @endif
        </section>

        {{-- Catatan pengecualian bukti (audit) --}}
        @if ($order->catatan_pengecualian)
            <section class="overflow-hidden rounded-2xl border border-amber-200 bg-amber-50/60 shadow-sm">
                <div class="border-b border-amber-200/80 px-5 py-4">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-amber-600">Audit &middot; Pengecualian Bukti</p>
                    <h3 class="mt-1 text-sm font-bold text-slate-900">Catatan Pengecualian</h3>
                </div>
                <div class="px-5 py-4">
                    <p class="whitespace-pre-line text-sm leading-6 text-slate-700">{{ $order->catatan_pengecualian }}</p>
                </div>
            </section>
        @endif

        {{-- SPK Advertising: hanya pekerjaan, tanpa nominal --}}
        @if ($order->advertisingSpks->isNotEmpty())
            <section class="overflow-hidden rounded-2xl border-2 border-dashed border-amber-300 bg-amber-50/50 shadow-sm">
                <div class="flex flex-col gap-2 border-b border-amber-200/80 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-amber-600">SPK Advertising</p>
                        <h3 class="mt-1 text-sm font-bold text-slate-900">Rangka / Pemasangan / Papan Nama</h3>
                    </div>
                    <span class="shrink-0 self-start rounded-full bg-emerald-100 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-emerald-700 ring-1 ring-emerald-200 sm:self-auto">
                        Non-Billing &middot; Tanpa Nominal
                    </span>
                </div>
                <div class="space-y-3 p-5">
                    @foreach ($order->advertisingSpks as $spk)
                        <div class="rounded-xl border border-amber-200 bg-white p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-bold text-slate-900">{{ $spk->jenis_pekerjaan }}</p>
                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-semibold text-amber-700">{{ $spk->statusLapanganLabel() }}</span>
                            </div>
                            <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                                <div>
                                    <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Ukuran Konstruksi</dt>
                                    <dd class="mt-1 font-semibold text-slate-700">{{ $spk->ukuran_konstruksi }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Lokasi Pasang</dt>
                                    <dd class="mt-1 font-semibold text-slate-700">{{ $spk->lokasi_pasang }}</dd>
                                </div>
                                @if ($spk->catatan_teknis)
                                    <div class="sm:col-span-2">
                                        <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Catatan Teknis</dt>
                                        <dd class="mt-1 text-slate-600">{{ $spk->catatan_teknis }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    @endforeach
                    <p class="text-[11px] leading-5 text-amber-700">Nominal jasa advertising tidak dihitung pada tagihan pelanggan.</p>
                </div>
            </section>
        @endif
    </div>
@endsection
