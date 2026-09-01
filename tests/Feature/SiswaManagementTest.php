<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exports\TemplateSiswaExport;
use App\Filament\Resources\SiswaResource;
use App\Imports\SiswaImport;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class SiswaManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_kepsek_can_access_siswa_resource_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kepsek = User::factory()->create(['role' => 'kepsek']);

        $this->actingAs($admin);
        $responseAdmin = $this->get(SiswaResource::getUrl('index'));
        $responseAdmin->assertStatus(200);

        $this->actingAs($kepsek);
        $responseKepsek = $this->get(SiswaResource::getUrl('index'));
        $responseKepsek->assertStatus(200);
    }

    public function test_gukar_cannot_access_siswa_resource_page(): void
    {
        $gukar = User::factory()->create(['role' => 'gukar']);

        $this->actingAs($gukar);
        $response = $this->get(SiswaResource::getUrl('index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_create_siswa_manually(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        Livewire::test(SiswaResource\Pages\CreateSiswa::class)
            ->fillForm([
                'nama' => 'Budi Utomo',
                'nisn' => '0061234567',
                'kelas' => 'X RPL 1',
                'jurusan' => 'Rekayasa Perangkat Lunak',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('siswa', [
            'nama' => 'Budi Utomo',
            'nisn' => '0061234567',
            'kelas' => 'X RPL 1',
            'jurusan' => 'Rekayasa Perangkat Lunak',
        ]);
    }

    public function test_admin_can_edit_existing_siswa(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $siswa = Siswa::create([
            'nama' => 'Siti Nurhaliza',
            'nisn' => '0059988776',
            'kelas' => 'XI TKJ 1',
            'jurusan' => 'Teknik Komputer & Jaringan',
        ]);

        $this->actingAs($admin);

        Livewire::test(SiswaResource\Pages\EditSiswa::class, [
            'record' => $siswa->getRouteKey(),
        ])
            ->fillForm([
                'nama' => 'Siti Nurhaliza M.Kom',
                'kelas' => 'XII TKJ 1',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('siswa', [
            'id' => $siswa->id,
            'nama' => 'Siti Nurhaliza M.Kom',
            'kelas' => 'XII TKJ 1',
        ]);
    }

    public function test_siswa_export_template_contains_correct_headers(): void
    {
        $export = new TemplateSiswaExport();

        $this->assertEquals(['Nama', 'NISN', 'Kelas', 'Jurusan'], $export->headings());
        $this->assertEquals('Data Siswa', $export->title());
    }

    public function test_siswa_import_creates_model_from_excel_rows(): void
    {
        $import = new SiswaImport();

        $siswa = $import->model([
            'nama' => 'Fajar Sidik',
            'nisn' => '0071122334',
            'kelas' => 'X AKL 2',
            'jurusan' => 'Akuntansi',
        ]);

        $this->assertInstanceOf(Siswa::class, $siswa);
        $this->assertEquals('Fajar Sidik', $siswa->nama);
        $this->assertEquals('0071122334', $siswa->nisn);
        $this->assertEquals('X AKL 2', $siswa->kelas);
        $this->assertEquals('Akuntansi', $siswa->jurusan);
    }
}
