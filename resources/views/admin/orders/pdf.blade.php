<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Ringkasan Nota {{ $order->no_nota }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 14mm;
        }

        body {
            margin: 0;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9.5px;
            line-height: 1.45;
            color: #0f172a;
        }

        h1, h2, h3, p { margin: 0; }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .masthead {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .brand {
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 0.4px;
        }

        .brand-sub {
            margin-top: 2px;
            font-size: 9px;
            color: #475569;
        }

        .document-title {
            text-align: right;
        }

        .document-title strong {
            display: block;
            font-size: 13px;
        }

        .document-title span {
            color: #475569;
        }

        .section { margin-top: 14px; }

        .section-title {
            margin-bottom: 6px;
            padding-bottom: 4px;
            border-bottom: 1px solid #cbd5e1;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .meta td {
            padding: 3px 6px 3px 0;
            vertical-align: top;
        }

        .meta .label {
            width: 26%;
            color: #64748b;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .meta .value { font-weight: bold; }

        .items th, .items td {
            padding: 5px 6px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }

        .items th {
            background: #f1f5f9;
            color: #475569;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            text-align: left;
        }

        .items .right { text-align: right; }
        .items .center { text-align: center; }
        .items .name { font-weight: bold; }
        .items .muted { color: #64748b; font-size: 8.5px; }
        .items .total-row td { border-top: 1px solid #0f172a; border-bottom: none; font-weight: bold; }

        .payment {
            margin-top: 10px;
            margin-left: auto;
            width: 58%;
        }

        .payment td { padding: 4px 6px; }
        .payment .label { color: #475569; }
        .payment .right { text-align: right; font-weight: bold; }
        .payment .grand td {
            border-top: 1px solid #94a3b8;
            font-size: 11px;
        }

        .note {
            margin-top: 8px;
            padding: 8px 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .spk {
            margin-top: 16px;
            padding: 10px 12px;
            border: 1.5px dashed #b45309;
            background: #fffbeb;
            page-break-inside: avoid;
        }

        .spk-heading {
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 1px solid #fcd34d;
            font-size: 11px;
            font-weight: bold;
            color: #92400e;
        }

        .spk-heading span {
            float: right;
            font-size: 8.5px;
            font-weight: normal;
            color: #b45309;
        }

        .spk table td {
            padding: 4px 6px;
            border-bottom: 1px solid #fde68a;
            vertical-align: top;
        }

        .spk .label {
            width: 26%;
            color: #92400e;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .spk-disclaimer {
            margin-top: 8px;
            color: #92400e;
            font-size: 8.5px;
        }

        .footer {
            margin-top: 18px;
            padding-top: 8px;
            border-top: 1px solid #cbd5e1;
            color: #64748b;
            font-size: 8.5px;
        }
    </style>
</head>
<body>
    <div class="masthead">
        <table>
            <tr>
                <td style="width: 60%;">
                    <p class="brand">{{ config('app.name') }}</p>
                    <p class="brand-sub">ERP Digital Printing &amp; Advertising</p>
                </td>
                <td class="document-title">
                    <strong>RINGKASAN NOTA</strong>
                    <span>{{ $order->no_nota }}</span>
                    <span>{{ $order->created_at?->format('d F Y, H:i') }} WIB</span>
                </td>
            </tr>
        </table>
    </div>

    <section class="section">
        <h2 class="section-title">Data Pelanggan</h2>
        <table class="meta">
            <tr>
                <td class="label">Instansi</td>
                <td class="value">{{ $order->customer?->instansi ?: \App\Models\Customer::INSTANSI_DEFAULT }}</td>
                <td class="label">Kategori Harga</td>
                <td class="value">{{ $order->customer?->kategori }}</td>
            </tr>
            <tr>
                <td class="label">Nama Pelanggan</td>
                <td class="value">{{ $order->customer?->nama_pelanggan }}</td>
                <td class="label">WhatsApp</td>
                <td class="value">{{ $order->customer?->no_wa }}</td>
            </tr>
            <tr>
                <td class="label">Alamat</td>
                <td>{{ $order->customer?->alamat ?: '—' }}</td>
                <td class="label">Deadline</td>
                <td>{{ $order->deadline?->format('d/m/Y H:i') ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Status Bayar</td>
                <td>{{ $order->statusBayarLabel() }}</td>
                <td class="label">Status Desain</td>
                <td>{{ $order->statusDesainLabel() }}</td>
            </tr>
        </table>
    </section>

    <section class="section">
        <h2 class="section-title">Rincian Biaya Digital Printing</h2>
        <table class="items">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 25%;">Produk</th>
                    <th style="width: 24%;">Ukuran / Finishing</th>
                    <th style="width: 7%;" class="center">Qty</th>
                    <th style="width: 13%;" class="right">Luas Tagihan</th>
                    <th style="width: 13%;" class="right">Harga Satuan</th>
                    <th style="width: 13%;" class="right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="name">{{ $item->product?->nama_produk }}<br><span class="muted">{{ $item->product?->kategori_produk }}</span></td>
                        <td>
                            @if ($item->isMeteran())
                                {{ number_format((float) $item->lebar_m, 2, ',', '.') }} m × {{ number_format((float) $item->tinggi_m, 2, ',', '.') }} m
                                <br><span class="muted">{{ $item->finishing?->nama_finishing ?: 'Tanpa finishing' }}</span>
                            @else
                                <span class="muted">Produk satuan</span>
                            @endif
                        </td>
                        <td class="center">{{ $item->qty }}</td>
                        <td class="right">{{ $item->luas_tagihan_m2 !== null ? number_format((float) $item->luas_tagihan_m2, 2, ',', '.').' m²' : '—' }}</td>
                        <td class="right">Rp {{ number_format((float) $item->harga_satuan_dipakai, 0, ',', '.') }}</td>
                        <td class="right">Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="6" class="right">Total Tagihan</td>
                    <td class="right">Rp {{ number_format((float) $order->total_tagihan, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <table class="payment">
            <tr>
                <td class="label">DP Dibayar</td>
                <td class="right">Rp {{ number_format((float) $order->jumlah_dp, 0, ',', '.') }}</td>
            </tr>
            <tr class="grand">
                <td class="label">Sisa Tagihan</td>
                <td class="right">Rp {{ number_format($order->sisaTagihan(), 0, ',', '.') }}</td>
            </tr>
        </table>
    </section>

    <section class="section">
        <h2 class="section-title">Briefing Desain</h2>
        <div class="note">{{ $order->catatan_desain ?: 'Tidak ada catatan desain.' }}</div>
    </section>

    @if ($order->catatan_pengecualian)
        <section class="section">
            <h2 class="section-title">Catatan Pengecualian (Audit)</h2>
            <div class="note" style="white-space: pre-line;">{{ $order->catatan_pengecualian }}</div>
        </section>
    @endif

    @if ($order->advertisingSpks->isNotEmpty())
        <section class="spk">
            <h2 class="spk-heading">
                Lembar SPK Advertising — Rangka / Pemasangan / Papan Nama
                <span>Non-Billing · Tanpa nominal harga</span>
            </h2>

            @foreach ($order->advertisingSpks as $spk)
                <table>
                    <tr>
                        <td class="label">Jenis Pekerjaan</td>
                        <td><strong>{{ $spk->jenis_pekerjaan }}</strong></td>
                        <td class="label">Status Lapangan</td>
                        <td>{{ $spk->statusLapanganLabel() }}</td>
                    </tr>
                    <tr>
                        <td class="label">Ukuran Konstruksi</td>
                        <td>{{ $spk->ukuran_konstruksi }}</td>
                        <td class="label">Lokasi Pasang</td>
                        <td>{{ $spk->lokasi_pasang }}</td>
                    </tr>
                    <tr>
                        <td class="label">Catatan Teknis</td>
                        <td colspan="3">{{ $spk->catatan_teknis ?: '—' }}</td>
                    </tr>
                </table>
            @endforeach

            <p class="spk-disclaimer">
                Lembar ini hanya menerbitkan pekerjaan lapangan untuk divisi advertising.
                Tidak ada nominal harga pada SPK ini dan pekerjaan tidak dihitung pada tagihan pelanggan.
            </p>
        </section>
    @endif

    <div class="footer">
        Nota dibuat oleh {{ $order->user?->name }}. Dokumen ini dicetak otomatis dari sistem pada {{ now()->format('d/m/Y H:i') }} WIB.
    </div>
</body>
</html>
