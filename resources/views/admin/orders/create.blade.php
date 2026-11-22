@extends('layouts.app')

@section('title', 'Input Order Baru')
@section('section', 'Transaksi Admin')
@section('page-title', 'Input Order Baru')

@section('content')
    <div class="mx-auto max-w-6xl space-y-6">
        {{-- Page header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-700">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                    Kembali ke Daftar Nota
                </a>
                <h2 class="mt-2 text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Buat Nota Order Baru</h2>
                <p class="mt-1 text-sm text-slate-500">Satu nota dapat memuat beberapa item Digital Printing sekaligus SPK Advertising non-billing.</p>
            </div>
            <div class="flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-semibold text-amber-800">
                <svg class="h-5 w-5 shrink-0 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM10.34 3.75h3.32a2.25 2.25 0 0 1 1.94 1.12l6.1 10.57a2.25 2.25 0 0 1-1.94 3.37H4.24a2.25 2.25 0 0 1-1.94-3.37l6.1-10.57a2.25 2.25 0 0 1 1.94-1.12Z" />
                </svg>
                <span>Tagihan meteran minimal 1 m². Jasa advertising selalu Rp 0.</span>
            </div>
        </div>

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <p class="font-semibold">Nota belum bisa disimpan:</p>
                <ul class="mt-2 list-inside list-disc space-y-1 text-rose-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('admin.orders.store') }}"
            enctype="multipart/form-data"
            class="space-y-6"
            x-data="orderForm(JSON.parse(document.getElementById('order-form-data').textContent))"
        >
            @csrf
            <input type="hidden" name="customer_id" :value="customerId ?? ''">

            {{-- CARD 1 — Data Pelanggan --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-start gap-4 border-b border-slate-100 px-6 py-5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-sm font-bold text-white">1</span>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Data Pelanggan</h3>
                        <p class="mt-0.5 text-sm text-slate-500">Cari pelanggan yang sudah terdaftar atau isi data pelanggan baru.</p>
                    </div>
                </div>

                <div class="space-y-5 p-6" @click.outside="showResults = false">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="nama_pelanggan" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Nama Pelanggan / PIC <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.6-5.15a6.75 6.75 0 1 1-13.5 0 6.75 6.75 0 0 1 13.5 0Z" />
                                    </svg>
                                </span>
                                <input
                                    type="text"
                                    id="nama_pelanggan"
                                    name="nama_pelanggan"
                                    x-model="customer.nama_pelanggan"
                                    @input.debounce.300ms="customerId = null; searchCustomers()"
                                    @focus="searchCustomers()"
                                    autocomplete="off"
                                    required
                                    placeholder="Ketik nama pelanggan atau PIC…"
                                    class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                >
                            </div>
                            <p class="mt-1.5 text-[11px] leading-5 text-slate-500">Auto-complete aktif setelah 2 huruf, dibatasi oleh instansi yang dipilih. Jika tidak ada yang cocok, pelanggan baru akan dibuat dengan instansi terpilih.</p>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="instansi_id" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Instansi <span class="text-rose-500">*</span>
                            </label>
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <select
                                    id="instansi_id"
                                    name="instansi_id"
                                    x-model="customer.instansi_id"
                                    @change="onInstansiChange()"
                                    required
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                >
                                    <option value="">Pilih instansi…</option>
                                    <template x-for="instansi in instansis" :key="instansi.id">
                                        <option :value="instansi.id" x-text="instansi.nama_instansi"></option>
                                    </template>
                                    <option value="__new__">+ Tambah instansi baru…</option>
                                </select>
                                <input type="hidden" name="instansi" x-model="customer.instansi">
                            </div>

                            <div x-show="instansiBaruMode" x-cloak class="mt-3 rounded-xl border border-indigo-200 bg-indigo-50/60 p-3">
                                <label class="block text-xs font-semibold text-slate-700" for="instansi_baru">Nama Instansi Baru</label>
                                <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                                    <input
                                        type="text"
                                        id="instansi_baru"
                                        x-model="instansiBaru"
                                        @keydown.enter.prevent="tambahInstansi()"
                                        placeholder="Contoh: PT Contoh Nusantara"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                    >
                                    <button type="button" @click="tambahInstansi()"
                                        class="shrink-0 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                        Tambah Instansi
                                    </button>
                                    <button type="button" @click="batalkanInstansiBaru()"
                                        class="shrink-0 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                                        Batal
                                    </button>
                                </div>
                                <p x-show="instansiError" x-cloak class="mt-2 text-xs text-rose-600" x-text="instansiError"></p>
                            </div>

                            <p class="mt-1.5 text-[11px] leading-5 text-slate-500">Pilih instansi terdaftar atau tambah baru. Kolom instansi pada data pelanggan akan otomatis disinkronkan.</p>
                        </div>

                        <div>
                            <label for="no_wa" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Nomor WhatsApp <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="no_wa"
                                name="no_wa"
                                x-model="customer.no_wa"
                                @input="customerId = null"
                                required
                                placeholder="08xxxxxxxxxx"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                            >
                        </div>

                        <div>
                            <label for="kategori" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Kategori Pelanggan <span class="text-rose-500">*</span>
                            </label>
                            <select
                                id="kategori"
                                name="kategori"
                                x-model="customer.kategori"
                                @change="customerId = null"
                                required
                                class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                            >
                                <option value="umum">Umum</option>
                                <option value="reseller">Reseller</option>
                                <option value="prioritas">Prioritas</option>
                                <option value="pegawai">Pegawai</option>
                            </select>
                            <p class="mt-1.5 text-[11px] leading-5 text-slate-500">Harga item otomatis mengikuti kategori ini.</p>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="alamat" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Alamat</label>
                            <textarea
                                id="alamat"
                                name="alamat"
                                x-model="customer.alamat"
                                @input="customerId = null"
                                rows="2"
                                placeholder="Alamat pelanggan (opsional)"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                            ></textarea>
                        </div>
                    </div>

                    {{-- Auto-complete results --}}
                    <div x-show="showResults && results.length > 0" x-cloak class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-2">
                        <p class="px-2 py-1.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-indigo-500">Pelanggan terdaftar</p>
                        <template x-for="result in results" :key="result.id">
                            <button
                                type="button"
                                @click="pickCustomer(result)"
                                class="flex w-full flex-col gap-1 rounded-lg px-3 py-2.5 text-left transition hover:bg-white sm:flex-row sm:items-center sm:justify-between"
                            >
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-slate-800" x-text="result.nama_pelanggan"></span>
                                    <span class="block truncate text-[11px] text-slate-500">
                                        <span x-text="result.instansi"></span>
                                        <span> · </span>
                                        <span x-text="result.no_wa"></span>
                                    </span>
                                </span>
                                <span class="shrink-0 rounded-full bg-white px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-indigo-600 ring-1 ring-indigo-100" x-text="result.kategori"></span>
                            </button>
                        </template>
                    </div>

                    <div x-show="customerId" x-cloak class="flex flex-col gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-800 sm:flex-row sm:items-center sm:justify-between">
                        <p class="flex items-center gap-2">
                            <svg class="h-4 w-4 shrink-0 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            Data pelanggan terdaftar dipilih. Mengubah field akan menyimpan sebagai pelanggan baru.
                        </p>
                        <button type="button" @click="resetCustomer()" class="shrink-0 font-semibold text-emerald-700 underline decoration-emerald-300 underline-offset-2 hover:text-emerald-900">
                            Batalkan pilihan
                        </button>
                    </div>
                </div>
            </section>

            {{-- CARD 2 — Keranjang Multi-Item --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-start gap-4 border-b border-slate-100 px-6 py-5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-sm font-bold text-white">2</span>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Keranjang Multi-Item</h3>
                        <p class="mt-0.5 text-sm text-slate-500">Campur produk meteran, produk satuan, dan SPK advertising dalam satu nota.</p>
                    </div>
                </div>

                <div class="space-y-4 p-6">
                    <template x-for="(item, index) in items" :key="item.uid">
                        <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-900 text-[11px] font-bold text-white" x-text="index + 1"></span>
                                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Baris Item</span>
                                    <span
                                        class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider"
                                        :class="productFor(item)?.kategori_produk === 'satuan' ? 'bg-sky-100 text-sky-700' : 'bg-indigo-100 text-indigo-700'"
                                        x-show="productFor(item)"
                                        x-cloak
                                        x-text="productFor(item)?.kategori_produk"
                                    ></span>
                                </div>
                                <button
                                    type="button"
                                    @click="removeItem(index)"
                                    x-show="items.length > 1"
                                    x-cloak
                                    class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-[11px] font-semibold text-rose-600 transition hover:bg-rose-50"
                                >
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                    Hapus
                                </button>
                            </div>

                            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-12">
                                <div class="lg:col-span-12">
                                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Nama Item <span class="font-normal normal-case text-slate-400">(opsional)</span></label>
                                    <input
                                        type="text"
                                        maxlength="255"
                                        :name="`items[${index}][nama_item]`"
                                        x-model="item.nama_item"
                                        placeholder="Contoh: Spanduk promo Minggu Belanja"
                                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                    >
                                </div>

                                <div class="lg:col-span-4">
                                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Produk <span class="text-rose-500">*</span></label>
                                    <select
                                        :name="`items[${index}][product_id]`"
                                        x-model="item.product_id"
                                        @change="onProductChange(item)"
                                        required
                                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                    >
                                        <option value="">Pilih produk…</option>
                                        <optgroup label="Cetak Meteran">
                                            <template x-for="product in products.filter((row) => row.kategori_produk === 'meteran')" :key="product.id">
                                                <option :value="product.id" x-text="product.nama_produk"></option>
                                            </template>
                                        </optgroup>
                                        <optgroup label="Produk Satuan">
                                            <template x-for="product in products.filter((row) => row.kategori_produk === 'satuan')" :key="product.id">
                                                <option :value="product.id" x-text="product.nama_produk"></option>
                                            </template>
                                        </optgroup>
                                    </select>
                                </div>

                                <div class="lg:col-span-2" x-show="isMeteran(item)" x-cloak>
                                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Lebar (m)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        :name="`items[${index}][lebar_m]`"
                                        x-model="item.lebar_m"
                                        :required="isMeteran(item)"
                                        placeholder="0.00"
                                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                    >
                                </div>

                                <div class="lg:col-span-2" x-show="isMeteran(item)" x-cloak>
                                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Tinggi (m)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        :name="`items[${index}][tinggi_m]`"
                                        x-model="item.tinggi_m"
                                        :required="isMeteran(item)"
                                        placeholder="0.00"
                                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                    >
                                </div>

                                <div class="lg:col-span-2">
                                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Qty <span class="text-rose-500">*</span></label>
                                    <input
                                        type="number"
                                        min="1"
                                        step="1"
                                        :name="`items[${index}][qty]`"
                                        x-model="item.qty"
                                        required
                                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                    >
                                </div>

                                <div class="lg:col-span-2" x-show="isMeteran(item)" x-cloak>
                                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Finishing</label>
                                    <select
                                        :name="`items[${index}][finishing_id]`"
                                        x-model="item.finishing_id"
                                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                    >
                                        <option value="">Tanpa finishing</option>
                                        <template x-for="finishing in finishings" :key="finishing.id">
                                            <option :value="finishing.id" x-text="finishing.nama_finishing"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-col gap-2 border-t border-slate-200/80 pt-3 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                                    <span x-show="isMeteran(item)" x-cloak>
                                        Luas desain:
                                        <span class="font-semibold text-slate-700" x-text="decimal(designArea(item) ?? 0)"></span> m²
                                    </span>
                                    <span x-show="isMeteran(item)" x-cloak>
                                        Luas tagihan:
                                        <span class="font-semibold text-slate-700" x-text="decimal(billedArea(item) ?? 0)"></span> m²
                                    </span>
                                    <span x-show="isMeteran(item) && (designArea(item) ?? 0) > 0 && (designArea(item) ?? 0) < 1" x-cloak class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">
                                        Dibulatkan ke 1 m²
                                    </span>
                                    <span>
                                        Harga:
                                        <span class="font-semibold text-slate-700" x-text="money(priceFor(item))"></span>
                                        <span x-show="isMeteran(item)" x-cloak>/ m²</span>
                                    </span>
                                </div>
                                <p class="text-sm">
                                    Subtotal:
                                    <span class="font-bold text-slate-900" x-text="money(lineSubtotal(item))"></span>
                                </p>
                            </div>
                        </div>
                    </template>

                    <button
                        type="button"
                        @click="addItem()"
                        class="inline-flex items-center gap-2 rounded-xl border border-dashed border-indigo-300 bg-indigo-50/50 px-4 py-2.5 text-sm font-semibold text-indigo-700 transition hover:border-indigo-400 hover:bg-indigo-50"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Tambah Item
                    </button>

                    {{-- SPK Advertising --}}
                    <div class="rounded-xl border border-dashed border-amber-300 bg-amber-50/60 p-4">
                        <label class="flex cursor-pointer items-start gap-3">
                            <input
                                type="checkbox"
                                name="spk_enabled"
                                value="1"
                                x-model="spkEnabled"
                                class="mt-0.5 h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500"
                            >
                            <span class="min-w-0">
                                <span class="flex flex-wrap items-center gap-2 text-sm font-bold text-slate-800">
                                    Tambah SPK Rangka / Pemasangan / Papan Nama
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-emerald-700">Rp 0 · Non-Billing</span>
                                </span>
                                <span class="mt-1 block text-xs leading-5 text-slate-500">
                                    SPK hanya diterbitkan untuk divisi advertising. Nominal jasa tidak dihitung pada tagihan pelanggan.
                                </span>
                            </span>
                        </label>

                        <div x-show="spkEnabled" x-cloak class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Jenis Pekerjaan <span class="text-rose-500">*</span></label>
                                <input
                                    type="text"
                                    name="spk_jenis_pekerjaan"
                                    x-model="spk.jenis_pekerjaan"
                                    :required="spkEnabled"
                                    placeholder="Contoh: Rangka Baliho + Pemasangan"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-500 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
                                >
                            </div>
                            <div>
                                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Ukuran Konstruksi <span class="text-rose-500">*</span></label>
                                <input
                                    type="text"
                                    name="spk_ukuran_konstruksi"
                                    x-model="spk.ukuran_konstruksi"
                                    :required="spkEnabled"
                                    placeholder="Contoh: 4 m x 2 m"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-500 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
                                >
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Lokasi Pasang <span class="text-rose-500">*</span></label>
                                <input
                                    type="text"
                                    name="spk_lokasi_pasang"
                                    x-model="spk.lokasi_pasang"
                                    :required="spkEnabled"
                                    placeholder="Alamat / titik lokasi pemasangan"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-500 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
                                >
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Catatan Teknis</label>
                                <textarea
                                    name="spk_catatan_teknis"
                                    x-model="spk.catatan_teknis"
                                    rows="2"
                                    placeholder="Kebutuhan bahan, alat, atau instruksi khusus tim lapangan (opsional)"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-500 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Total --}}
                    <div class="flex flex-col gap-4 rounded-2xl bg-slate-900 p-5 text-white sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">Total Tagihan Nota</p>
                            <p class="mt-1 text-2xl font-bold tracking-tight" x-text="money(orderTotal())"></p>
                            <p class="mt-1 text-[11px] text-slate-500">Belum termasuk jasa advertising (selalu Rp 0 pada Fase 1).</p>
                        </div>
                        <div class="rounded-xl bg-white/10 px-4 py-3 text-xs text-slate-200 ring-1 ring-white/10">
                            <p><span x-text="items.length"></span> baris item · minimal 1 m² per baris meteran</p>
                            <p class="mt-1 text-slate-500">Total dihitung otomatis mengikuti kategori pelanggan.</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- CARD 3 — Status Desain, Lampiran & Pembayaran --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-start gap-4 border-b border-slate-100 px-6 py-5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-sm font-bold text-white">3</span>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Status Desain, Lampiran &amp; Pembayaran</h3>
                        <p class="mt-0.5 text-sm text-slate-500">Brief desain disimpan ke media lokal dan diteruskan ke divisi desainer.</p>
                    </div>
                </div>

                <div class="space-y-6 p-6">
                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Kondisi Desain <span class="text-rose-500">*</span></p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ($statusDesainOptions as $value => $label)
                                <label class="group flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/60 p-3.5 transition hover:border-indigo-200 hover:bg-indigo-50/40 has-[:checked]:border-indigo-300 has-[:checked]:bg-indigo-50">
                                    <input
                                        type="radio"
                                        name="status_desain"
                                        value="{{ $value }}"
                                        x-model="statusDesain"
                                        required
                                        class="mt-0.5 h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    >
                                    <span class="min-w-0 text-sm font-medium text-slate-700 group-has-[:checked]:text-indigo-900">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="catatan_desain" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Materi / Catatan Desain</label>
                            <textarea
                                id="catatan_desain"
                                name="catatan_desain"
                                rows="3"
                                placeholder="Teks, warna, ukuran, referensi, atau instruksi desain dari pelanggan…"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                            >{{ old('catatan_desain') }}</textarea>
                        </div>

                        <div x-data="imagePreview()" class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                            <label for="lampiran_briefing" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Lampiran Briefing Desain</label>
                            <input
                                type="file"
                                id="lampiran_briefing"
                                name="lampiran_briefing"
                                x-ref="file"
                                @change="pick($event)"
                                accept="image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif,.pdf,.tif,.tiff,.cdr,.psd,.ai,.zip,.rar"
                                class="block w-full cursor-pointer rounded-xl border border-slate-300 bg-white p-2 text-sm text-slate-500 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-600 file:px-4 file:py-2.5 file:text-xs file:font-semibold file:text-white hover:file:bg-indigo-700"
                            >
                            <p class="mt-1.5 text-[11px] leading-5 text-slate-500">Gambar, PDF, TIFF, CDR, PSD, AI, ZIP, atau RAR. Maksimal 20 MB. Disimpan di <span class="font-mono">media_sistem/briefing_desain</span>.</p>

                            <template x-if="url">
                                <div class="mt-3 flex items-start gap-3 rounded-lg border border-indigo-100 bg-indigo-50/60 p-2.5">
                                    <div class="h-20 w-20 shrink-0 overflow-hidden rounded-lg bg-white ring-1 ring-indigo-100">
                                        <img :src="url" :alt="'Pratinjau ' + name" x-on:error="failed = true" x-show="!failed"
                                            class="h-full w-full object-contain">
                                        <div x-show="failed" x-cloak class="flex h-full items-center justify-center px-1 text-center text-[10px] leading-4 text-slate-500">
                                            Pratinjau tidak didukung
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[10px] font-semibold uppercase tracking-wider text-indigo-500">Pratinjau berkas</p>
                                        <p class="mt-1 truncate text-xs font-semibold text-slate-700" x-text="name"></p>
                                        <button type="button" @click="clear()" class="mt-1 text-[11px] font-semibold text-rose-600 hover:underline">Hapus pilihan</button>
                                    </div>
                                </div>
                            </template>

                            <div x-show="name && !url" x-cloak class="mt-3 flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                <svg class="h-4 w-4 shrink-0 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5A3.375 3.375 0 0 0 10.125 2.25H8.25a2.25 2.25 0 0 0-2.25 2.25v13.5a2.25 2.25 0 0 0 2.25 2.25h7.5a2.25 2.25 0 0 0 2.25-2.25V14.25Z" />
                                </svg>
                                <p class="min-w-0 flex-1 truncate text-xs text-slate-600" x-text="name"></p>
                                <button type="button" @click="clear()" class="shrink-0 text-[11px] font-semibold text-rose-600 hover:underline">Hapus</button>
                            </div>
                        </div>

                        <div>
                            <label for="deadline" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500">Deadline Pengerjaan</label>
                            <input
                                type="datetime-local"
                                id="deadline"
                                name="deadline"
                                value="{{ old('deadline') }}"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                            >
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Pembayaran</p>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="status_bayar" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Status Bayar <span class="text-rose-500">*</span></label>
                                <select
                                    id="status_bayar"
                                    name="status_bayar"
                                    x-model="statusBayar"
                                    required
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                >
                                    @foreach ($statusBayarOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div x-show="statusBayar === 'dp'" x-cloak>
                                <label for="jumlah_dp" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Jumlah DP (Rp) <span class="text-rose-500">*</span></label>
                                <input
                                    type="number"
                                    id="jumlah_dp"
                                    name="jumlah_dp"
                                    x-model="jumlahDp"
                                    min="0"
                                    step="1000"
                                    :required="statusBayar === 'dp'"
                                    placeholder="0"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                >
                                <p class="mt-1.5 text-[11px] text-slate-500">Sisa tagihan: <span class="font-semibold text-slate-700" x-text="money(remaining())"></span></p>
                            </div>

                            <div x-show="statusBayar === 'lunas'" x-cloak class="flex items-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-800">
                                Nota akan ditandai <span class="mx-1 font-bold">LUNAS</span>. DP otomatis dicatat sebesar total tagihan.
                            </div>

                            <div x-show="statusBayar === 'belum_lunas'" x-cloak class="flex items-center rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
                                Belum ada pembayaran. Sisa tagihan penuh: <span class="ml-1 font-semibold" x-text="money(orderTotal())"></span>.
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="flex flex-col-reverse items-stretch justify-between gap-3 sm:flex-row sm:items-center">
                <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
                    Batal
                </a>
                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75 9 17.25 19.5 6.75" />
                    </svg>
                    Simpan Nota &amp; Lanjut ke Ringkasan
                </button>
            </div>
        </form>
    </div>

    <script type="application/json" id="order-form-data">@json($formData)</script>
@endsection

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('orderForm', (config) => ({
                products: config.products,
                finishings: config.finishings,
                instansis: config.instansis,
                instansiStoreUrl: config.instansiStoreUrl,
                items: (Array.isArray(config.items) && config.items.length > 0 ? config.items : [{}]).map((item) => ({
                    uid: Math.random().toString(36).slice(2),
                    nama_item: item.nama_item ?? '',
                    product_id: item.product_id ?? '',
                    qty: item.qty ?? 1,
                    lebar_m: item.lebar_m ?? '',
                    tinggi_m: item.tinggi_m ?? '',
                    finishing_id: item.finishing_id ?? '',
                })),
                customerId: config.customerId ?? null,
                customer: { ...config.customer },
                results: [],
                showResults: false,
                searchUrl: config.searchUrl,
                instansiBaruMode: false,
                instansiBaru: '',
                instansiError: '',
                statusDesain: config.statusDesain,
                statusBayar: config.statusBayar,
                jumlahDp: config.jumlahDp ?? '',
                spkEnabled: Boolean(config.spkEnabled),
                spk: { ...config.spk },

                addItem() {
                    this.items.push({
                        uid: Math.random().toString(36).slice(2),
                        nama_item: '',
                        product_id: '',
                        qty: 1,
                        lebar_m: '',
                        tinggi_m: '',
                        finishing_id: '',
                    });
                },

                removeItem(index) {
                    if (this.items.length <= 1) {
                        return;
                    }

                    this.items.splice(index, 1);
                },

                productFor(item) {
                    return this.products.find((product) => String(product.id) === String(item.product_id)) || null;
                },

                isMeteran(item) {
                    return this.productFor(item)?.kategori_produk === 'meteran';
                },

                priceFor(item) {
                    const product = this.productFor(item);

                    if (!product) {
                        return 0;
                    }

                    const key = 'harga_' + (this.customer.kategori || 'umum');

                    return Number(product[key] ?? product.harga_umum ?? 0);
                },

                designArea(item) {
                    if (!this.isMeteran(item)) {
                        return null;
                    }

                    const lebar = parseFloat(item.lebar_m) || 0;
                    const tinggi = parseFloat(item.tinggi_m) || 0;
                    const qty = parseInt(item.qty, 10) || 0;

                    return this.round(lebar * tinggi * qty, 2);
                },

                billedArea(item) {
                    const area = this.designArea(item);

                    return area === null ? null : Math.max(area, 1);
                },

                lineSubtotal(item) {
                    const product = this.productFor(item);

                    if (!product) {
                        return 0;
                    }

                    if (product.kategori_produk !== 'meteran') {
                        return this.round(this.priceFor(item) * (parseInt(item.qty, 10) || 0), 2);
                    }

                    const area = this.billedArea(item);

                    return area === null ? 0 : this.round(this.priceFor(item) * area, 2);
                },

                orderTotal() {
                    return this.round(this.items.reduce((total, item) => total + this.lineSubtotal(item), 0), 2);
                },

                remaining() {
                    return Math.max(this.round(this.orderTotal() - (parseFloat(this.jumlahDp) || 0), 2), 0);
                },

                round(value, digits = 2) {
                    const factor = 10 ** digits;

                    return Math.round((Number(value) + Number.EPSILON) * factor) / factor;
                },

                money(value) {
                    return new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        maximumFractionDigits: 0,
                    }).format(Number(value) || 0);
                },

                decimal(value) {
                    return new Intl.NumberFormat('id-ID', {
                        maximumFractionDigits: 2,
                    }).format(Number(value) || 0);
                },

                onProductChange(item) {
                    const product = this.productFor(item);

                    if (product && product.kategori_produk === 'satuan') {
                        item.lebar_m = '';
                        item.tinggi_m = '';
                        item.finishing_id = '';
                    }
                },

                namaInstansiTerpilih() {
                    return this.instansis.find(
                        (instansi) => String(instansi.id) === String(this.customer.instansi_id),
                    )?.nama_instansi || '';
                },

                onInstansiChange() {
                    this.customerId = null;
                    this.results = [];
                    this.showResults = false;
                    this.instansiError = '';

                    if (this.customer.instansi_id === '__new__') {
                        this.instansiBaruMode = true;
                        this.customer.instansi = '';

                        return;
                    }

                    this.instansiBaruMode = false;
                    this.customer.instansi = this.namaInstansiTerpilih();
                },

                async tambahInstansi() {
                    const nama = (this.instansiBaru || '').trim();

                    if (!nama) {
                        this.instansiError = 'Nama instansi wajib diisi.';

                        return;
                    }

                    try {
                        const response = await fetch(this.instansiStoreUrl, {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                            },
                            body: JSON.stringify({ nama_instansi: nama }),
                        });
                        const data = await response.json();

                        if (!response.ok) {
                            this.instansiError = data.errors?.nama_instansi?.[0] ?? 'Instansi gagal ditambahkan.';

                            return;
                        }

                        this.instansis.push(data);
                        this.customer.instansi_id = data.id;
                        this.customer.instansi = data.nama_instansi;
                        this.instansiBaruMode = false;
                        this.instansiBaru = '';
                        this.instansiError = '';
                    } catch (error) {
                        this.instansiError = 'Instansi gagal ditambahkan. Coba lagi.';
                    }
                },

                batalkanInstansiBaru() {
                    this.customer.instansi_id = '';
                    this.customer.instansi = '';
                    this.instansiBaruMode = false;
                    this.instansiBaru = '';
                    this.instansiError = '';
                },

                async searchCustomers() {
                    const term = (this.customer.nama_pelanggan || '').trim();

                    if (term.length < 2 || !this.customer.instansi_id || this.customer.instansi_id === '__new__') {
                        this.results = [];
                        this.showResults = false;

                        return;
                    }

                    const params = new URLSearchParams({
                        q: term,
                        instansi_id: String(this.customer.instansi_id),
                    });

                    try {
                        const response = await fetch(`${this.searchUrl}?${params.toString()}`, {
                            headers: { Accept: 'application/json' },
                        });

                        if (!response.ok) {
                            return;
                        }

                        this.results = await response.json();
                        this.showResults = this.results.length > 0;
                    } catch (error) {
                        this.results = [];
                        this.showResults = false;
                    }
                },

                pickCustomer(customer) {
                    const instansiId = customer.instansi_id
                        ?? this.instansis.find(
                            (instansi) => instansi.nama_instansi === customer.instansi,
                        )?.id
                        ?? '';

                    this.customerId = customer.id;
                    this.customer = {
                        instansi_id: instansiId,
                        instansi: customer.instansi || 'Order Pribadi',
                        nama_pelanggan: customer.nama_pelanggan ?? '',
                        no_wa: customer.no_wa ?? '',
                        kategori: customer.kategori ?? 'umum',
                        alamat: customer.alamat ?? '',
                    };
                    this.instansiBaruMode = false;
                    this.results = [];
                    this.showResults = false;
                },

                resetCustomer() {
                    const instansiId = this.customer.instansi_id;
                    const instansi = this.customer.instansi || 'Order Pribadi';

                    this.customerId = null;
                    this.customer = {
                        instansi_id: instansiId,
                        instansi,
                        nama_pelanggan: '',
                        no_wa: '',
                        kategori: 'umum',
                        alamat: '',
                    };
                    this.results = [];
                    this.showResults = false;
                },
            }));
        });
    </script>
@endpush
