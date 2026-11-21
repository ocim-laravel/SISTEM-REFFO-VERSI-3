@extends('layouts.guest')

@section('title', 'Masuk Pegawai')

@section('content')
    <div class="min-h-screen lg:grid lg:grid-cols-2">
        {{-- Brand panel --}}
        <aside class="relative hidden overflow-hidden bg-slate-950 px-10 py-12 lg:flex lg:flex-col lg:justify-between xl:px-14">
            <div class="pointer-events-none absolute -left-32 top-10 h-96 w-96 rounded-full bg-indigo-600/25 blur-3xl"></div>
            <div class="pointer-events-none absolute -right-24 bottom-0 h-96 w-96 rounded-full bg-fuchsia-500/15 blur-3xl"></div>
            <div class="pointer-events-none absolute inset-0 opacity-[0.04]" style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 44px 44px;"></div>

            <div class="relative">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-500 text-white shadow-xl shadow-indigo-500/30">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5V4.5h10.5v3M6.75 16.5h-1.5A1.5 1.5 0 0 1 3.75 15v-4.5A1.5 1.5 0 0 1 5.25 9h13.5a1.5 1.5 0 0 1 1.5 1.5V15a1.5 1.5 0 0 1-1.5 1.5h-1.5M6.75 12.75h10.5v6.75H6.75v-6.75Z" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-sm font-bold tracking-tight text-white">{{ config('app.name') }}</p>
                        <p class="text-[11px] text-slate-500">ERP Digital Printing &amp; Advertising</p>
                    </div>
                </div>

                <div class="mt-16 max-w-lg">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-indigo-200">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                        Portal Pegawai Internal
                    </span>
                    <h1 class="mt-5 text-4xl font-bold leading-tight tracking-tight text-white xl:text-5xl">
                        Satu workspace,<br>
                        <span class="text-indigo-300">enam divisi terisolasi.</span>
                    </h1>
                    <p class="mt-4 text-sm leading-6 text-slate-400">
                        Setiap pegawai hanya dapat membuka dashboard divisinya sendiri. Akses lintas divisi diblokir oleh middleware role.
                    </p>

                    <div class="mt-9 grid grid-cols-2 gap-3">
                        @foreach (['Admin', 'Desainer', 'Operator Cetak', 'Finishing', 'Advertising', 'Owner'] as $division)
                            <div class="flex items-center gap-2.5 rounded-xl border border-white/10 bg-white/[0.04] px-3 py-2.5">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-400/15 text-emerald-300">
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                </span>
                                <span class="truncate text-xs font-medium text-slate-300">{{ $division }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="relative flex items-center gap-3 rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-500/15 text-indigo-300">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5 12 3.75 3.75 7.5m16.5 0L12 11.25 3.75 7.5m16.5 0v9L12 20.25l-8.25-3.75v-9M12 11.25v9" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-slate-200">Penyimpanan lokal aktif</p>
                    <p class="mt-0.5 truncate text-[11px] text-slate-500">storage/app/public/MEDIA SISTEM</p>
                </div>
            </div>
        </aside>

        {{-- Login form --}}
        <main class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10 sm:px-8">
            <div class="w-full max-w-md">
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/25">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5V4.5h10.5v3M6.75 16.5h-1.5A1.5 1.5 0 0 1 3.75 15v-4.5A1.5 1.5 0 0 1 5.25 9h13.5a1.5 1.5 0 0 1 1.5 1.5V15a1.5 1.5 0 0 1-1.5 1.5h-1.5M6.75 12.75h10.5v6.75H6.75v-6.75Z" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-sm font-bold text-slate-900">{{ config('app.name') }}</p>
                        <p class="text-[11px] text-slate-500">ERP Printing &amp; Advertising</p>
                    </div>
                </div>

                <div>
                    <span class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-indigo-700 ring-1 ring-indigo-100">
                        <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>
                        Portal Pegawai
                    </span>
                    <h2 class="mt-4 text-3xl font-bold tracking-tight text-slate-900">Masuk ke workspace</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Gunakan email dan password pegawai yang diberikan oleh admin.</p>
                </div>

                @if (session('status'))
                    <div class="mt-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <p>{{ session('status') }}</p>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mt-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        <div class="flex items-start gap-3">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-rose-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM10.34 3.75h3.32a2.25 2.25 0 0 1 1.94 1.12l6.1 10.57a2.25 2.25 0 0 1-1.94 3.37H4.24a2.25 2.25 0 0 1-1.94-3.37l6.1-10.57a2.25 2.25 0 0 1 1.94-1.12Z" />
                            </svg>
                            <div>
                                <p class="font-semibold">Login gagal</p>
                                <ul class="mt-1 space-y-1 text-xs leading-5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="mt-7 space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-semibold text-slate-700">Email pegawai</label>
                        <div class="relative mt-2">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                </svg>
                            </span>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="nama@printflow.test"
                                class="block w-full rounded-xl border border-slate-300 bg-white py-3 pl-11 pr-3.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10"
                            >
                        </div>
                        @error('email')
                            <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-data="{ showPassword: false }">
                        <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
                        <div class="relative mt-2">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                            </span>
                            <input
                                id="password"
                                name="password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="current-password"
                                placeholder="Masukkan password"
                                class="block w-full rounded-xl border border-slate-300 bg-white py-3 pl-11 pr-20 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10"
                            >
                            <button
                                type="button"
                                class="absolute inset-y-0 right-0 flex items-center px-3.5 text-xs font-semibold text-slate-500 transition hover:text-indigo-600"
                                @click="showPassword = !showPassword"
                                x-text="showPassword ? 'Sembunyikan' : 'Lihat'"
                            >Lihat</button>
                        </div>
                        @error('password')
                            <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <label for="remember" class="inline-flex items-center gap-2 text-sm text-slate-600">
                            <input
                                id="remember"
                                name="remember"
                                type="checkbox"
                                value="1"
                                @checked(old('remember'))
                                class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            >
                            Ingat saya
                        </label>
                        <span class="text-xs text-slate-500">Lupa password? Hubungi admin.</span>
                    </div>

                    <button
                        type="submit"
                        class="group flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-500/20"
                    >
                        Masuk ke dashboard
                        <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </button>
                </form>

                @if (app()->environment('local'))
                    <div class="mt-8 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Akun pengujian (klik untuk isi otomatis)</p>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                            @foreach ([
                                ['Rina (Admin)', 'admin@printflow.test'],
                                ['Budi (Desainer)', 'desainer@printflow.test'],
                                ['Agus (Operator)', 'operator@printflow.test'],
                                ['Dedi (Finishing)', 'finishing@printflow.test'],
                                ['Eko (Advertising)', 'advertising@printflow.test'],
                                ['Pak Hendra (Owner)', 'owner@printflow.test'],
                            ] as [$label, $email])
                                <button
                                    type="button"
                                    onclick="document.getElementById('email').value='{{ $email }}'; document.getElementById('password').value='password123';"
                                    class="rounded-lg border border-slate-200 bg-slate-50 px-2 py-2 text-[11px] font-medium text-slate-700 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700"
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                        <p class="mt-2 text-[10px] text-slate-400">Password semua akun: <span class="font-mono">password123</span></p>
                    </div>
                @endif

                <p class="mt-8 text-center text-[11px] leading-5 text-slate-400">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. Akses internal pegawai.<br>
                    Semua aktivitas dicatat untuk keamanan sistem.
                </p>
            </div>
        </main>
    </div>
@endsection
