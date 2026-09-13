<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\TemplatePresets;
use Tests\TestCase;

class TemplatePresetsTest extends TestCase
{
    public function test_get_presets_returns_presets_with_name_and_content(): void
    {
        $presets = TemplatePresets::getPresets();

        $this->assertGreaterThanOrEqual(11, count($presets));
        $this->assertArrayHasKey('surat_tugas', $presets);
        $this->assertArrayHasKey('surat_keterangan', $presets);
        $this->assertArrayHasKey('surat_izin', $presets);
        $this->assertArrayHasKey('surat_dispensasi', $presets);
        $this->assertArrayHasKey('surat_panggilan_ortu', $presets);
        $this->assertArrayHasKey('surat_peringatan_siswa', $presets);
        $this->assertArrayHasKey('surat_peringatan_pegawai', $presets);
        $this->assertArrayHasKey('surat_home_visit', $presets);
        $this->assertArrayHasKey('surat_sppd', $presets);
        $this->assertArrayHasKey('surat_rekomendasi', $presets);
        $this->assertArrayHasKey('surat_pindah', $presets);
        $this->assertArrayHasKey('surat_undangan', $presets);

        foreach ($presets as $preset) {
            $this->assertArrayHasKey('name', $preset);
            $this->assertArrayHasKey('letter_code', $preset);
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

    public function test_surat_dispensasi_preset_uses_dispensasi_placeholders(): void
    {
        $content = TemplatePresets::getPresets()['surat_dispensasi']['content'];

        $this->assertStringContainsString('SURAT DISPENSASI SISWA', $content);
        $this->assertStringContainsString('{{ nisn }}', $content);
        $this->assertStringContainsString('{{ kelas }}', $content);
        $this->assertStringContainsString('{{ jurusan }}', $content);
        $this->assertStringContainsString('{{ daftar_peserta }}', $content);
    }

    public function test_surat_panggilan_ortu_preset_uses_ortu_placeholders(): void
    {
        $content = TemplatePresets::getPresets()['surat_panggilan_ortu']['content'];

        $this->assertStringContainsString('SURAT PANGGILAN ORANG TUA', $content);
        $this->assertStringContainsString('{{ hari_tanggal }}', $content);
        $this->assertStringContainsString('{{ waktu }}', $content);
        $this->assertStringContainsString('{{ tempat }}', $content);
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

    public function test_surat_pindah_preset_uses_student_transfer_placeholders(): void
    {
        $preset = TemplatePresets::getPresets()['surat_pindah'];
        $content = $preset['content'];

        $this->assertStringContainsString('SURAT KETERANGAN PINDAH SEKOLAH', $content);
        $this->assertStringContainsString('{{ tempat_tanggal_lahir }}', $content);
        $this->assertStringContainsString('{{ jenis_kelamin }}', $content);
        $this->assertStringContainsString('{{ nis }}', $content);
        $this->assertStringContainsString('{{ sekolah_tujuan }}', $content);
        $this->assertStringContainsString('{{ alasan_pindah }}', $content);
        $this->assertContains('tempat_tanggal_lahir', $preset['identity_fields']);
        $this->assertContains('sekolah_tujuan', $preset['detail_fields']);
    }

    public function test_surat_sppd_preset_uses_official_travel_placeholders(): void
    {
        $preset = TemplatePresets::getPresets()['surat_sppd'];
        $content = $preset['content'];

        $this->assertStringContainsString('SURAT PERINTAH PERJALANAN DINAS', $content);
        $this->assertStringContainsString('{{ pejabat_pemberi_perintah }}', $content);
        $this->assertStringContainsString('{{ pangkat_golongan }}', $content);
        $this->assertStringContainsString('{{ beban_anggaran }}', $content);
        $this->assertStringContainsString('{{ lama_perjalanan }}', $content);
        $this->assertContains('pejabat_pemberi_perintah', $preset['detail_fields']);
        $this->assertContains('beban_anggaran', $preset['detail_fields']);
    }

    public function test_surat_peringatan_siswa_preset_uses_discipline_placeholders(): void
    {
        $preset = TemplatePresets::getPresets()['surat_peringatan_siswa'];
        $content = $preset['content'];

        $this->assertStringContainsString('SURAT PERINGATAN SISWA', $content);
        $this->assertStringContainsString('{{ bentuk_pelanggaran }}', $content);
        $this->assertStringContainsString('{{ poin_pelanggaran }}', $content);
        $this->assertStringContainsString('{{ tindakan_pembinaan }}', $content);
        $this->assertContains('bentuk_pelanggaran', $preset['detail_fields']);
    }

    public function test_template_compiler_renders_indonesian_labels_for_school_variables(): void
    {
        $compiled = \App\Helpers\TemplateCompiler::compile([
            'title_text' => 'SURAT KETERANGAN SISWA',
            'identity_fields' => ['nama', 'nis', 'tempat_tanggal_lahir', 'jenis_kelamin', 'nama_orang_tua'],
            'detail_fields' => ['bentuk_pelanggaran', 'sekolah_tujuan', 'beban_anggaran'],
        ]);

        $this->assertStringContainsString('Tempat, Tanggal Lahir', $compiled['content']);
        $this->assertStringContainsString('Jenis Kelamin', $compiled['content']);
        $this->assertStringContainsString('Nama Orang Tua', $compiled['content']);
        $this->assertStringContainsString('Bentuk Pelanggaran', $compiled['content']);
        $this->assertStringContainsString('Sekolah Tujuan', $compiled['content']);
        $this->assertStringContainsString('Pembebanan Anggaran', $compiled['content']);
        $this->assertContains('tempat_tanggal_lahir', $compiled['variables']);
        $this->assertContains('sekolah_tujuan', $compiled['variables']);
    }
}