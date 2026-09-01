<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Siswa;
use Illuminate\Database\Seeder;

/**
 * Seeder: SiswaSeeder
 *
 * Mengisi data master Siswa berbagai kelas dan jurusan (RPL, TKJ, AKL, IPA, IPS).
 */
class SiswaSeeder extends Seeder
{
    public function run(): void
    {
        $siswaData = [
            [
                'nama' => 'Ahmad Rizky Pratama',
                'nisn' => '0051234501',
                'kelas' => 'X RPL 1',
                'jurusan' => 'Rekayasa Perangkat Lunak',
            ],
            [
                'nama' => 'Anisa Rahmawati',
                'nisn' => '0051234502',
                'kelas' => 'X RPL 1',
                'jurusan' => 'Rekayasa Perangkat Lunak',
            ],
            [
                'nama' => 'Bagus Kurniawan',
                'nisn' => '0049876503',
                'kelas' => 'XI TKJ 2',
                'jurusan' => 'Teknik Komputer & Jaringan',
            ],
            [
                'nama' => 'Citra Dewi Permata',
                'nisn' => '0049876504',
                'kelas' => 'XI TKJ 2',
                'jurusan' => 'Teknik Komputer & Jaringan',
            ],
            [
                'nama' => 'Dwi Nur Hidayat',
                'nisn' => '0035544305',
                'kelas' => 'XII AKL 3',
                'jurusan' => 'Akuntansi & Keuangan Lembaga',
            ],
            [
                'nama' => 'Fadhil Muhammad',
                'nisn' => '0057788906',
                'kelas' => 'X IPA 1',
                'jurusan' => 'Matematika & Ilmu Pengetahuan Alam',
            ],
            [
                'nama' => 'Gita Gutawa Putri',
                'nisn' => '0041122307',
                'kelas' => 'XI IPS 2',
                'jurusan' => 'Ilmu Pengetahuan Sosial',
            ],
            [
                'nama' => 'Indra Maulana',
                'nisn' => '0039988708',
                'kelas' => 'XII RPL 2',
                'jurusan' => 'Rekayasa Perangkat Lunak',
            ],
        ];

        foreach ($siswaData as $data) {
            Siswa::updateOrCreate(
                ['nisn' => $data['nisn']],
                $data
            );
        }
    }
}
