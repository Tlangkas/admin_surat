<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder utama — membuat akun sistem default dan memanggil KaryawanSeeder, SiswaSeeder, & LetterSeeder.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@sekolah.sch.id'],
            [
                'name' => 'Admin Sekolah',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        User::firstOrCreate(
            ['email' => 'kepsek@sekolah.sch.id'],
            [
                'name' => 'Kepala Sekolah',
                'password' => Hash::make('password'),
                'role' => 'kepsek',
            ]
        );

        // Panggil seeder master data Guru & Karyawan
        $this->call(KaryawanSeeder::class);

        // Panggil seeder master data Siswa
        $this->call(SiswaSeeder::class);

        // Panggil seeder template surat + contoh pengajuan surat
        $this->call(LetterSeeder::class);
    }
}
