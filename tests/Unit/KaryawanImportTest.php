<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Imports\KaryawanImport;
use App\Models\Karyawan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KaryawanImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_maps_row_to_karyawan(): void
    {
        $import = new KaryawanImport();

        $karyawan = $import->model([
            'nama' => 'Budi Santoso',
            'nip' => '198501012010011001',
            'jabatan' => 'Guru Matematika',
        ]);

        $this->assertInstanceOf(Karyawan::class, $karyawan);
        $this->assertSame('Budi Santoso', $karyawan->nama);
        $this->assertSame('198501012010011001', $karyawan->nip);
        $this->assertSame('Guru Matematika', $karyawan->jabatan);
    }

    public function test_rules_require_and_validate_each_column(): void
    {
        $import = new KaryawanImport();

        $rules = $import->rules();

        $this->assertContains('required', $rules['nama']);
        $this->assertContains('string', $rules['nama']);
        $this->assertContains('max:255', $rules['nama']);

        $this->assertContains('required', $rules['nip']);
        $this->assertContains('unique:karyawan,nip', $rules['nip']);
        $this->assertContains('max:30', $rules['nip']);

        $this->assertContains('required', $rules['jabatan']);
        $this->assertContains('string', $rules['jabatan']);
        $this->assertContains('max:255', $rules['jabatan']);
    }

    public function test_custom_validation_messages_are_in_indonesian(): void
    {
        $import = new KaryawanImport();

        $messages = $import->customValidationMessages();

        $this->assertSame('Kolom Nama harus diisi.', $messages['nama.required']);
        $this->assertSame('Kolom NIP harus diisi.', $messages['nip.required']);
        $this->assertSame('NIP :input sudah terdaftar.', $messages['nip.unique']);
        $this->assertSame('Kolom Jabatan harus diisi.', $messages['jabatan.required']);
    }
}