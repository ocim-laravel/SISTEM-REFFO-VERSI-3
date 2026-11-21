<form method="GET" action="{{ request()->url() }}" class="flex flex-wrap items-end gap-3">
    <div>
        <label for="dari" class="block text-[10px] font-semibold uppercase tracking-wider text-slate-400">Dari Tanggal</label>
        <input
            type="date"
            id="dari"
            name="dari"
            value="{{ $periode->dari }}"
            class="mt-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100"
        >
    </div>
    <div>
        <label for="sampai" class="block text-[10px] font-semibold uppercase tracking-wider text-slate-400">Sampai Tanggal</label>
        <input
            type="date"
            id="sampai"
            name="sampai"
            value="{{ $periode->sampai }}"
            class="mt-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100"
        >
    </div>
    <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">
        Terapkan
    </button>
    @if (request()->has('dari') || request()->has('sampai'))
        <a href="{{ request()->url() }}" class="rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-500 transition hover:text-slate-800">Reset</a>
    @endif
</form>
