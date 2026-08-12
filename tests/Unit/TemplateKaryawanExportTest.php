<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exports\TemplateKaryawanExport;
use Tests\TestCase;

class TemplateKaryawanExportTest extends TestCase
{
    public function test_headings_match_expected_columns(): void
    {
        $export = new TemplateKaryawanExport();

        $this->assertSame(['Nama', 'NIP', 'Jabatan'], $export->headings());
    }

    public function test_title_is_expected_sheet_name(): void
    {
        $export = new TemplateKaryawanExport();

        $this->assertSame('Data Guru & Karyawan', $export->title());
    }
}