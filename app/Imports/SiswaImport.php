<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Siswa;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

/**
 * Import: Data Siswa dari Excel (.xlsx)
 *
 * Membaca file .xlsx dengan heading row dan memetakan
 * kolom Nama, NISN, Kelas, Jurusan ke model Siswa.
 */
class SiswaImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row): Siswa
    {
        return new Siswa([
            'nama' => trim((string) $row['nama']),
            'nisn' => trim((string) $row['nisn']),
            'kelas' => trim((string) $row['kelas']),
            'jurusan' => trim((string) $row['jurusan']),
        ]);
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'nisn' => ['required', 'string', 'max:30', 'unique:siswa,nisn'],
            'kelas' => ['required', 'string', 'max:100'],
            'jurusan' => ['required', 'string', 'max:150'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nama.required' => 'Kolom Nama harus diisi.',
            'nisn.required' => 'Kolom NISN harus diisi.',
            'nisn.unique' => 'NISN :input sudah terdaftar.',
            'kelas.required' => 'Kolom Kelas harus diisi.',
            'jurusan.required' => 'Kolom Jurusan harus diisi.',
        ];
    }
}
