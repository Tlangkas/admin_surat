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
 *   1. Template "Surat Tugas Perjalanan Dinas" (Guru / Karyawan).
 *   2. Template "Surat Dispensasi Siswa" (Multi-Peserta).
 *   3. Sample LetterRequest untuk testing alur kerja.
 */
class LetterSeeder extends Seeder
{
    public function run(): void
    {
        $templateContentSPD = <<<'HTML'
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

        $templateSPD = LetterTemplate::updateOrCreate(
            ['name' => 'Surat Tugas Perjalanan Dinas'],
            [
                'letter_code' => 'SPD',
                'title_text' => 'SURAT TUGAS PERJALANAN DINAS',
                'opening_text' => 'Yang bertanda tangan di bawah ini, Kepala Sekolah menerangkan bahwa:',
                'middle_text' => 'Diberikan tugas untuk melaksanakan perjalanan dinas ke:',
                'closing_text' => 'Demikian surat tugas ini dibuat untuk dipergunakan sebagaimana mestinya dan dilaksanakan dengan penuh tanggung jawab.',
                'identity_fields' => ['nama', 'nip', 'jabatan'],
                'detail_fields' => ['tujuan', 'keperluan', 'tanggal_berangkat', 'tanggal_kembali'],
                'use_advanced_html' => false,
                'content' => $templateContentSPD,
                'variables' => [
                    'nomor_surat', 'nama', 'nip', 'jabatan',
                    'tujuan', 'keperluan', 'tanggal_berangkat', 'tanggal_kembali',
                ],
                'is_active' => true,
            ]
        );

        $templateContentDispen = <<<'HTML'
<div style="text-align: center; margin-bottom: 25px;">
    <div style="font-size: 13pt; font-weight: bold; text-decoration: underline; letter-spacing: 0.5px;">SURAT DISPENSASI SISWA</div>
    <div style="font-size: 11pt; margin-top: 4px;">Nomor: {{ nomor_surat }}</div>
</div>

<div class="isi">
    <p>Yang bertanda tangan di bawah ini, Kepala Sekolah memberikan dispensasi / izin meninggalkan Kegiatan Belajar Mengajar (KBM) kepada siswa-siswi terlampir di bawah ini:</p>

    {{ daftar_peserta }}

    <p style="margin-top: 15px;">Untuk mengikuti agenda kegiatan / perlombaan dengan rincian sebagai berikut:</p>
    <table class="table-data">
        <tr><td style="width: 160px;">Nama Kegiatan</td><td style="width: 15px;">:</td><td><strong>{{ nama_kegiatan }}</strong></td></tr>
        <tr><td>Tempat / Lokasi</td><td>:</td><td>{{ tujuan }}</td></tr>
        <tr><td>Tanggal Berangkat</td><td>:</td><td>{{ tanggal_berangkat }}</td></tr>
        <tr><td>Tanggal Kembali</td><td>:</td><td>{{ tanggal_kembali }}</td></tr>
    </table>

    <p style="margin-top: 15px; text-align: justify;">
        Demikian surat dispensasi ini diberikan agar siswa yang bersangkutan dapat melaksanakan tugas dengan sebaik-baiknya.
    </p>
</div>
HTML;

        $templateDispen = LetterTemplate::updateOrCreate(
            ['name' => 'Surat Dispensasi Siswa'],
            [
                'letter_code' => 'DISPEN',
                'title_text' => 'SURAT DISPENSASI SISWA',
                'opening_text' => 'Yang bertanda tangan di bawah ini, Kepala Sekolah memberikan dispensasi / izin meninggalkan Kegiatan Belajar Mengajar (KBM) kepada siswa-siswi terlampir di bawah ini:',
                'middle_text' => 'Untuk mengikuti agenda kegiatan / perlombaan dengan rincian sebagai berikut:',
                'closing_text' => 'Demikian surat dispensasi ini diberikan agar siswa yang bersangkutan dapat melaksanakan tugas dengan sebaik-baiknya.',
                'identity_fields' => ['nama', 'nip', 'jabatan'],
                'detail_fields' => ['daftar_peserta', 'nama_kegiatan', 'tujuan', 'tanggal_berangkat', 'tanggal_kembali'],
                'use_advanced_html' => false,
                'content' => $templateContentDispen,
                'variables' => [
                    'nomor_surat', 'daftar_peserta', 'nama_kegiatan', 'tujuan',
                    'tanggal_berangkat', 'tanggal_kembali',
                ],
                'is_active' => true,
            ]
        );

        $payloadBase = [
            'nama' => 'Guru Karyawan',
            'nip' => '198501012010011001',
            'jabatan' => 'Guru Kelas',
        ];

        $gukarUser = \App\Models\User::where('email', 'gukar@sekolah.sch.id')->first() ?? \App\Models\User::first();

        LetterRequest::firstOrCreate(
            ['payload_data->nomor_surat' => '421/001/SPD/2026'],
            [
                'user_id' => $gukarUser?->id,
                'template_id' => $templateSPD->id,
                'status' => 'signed',
                'payload_data' => array_merge($payloadBase, [
                    'nomor_surat' => '421/001/SPD/2026',
                    'tujuan' => 'Dinas Pendidikan Kota Surakarta',
                    'keperluan' => 'Mengikuti Sosialisasi Kurikulum Merdeka',
                    'tanggal_berangkat' => '2 Januari 2026',
                    'tanggal_kembali' => '2 Januari 2026',
                ]),
            ]
        );

        LetterRequest::firstOrCreate(
            ['payload_data->nomor_surat' => '421/002/DISPEN/2026'],
            [
                'user_id' => $gukarUser?->id,
                'template_id' => $templateDispen->id,
                'status' => 'approved_admin',
                'payload_data' => [
                    'nomor_surat' => '421/002/DISPEN/2026',
                    'nama_kegiatan' => 'Olimpiade Sains Nasional (OSN) Tingkat Kota',
                    'tujuan' => 'SMA Negeri 1 Surakarta',
                    'tanggal_berangkat' => '15 Maret 2026',
                    'tanggal_kembali' => '16 Maret 2026',
                    'daftar_peserta' => [
                        [
                            'nama' => 'Ahmad Rizky Pratama',
                            'identitas' => '0051234501',
                            'kelas_jabatan' => 'X RPL 1',
                            'peran' => 'Peserta Bidang Informatika',
                        ],
                        [
                            'nama' => 'Anisa Rahmawati',
                            'identitas' => '0051234502',
                            'kelas_jabatan' => 'X RPL 1',
                            'peran' => 'Peserta Bidang Matematika',
                        ],
                        [
                            'nama' => 'Bagus Kurniawan',
                            'identitas' => '0049876503',
                            'kelas_jabatan' => 'XI TKJ 2',
                            'peran' => 'Peserta Bidang Fisika',
                        ],
                    ],
                ],
            ]
        );

        // Seed audit logs untuk surat-surat awal agar riwayat audit tampil lengkap
        $adminUser = \App\Models\User::where('role', 'admin')->first();
        $kepsekUser = \App\Models\User::where('role', 'kepsek')->first();

        // 1. Audit trail untuk SPD (status signed: Draft -> Approved Admin -> Signed)
        if ($lrSPD = LetterRequest::where('payload_data->nomor_surat', '421/001/SPD/2026')->first()) {
            \App\Models\LetterStatusLog::firstOrCreate([
                'letter_request_id' => $lrSPD->id,
                'from_status' => null,
                'to_status' => 'pending',
            ], [
                'user_id' => $gukarUser?->id,
                'note' => 'Pengajuan surat baru diajukan oleh pemohon',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0',
                'created_at' => now()->subHours(4),
            ]);

            \App\Models\LetterStatusLog::firstOrCreate([
                'letter_request_id' => $lrSPD->id,
                'from_status' => 'pending',
                'to_status' => 'approved_admin',
            ], [
                'user_id' => $adminUser?->id,
                'note' => 'Disetujui oleh admin (Nomor Surat Terbit)',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0',
                'created_at' => now()->subHours(3),
            ]);

            \App\Models\LetterStatusLog::firstOrCreate([
                'letter_request_id' => $lrSPD->id,
                'from_status' => 'approved_admin',
                'to_status' => 'signed',
            ], [
                'user_id' => $kepsekUser?->id,
                'note' => 'Ditandatangani oleh Kepala Sekolah',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0',
                'created_at' => now()->subHours(2),
            ]);
        }

        // 2. Audit trail untuk DISPEN (status approved_admin: Draft -> Approved Admin)
        if ($lrDispen = LetterRequest::where('payload_data->nomor_surat', '421/002/DISPEN/2026')->first()) {
            \App\Models\LetterStatusLog::firstOrCreate([
                'letter_request_id' => $lrDispen->id,
                'from_status' => null,
                'to_status' => 'pending',
            ], [
                'user_id' => $gukarUser?->id,
                'note' => 'Pengajuan surat baru diajukan oleh pemohon',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0',
                'created_at' => now()->subHours(3),
            ]);

            \App\Models\LetterStatusLog::firstOrCreate([
                'letter_request_id' => $lrDispen->id,
                'from_status' => 'pending',
                'to_status' => 'approved_admin',
            ], [
                'user_id' => $adminUser?->id,
                'note' => 'Disetujui oleh admin (Nomor Surat Terbit)',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0',
                'created_at' => now()->subHours(1),
            ]);
        }
    }
}