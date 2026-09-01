<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Helpers\TemplateCompiler;
use App\Models\LetterRequest;
use App\Models\LetterTemplate;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiParticipantLetterTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_compiler_includes_daftar_peserta_variable_when_configured(): void
    {
        $compiled = TemplateCompiler::compile([
            'title_text' => 'SURAT DISPENSASI SISWA',
            'opening_text' => 'Kepala sekolah memberikan dispensasi kepada:',
            'include_participants' => true,
            'detail_fields' => ['tujuan', 'nama_kegiatan'],
            'closing_text' => 'Demikian surat ini dibuat.',
        ]);

        $this->assertContains('daftar_peserta', $compiled['variables']);
        $this->assertStringContainsString('{{ daftar_peserta }}', $compiled['content']);
        $this->assertStringContainsString('SURAT DISPENSASI SISWA', $compiled['content']);
    }

    public function test_letter_request_renders_daftar_peserta_as_html_table(): void
    {
        $user = User::factory()->create(['role' => 'gukar']);
        $template = LetterTemplate::create([
            'name' => 'Surat Tugas Siswa',
            'content' => '<div>Judul</div><p>Berikut daftar peserta:</p>{{ daftar_peserta }}<p>Penutup</p>',
            'variables' => ['daftar_peserta'],
            'is_active' => true,
        ]);

        $participants = [
            [
                'nama' => 'Ahmad Rizky Pratama',
                'identitas' => '0051234501',
                'kelas_jabatan' => 'X RPL 1',
                'peran' => 'Ketua Tim',
            ],
            [
                'nama' => 'Anisa Rahmawati',
                'identitas' => '0051234502',
                'kelas_jabatan' => 'X RPL 1',
                'peran' => 'Anggota Tim',
            ],
        ];

        $letterRequest = LetterRequest::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => 'pending',
            'payload_data' => [
                'daftar_peserta' => $participants,
            ],
        ]);

        $rendered = $letterRequest->renderContent();

        $this->assertStringContainsString('<table class="table-data"', $rendered);
        $this->assertStringContainsString('Ahmad Rizky Pratama', $rendered);
        $this->assertStringContainsString('0051234501', $rendered);
        $this->assertStringContainsString('X RPL 1', $rendered);
        $this->assertStringContainsString('Ketua Tim', $rendered);
        $this->assertStringContainsString('Anisa Rahmawati', $rendered);
    }

    public function test_public_verification_page_displays_multi_participant_table(): void
    {
        $user = User::factory()->create(['role' => 'gukar']);
        $template = LetterTemplate::create([
            'name' => 'Surat Dispensasi Siswa',
            'content' => '<p>Konten</p>',
            'variables' => ['daftar_peserta'],
            'is_active' => true,
        ]);

        $participants = [
            [
                'nama' => 'Bagus Kurniawan',
                'identitas' => '0049876503',
                'kelas_jabatan' => 'XI TKJ 2',
                'peran' => 'Peserta Lomba Jaringan',
            ],
        ];

        $letterRequest = LetterRequest::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => 'signed',
            'payload_data' => [
                'nama_kegiatan' => 'LKS SMK Tingkat Provinsi',
                'daftar_peserta' => $participants,
            ],
        ]);

        $response = $this->get($letterRequest->verificationUrl());

        $response->assertStatus(200);
        $response->assertSee('Daftar Peserta / Kontingen');
        $response->assertSee('Bagus Kurniawan');
        $response->assertSee('0049876503');
        $response->assertSee('XI TKJ 2');
        $response->assertSee('Peserta Lomba Jaringan');
    }
}
