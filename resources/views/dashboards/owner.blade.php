@extends('layouts.app')

@section('title', 'Dashboard Owner')
@section('section', 'Divisi Owner')
@section('page-title', 'Dashboard Owner')

@section('content')
    @include('dashboards.partials.foundation', [
        'roleLabel' => 'Owner',
        'summary' => 'Pantau kesiapan fondasi sistem dan pastikan seluruh divisi bekerja dalam ruang aksesnya masing-masing sebelum laporan operasional tersedia.',
        'tasks' => [
            'Tinjau ringkasan operasional seluruh divisi pada tahap laporan berikutnya.',
            'Pastikan enam role pegawai memiliki akses yang terisolasi dengan benar.',
            'Awasi kesiapan penyimpanan media lokal untuk seluruh pekerjaan produksi.',
            'Validasi alur bisnis sebelum modul transaksi order diaktifkan.',
        ],
    ])
@endsection
