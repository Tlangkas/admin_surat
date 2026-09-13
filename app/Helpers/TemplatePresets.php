<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Helper: TemplatePresets
 *
 * Menyediakan preset template surat resmi siap pakai yang diadopsi dari standar operasional sekolah (SURAT 2025).
 */
class TemplatePresets
{
    /**
     * Mengambil seluruh daftar preset template surat resmi.
     *
     * @return array<string, array{
     *     name: string,
     *     letter_code: string,
     *     title_text: string,
     *     opening_text: string,
     *     identity_fields: array<int, string>,
     *     middle_text: string,
     *     detail_fields: array<int, string>,
     *     closing_text: string,
     *     content: string
     * }>
     */
    public static function getPresets(): array
    {
        $presets = [
            'surat_tugas' => [
                'name' => 'Surat Tugas Perjalanan Dinas',
                'letter_code' => 'ST-GUKAR',
                'classification_code' => 'E',
                'title_text' => 'SURAT TUGAS',
                'opening_text' => 'Yang bertanda tangan di bawah ini Kepala Sekolah menerangkan bahwa:',
                'identity_fields' => ['nama', 'nip', 'jabatan'],
                'middle_text' => 'Diberikan tugas untuk melaksanakan perjalanan dinas dengan rincian sebagai berikut:',
                'detail_fields' => ['tujuan', 'keperluan', 'tanggal_berangkat', 'tanggal_kembali'],
                'closing_text' => 'Demikian Surat Tugas ini dibuat untuk dipergunakan sebagaimana mestinya dan dilaksanakan dengan penuh tanggung jawab.',
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
        <td style="padding: 4px 0;">Keperluan</td>
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
                'name' => 'Surat Keterangan Aktif Mengajar',
                'letter_code' => 'SK-AKTIF',
                'classification_code' => 'G',
                'title_text' => 'SURAT KETERANGAN',
                'opening_text' => 'Yang bertanda tangan di bawah ini Kepala Sekolah menerangkan dengan sebenarnya bahwa:',
                'identity_fields' => ['nama', 'nip', 'jabatan', 'sekolah'],
                'middle_text' => 'Adalah benar merupakan pegawai aktif yang saat ini masih bertugas pada unit kerja kami.',
                'detail_fields' => ['keperluan'],
                'closing_text' => 'Demikian Surat Keterangan ini dibuat dengan sebenarnya agar dapat dipergunakan sebagaimana mestinya.',
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

<p>Adalah benar merupakan pegawai aktif yang saat ini masih bertugas pada unit kerja kami.</p>

<p>Surat keterangan ini diberikan kepada yang bersangkutan untuk keperluan: <strong>{{ keperluan }}</strong>.</p>

<p>Demikian Surat Keterangan ini dibuat dengan sebenarnya agar dapat dipergunakan sebagaimana mestinya.</p>
HTML,
            ],

            'surat_izin' => [
                'name' => 'Surat Izin Permohonan Resmi',
                'letter_code' => 'SI',
                'classification_code' => 'E',
                'title_text' => 'SURAT IZIN',
                'opening_text' => 'Yang bertanda tangan di bawah ini Kepala Sekolah memberikan izin kepada:',
                'identity_fields' => ['nama', 'nip', 'jabatan'],
                'middle_text' => 'Untuk melaksanakan kegiatan dengan rincian sebagai berikut:',
                'detail_fields' => ['keperluan', 'tujuan', 'tanggal_berangkat', 'tanggal_kembali'],
                'closing_text' => 'Demikian surat izin ini diberikan untuk dapat dipergunakan sebagaimana mestinya.',
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

<p>Untuk melaksanakan kegiatan dengan rincian sebagai berikut:</p>

<table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
    <tr>
        <td style="width: 25%; padding: 4px 0;">Keperluan</td>
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
        <td style="padding: 4px 0;">{{ tanggal_berangkat }} s.d. {{ tanggal_kembali }}</td>
    </tr>
</table>

<p>Demikian surat izin ini diberikan untuk dapat dipergunakan sebagaimana mestinya.</p>
HTML,
            ],

            'surat_dispensasi' => [
                'name' => 'Surat Dispensasi Siswa',
                'letter_code' => 'DISPEN',
                'classification_code' => 'E',
                'title_text' => 'SURAT DISPENSASI SISWA',
                'opening_text' => 'Yang bertanda tangan di bawah ini Kepala Sekolah memberikan dispensasi kepada:',
                'identity_fields' => ['nama', 'nisn', 'kelas', 'jurusan'],
                'middle_text' => 'Untuk mengikuti agenda kegiatan dengan rincian sebagai berikut:',
                'detail_fields' => ['nama_kegiatan', 'tujuan', 'tanggal_berangkat', 'tanggal_kembali', 'daftar_peserta'],
                'closing_text' => 'Demikian Surat Dispensasi ini diberikan agar yang bersangkutan dapat melaksanakan tugas dengan sebaik-baiknya.',
                'content' => <<<'HTML'
<div style="text-align: center; margin-bottom: 20px;">
    <h3 style="margin: 0; text-transform: uppercase; font-size: 16px; font-weight: bold; text-decoration: underline;">SURAT DISPENSASI SISWA</h3>
    <p style="margin: 5px 0 0 0; font-size: 13px;">Nomor: {{ nomor_surat }}</p>
</div>

<p>Yang bertanda tangan di bawah ini Kepala Sekolah memberikan dispensasi kepada:</p>

<table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
    <tr>
        <td style="width: 28%; padding: 4px 0;">Nama Lengkap</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="font-weight: bold; padding: 4px 0;">{{ nama }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">NISN</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ nisn }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Kelas</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ kelas }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Jurusan</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ jurusan }}</td>
    </tr>
</table>

{{ daftar_peserta }}

<p>Untuk mengikuti agenda kegiatan dengan rincian sebagai berikut:</p>

<table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
    <tr>
        <td style="width: 28%; padding: 4px 0;">Nama Kegiatan</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ nama_kegiatan }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Tujuan</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ tujuan }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Tanggal Berangkat</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ tanggal_berangkat }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Tanggal Kembali</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ tanggal_kembali }}</td>
    </tr>
</table>

<p>Demikian Surat Dispensasi ini diberikan agar yang bersangkutan dapat melaksanakan tugas dengan sebaik-baiknya.</p>
HTML,
            ],

            'surat_panggilan_ortu' => [
                'name' => 'Surat Panggilan Orang Tua',
                'letter_code' => 'SP-ORTU',
                'classification_code' => 'G',
                'title_text' => 'SURAT PANGGILAN ORANG TUA',
                'opening_text' => 'Sehubungan dengan pembinaan dan kedisiplinan belajar di sekolah, dengan ini kami mengharap kehadiran orang tua dari peserta didik:',
                'identity_fields' => ['nama', 'nis', 'nisn', 'kelas', 'jurusan', 'nama_orang_tua'],
                'middle_text' => 'Untuk hadir menghadap Tim Bimbingan Konseling pada:',
                'detail_fields' => ['hari_tanggal', 'waktu', 'tempat', 'keperluan'],
                'closing_text' => 'Mengingat pentingnya agenda ini demi masa depan pendidikan putra/putri Bapak/Ibu, kami sangat mengharapkan kehadirannya tepat waktu. Atas kerja samanya kami ucapkan terima kasih.',
                'content' => <<<'HTML'
<div style="text-align: center; margin-bottom: 20px;">
    <h3 style="margin: 0; text-transform: uppercase; font-size: 16px; font-weight: bold; text-decoration: underline;">SURAT PANGGILAN ORANG TUA</h3>
    <p style="margin: 5px 0 0 0; font-size: 13px;">Nomor: {{ nomor_surat }}</p>
</div>

<p>Sehubungan dengan pembinaan dan kedisiplinan belajar di sekolah, dengan ini kami mengharap kehadiran orang tua dari peserta didik:</p>

<table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
    <tr>
        <td style="width: 28%; padding: 4px 0;">Nama Siswa</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="font-weight: bold; padding: 4px 0;">{{ nama }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">NIS</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ nis }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">NISN</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ nisn }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Kelas</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ kelas }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Jurusan</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ jurusan }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Nama Orang Tua</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ nama_orang_tua }}</td>
    </tr>
</table>

<p>Untuk hadir menghadap Tim Bimbingan Konseling pada:</p>

<table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
    <tr>
        <td style="width: 28%; padding: 4px 0;">Hari, Tanggal</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ hari_tanggal }}</td>
    </tr>
    <tr>
        <td style="width: 28%; padding: 4px 0;">Waktu</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ waktu }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Tempat</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ tempat }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Keperluan</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ keperluan }}</td>
    </tr>
</table>

<p>Mengingat pentingnya agenda ini demi masa depan pendidikan putra/putri Bapak/Ibu, kami sangat mengharapkan kehadirannya tepat waktu. Atas kerja samanya kami ucapkan terima kasih.</p>
HTML,
            ],

            'surat_peringatan_siswa' => [
                'name' => 'Surat Peringatan Siswa',
                'letter_code' => 'SP-SISWA',
                'classification_code' => 'G',
                'title_text' => 'SURAT PERINGATAN SISWA',
                'opening_text' => 'Berdasarkan rekapitulasi data pelanggaran tata tertib dan tata krama peserta didik SMK Komputama Majenang, dengan ini pihak sekolah memberikan surat peringatan kepada:',
                'identity_fields' => ['nama', 'nis', 'nisn', 'kelas', 'jurusan', 'nama_orang_tua'],
                'middle_text' => 'Atas pelanggaran tata tertib sekolah berupa:',
                'detail_fields' => ['bentuk_pelanggaran', 'poin_pelanggaran', 'tindakan_pembinaan'],
                'closing_text' => 'Demikian Surat Peringatan ini dibuat untuk dijadikan bahan evaluasi, perhatian serius, dan perbaikan sikap belajar demi kelancaran pendidikan yang bersangkutan.',
                'content' => <<<'HTML'
<div style="text-align: center; margin-bottom: 20px;">
    <h3 style="margin: 0; text-transform: uppercase; font-size: 16px; font-weight: bold; text-decoration: underline;">SURAT PERINGATAN SISWA</h3>
    <p style="margin: 5px 0 0 0; font-size: 13px;">Nomor: {{ nomor_surat }}</p>
</div>

<p>Berdasarkan rekapitulasi data pelanggaran tata tertib dan tata krama peserta didik SMK Komputama Majenang, dengan ini pihak sekolah memberikan surat peringatan kepada:</p>

<table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
    <tr>
        <td style="width: 28%; padding: 4px 0;">Nama Siswa</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="font-weight: bold; padding: 4px 0;">{{ nama }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">NIS</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ nis }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">NISN</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ nisn }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Kelas</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ kelas }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Jurusan</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ jurusan }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Nama Orang Tua</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ nama_orang_tua }}</td>
    </tr>
</table>

<p>Atas pelanggaran tata tertib sekolah berupa:</p>

<table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
    <tr>
        <td style="width: 28%; padding: 4px 0;">Bentuk Pelanggaran</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0; color: #b91c1c; font-weight: bold;">{{ bentuk_pelanggaran }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Poin Pelanggaran</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0; font-weight: bold;">{{ poin_pelanggaran }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Tindakan Pembinaan</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ tindakan_pembinaan }}</td>
    </tr>
</table>

<p>Demikian Surat Peringatan ini dibuat untuk dijadikan bahan evaluasi, perhatian serius, dan perbaikan sikap belajar demi kelancaran pendidikan yang bersangkutan.</p>
HTML,
            ],

            'surat_peringatan_pegawai' => [
                'name' => 'Surat Peringatan Pegawai',
                'letter_code' => 'SP-GUKAR',
                'classification_code' => 'G',
                'title_text' => 'SURAT PERINGATAN PEGAWAI',
                'opening_text' => 'Berdasarkan rekapitulasi kehadiran dan evaluasi kinerja pegawai pada unit kerja SMK Komputama Majenang, dengan ini kami memberikan peringatan kepada:',
                'identity_fields' => ['nama', 'nip', 'jabatan'],
                'middle_text' => 'Atas ketidaksesuaian kedisiplinan kerja dan tata tertib pegawai dengan rincian:',
                'detail_fields' => ['keterangan', 'keperluan'],
                'closing_text' => 'Demikian surat peringatan ini diterbitkan agar dapat dipedomani dan menjadi perhatian penuh guna perbaikan integritas dan kinerja ke depan.',
                'content' => <<<'HTML'
<div style="text-align: center; margin-bottom: 20px;">
    <h3 style="margin: 0; text-transform: uppercase; font-size: 16px; font-weight: bold; text-decoration: underline;">SURAT PERINGATAN PEGAWAI</h3>
    <p style="margin: 5px 0 0 0; font-size: 13px;">Nomor: {{ nomor_surat }}</p>
</div>

<p>Berdasarkan rekapitulasi kehadiran dan evaluasi kinerja pegawai pada unit kerja SMK Komputama Majenang, dengan ini kami memberikan peringatan kepada:</p>

<table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
    <tr>
        <td style="width: 28%; padding: 4px 0;">Nama Pegawai</td>
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

<p>Atas ketidaksesuaian kedisiplinan kerja dan tata tertib pegawai dengan rincian:</p>

<table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
    <tr>
        <td style="width: 28%; padding: 4px 0;">Uraian Pelanggaran</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0; color: #b91c1c; font-weight: bold;">{{ keterangan }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Kewajiban Perbaikan</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ keperluan }}</td>
    </tr>
</table>

<p>Demikian surat peringatan ini diterbitkan agar dapat dipedomani dan menjadi perhatian penuh guna perbaikan integritas dan kinerja ke depan.</p>
HTML,
            ],

            'surat_home_visit' => [
                'name' => 'Surat Tugas Home Visit',
                'letter_code' => 'SHV',
                'classification_code' => 'G',
                'title_text' => 'SURAT TUGAS HOME VISIT',
                'opening_text' => 'Yang bertanda tangan di bawah ini Kepala Sekolah menugaskan kepada Guru BK berikut:',
                'identity_fields' => ['nama', 'nip', 'jabatan'],
                'middle_text' => 'Untuk melaksanakan kunjungan rumah (Home Visit) kepada peserta didik:',
                'detail_fields' => ['nama_siswa', 'kelas', 'alamat_tujuan', 'keperluan'],
                'closing_text' => 'Demikian surat tugas ini diberikan untuk dilaksanakan dengan penuh rasa tanggung jawab dan membuat laporan hasil kunjungan.',
                'content' => <<<'HTML'
<div style="text-align: center; margin-bottom: 20px;">
    <h3 style="margin: 0; text-transform: uppercase; font-size: 16px; font-weight: bold; text-decoration: underline;">SURAT TUGAS HOME VISIT</h3>
    <p style="margin: 5px 0 0 0; font-size: 13px;">Nomor: {{ nomor_surat }}</p>
</div>

<p>Yang bertanda tangan di bawah ini Kepala Sekolah menugaskan kepada Guru BK berikut:</p>

<table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
    <tr>
        <td style="width: 28%; padding: 4px 0;">Nama Petugas Home Visit</td>
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

<p>Untuk melaksanakan kunjungan rumah (Home Visit) kepada peserta didik:</p>

<table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
    <tr>
        <td style="width: 28%; padding: 4px 0;">Nama Siswa</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="font-weight: bold; padding: 4px 0;">{{ nama_siswa }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Kelas</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ kelas }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Alamat</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ alamat_tujuan }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Keperluan</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ keperluan }}</td>
    </tr>
</table>

<p>Demikian surat tugas ini diberikan untuk dilaksanakan dengan penuh rasa tanggung jawab dan membuat laporan hasil kunjungan.</p>
HTML,
            ],

            'surat_sppd' => [
                'name' => 'Surat Perintah Perjalanan Dinas (SPPD)',
                'letter_code' => 'SPPD',
                'classification_code' => 'E',
                'title_text' => 'SURAT PERINTAH PERJALANAN DINAS',
                'opening_text' => 'Pejabat yang berwenang memberikan perintah perjalanan dinas kepada pegawai berikut:',
                'identity_fields' => ['nama', 'nip', 'pangkat_golongan', 'jabatan'],
                'middle_text' => 'Rincian instruksi pelaksanaan perjalanan dinas:',
                'detail_fields' => ['pejabat_pemberi_perintah', 'tujuan', 'transportasi', 'lama_perjalanan', 'tanggal_berangkat', 'tanggal_kembali', 'beban_anggaran', 'keperluan'],
                'closing_text' => 'Demikian Surat Perintah Perjalanan Dinas ini diterbitkan untuk dilaksanakan dengan sebaik-baiknya.',
                'content' => <<<'HTML'
<div style="text-align: center; margin-bottom: 20px;">
    <h3 style="margin: 0; text-transform: uppercase; font-size: 16px; font-weight: bold; text-decoration: underline;">SURAT PERINTAH PERJALANAN DINAS</h3>
    <p style="margin: 5px 0 0 0; font-size: 13px;">Nomor: {{ nomor_surat }}</p>
</div>

<p>Pejabat yang berwenang memberikan perintah perjalanan dinas kepada pegawai berikut:</p>

<table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
    <tr>
        <td style="width: 28%; padding: 4px 0;">Nama Pegawai</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="font-weight: bold; padding: 4px 0;">{{ nama }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">NIP</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ nip }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Pangkat dan Golongan</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ pangkat_golongan }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Jabatan</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ jabatan }}</td>
    </tr>
</table>

<p>Rincian instruksi pelaksanaan perjalanan dinas:</p>

<table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
    <tr>
        <td style="width: 28%; padding: 4px 0;">Pejabat Pemberi Perintah</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ pejabat_pemberi_perintah }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Tujuan</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ tujuan }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Transportasi</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ transportasi }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Lama Perjalanan</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ lama_perjalanan }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Tanggal Berangkat</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ tanggal_berangkat }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Tanggal Kembali</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ tanggal_kembali }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Pembebanan Anggaran</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ beban_anggaran }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Keperluan</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ keperluan }}</td>
    </tr>
</table>

<p>Demikian Surat Perintah Perjalanan Dinas ini diterbitkan untuk dilaksanakan dengan sebaik-baiknya.</p>
HTML,
            ],

            'surat_rekomendasi' => [
                'name' => 'Surat Rekomendasi Resmi',
                'letter_code' => 'REKOM',
                'classification_code' => 'E',
                'title_text' => 'SURAT REKOMENDASI',
                'opening_text' => 'Yang bertanda tangan di bawah ini Kepala SMK Komputama Majenang memberikan rekomendasi resmi kepada:',
                'identity_fields' => ['nama', 'nip', 'jabatan', 'sekolah'],
                'middle_text' => 'Berdasarkan integritas, kompetensi, dan rekam jejak yang bersangkutan, diberikan rekomendasi untuk:',
                'detail_fields' => ['keperluan', 'tujuan', 'keterangan'],
                'closing_text' => 'Demikian surat rekomendasi ini dibuat dengan sesungguhnya untuk dapat dipergunakan sebagaimana mestinya.',
                'content' => <<<'HTML'
<div style="text-align: center; margin-bottom: 20px;">
    <h3 style="margin: 0; text-transform: uppercase; font-size: 16px; font-weight: bold; text-decoration: underline;">SURAT REKOMENDASI</h3>
    <p style="margin: 5px 0 0 0; font-size: 13px;">Nomor: {{ nomor_surat }}</p>
</div>

<p>Yang bertanda tangan di bawah ini Kepala SMK Komputama Majenang memberikan rekomendasi resmi kepada:</p>

<table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
    <tr>
        <td style="width: 28%; padding: 4px 0;">Nama Lengkap</td>
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

<p>Berdasarkan integritas, kompetensi, dan rekam jejak yang bersangkutan, diberikan rekomendasi untuk:</p>

<table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
    <tr>
        <td style="width: 28%; padding: 4px 0;">Keperluan</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ keperluan }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Tujuan</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ tujuan }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Keterangan</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ keterangan }}</td>
    </tr>
</table>

<p>Demikian surat rekomendasi ini dibuat dengan sesungguhnya untuk dapat dipergunakan sebagaimana mestinya.</p>
HTML,
            ],

            'surat_pindah' => [
                'name' => 'Surat Keterangan Pindah Siswa',
                'letter_code' => 'SK-PINDAH',
                'classification_code' => 'G',
                'title_text' => 'SURAT KETERANGAN PINDAH SEKOLAH',
                'opening_text' => 'Yang bertanda tangan di bawah ini Kepala Sekolah menerangkan bahwa peserta didik berikut:',
                'identity_fields' => ['nama', 'nis', 'nisn', 'tempat_tanggal_lahir', 'jenis_kelamin', 'kelas', 'jurusan', 'alamat'],
                'middle_text' => 'Telah mengajukan permohonan pindah sekolah atas kehendak orang tua murid dengan rincian:',
                'detail_fields' => ['sekolah_tujuan', 'alasan_pindah', 'keterangan'],
                'closing_text' => 'Demikian surat keterangan pindah ini kami berikan sebagai kelengkapan administrasi pada sekolah yang dituju.',
                'content' => <<<'HTML'
<div style="text-align: center; margin-bottom: 20px;">
    <h3 style="margin: 0; text-transform: uppercase; font-size: 16px; font-weight: bold; text-decoration: underline;">SURAT KETERANGAN PINDAH SEKOLAH</h3>
    <p style="margin: 5px 0 0 0; font-size: 13px;">Nomor: {{ nomor_surat }}</p>
</div>

<p>Yang bertanda tangan di bawah ini Kepala Sekolah menerangkan bahwa peserta didik berikut:</p>

<table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
    <tr>
        <td style="width: 28%; padding: 4px 0;">Nama Siswa</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="font-weight: bold; padding: 4px 0;">{{ nama }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">NIS</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ nis }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">NISN</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ nisn }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Tempat, Tanggal Lahir</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ tempat_tanggal_lahir }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Jenis Kelamin</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ jenis_kelamin }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Kelas</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ kelas }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Jurusan</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ jurusan }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Alamat</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ alamat }}</td>
    </tr>
</table>

<p>Telah mengajukan permohonan pindah sekolah atas kehendak orang tua murid dengan rincian:</p>

<table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
    <tr>
        <td style="width: 28%; padding: 4px 0;">Sekolah Tujuan</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ sekolah_tujuan }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Alasan Pindah</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ alasan_pindah }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Keterangan</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ keterangan }}</td>
    </tr>
</table>

<p>Demikian surat keterangan pindah ini kami berikan sebagai kelengkapan administrasi pada sekolah yang dituju.</p>
HTML,
            ],

            'surat_undangan' => [
                'name' => 'Surat Undangan Rapat',
                'letter_code' => 'UND',
                'classification_code' => 'E',
                'title_text' => 'SURAT UNDANGAN RESMI',
                'opening_text' => 'Salam sejahtera kami sampaikan. Sehubungan dengan agenda kegiatan sekolah, dengan ini kami bermaksud mengundang Bapak/Ibu untuk berkenan hadir pada:',
                'identity_fields' => ['nama', 'jabatan', 'sekolah'],
                'middle_text' => 'Agenda kegiatan yang akan dilaksanakan dengan rincian jadwal sebagai berikut:',
                'detail_fields' => ['nama_kegiatan', 'hari_tanggal', 'waktu', 'tempat', 'keperluan'],
                'closing_text' => 'Mengingat pentingnya agenda ini, kami sangat mengharapkan kehadiran Bapak/Ibu tepat pada waktunya. Atas perhatian dan kerjasamanya diucapkan terima kasih.',
                'content' => <<<'HTML'
<div style="text-align: center; margin-bottom: 20px;">
    <h3 style="margin: 0; text-transform: uppercase; font-size: 16px; font-weight: bold; text-decoration: underline;">SURAT UNDANGAN RESMI</h3>
    <p style="margin: 5px 0 0 0; font-size: 13px;">Nomor: {{ nomor_surat }}</p>
</div>

<p>Salam sejahtera kami sampaikan. Sehubungan dengan agenda kegiatan sekolah, dengan ini kami bermaksud mengundang Bapak/Ibu untuk berkenan hadir pada:</p>

<table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
    <tr>
        <td style="width: 28%; padding: 4px 0;">Nama Undangan</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="font-weight: bold; padding: 4px 0;">{{ nama }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Jabatan</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ jabatan }}</td>
    </tr>
</table>

<p>Agenda kegiatan yang akan dilaksanakan dengan rincian jadwal sebagai berikut:</p>

<table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
    <tr>
        <td style="width: 28%; padding: 4px 0;">Nama Kegiatan</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ nama_kegiatan }}</td>
    </tr>
    <tr>
        <td style="width: 28%; padding: 4px 0;">Hari, Tanggal</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ hari_tanggal }}</td>
    </tr>
    <tr>
        <td style="width: 28%; padding: 4px 0;">Waktu</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ waktu }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Tempat</td>
        <td style="padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ tempat }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 0;">Keperluan</td>
        <td style="width: 3%; padding: 4px 0;">:</td>
        <td style="padding: 4px 0;">{{ keperluan }}</td>
    </tr>
</table>

<p>Mengingat pentingnya agenda ini, kami sangat mengharapkan kehadiran Bapak/Ibu tepat pada waktunya. Atas perhatian dan kerjasamanya diucapkan terima kasih.</p>
HTML,
            ],
        ];

        return $presets;
    }
}
