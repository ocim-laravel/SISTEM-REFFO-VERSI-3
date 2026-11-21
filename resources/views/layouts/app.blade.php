<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="h-full font-sans text-slate-900 antialiased" x-data="{ sidebarOpen: false }">
    <div class="min-h-full">
        {{-- Mobile sidebar overlay --}}
        <div
            x-cloak
            x-show="sidebarOpen"
            x-transition.opacity
            class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden"
            @click="sidebarOpen = false"
            aria-hidden="true"
        ></div>

        {{-- Sidebar --}}
        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-slate-950 text-slate-300 transition-transform duration-200 ease-out lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            aria-label="Navigasi utama"
        >
            <div class="flex h-16 items-center justify-between border-b border-white/10 px-5">
                <a href="{{ route(auth()->user()->dashboardRouteName()) }}" class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-500 text-white shadow-lg shadow-indigo-500/30">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5V4.5h10.5v3M6.75 16.5h-1.5A1.5 1.5 0 0 1 3.75 15v-4.5A1.5 1.5 0 0 1 5.25 9h13.5a1.5 1.5 0 0 1 1.5 1.5V15a1.5 1.5 0 0 1-1.5 1.5h-1.5M6.75 12.75h10.5v6.75H6.75v-6.75Z" />
                        </svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-bold tracking-tight text-white">{{ config('app.name') }}</span>
                        <span class="block truncate text-[11px] text-slate-500">ERP Printing &amp; Advertising</span>
                    </span>
                </a>
                <button
                    type="button"
                    class="rounded-lg p-2 text-slate-400 transition hover:bg-white/10 hover:text-white lg:hidden"
                    @click="sidebarOpen = false"
                    aria-label="Tutup menu"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-6">
                <p class="px-3 text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">Menu Divisi</p>

                <a
                    href="{{ route(auth()->user()->dashboardRouteName()) }}"
                    class="group flex items-center gap-3 rounded-xl bg-white/10 px-3 py-2.5 text-sm font-semibold text-white ring-1 ring-white/10 transition hover:bg-white/15"
                    aria-current="page"
                >
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-500/20 text-indigo-300">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 10.5 12 3.75l8.25 6.75V20.25H3.75V10.5Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 20.25v-6h6v6" />
                        </svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate">Dashboard</span>
                        <span class="block truncate text-[11px] font-normal text-slate-400">{{ auth()->user()->roleLabel() }}</span>
                    </span>
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                </a>

                @php
                    $riwayatRute = [
                        \App\Models\User::ROLE_DESAINER => 'desainer.riwayat',
                        \App\Models\User::ROLE_OPERATOR_CETAK => 'operator.riwayat',
                        \App\Models\User::ROLE_FINISHING => 'finishing.riwayat',
                        \App\Models\User::ROLE_ADVERTISING => 'advertising.riwayat',
                    ];
                @endphp

                @if (isset($riwayatRute[auth()->user()->role]))
                    <a
                        href="{{ route($riwayatRute[auth()->user()->role]) }}"
                        class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('desainer.riwayat', 'operator.riwayat', 'finishing.riwayat', 'advertising.riwayat', 'desainer.orders.show', 'operator.orders.show', 'finishing.orders.show', 'advertising.orders.show') ? 'bg-white/10 text-white ring-1 ring-white/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"
                    >
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-cyan-500/15 text-cyan-300">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate">Riwayat Selesai</span>
                            <span class="block truncate text-[11px] font-normal text-slate-400">Audit order per nota</span>
                        </span>
                    </a>
                @endif

                @php
                    $serahTerimaRute = [
                        \App\Models\User::ROLE_ADMIN => 'admin.serah-terima.index',
                        \App\Models\User::ROLE_DESAINER => 'desainer.serah-terima.index',
                        \App\Models\User::ROLE_OPERATOR_CETAK => 'operator.serah-terima.index',
                        \App\Models\User::ROLE_FINISHING => 'finishing.serah-terima.index',
                        \App\Models\User::ROLE_ADVERTISING => 'advertising.serah-terima.index',
                    ];
                @endphp

                @if (isset($serahTerimaRute[auth()->user()->role]))
                    <a
                        href="{{ route($serahTerimaRute[auth()->user()->role]) }}"
                        class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('*.serah-terima.index', '*.orders.serah-terima.create', '*.orders.pelunasan.create') ? 'bg-white/10 text-white ring-1 ring-white/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"
                    >
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-300">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5 12 3.75 3.75 7.5m16.5 0L12 11.25 3.75 7.5m16.5 0v9L12 20.25l-8.25-3.75v-9M12 11.25v9" />
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate">Serah Terima</span>
                            <span class="block truncate text-[11px] font-normal text-slate-400">Foto barang &amp; penerima</span>
                        </span>
                    </a>
                @endif

                @if (auth()->user()->role === \App\Models\User::ROLE_ADMIN)
                    <a
                        href="{{ route('admin.orders.create') }}"
                        class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.orders.create') ? 'bg-white/10 text-white ring-1 ring-white/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"
                    >
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-300">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate">Input Order Baru</span>
                            <span class="block truncate text-[11px] font-normal text-slate-400">Nota &amp; keranjang multi-item</span>
                        </span>
                    </a>

                    <a
                        href="{{ route('admin.orders.index') }}"
                        class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.orders.index', 'admin.orders.summary') ? 'bg-white/10 text-white ring-1 ring-white/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"
                    >
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-500/15 text-sky-300">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12M8.25 17.25h12M3.75 6.75h.007v.008H3.75V6.75Zm0 5.25h.007v.008H3.75V12Zm0 5.25h.007v.008H3.75v-.008Z" />
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate">Daftar Nota</span>
                            <span class="block truncate text-[11px] font-normal text-slate-400">Satu baris per nota &amp; detail item</span>
                        </span>
                    </a>
                @endif

                @if (auth()->user()->role === \App\Models\User::ROLE_OWNER)
                    <p class="px-3 pt-5 text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">Menu Owner</p>

                    <a
                        href="{{ route('owner.rekap-orders') }}"
                        class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('owner.rekap-orders', 'owner.orders.show') ? 'bg-white/10 text-white ring-1 ring-white/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"
                    >
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-500/15 text-sky-300">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate">Rekap Order</span>
                            <span class="block truncate text-[11px] font-normal text-slate-400">Satu baris per nota</span>
                        </span>
                    </a>

                    <a
                        href="{{ route('owner.reports.materials') }}"
                        class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('owner.reports.materials') ? 'bg-white/10 text-white ring-1 ring-white/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"
                    >
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500/15 text-amber-300">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5 12 3.75 3.75 7.5m16.5 0L12 11.25 3.75 7.5m16.5 0v9L12 20.25l-8.25-3.75v-9M12 11.25v9" />
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate">Laporan Bahan</span>
                            <span class="block truncate text-[11px] font-normal text-slate-400">Stok &amp; audit pemakaian</span>
                        </span>
                    </a>

                    <a
                        href="{{ route('owner.reports.profit-loss') }}"
                        class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('owner.reports.profit-loss') ? 'bg-white/10 text-white ring-1 ring-white/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"
                    >
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-300">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 6.75h18v12H3v-12Zm3 8.25h4.5m3 0H18M6.75 10.5h3m4.5 0h3" />
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate">Laba Rugi</span>
                            <span class="block truncate text-[11px] font-normal text-slate-400">Omzet, HPP &amp; biaya op.</span>
                        </span>
                    </a>

                    <a
                        href="{{ route('owner.settings') }}"
                        class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('owner.settings*') ? 'bg-white/10 text-white ring-1 ring-white/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"
                    >
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.59c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.76 6.76 0 0 1 0 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.94-1.11.94h-2.59c-.55 0-1.019-.397-1.11-.94l-.213-1.281a.973.973 0 0 0-.645-.87 6.53 6.53 0 0 1-.22-.127c-.325-.196-.72-.257-1.075-.124l-1.217.456a1.125 1.125 0 0 1-1.37-.49L4.305 15.5a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.76 6.76 0 0 1 0-.255c.007-.378-.138-.75-.43-.99l-1.005-.828a1.125 1.125 0 0 1-.26-1.43l1.298-2.247a1.125 1.125 0 0 1 1.369-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.281Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate">Pengaturan Master</span>
                            <span class="block truncate text-[11px] font-normal text-slate-400">Harga, stok &amp; finishing</span>
                        </span>
                    </a>
                @endif

                <div class="mt-6 rounded-xl border border-white/10 bg-white/[0.03] p-3.5">
                    <div class="flex items-start gap-2.5">
                        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-400/10 text-amber-300">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM10.34 3.75h3.32a2.25 2.25 0 0 1 1.94 1.12l6.1 10.57a2.25 2.25 0 0 1-1.94 3.37H4.24a2.25 2.25 0 0 1-1.94-3.37l6.1-10.57a2.25 2.25 0 0 1 1.94-1.12Z" />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-slate-200">Tahap 1 — Fondasi</p>
                            <p class="mt-1 text-[11px] leading-5 text-slate-400">Login RBAC, isolasi divisi, dan storage media lokal sudah aktif.</p>
                        </div>
                    </div>
                </div>

                <div class="px-3 pt-6">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">Role Anda</p>
                    <p class="mt-2 text-sm font-semibold text-slate-200">{{ auth()->user()->roleLabel() }}</p>
                    <p class="mt-1 text-[11px] leading-5 text-slate-500">Akses dibatasi hanya untuk dashboard divisi ini.</p>
                </div>
            </nav>

            <div class="border-t border-white/10 p-4">
                <div class="flex items-center gap-3 rounded-xl bg-white/5 p-3 ring-1 ring-white/10">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-500/20 text-sm font-bold text-indigo-200">
                        {{ mb_substr(auth()->user()->name, 0, 1) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-white">{{ auth()->user()->name }}</p>
                        <p class="truncate text-[11px] text-slate-400">{{ auth()->user()->roleLabel() }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button
                        type="submit"
                        class="flex w-full items-center justify-center gap-2 rounded-xl border border-white/10 px-3 py-2.5 text-sm font-semibold text-slate-300 transition hover:border-rose-400/30 hover:bg-rose-500/10 hover:text-rose-300"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                        </svg>
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <div class="lg:pl-72">
            {{-- Topbar --}}
            <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 backdrop-blur">
                <div class="flex h-16 items-center gap-3 px-4 sm:gap-4 sm:px-6 lg:px-8">
                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 lg:hidden"
                        @click="sidebarOpen = true"
                        aria-label="Buka menu"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                        </svg>
                    </button>

                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-indigo-600">@yield('section', 'Workspace')</p>
                        <h1 class="truncate text-base font-bold text-slate-900 sm:text-lg">@yield('page-title', 'Dashboard')</h1>
                    </div>

                    <span class="hidden items-center gap-1.5 rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-100 sm:inline-flex">
                        <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>
                        {{ auth()->user()->roleLabel() }}
                    </span>

                    <div
                        x-data="topbarNotifications(@js(auth()->user()->role))"
                        x-init="init()"
                        class="relative"
                    >
                        <button
                            type="button"
                            class="relative inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-slate-600 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-600"
                            @click="open = !open"
                            aria-label="Notifikasi"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                            </svg>
                            <span
                                x-cloak
                                x-show="unreadCount > 0"
                                x-text="unreadCount > 9 ? '9+' : unreadCount"
                                class="absolute -right-1 -top-1 inline-flex min-w-5 items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white"
                            ></span>
                        </button>

                        <div
                            x-cloak
                            x-show="open"
                            @click.outside="open = false"
                            x-transition
                            class="absolute right-0 z-40 mt-2 w-80 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl"
                        >
                            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                                <p class="text-sm font-semibold text-slate-900">Notifikasi</p>
                                <button type="button" class="text-xs font-medium text-indigo-600 hover:text-indigo-700" @click="markAllRead()">Tandai dibaca</button>
                            </div>
                            <div class="max-h-80 overflow-y-auto">
                                <template x-if="items.length === 0">
                                    <p class="px-4 py-6 text-center text-sm text-slate-500">Belum ada notifikasi.</p>
                                </template>
                                <template x-for="item in items" :key="item.id">
                                    <div class="border-b border-slate-100 px-4 py-3 last:border-b-0" :class="item.read ? 'bg-white' : 'bg-indigo-50/40'">
                                        <p class="text-sm font-medium text-slate-800" x-text="item.message"></p>
                                        <p class="mt-1 text-[11px] text-slate-500" x-text="item.time"></p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="hidden h-8 w-px bg-slate-200 sm:block"></div>

                    <div class="hidden text-right sm:block">
                        <p class="max-w-[10rem] truncate text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                        <p class="text-[11px] text-slate-500">{{ auth()->user()->email }}</p>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            aria-label="Logout"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 shadow-sm transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                            </svg>
                            <span class="hidden sm:inline">Logout</span>
                        </button>
                    </form>
                </div>
            </header>

            <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                @if (session('status'))
                    <div class="mb-5 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <p>{{ session('status') }}</p>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-rose-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM10.34 3.75h3.32a2.25 2.25 0 0 1 1.94 1.12l6.1 10.57a2.25 2.25 0 0 1-1.94 3.37H4.24a2.25 2.25 0 0 1-1.94-3.37l6.1-10.57a2.25 2.25 0 0 1 1.94-1.12Z" />
                        </svg>
                        <p>{{ session('error') }}</p>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
