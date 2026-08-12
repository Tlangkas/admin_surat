<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Helper: TemplatePresets
 *
 * Menyediakan preset template surat resmi siap pakai (Surat Tugas, Surat Keterangan, Surat Izin).
 */
class TemplatePresets
{
    public static function getPresets(): array
    {
        return [
            'surat_tugas' => [
                'name' => 'Surat Tugas Perjalanan Dinas',
                'content' => <<<'HTML'
<div style="text-align: center; margin-bottom: 20px;">
    <h3 style="margin: 0; text-transform: uppercase; font-size: 16px; font-weight: bold; text-decoration: underline;">SURAT TUGAS</h3>
    <p style="margin: 5px 0 0 0; font-size: 13px;">Nomor: {{ nomor_surat }}</p>
</div>

<p>Yang bertanda tangan di bawah ini Kepala Sekolah menerangkan bahwa:</p>

<table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
    <tr>
        <td style="width: 25%; padding: 4px 0;">Nama</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="font-weight: bold; padding: 4px 0;">{{ nama }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">NIP</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ nip }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Jabatan</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ jabatan }}</td>
    </tr>
</table>

<p>Diberikan tugas untuk melaksanakan perjalanan dinas dengan rincian sebagai berikut:</p>

<table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
    <tr>
        <td style="width: 25%; padding: 4px 0;">Tujuan Dinas</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ tujuan }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Maksud / Keperluan</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ keperluan }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Tanggal Berangkat</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ tanggal_berangkat }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Tanggal Kembali</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ tanggal_kembali }}</td>
    </tr>
</table>

<p>Demikian Surat Tugas ini dibuat untuk dipergunakan sebagaimana mestinya dan dilaksanakan dengan penuh tanggung jawab.</p>
HTML,
            ],

            'surat_keterangan' => [
                'name' => 'Surat Keterangan Aktif',
                'content' => <<<'HTML'
<div style="text-align: center; margin-bottom: 20px;">
    <h3 style="margin: 0; text-transform: uppercase; font-size: 16px; font-weight: bold; text-decoration: underline;">SURAT KETERANGAN</h3>
    <p style="margin: 5px 0 0 0; font-size: 13px;">Nomor: {{ nomor_surat }}</p>
</div>

<p>Yang bertanda tangan di bawah ini Kepala Sekolah menerangkan dengan sebenarnya bahwa:</p>

<table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
    <tr>
        <td style="width: 25%; padding: 4px 0;">Nama</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="font-weight: bold; padding: 4px 0;">{{ nama }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">NIP</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ nip }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Jabatan</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ jabatan }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Unit Kerja</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ sekolah }}</td>
    </tr>
</table>

<p>Adalah benar merupakan Guru / Karyawan aktif yang saat ini masih bertugas pada unit kerja kami.</p>

<p>Surat keterangan ini diberikan kepada yang bersangkutan untuk keperluan: <strong>{{ keperluan }}</strong>.</p>

<p>Demikian Surat Keterangan ini dibuat dengan sebenarnya agar dapat dipergunakan sebagaimana mestinya.</p>
HTML,
            ],

            'surat_izin' => [
                'name' => 'Surat Izin Permohonan',
                'content' => <<<'HTML'
<div style="text-align: center; margin-bottom: 20px;">
    <h3 style="margin: 0; text-transform: uppercase; font-size: 16px; font-weight: bold; text-decoration: underline;">SURAT IZIN</h3>
    <p style="margin: 5px 0 0 0; font-size: 13px;">Nomor: {{ nomor_surat }}</p>
</div>

<p>Yang bertanda tangan di bawah ini Kepala Sekolah memberikan izin kepada:</p>

<table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
    <tr>
        <td style="width: 25%; padding: 4px 0;">Nama</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="font-weight: bold; padding: 4px 0;">{{ nama }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">NIP</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ nip }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Jabatan</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ jabatan }}</td>
    </tr>
</table>

<p>Untuk tidak hadir bertugas / melaksanakan kegiatan dengan rincian:</p>

<table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
    <tr>
        <td style="width: 25%; padding: 4px 0;">Alasan / Keperluan</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ keperluan }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Tujuan</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ tujuan }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Tanggal Izin</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ tanggal_berangkat }} s/d {{ tanggal_kembali }}</td>
    </tr>
</table>

<p>Demikian surat izin ini diberikan untuk dapat dipergunakan sebagaimana mestinya.</p>
HTML,
            ],
        ];
    }
}
