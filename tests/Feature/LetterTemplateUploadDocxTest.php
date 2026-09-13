<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\LetterTemplateResource\Pages\CreateLetterTemplate;
use App\Filament\Resources\LetterTemplateResource\Pages\ListLetterTemplates;
use App\Models\LetterTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

class LetterTemplateUploadDocxTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        return $admin;
    }

    private function createSampleDocx(string $title = 'SURAT DISPENSASI KHUSUS'): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'test_upload_') . '.docx';
        $zip = new ZipArchive();
        $zip->open($tempPath, ZipArchive::CREATE);

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>{$title}</w:t></w:r></w:p>
        <w:p><w:r><w:t>Yang bertanda tangan di bawah ini Kepala Sekolah memberikan dispensasi kepada:</w:t></w:r></w:p>
        <w:p><w:r><w:t>Nama: {{ nama }}</w:t></w:r></w:p>
        <w:p><w:r><w:t>NISN: {{ nisn }}</w:t></w:r></w:p>
        <w:p><w:r><w:t>Kelas: {{ kelas }}</w:t></w:r></w:p>
        <w:p><w:r><w:t>Untuk mengikuti kegiatan {{ nama_kegiatan }} ke [tujuan].</w:t></w:r></w:p>
        <w:p><w:r><w:t>Demikian surat dispensasi ini dibuat untuk dipergunakan sebagaimana mestinya.</w:t></w:r></w:p>
    </w:body>
</w:document>
XML;

        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        return $tempPath;
    }

    public function test_upload_docx_direct_mode_creates_letter_template(): void
    {
        $this->actingAsAdmin();
        Storage::fake('local');

        $tempDocx = $this->createSampleDocx('SURAT DISPENSASI KHUSUS');
        $storedPath = 'temp-uploads/sample.docx';
        Storage::disk('local')->put($storedPath, file_get_contents($tempDocx));
        @unlink($tempDocx);

        Livewire::test(ListLetterTemplates::class)
            ->callAction('upload_docx', [
                'file' => [$storedPath],
                'mode' => 'direct',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('letter_templates', [
            'name' => 'SURAT DISPENSASI KHUSUS',
            'letter_code' => 'DISPEN',
            'is_active' => true,
        ]);
    }

    public function test_upload_docx_editor_mode_redirects_and_prefills_create_form(): void
    {
        $this->actingAsAdmin();
        Storage::fake('local');

        $tempDocx = $this->createSampleDocx('SURAT TUGAS KHUSUS');
        $storedPath = 'temp-uploads/sample2.docx';
        Storage::disk('local')->put($storedPath, file_get_contents($tempDocx));
        @unlink($tempDocx);

        Livewire::test(ListLetterTemplates::class)
            ->callAction('upload_docx', [
                'file' => [$storedPath],
                'mode' => 'editor',
            ])
            ->assertRedirect();

        // Verify session was set
        $this->assertTrue(session()->has('imported_template_docx'));

        // Mount CreateLetterTemplate page and verify prefill
        $component = Livewire::test(CreateLetterTemplate::class);
        $component->assertSet('data.title_text', 'SURAT TUGAS KHUSUS');
        $component->assertSet('data.letter_code', 'ST-GUKAR');
    }
}
