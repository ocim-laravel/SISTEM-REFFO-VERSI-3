@extends('layouts.app')

@section('title', 'Serah Terima '.$order->no_nota)
@section('section', 'Serah Terima')
@section('page-title', $divisi === 'admin' ? 'Serah Terima Barang' : 'Pengambilan Barang')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <a href="{{ route($divisi.'.serah-terima.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-700">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
            Kembali ke daftar serah terima
        </a>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-4">
                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Nota Siap Diambil · {{ $judulDivisi }}</p>
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
                    <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">WhatsApp Terdaftar</dt>
                    <dd class="mt-1 text-sm font-semibold text-slate-700">{{ $order->customer?->no_wa ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Status Bayar</dt>
                    <dd class="mt-1 text-sm font-bold text-emerald-600">{{ $order->statusBayarLabel() }}</dd>
                </div>
            </dl>
        </section>

        <form
            method="POST"
            action="{{ route($divisi.'.orders.serah-terima', $order) }}"
            enctype="multipart/form-data"
            x-data="{ adaFoto: false }"
            class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"
        >
            @csrf

            <div>
                <label for="foto_serah_terima" class="block text-sm font-bold text-slate-900">
                    Foto Barang yang Diserahkan
                </label>
                <p class="mt-1 text-xs leading-5 text-slate-500">Ambang foto barang saat serah terima &middot; format JPG, JPEG, atau PNG &middot; maksimal 5 MB. Jika foto tidak tersedia, isi catatan pengecualian di bawah.</p>
                <input
                    id="foto_serah_terima"
                    name="foto_serah_terima"
                    type="file"
                    accept=".jpg,.jpeg,.png"
                    x-on:change="adaFoto = Boolean($event.target.files.length)"
                    class="mt-3 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                >
                @error('foto_serah_terima')
                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-[11px] text-slate-400">Berkas disimpan lokal di media_sistem/foto_serah_terima.</p>
            </div>

            <div>
                <label for="catatan_pengecualian" class="block text-sm font-bold text-slate-900">
                    Catatan Pengecualian <span class="text-xs font-semibold text-slate-400">(opsional)</span>
                </label>
                <p class="mt-1 text-xs leading-5 text-slate-500">
                    Wajib diisi jika foto tidak diunggah. Catatan ini disimpan untuk audit.
                </p>
                <textarea
                    id="catatan_pengecualian"
                    name="catatan_pengecualian"
                    rows="3"
                    maxlength="2000"
                    x-bind:required="!adaFoto"
                    placeholder="Contoh: barang diambil langsung oleh pelanggan, foto tidak sempat diambil."
                    class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm leading-6 text-slate-700 shadow-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                >{{ old('catatan_pengecualian', $order->catatan_pengecualian) }}</textarea>
                @error('catatan_pengecualian')
                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="nama_pengambil" class="block text-sm font-bold text-slate-900">
                        Atas Nama Pengambil <span class="text-rose-500">*</span>
                    </label>
                    <input
                        id="nama_pengambil"
                        name="nama_pengambil"
                        type="text"
                        value="{{ old('nama_pengambil', 'ambil sendiri') }}"
                        maxlength="255"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-700 shadow-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                    >
                    @error('nama_pengambil')
                        <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="no_wa_pengambil" class="block text-sm font-bold text-slate-900">
                        Nomor WA Pengambil <span class="text-rose-500">*</span>
                    </label>
                    <input
                        id="no_wa_pengambil"
                        name="no_wa_pengambil"
                        type="text"
                        value="{{ old('no_wa_pengambil', $order->customer?->no_wa) }}"
                        maxlength="30"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-700 shadow-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                    >
                    @error('no_wa_pengambil')
                        <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-[11px] text-slate-400">Default mengikuti nomor WhatsApp pelanggan terdaftar.</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5">
                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700">
                    {{ $divisi === 'admin' ? 'Simpan Serah Terima' : 'Simpan Pengambilan' }}
                </button>
                <a href="{{ route($divisi.'.serah-terima.index') }}" class="text-sm font-semibold text-slate-500 transition hover:text-slate-800">Batal</a>
            </div>
        </form>
    </div>
@endsection
