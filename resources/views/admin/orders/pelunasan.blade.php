@extends('layouts.app')

@section('title', 'Pelunasan '.$order->no_nota)
@section('section', 'Transaksi Admin')
@section('page-title', 'Pelunasan Nota')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <a href="{{ route('admin.orders.index', ['tab' => 'siap_diambil']) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-700">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
            Kembali ke daftar nota
        </a>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-4">
                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Pelunasan</p>
                <h2 class="mt-1 font-mono text-lg font-bold text-slate-900">{{ $order->no_nota }}</h2>
            </div>
            <dl class="grid gap-4 px-5 py-5 sm:grid-cols-2">
                <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Instansi</dt>
                    <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $order->instansiLabel() }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Pelanggan / PIC</dt>
                    <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $order->picLabel() }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Total Tagihan</dt>
                    <dd class="mt-1 text-sm font-bold text-slate-900">Rp {{ number_format((float) $order->total_tagihan, 0, ',', '.') }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Sisa Tagihan</dt>
                    <dd class="mt-1 text-sm font-bold text-rose-600">Rp {{ number_format($order->sisaTagihan(), 0, ',', '.') }}</dd>
                </div>
            </dl>
        </section>

        <form
            method="POST"
            action="{{ route('admin.orders.pelunasan', $order) }}"
            enctype="multipart/form-data"
            x-data="{ adaBukti: false }"
            class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"
        >
            @csrf

            <div>
                <label for="bukti_pelunasan" class="block text-sm font-bold text-slate-900">
                    Bukti Transfer / Nota Pembayaran
                </label>
                <p class="mt-1 text-xs leading-5 text-slate-500">
                    Unggah bukti transfer atau nota pembayaran yang sudah berganti stempel kantor. Format JPG, JPEG, PNG, atau PDF &middot; maksimal 5 MB.
                    Jika bukti tidak tersedia, isi catatan pengecualian di bawah.
                </p>
                <input
                    id="bukti_pelunasan"
                    name="bukti_pelunasan"
                    type="file"
                    accept=".jpg,.jpeg,.png,.pdf"
                    x-on:change="adaBukti = Boolean($event.target.files.length)"
                    class="mt-3 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                >
                @error('bukti_pelunasan')
                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-[11px] text-slate-400">Berkas disimpan lokal di media_sistem/bukti_pelunasan.</p>
            </div>

            <div>
                <label for="catatan_pengecualian" class="block text-sm font-bold text-slate-900">
                    Catatan Pengecualian <span class="text-xs font-semibold text-slate-400">(opsional)</span>
                </label>
                <p class="mt-1 text-xs leading-5 text-slate-500">
                    Wajib diisi jika bukti tidak diunggah. Catatan ini disimpan untuk audit.
                </p>
                <textarea
                    id="catatan_pengecualian"
                    name="catatan_pengecualian"
                    rows="3"
                    maxlength="2000"
                    x-bind:required="!adaBukti"
                    placeholder="Contoh: bukti transfer belum dikirim pelanggan, pelunasan dicatat berdasarkan konfirmasi WhatsApp."
                    class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm leading-6 text-slate-700 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                >{{ old('catatan_pengecualian', $order->catatan_pengecualian) }}</textarea>
                @error('catatan_pengecualian')
                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5">
                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700">
                    Simpan Pelunasan
                </button>
                <a href="{{ route('admin.orders.index', ['tab' => 'siap_diambil']) }}" class="text-sm font-semibold text-slate-500 transition hover:text-slate-800">Batal</a>
            </div>
        </form>
    </div>
@endsection
