<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Karyawan;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

/**
 * Import: Data Karyawan dari Excel
 *
 * Membaca file .xlsx dengan heading row dan memetakan
 * kolom Nama, NIP, Jabatan ke model Karyawan.
 *
 * Validasi:
 *   - Nama: required, string, max 255
 *   - NIP:  required, string, max 30, unique di tabel karyawan
 *   - Jabatan: nullable, string, max 255 (opsional)
 */
class KaryawanImport implements ToModel, WithHeadingRow, WithValidation
{
    /** Petakan baris Excel ke model Karyawan. */
    public function model(array $row): Karyawan
    {
        $jabatan = isset($row['jabatan']) && trim((string) $row['jabatan']) !== ''
            ? trim((string) $row['jabatan'])
            : null;

        return new Karyawan([
            'nama' => trim((string) $row['nama']),
            'nip' => trim((string) $row['nip']),
            'jabatan' => $jabatan,
        ]);
    }

    /** Aturan validasi per kolom. */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'nip' => ['required', 'string', 'max:30', 'unique:karyawan,nip'],
            'jabatan' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** Pesan error validasi dalam Bahasa Indonesia. */
    public function customValidationMessages(): array
    {
        return [
            'nama.required' => 'Kolom Nama harus diisi.',
            'nip.required' => 'Kolom NIP harus diisi.',
            'nip.unique' => 'NIP :input sudah terdaftar.',
        ];
    }
}
