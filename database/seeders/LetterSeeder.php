<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\LetterRequest;
use App\Models\LetterTemplate;
use Illuminate\Database\Seeder;

/**
 * Seeder: LetterSeeder
 *
 * Membuat:
 *   1. Template "Surat Jalan" resmi yang bersih tanpa tag wrapper ganda.
 *   2. Sample LetterRequest dengan berbagai status untuk testing.
 */
class LetterSeeder extends Seeder
{
    public function run(): void
    {
        $templateContent = <<<'HTML'
<div style="text-align: center; margin-bottom: 25px;">
    <div style="font-size: 13pt; font-weight: bold; text-decoration: underline; letter-spacing: 0.5px;">SURAT TUGAS PERJALANAN DINAS</div>
    <div style="font-size: 11pt; margin-top: 4px;">Nomor: {{ nomor_surat }}</div>
</div>

<div class="isi">
    <p>Yang bertanda tangan di bawah ini, Kepala Sekolah menerangkan bahwa:</p>
    <table class="table-data">
        <tr><td style="width: 140px;">Nama</td><td style="width: 15px;">:</td><td><strong>{{ nama }}</strong></td></tr>
        <tr><td>NIP</td><td>:</td><td>{{ nip }}</td></tr>
        <tr><td>Jabatan</td><td>:</td><td>{{ jabatan }}</td></tr>
    </table>

    <p style="margin-top: 15px;">Diberikan tugas untuk melaksanakan perjalanan dinas ke:</p>
    <table class="table-data">
        <tr><td style="width: 140px;">Tujuan</td><td style="width: 15px;">:</td><td>{{ tujuan }}</td></tr>
        <tr><td>Keperluan</td><td>:</td><td>{{ keperluan }}</td></tr>
        <tr><td>Tanggal Berangkat</td><td>:</td><td>{{ tanggal_berangkat }}</td></tr>
        <tr><td>Tanggal Kembali</td><td>:</td><td>{{ tanggal_kembali }}</td></tr>
    </table>

    <p style="margin-top: 15px; text-align: justify;">
        Demikian surat tugas ini dibuat untuk dipergunakan sebagaimana mestinya.
    </p>
</div>
HTML;

        $template = LetterTemplate::create([
            'name' => 'Surat Jalan',
            'content' => $templateContent,
            'variables' => [
                'nomor_surat', 'nama', 'nip', 'jabatan',
                'tujuan', 'keperluan', 'tanggal_berangkat', 'tanggal_kembali',
            ],
            'is_active' => true,
        ]);

        $payloadBase = [
            'nama' => 'Guru Karyawan',
            'nip' => '1234567890',
            'jabatan' => 'Guru Kelas',
        ];

        LetterRequest::create([
            'user_id' => 3,
            'template_id' => $template->id,
            'status' => 'signed',
            'payload_data' => array_merge($payloadBase, [
                'nomor_surat' => '421/001/SPD/2026',
                'tujuan' => 'Dinas Pendidikan Kota Surakarta',
                'keperluan' => 'Mengikuti Sosialisasi Kurikulum Merdeka',
                'tanggal_berangkat' => '2 Januari 2026',
                'tanggal_kembali' => '2 Januari 2026',
            ]),
        ]);

        LetterRequest::create([
            'user_id' => 3,
            'template_id' => $template->id,
            'status' => 'approved_admin',
            'payload_data' => array_merge($payloadBase, [
                'nomor_surat' => '421/002/SPD/2026',
                'tujuan' => 'UPT Perpustakaan Daerah Surakarta',
                'keperluan' => 'Studi Banding Pengelolaan Perpustakaan Sekolah',
                'tanggal_berangkat' => '15 Januari 2026',
                'tanggal_kembali' => '16 Januari 2026',
            ]),
        ]);

        LetterRequest::create([
            'user_id' => 3,
            'template_id' => $template->id,
            'status' => 'pending',
            'payload_data' => array_merge($payloadBase, [
                'nomor_surat' => '421/003/SPD/2026',
                'tujuan' => 'Balai Diklat Surakarta',
                'keperluan' => 'Pelatihan Media Pembelajaran Digital',
                'tanggal_berangkat' => '5 Februari 2026',
                'tanggal_kembali' => '7 Februari 2026',
            ]),
        ]);

        LetterRequest::create([
            'user_id' => 3,
            'template_id' => $template->id,
            'status' => 'rejected',
            'payload_data' => array_merge($payloadBase, [
                'nomor_surat' => '421/004/SPD/2026',
                'tujuan' => 'Museum Radyapustaka',
                'keperluan' => 'Kunjungan Edukasi Siswa',
                'tanggal_berangkat' => '20 Januari 2026',
                'tanggal_kembali' => '20 Januari 2026',
            ]),
        ]);

        LetterRequest::create([
            'user_id' => 3,
            'template_id' => $template->id,
            'status' => 'pending',
            'payload_data' => array_merge($payloadBase, [
                'nomor_surat' => '421/005/SPD/2026',
                'tujuan' => 'Kantor Kementerian Agama Surakarta',
                'keperluan' => 'Koordinasi Kegiatan Keagamaan Sekolah',
                'tanggal_berangkat' => '12 Maret 2026',
                'tanggal_kembali' => '12 Maret 2026',
            ]),
        ]);
    }
}