<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Export: Template Import Siswa
 *
 * Menghasilkan file .xlsx dengan header kolom: Nama, NISN, Kelas, Jurusan
 */
class TemplateSiswaExport implements WithHeadings, WithTitle
{
    public function headings(): array
    {
        return [
            'Nama',
            'NISN',
            'Kelas',
            'Jurusan',
        ];
    }

    public function title(): string
    {
        return 'Data Siswa';
    }
}
