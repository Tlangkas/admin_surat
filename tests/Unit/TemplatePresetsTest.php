<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\TemplatePresets;
use Tests\TestCase;

class TemplatePresetsTest extends TestCase
{
    public function test_get_presets_returns_three_presets_with_name_and_content(): void
    {
        $presets = TemplatePresets::getPresets();

        $this->assertCount(3, $presets);
        $this->assertArrayHasKey('surat_tugas', $presets);
        $this->assertArrayHasKey('surat_keterangan', $presets);
        $this->assertArrayHasKey('surat_izin', $presets);

        foreach ($presets as $preset) {
            $this->assertArrayHasKey('name', $preset);
            $this->assertArrayHasKey('content', $preset);
            $this->assertNotSame('', $preset['name']);
            $this->assertNotSame('', $preset['content']);
        }
    }

    public function test_every_preset_contains_required_placeholders(): void
    {
        foreach (TemplatePresets::getPresets() as $preset) {
            $this->assertStringContainsString('{{ nomor_surat }}', $preset['content']);
            $this->assertStringContainsString('{{ nama }}', $preset['content']);
        }
    }

    public function test_surat_tugas_preset_uses_dinas_placeholders(): void
    {
        $content = TemplatePresets::getPresets()['surat_tugas']['content'];

        $this->assertStringContainsString('SURAT TUGAS', $content);
        $this->assertStringContainsString('{{ nip }}', $content);
        $this->assertStringContainsString('{{ jabatan }}', $content);
        $this->assertStringContainsString('{{ tujuan }}', $content);
        $this->assertStringContainsString('{{ keperluan }}', $content);
        $this->assertStringContainsString('{{ tanggal_berangkat }}', $content);
        $this->assertStringContainsString('{{ tanggal_kembali }}', $content);
    }

    public function test_surat_keterangan_preset_uses_school_placeholders(): void
    {
        $content = TemplatePresets::getPresets()['surat_keterangan']['content'];

        $this->assertStringContainsString('SURAT KETERANGAN', $content);
        $this->assertStringContainsString('{{ sekolah }}', $content);
        $this->assertStringContainsString('{{ keperluan }}', $content);
    }

    public function test_surat_izin_preset_uses_izin_placeholders(): void
    {
        $content = TemplatePresets::getPresets()['surat_izin']['content'];

        $this->assertStringContainsString('SURAT IZIN', $content);
        $this->assertStringContainsString('{{ keperluan }}', $content);
        $this->assertStringContainsString('{{ tujuan }}', $content);
        $this->assertStringContainsString('{{ tanggal_berangkat }}', $content);
        $this->assertStringContainsString('{{ tanggal_kembali }}', $content);
    }
}