<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Support\Str;

/**
 * Helper: TemplateCompiler
 *
 * Mengompilasi data isian No-Code Visual Form Builder menjadi HTML DomPDF yang rapi
 * dan menyusun daftar variabel {{ placeholder }} secara otomatis.
 */
class TemplateCompiler
{
    /**
     * Kompilasi masukan visual builder menjadi HTML dan variabel.
     *
     * @param  array  $builderData  Data isian visual form builder
     * @return array{content: string, variables: array<int, string>}
     */
    public static function compile(array $builderData): array
    {
        $title = trim((string) ($builderData['title_text'] ?? 'SURAT RESMI'));
        $opening = trim((string) ($builderData['opening_text'] ?? 'Yang bertanda tangan di bawah ini Kepala Sekolah menerangkan bahwa:'));
        $middle = trim((string) ($builderData['middle_text'] ?? ''));
        $closing = trim((string) ($builderData['closing_text'] ?? 'Demikian surat ini dibuat untuk dipergunakan sebagaimana mestinya.'));

        $identityFields = (array) ($builderData['identity_fields'] ?? ['nama', 'nip', 'jabatan']);
        $detailFields = (array) ($builderData['detail_fields'] ?? ['keperluan', 'tujuan']);
        $includeParticipants = ! empty($builderData['include_participants']) || in_array('daftar_peserta', $identityFields, true) || in_array('daftar_peserta', $detailFields, true);

        // Filter out daftar_peserta from normal single-value key-value tables
        $identityFields = array_values(array_filter($identityFields, fn ($f) => $f !== 'daftar_peserta'));
        $detailFields = array_values(array_filter($detailFields, fn ($f) => $f !== 'daftar_peserta'));

        $allVars = [];

        $html = [];
        $html[] = '<div style="text-align: center; margin-bottom: 20px;">';
        $html[] = '    <h3 style="margin: 0; text-transform: uppercase; font-size: 16px; font-weight: bold; text-decoration: underline;">' . e($title) . '</h3>';
        $html[] = '    <p style="margin: 5px 0 0 0; font-size: 13px;">Nomor: {{ nomor_surat }}</p>';
        $html[] = '</div>';

        $allVars[] = 'nomor_surat';

        if (! empty($opening)) {
            $html[] = '<p>' . nl2br(e($opening)) . '</p>';
        }

        if (! empty($identityFields)) {
            $html[] = '<table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">';
            foreach ($identityFields as $field) {
                $cleanKey = trim((string) $field);
                if (empty($cleanKey)) {
                    continue;
                }

                $label = match (strtolower($cleanKey)) {
                    'nama' => 'Nama',
                    'nip' => 'NIP',
                    'nisn' => 'NISN',
                    'nis' => 'NIS',
                    'tempat_tanggal_lahir', 'ttl' => 'Tempat, Tanggal Lahir',
                    'jenis_kelamin', 'jk' => 'Jenis Kelamin',
                    'kelas' => 'Kelas',
                    'jurusan' => 'Jurusan',
                    'jabatan' => 'Jabatan',
                    'mata_pelajaran', 'mapel' => 'Mata Pelajaran',
                    'alamat', 'alamat_domisili', 'alamat_tinggal' => 'Alamat',
                    'sekolah', 'nama_sekolah' => 'Unit Kerja',
                    'nama_orang_tua', 'orang_tua', 'wali' => 'Nama Orang Tua',
                    'pekerjaan_orang_tua' => 'Pekerjaan Orang Tua',
                    'no_hp', 'telepon', 'telp', 'hp' => 'Nomor Telepon',
                    default => Str::headline($cleanKey),
                };

                $html[] = '    <tr>';
                $html[] = '        <td style="width: 28%; padding: 4px 0; vertical-align: top;">' . e($label) . '</td>';
                $html[] = '        <td style="width: 3%; padding: 4px 0; vertical-align: top;">:</td>';
                $html[] = '        <td style="padding: 4px 0; vertical-align: top;' . ($cleanKey === 'nama' ? ' font-weight: bold;' : '') . '">{{ ' . $cleanKey . ' }}</td>';
                $html[] = '    </tr>';

                $allVars[] = $cleanKey;
            }
            $html[] = '</table>';
        }

        if ($includeParticipants) {
            $html[] = '{{ daftar_peserta }}';
            $allVars[] = 'daftar_peserta';
        }

        if (! empty($middle)) {
            $html[] = '<p>' . nl2br(e($middle)) . '</p>';
        }

        if (! empty($detailFields)) {
            $html[] = '<table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">';
            foreach ($detailFields as $field) {
                $cleanKey = trim((string) $field);
                if (empty($cleanKey)) {
                    continue;
                }

                $label = match (strtolower($cleanKey)) {
                    'tujuan' => 'Tujuan',
                    'alamat_tujuan', 'alamat_lokasi' => 'Alamat Tujuan',
                    'keperluan', 'maksud' => 'Keperluan',
                    'nama_kegiatan', 'kegiatan' => 'Nama Kegiatan',
                    'tempat_kegiatan', 'lokasi', 'tempat' => 'Tempat Pelaksanaan',
                    'hari_tanggal' => 'Hari, Tanggal',
                    'waktu', 'tanggal_pelaksanaan' => 'Waktu Pelaksanaan',
                    'tanggal_berangkat', 'tgl_berangkat' => 'Tanggal Berangkat',
                    'tanggal_kembali', 'tgl_kembali' => 'Tanggal Kembali',
                    'nama_siswa' => 'Nama Siswa',
                    'transportasi' => 'Transportasi',
                    'pejabat_pemberi_perintah' => 'Pejabat Pemberi Perintah',
                    'pangkat_golongan' => 'Pangkat dan Golongan',
                    'tingkat_biaya' => 'Tingkat Biaya',
                    'beban_anggaran' => 'Pembebanan Anggaran',
                    'lama_perjalanan' => 'Lama Perjalanan',
                    'bentuk_pelanggaran', 'pelanggaran' => 'Bentuk Pelanggaran',
                    'poin_pelanggaran' => 'Poin Pelanggaran',
                    'tindakan_pembinaan' => 'Tindakan Pembinaan',
                    'dudi_mitra', 'perusahaan' => 'Mitra Industri',
                    'sekolah_tujuan' => 'Sekolah Tujuan',
                    'alasan_pindah' => 'Alasan Pindah',
                    'tahun_lulus' => 'Tahun Lulus',
                    'no_ijazah' => 'Nomor Ijazah',
                    'keterangan' => 'Keterangan',
                    default => Str::headline($cleanKey),
                };

                $html[] = '    <tr>';
                $html[] = '        <td style="width: 28%; padding: 4px 0; vertical-align: top;">' . e($label) . '</td>';
                $html[] = '        <td style="width: 3%; padding: 4px 0; vertical-align: top;">:</td>';
                $html[] = '        <td style="padding: 4px 0; vertical-align: top;">{{ ' . $cleanKey . ' }}</td>';
                $html[] = '    </tr>';

                $allVars[] = $cleanKey;
            }
            $html[] = '</table>';
        }

        if (! empty($closing)) {
            $html[] = '<p>' . nl2br(e($closing)) . '</p>';
        }

        return [
            'content' => implode("\n", $html),
            'variables' => array_values(array_unique($allVars)),
        ];
    }
}
