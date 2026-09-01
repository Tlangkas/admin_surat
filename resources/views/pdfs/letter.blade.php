<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Surat - {{ $letterRequest->uuid }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1.5cm 2cm 2cm 2cm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
            margin: 0;
            padding: 0;
        }

        /* Kop Surat Resmi */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        .header-table td {
            vertical-align: middle;
        }

        .logo-cell {
            width: 85px;
            text-align: left;
        }

        .logo-img {
            width: 75px;
            height: auto;
            max-height: 80px;
        }

        .header-text {
            text-align: center;
        }

        .header-text h3 {
            font-size: 12pt;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header-text h2 {
            font-size: 14pt;
            font-weight: bold;
            margin: 2px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .header-text h1 {
            font-size: 15pt;
            font-weight: bold;
            margin: 2px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .header-text p {
            font-size: 9.5pt;
            margin: 1px 0;
            line-height: 1.2;
        }

        .header-divider {
            border: none;
            border-top: 3px double #000;
            margin-top: 6px;
            margin-bottom: 20px;
        }

        /* Body Content */
        .content {
            font-size: 11pt;
            line-height: 1.5;
            text-align: justify;
        }

        .content table.table-data {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0 12px 20px;
        }

        .content table.table-data td {
            padding: 3px 4px;
            vertical-align: top;
            font-size: 11pt;
        }

        /* Footer TTD & QR Code */
        .footer-table {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }

        .footer-table td {
            vertical-align: top;
        }

        .qr-section {
            width: 45%;
            text-align: left;
            padding-top: 10px;
        }

        .qr-box {
            display: inline-block;
            text-align: center;
            padding: 6px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background-color: #fff;
        }

        .qr-box svg {
            width: 85px;
            height: 85px;
        }

        .qr-text {
            font-size: 7.5pt;
            color: #475569;
            margin-top: 4px;
            font-family: sans-serif;
        }

        .ttd-section {
            width: 55%;
            text-align: right;
        }

        .ttd-box {
            display: inline-block;
            text-align: left;
            min-width: 220px;
        }

        .ttd-image {
            height: 55px;
            width: auto;
            max-height: 60px;
            margin: 4px 0;
        }

        .ttd-space {
            height: 55px;
        }

        /* Stempel Tanda Tangan Digital (TTE) */
        .tte-stamp {
            display: block;
            margin: 6px 0;
            padding: 6px 10px;
            border: 1.5px solid #0284c7;
            border-radius: 5px;
            background-color: #f0f9ff;
            width: 210px;
            font-family: sans-serif;
        }

        .tte-header {
            font-size: 7.5pt;
            font-weight: bold;
            color: #0369a1;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .tte-check {
            color: #16a34a;
            font-weight: bold;
            font-size: 9pt;
        }

        .tte-body {
            font-size: 7pt;
            color: #334155;
            margin-top: 2px;
        }

        .tte-id {
            font-size: 6.5pt;
            color: #64748b;
            font-family: monospace;
            margin-top: 2px;
        }
    </style>
</head>
<body>
    {{-- Header Kop Surat Resmi --}}
    <table class="header-table">
        <tr>
            @if($settings->getLogoBase64())
                <td class="logo-cell">
                    <img src="{{ $settings->getLogoBase64() }}" alt="Logo" class="logo-img">
                </td>
            @endif
            <td class="header-text">
                <h3>{{ $settings->kop_line_1 ?? 'PEMERINTAH KOTA SURAKARTA' }}</h3>
                <h2>{{ $settings->kop_line_2 ?? 'DINAS PENDIDIKAN' }}</h2>
                <h1>{{ strtoupper($settings->nama_sekolah ?? 'SD NEGERI 1 SURAKARTA') }}</h1>
                <p>{{ $settings->alamat_lengkap ?? $settings->alamat }}</p>
                <p>{{ $settings->kontak_lengkap ?? ('Telp. ' . ($settings->telepon ?? '-') . ' | Email: ' . ($settings->email ?? '-')) }}</p>
            </td>
        </tr>
    </table>
    <hr class="header-divider">

    {{-- Konten Surat --}}
    <div class="content">
        {!! $content !!}
    </div>

    {{-- Footer TTD & QR Code --}}
    <table class="footer-table">
        <tr>
            <td class="qr-section">
                <div class="qr-box">
                    {!! $qrCodeSvg !!}
                    <div class="qr-text">Dokumen Resmi E-Surat<br>Scan untuk Verifikasi</div>
                </div>
            </td>
            <td class="ttd-section">
                <div class="ttd-box">
                    <p style="margin: 0;">{{ $settings->kota_kabupaten ?? 'Surakarta' }}, {{ now()->translatedFormat('d F Y') }}</p>
                    <p style="font-weight: bold; margin: 4px 0 0 0;">{{ $settings->kepala_sekolah_jabatan ?? 'Kepala Sekolah' }}</p>
                    
                    @if($settings->getTtdKepsekBase64())
                        <div>
                            <img src="{{ $settings->getTtdKepsekBase64() }}" alt="TTD" class="ttd-image">
                        </div>
                    @elseif($letterRequest->isSigned())
                        <div class="tte-stamp">
                            <div class="tte-header"><span class="tte-check">✓</span> DITANDATANGANI DIGITAL</div>
                            <div class="tte-body">Sistem Layanan E-Surat Resmi</div>
                            <div class="tte-id">ID: {{ substr($letterRequest->uuid, 0, 18) }}...</div>
                        </div>
                    @else
                        <div class="ttd-space"></div>
                    @endif

                    <p style="font-weight: bold; text-decoration: underline; margin: 0;">{{ $settings->kepala_sekolah_nama ?? 'Drs. H. Supriyanto, M.Pd.' }}</p>
                    <p style="margin: 2px 0 0 0;">NIP. {{ $settings->kepala_sekolah_nip ?? '196501011990031010' }}</p>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
