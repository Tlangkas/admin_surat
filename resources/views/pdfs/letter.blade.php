<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Surat - {{ $letterRequest->uuid }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1.2cm 1.5cm 2cm 1.5cm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
            margin: 0;
            padding: 0;
        }

        /* Kop Surat Resmi Standar Instansi Pendidikan */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }

        .kop-table td {
            padding: 0;
        }

        .kop-container {
            position: relative;
            width: 100%;
            margin-bottom: 0;
            box-sizing: border-box;
        }

        .kop-text {
            text-align: center;
            vertical-align: middle;
            box-sizing: border-box;
        }

        .kop-text .kop-line-1,
        .kop-text h3 {
            font-size: 9.5pt;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.2;
        }

        .kop-text .kop-line-2,
        .kop-text h2 {
            font-size: 11pt;
            font-weight: bold;
            margin: 2px 0 0 0;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            line-height: 1.2;
        }

        .kop-text .kop-line-3,
        .kop-text h1 {
            font-size: 13pt;
            font-weight: bold;
            margin: 2px 0 0 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.2;
        }

        .kop-text .kop-line-4,
        .kop-text p.kop-alamat {
            font-size: 7.2pt;
            margin: 3px 0 0 0;
            line-height: 1.25;
            font-family: Arial, Helvetica, sans-serif;
        }

        .kop-text .kop-line-5,
        .kop-text p.kop-kontak {
            font-size: 7pt;
            margin: 2px 0 0 0;
            line-height: 1.25;
            font-family: Arial, Helvetica, sans-serif;
        }

        .kop-text p {
            margin-bottom: 0;
        }

        .header-divider {
            height: 0;
            line-height: 0;
            font-size: 0;
            border: none;
            border-top: 3px double #000;
            margin-top: 8px;
            margin-bottom: 18px;
            clear: both;
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
            vertical-align: bottom;
        }

        .qr-section {
            width: 40%;
            text-align: left;
            padding-bottom: 5px;
        }

        .qr-box {
            display: inline-block;
            text-align: left;
        }

        .qr-box svg {
            width: 75px;
            height: 75px;
        }

        .ttd-section {
            width: 60%;
            text-align: right;
        }

        .ttd-box {
            display: inline-block;
            text-align: left;
            min-width: 220px;
        }

        .ttd-image {
            height: 60px;
            width: auto;
            max-height: 65px;
            margin: 4px 0;
        }

        .ttd-space {
            height: 60px;
        }
    </style>
</head>
<body>
    @php
        $hasLogoKiri = !empty($settings->getLogoBase64());
        $useLogoKanan = !empty($settings->has_logo_kanan) && !empty($settings->getLogoKananBase64());
        
        $logoWidth = (int) ($settings->logo_width ?? 85);
        $logoKananWidth = (int) ($settings->logo_kanan_width ?? 85);
        $kopGap = (int) ($settings->kop_gap ?? 10);
        
        $logoValign = in_array($settings->logo_valign, ['top', 'middle', 'bottom']) ? $settings->logo_valign : 'middle';
        $logoOffsetX = (int) ($settings->logo_offset_x ?? 0);
        $logoOffsetY = (int) ($settings->logo_offset_y ?? 0);

        $logoKananValign = in_array($settings->logo_kanan_valign, ['top', 'middle', 'bottom']) ? $settings->logo_kanan_valign : 'middle';
        $logoKananOffsetX = (int) ($settings->logo_kanan_offset_x ?? 0);
        $logoKananOffsetY = (int) ($settings->logo_kanan_offset_y ?? 0);

        // Hitung lebar samping simetris (logo + jarak gap) agar teks berada di tengah dan tidak bertabrakan dengan logo
        $leftSideWidth = $hasLogoKiri ? ($logoWidth + max(0, $logoOffsetX) + $kopGap) : 0;
        $rightSideWidth = $useLogoKanan ? ($logoKananWidth + max(0, -$logoKananOffsetX) + $kopGap) : 0;
        $sideWidth = $useLogoKanan ? max($leftSideWidth, $rightSideWidth) : $leftSideWidth;
    @endphp

    {{-- Kop Surat Resmi Standar Instansi Pendidikan (Presisi Senter Simetris) --}}
    <table class="kop-table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
        <tr>
            {{-- Kolom Kiri: Logo Utama --}}
            <td style="width: {{ $sideWidth }}px; text-align: left; vertical-align: {{ $logoValign }}; padding: 0;">
                @if($hasLogoKiri)
                    <div style="margin-left: {{ $logoOffsetX + $kopGap }}px; margin-top: {{ $logoOffsetY }}px;">
                        <img src="{{ $settings->getLogoBase64() }}" 
                             alt="Logo Utama" 
                             style="width: {{ $logoWidth }}px; height: auto; display: block;">
                    </div>
                @else
                    <div style="width: {{ $sideWidth }}px; height: 1px;"></div>
                @endif
            </td>

            {{-- Kolom Tengah: Teks Instansi Kop Surat --}}
            <td class="kop-text" style="text-align: center; vertical-align: {{ $logoValign }}; padding: 0;">
                <div class="kop-line-1">{{ $settings->kop_line_1 ?? 'PEMERINTAH KOTA SURAKARTA' }}</div>
                <div class="kop-line-2">{{ $settings->kop_line_2 ?? 'DINAS PENDIDIKAN' }}</div>
                <div class="kop-line-3">{{ strtoupper($settings->nama_sekolah ?? 'SD NEGERI 1 SURAKARTA') }}</div>
                <div class="kop-line-4">{{ $settings->alamat_lengkap ?? $settings->alamat }}</div>
                @php
                    $contactParts = [];
                    if (!empty($settings->telepon)) {
                        $telp = trim((string) $settings->telepon);
                        $contactParts[] = str_starts_with(strtolower($telp), 'telp') ? $telp : 'Telp. ' . $telp;
                    }
                    if (!empty($settings->email)) {
                        $email = trim((string) $settings->email);
                        $contactParts[] = str_starts_with(strtolower($email), 'email') ? $email : 'Email: ' . $email;
                    }
                    if (!empty($settings->website)) {
                        $contactParts[] = trim((string) $settings->website);
                    }
                    if (!empty($settings->npsn)) {
                        $contactParts[] = 'NPSN: ' . trim((string) $settings->npsn);
                    }
                    if (!empty($settings->formatted_akreditasi)) {
                        $contactParts[] = $settings->formatted_akreditasi;
                    }
                @endphp
                <div class="kop-line-5">{{ implode(' | ', $contactParts) }}</div>
            </td>

            {{-- Kolom Kanan: Logo Sekunder atau Penyeimbang Simetris Senter Kertas --}}
            <td style="width: {{ $sideWidth }}px; text-align: right; vertical-align: {{ $logoKananValign }}; padding: 0;">
                @if($useLogoKanan)
                    <div style="margin-right: {{ -$logoKananOffsetX + $kopGap }}px; margin-top: {{ $logoKananOffsetY }}px; float: right;">
                        <img src="{{ $settings->getLogoKananBase64() }}" 
                             alt="Logo Sekunder" 
                             style="width: {{ $logoKananWidth }}px; height: auto; display: block;">
                    </div>
                @else
                    <div style="width: {{ $sideWidth }}px; height: 1px;"></div>
                @endif
            </td>
        </tr>
    </table>

    <div class="header-divider"></div>

    {{-- Konten Surat --}}
    <div class="content">
        {!! $content !!}
    </div>

    {{-- Footer TTD & QR Code --}}
    <table class="footer-table">
        <tr>
            <td class="qr-section">
                @if(!empty($qrCodeSvg))
                    <div class="qr-box">
                        <img src="data:image/svg+xml;base64,{{ base64_encode($qrCodeSvg) }}" 
                             alt="QR Code Verifikasi" 
                             style="width: 75px; height: 75px; display: block;">
                        <p style="font-size: 7pt; margin: 4px 0 0 0; color: #475569; text-align: left; line-height: 1.2;">
                            Dokumen ini resmi dan ditandatangani elektronik.<br>
                            Pindai QR Code untuk verifikasi keaslian.
                        </p>
                    </div>
                @endif
            </td>
            <td class="ttd-section">
                <div class="ttd-box">
                    @php
                        $tanggalTtd = $letterRequest->signed_at 
                            ? $letterRequest->signed_at->translatedFormat('d F Y') 
                            : ($letterRequest->created_at ? $letterRequest->created_at->translatedFormat('d F Y') : now()->translatedFormat('d F Y'));
                    @endphp
                    <p style="margin: 0;">{{ $settings->kota_kabupaten ?? 'Surakarta' }}, {{ $tanggalTtd }}</p>
                    <p style="font-weight: bold; margin: 4px 0 0 0;">{{ $settings->kepala_sekolah_jabatan ?? 'Kepala Sekolah' }}</p>
                    
                    @if($settings->getTtdKepsekBase64())
                        <div>
                            <img src="{{ $settings->getTtdKepsekBase64() }}" alt="TTD" class="ttd-image">
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
