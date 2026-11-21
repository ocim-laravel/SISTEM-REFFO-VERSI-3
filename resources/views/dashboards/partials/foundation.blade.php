@php
    $readyCount = collect($mediaFolders)->where('exists', true)->count();
    $totalFolders = count($mediaFolders);
@endphp

<div class="space-y-6">
    {{-- Welcome banner --}}
    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-800 px-6 py-7 text-white shadow-xl shadow-indigo-900/10 sm:px-8">
        <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-white/10 blur-2xl"></div>
        <div class="pointer-events-none absolute -bottom-28 right-24 h-56 w-56 rounded-full bg-fuchsia-400/20 blur-3xl"></div>

        <div class="relative flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-indigo-200">{{ $roleLabel }} Workspace</p>
                <h2 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Selamat datang, {{ auth()->user()->name }}</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-indigo-100">{{ $summary }}</p>
            </div>

            <div class="shrink-0 rounded-xl bg-white/10 px-4 py-3 ring-1 ring-white/15 backdrop-blur">
                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-indigo-200">Role aktif</p>
                <p class="mt-1 text-sm font-semibold">{{ $roleLabel }}</p>
            </div>
        </div>
    </section>

    {{-- Quick status --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Akses Divisi</p>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.96 11.96 0 0 1 3.6 6c-.02.32-.03.65-.03.98 0 5.2 3.3 9.64 7.93 11.32 4.63-1.68 7.93-6.12 7.93-11.32 0-.33-.01-.66-.03-.98A11.96 11.96 0 0 1 12 2.714Z" />
                    </svg>
                </span>
            </div>
            <p class="mt-2 text-lg font-bold text-slate-900">{{ $roleLabel }}</p>
            <p class="mt-1 text-xs leading-5 text-slate-500">Hanya dashboard divisi ini yang dapat dibuka.</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Isolasi RBAC</p>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </span>
            </div>
            <p class="mt-2 text-lg font-bold text-slate-900">Aktif</p>
            <p class="mt-1 text-xs leading-5 text-slate-500">Middleware role memblokir URL divisi lain.</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Media Sistem</p>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5 12 3.75 3.75 7.5m16.5 0L12 11.25 3.75 7.5m16.5 0v9L12 20.25l-8.25-3.75v-9M12 11.25v9" />
                    </svg>
                </span>
            </div>
            <p class="mt-2 text-lg font-bold text-slate-900">{{ $readyCount }}/{{ $totalFolders }} folder</p>
            <p class="mt-1 text-xs leading-5 text-slate-500">Disk lokal <span class="font-mono text-[11px]">media_sistem</span>.</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-5">
        {{-- Role focus --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-3">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Fokus Divisi</h3>
                    <p class="mt-1 text-sm text-slate-500">Ruang kerja tahap fondasi untuk {{ $roleLabel }}.</p>
                </div>
                <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Tahap 1</span>
            </div>

            <ul class="mt-5 space-y-3">
                @foreach ($tasks as $task)
                    <li class="flex gap-3 rounded-xl bg-slate-50 p-3.5 ring-1 ring-slate-100">
                        <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </span>
                        <span class="text-sm leading-6 text-slate-600">{{ $task }}</span>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- Storage status --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Storage Lokal</h3>
                    <p class="mt-1 text-sm text-slate-500">Folder media wajib.</p>
                </div>
                <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider ring-1 {{ $mediaReady ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-amber-50 text-amber-700 ring-amber-100' }}">
                    {{ $mediaReady ? 'Siap' : 'Perlu dicek' }}
                </span>
            </div>

            <div class="mt-5 space-y-2">
                @foreach ($mediaFolders as $folder)
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 bg-slate-50 px-3.5 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-700">{{ $folder['label'] }}</p>
                            <p class="truncate font-mono text-[10px] text-slate-400">{{ $folder['key'] }}</p>
                        </div>
                        @if ($folder['exists'])
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-emerald-100 px-2 py-1 text-[10px] font-semibold text-emerald-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                Siap
                            </span>
                        @else
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-amber-100 px-2 py-1 text-[10px] font-semibold text-amber-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                Kosong
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>

            <p class="mt-4 text-[11px] leading-5 text-slate-400">
                Semua unggahan disimpan di <span class="font-mono">storage/app/public/MEDIA SISTEM</span> dan diakses melalui symbolic link <span class="font-mono">public/storage</span>.
            </p>
        </section>
    </div>
</div>
