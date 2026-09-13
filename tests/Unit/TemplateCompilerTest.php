<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\TemplateCompiler;
use Tests\TestCase;

class TemplateCompilerTest extends TestCase
{
    public function test_compile_with_empty_input_uses_defaults_and_standard_fields(): void
    {
        $result = TemplateCompiler::compile([]);

        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('variables', $result);
        $this->assertIsArray($result['variables']);

        $content = $result['content'];
        $this->assertStringContainsString('SURAT RESMI', $content);
        $this->assertStringContainsString('Nomor: {{ nomor_surat }}', $content);
        $this->assertStringContainsString('Yang bertanda tangan di bawah ini Kepala Sekolah menerangkan bahwa:', $content);
        $this->assertStringContainsString('Demikian surat ini dibuat untuk dipergunakan sebagaimana mestinya.', $content);

        $variables = $result['variables'];
        $this->assertContains('nomor_surat', $variables);
        $this->assertContains('nama', $variables);
        $this->assertContains('nip', $variables);
        $this->assertContains('jabatan', $variables);
        $this->assertContains('keperluan', $variables);
        $this->assertContains('tujuan', $variables);
    }

    public function test_compile_maps_known_identity_and_detail_labels(): void
    {
        $result = TemplateCompiler::compile([
            'identity_fields' => ['nama', 'nip', 'jabatan', 'sekolah', 'nama_sekolah', 'custom_identity'],
            'detail_fields' => ['tujuan', 'keperluan', 'maksud', 'tanggal_berangkat', 'tgl_berangkat', 'tanggal_kembali', 'tgl_kembali', 'keterangan', 'rincian_kegiatan'],
        ]);

        $content = $result['content'];

        $this->assertStringContainsString('Nama', $content);
        $this->assertStringContainsString('NIP', $content);
        $this->assertStringContainsString('Jabatan', $content);
        $this->assertStringContainsString('Unit Kerja', $content);

        $this->assertStringContainsString('Tujuan', $content);
        $this->assertStringContainsString('Keperluan', $content);
        $this->assertStringContainsString('Tanggal Berangkat', $content);
        $this->assertStringContainsString('Tanggal Kembali', $content);
        $this->assertStringContainsString('Keterangan', $content);

        $this->assertContains('rincian_kegiatan', $result['variables']);
        $this->assertContains('custom_identity', $result['variables']);
    }

    public function test_compile_deduplicates_variables_and_includes_nomor_surat_first(): void
    {
        $result = TemplateCompiler::compile([
            'identity_fields' => ['nama', 'nama', 'nip', ''],
            'detail_fields' => ['keperluan', 'keperluan', 'tujuan'],
        ]);

        $this->assertSame(['nomor_surat', 'nama', 'nip', 'keperluan', 'tujuan'], $result['variables']);
    }

    public function test_compile_skips_empty_fields_and_no_tr_rows_are_generated_for_them(): void
    {
        $result = TemplateCompiler::compile([
            'identity_fields' => ['nama', '', '   '],
            'detail_fields' => [],
        ]);

        $this->assertSame(1, substr_count($result['content'], '<tr>'));
        $this->assertSame(['nomor_surat', 'nama'], $result['variables']);
    }

    public function test_compile_escapes_html_in_user_supplied_text(): void
    {
        $result = TemplateCompiler::compile([
            'title_text' => '<b>JUDUL</b>',
            'opening_text' => '<script>alert(1)</script>',
        ]);

        $this->assertStringNotContainsString('<b>JUDUL</b>', $result['content']);
        $this->assertStringContainsString('&lt;b&gt;JUDUL&lt;/b&gt;', $result['content']);
        $this->assertStringContainsString('&lt;script&gt;', $result['content']);
    }

    public function test_compile_omits_empty_opening_middle_and_closing(): void
    {
        $result = TemplateCompiler::compile([
            'opening_text' => '   ',
            'middle_text' => '',
            'closing_text' => '',
        ]);

        $content = $result['content'];

        $this->assertStringNotContainsString('Yang bertanda tangan', $content);
        $this->assertStringNotContainsString('Demikian', $content);
    }

    public function test_compile_uses_default_closing_when_closing_is_null(): void
    {
        $result = TemplateCompiler::compile(['closing_text' => null]);

        $this->assertStringContainsString('Demikian surat ini dibuat untuk dipergunakan sebagaimana mestinya.', $result['content']);
    }

    public function test_compile_escapes_placeholder_keys_when_rendered(): void
    {
        $result = TemplateCompiler::compile([
            'identity_fields' => ['nama'],
        ]);

        $this->assertStringContainsString('{{ nama }}', $result['content']);
    }
}