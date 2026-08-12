<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Export: Template Import Karyawan
 *
 * Menghasilkan file .xlsx dengan header kolom yang sesuai
 * untuk digunakan sebagai template import data karyawan.
 *
 * Kolom: Nama, NIP, Jabatan
 */
class TemplateKaryawanExport implements WithHeadings, WithTitle
{
    /** Header kolom di file Excel. */
    public function headings(): array
    {
        return [
            'Nama',
            'NIP',
            'Jabatan',
        ];
    }

    /** Nama sheet Excel. */
    public function title(): string
    {
        return 'Data Guru & Karyawan';
    }
}
