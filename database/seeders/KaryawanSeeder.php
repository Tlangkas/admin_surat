<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder: KaryawanSeeder
 *
 * Mengisi data master Guru & Karyawan beserta akun login yang terhubung.
 * Menyediakan kombinasi karyawan yang sudah berakun dan yang siap didaftarkan mandiri via NIP.
 */
class KaryawanSeeder extends Seeder
{
    public function run(): void
    {
        $karyawanData = [
            [
                'nama' => 'Budi Santoso, S.Pd',
                'nip' => '198501012010011001',
                'jabatan' => 'Guru Matematika',
                'email' => 'gukar@sekolah.sch.id',
                'has_user' => true,
            ],
            [
                'nama' => 'Siti Aminah, S.Si',
                'nip' => '198803152014022003',
                'jabatan' => 'Guru Fisika',
                'email' => 'siti.aminah@sekolah.sch.id',
                'has_user' => true,
            ],
            [
                'nama' => 'Ahmad Dahlan, M.Pd',
                'nip' => '197505102000031002',
                'jabatan' => 'Guru Bahasa Indonesia',
                'email' => 'ahmad.dahlan@sekolah.sch.id',
                'has_user' => true,
            ],
            [
                'nama' => 'Dewi Lestari, S.Pd',
                'nip' => '199007202018012004',
                'jabatan' => 'Guru Bahasa Inggris',
                'email' => null,
                'has_user' => false,
            ],
            [
                'nama' => 'Hendra Gunawan, S.Kom',
                'nip' => '199211122019031005',
                'jabatan' => 'Guru Informatika & TIK',
                'email' => null,
                'has_user' => false,
            ],
            [
                'nama' => 'Rina Kartika, S.E',
                'nip' => '198709082012042002',
                'jabatan' => 'Bendahara Sekolah',
                'email' => null,
                'has_user' => false,
            ],
            [
                'nama' => 'Bambang Utomo, S.Pd',
                'nip' => '198304052008011003',
                'jabatan' => 'Guru Olahraga & Kesehatan',
                'email' => null,
                'has_user' => false,
            ],
            [
                'nama' => 'Ratna Juwita, A.Md',
                'nip' => '199406182020022001',
                'jabatan' => 'Staf Tata Usaha / Administrasi',
                'email' => null,
                'has_user' => false,
            ],
            [
                'nama' => 'Eko Prasetyo, S.T',
                'nip' => '199102282017031004',
                'jabatan' => 'Kepala Laboratorium Komputer',
                'email' => null,
                'has_user' => false,
            ],
            [
                'nama' => 'Nurhayati, S.Ag',
                'nip' => '198612012011022005',
                'jabatan' => 'Guru Agama Islam',
                'email' => null,
                'has_user' => false,
            ],
        ];

        foreach ($karyawanData as $data) {
            $userId = null;

            if ($data['has_user'] && ! empty($data['email'])) {
                $user = User::firstOrCreate(
                    ['email' => $data['email']],
                    [
                        'name' => $data['nama'],
                        'password' => Hash::make('password'),
                        'role' => 'gukar',
                    ]
                );
                $userId = $user->id;
            }

            Karyawan::updateOrCreate(
                ['nip' => $data['nip']],
                [
                    'user_id' => $userId,
                    'nama' => $data['nama'],
                    'jabatan' => $data['jabatan'],
                ]
            );
        }
    }
}
