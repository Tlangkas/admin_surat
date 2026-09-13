<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\DocxTemplateParser;
use Tests\TestCase;
use ZipArchive;

class DocxTemplateParserTest extends TestCase
{
    private DocxTemplateParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new DocxTemplateParser();
    }

    public function test_convert_placeholders_handles_brackets_braces_and_dots(): void
    {
        $input = 'Nama: ....................................... [nip] {jabatan}';
        $output = $this->parser->convertPlaceholders($input);

        $this->assertStringContainsString('{{ nama }}', $output);
        $this->assertStringContainsString('{{ nip }}', $output);
        $this->assertStringContainsString('{{ jabatan }}', $output);
    }

    public function test_convert_placeholders_handles_common_school_labels(): void
    {
        $input = "NISN: ..........\nKelas: ..........\nJurusan: ..........\nKeperluan: ..........\nTujuan: ..........";
        $output = $this->parser->convertPlaceholders($input);

        $this->assertStringContainsString('{{ nisn }}', $output);
        $this->assertStringContainsString('{{ kelas }}', $output);
        $this->assertStringContainsString('{{ jurusan }}', $output);
        $this->assertStringContainsString('{{ keperluan }}', $output);
        $this->assertStringContainsString('{{ tujuan }}', $output);
    }

    public function test_convert_placeholders_handles_expanded_school_labels(): void
    {
        $input = "Tempat, Tanggal Lahir: ..........\nJenis Kelamin: ..........\nSekolah Tujuan: ..........\nAlasan Pindah: ..........\nBeban Anggaran: ..........";
        $output = $this->parser->convertPlaceholders($input);

        $this->assertStringContainsString('{{ tempat_tanggal_lahir }}', $output);
        $this->assertStringContainsString('{{ jenis_kelamin }}', $output);
        $this->assertStringContainsString('{{ sekolah_tujuan }}', $output);
        $this->assertStringContainsString('{{ alasan_pindah }}', $output);
        $this->assertStringContainsString('{{ beban_anggaran }}', $output);
    }

    public function test_parse_valid_docx_extracts_title_and_variables(): void
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'test_docx_') . '.docx';

        $zip = new ZipArchive();
        $zip->open($tempPath, ZipArchive::CREATE);

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:t>SURAT TUGAS</w:t></w:r></w:p>
        <w:p><w:r><w:t>Yang bertanda tangan di bawah ini Kepala Sekolah menugaskan:</w:t></w:r></w:p>
        <w:p><w:r><w:t>Nama: {{ nama }}</w:t></w:r></w:p>
        <w:p><w:r><w:t>NIP: {{ nip }}</w:t></w:r></w:p>
        <w:p><w:r><w:t>Untuk melaksanakan dinas luar ke [tujuan].</w:t></w:r></w:p>
        <w:p><w:r><w:t>Demikian surat tugas ini dibuat agar dapat dipergunakan sebagaimana mestinya.</w:t></w:r></w:p>
    </w:body>
</w:document>
XML;

        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        $result = $this->parser->parse($tempPath);
        @unlink($tempPath);

        $this->assertSame('SURAT TUGAS', $result['title_text']);
        $this->assertSame('ST-GUKAR', $result['letter_code']);
        $this->assertContains('nama', $result['variables']);
        $this->assertContains('nip', $result['variables']);
        $this->assertContains('tujuan', $result['variables']);
        $this->assertStringContainsString('Yang bertanda tangan di bawah ini', $result['opening_text']);
        $this->assertStringContainsString('Demikian surat tugas ini dibuat', $result['closing_text']);
    }

    public function test_parse_actual_surat_2025_file_if_exists(): void
    {
        $realFile = 'd:\\e surat\\SURAT 2025\\Dispensasi 2025\\dispensasi siswa 2024.docx';
        if (! file_exists($realFile)) {
            $this->markTestSkipped('Berkas SURAT 2025 tidak ditemukan di jalur spesifik.');
        }

        $result = $this->parser->parse($realFile);

        $this->assertNotEmpty($result['name']);
        $this->assertNotEmpty($result['content']);
        $this->assertIsArray($result['variables']);
        $this->assertIsArray($result['identity_fields']);
        $this->assertIsArray($result['detail_fields']);
    }
}
