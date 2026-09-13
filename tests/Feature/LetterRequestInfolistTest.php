<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\LetterRequestResource;
use App\Models\LetterRequest;
use App\Models\LetterTemplate;
use App\Models\User;
use Filament\Infolists\Infolist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterRequestInfolistTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolve_field_label_returns_clean_unambiguous_indonesian_labels(): void
    {
        $this->assertEquals('Nama', LetterRequestResource::resolveFieldLabel('nama'));
        $this->assertEquals('NIP', LetterRequestResource::resolveFieldLabel('nip'));
        $this->assertEquals('NISN', LetterRequestResource::resolveFieldLabel('nisn'));
        $this->assertEquals('Tempat, Tanggal Lahir', LetterRequestResource::resolveFieldLabel('ttl'));
        $this->assertEquals('Jenis Kelamin', LetterRequestResource::resolveFieldLabel('jenis_kelamin'));
        $this->assertEquals('Alamat', LetterRequestResource::resolveFieldLabel('alamat'));
        $this->assertEquals('Nama Orang Tua', LetterRequestResource::resolveFieldLabel('nama_orang_tua'));
        $this->assertEquals('Nama Kegiatan', LetterRequestResource::resolveFieldLabel('nama_kegiatan'));
        $this->assertEquals('Tanggal Berangkat', LetterRequestResource::resolveFieldLabel('tanggal_berangkat'));
        $this->assertEquals('Tanggal Kembali', LetterRequestResource::resolveFieldLabel('tanggal_kembali'));
    }

    public function test_infolist_renders_detail_pengajuan_surat_without_submission_form_ambiguity(): void
    {
        $user = User::factory()->create(['name' => 'Budi Santoso', 'role' => 'gukar']);
        $template = LetterTemplate::create([
            'name' => 'Surat Dispensasi Siswa',
            'letter_code' => 'DISPEN',
            'content' => '<p>{{ nomor_surat }}</p>{{ daftar_peserta }}',
            'variables' => ['nomor_surat', 'nama_kegiatan', 'tujuan', 'daftar_peserta'],
            'is_active' => true,
        ]);

        $letterRequest = LetterRequest::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'nomor_surat' => '421/002/DISPEN/2026',
            'status' => 'approved_admin',
            'payload_data' => [
                'nomor_surat' => '421/002/DISPEN/2026',
                'nama_kegiatan' => 'Olimpiade Sains Nasional',
                'tujuan' => 'SMA Negeri 1 Surakarta',
                'daftar_peserta' => [
                    [
                        'nama' => 'Ahmad Rizky',
                        'identitas' => '0051234501',
                        'kelas_jabatan' => 'X RPL 1',
                        'peran' => 'Ketua Tim',
                    ],
                ],
            ],
        ]);

        $infolist = Infolist::make();
        $infolist->record($letterRequest);
        $configured = LetterRequestResource::infolist($infolist);

        $html = $configured->toHtml();

        $this->assertStringContainsString('Informasi Pengajuan Surat', $html);
        $this->assertStringContainsString('Rincian Data Surat', $html);
        $this->assertStringContainsString('Berkas &amp; Validasi Dokumen', $html);

        $this->assertStringContainsString('Surat Dispensasi Siswa', $html);
        $this->assertStringContainsString('421/002/DISPEN/2026', $html);
        $this->assertStringContainsString('Disetujui Admin', $html);
        $this->assertStringContainsString('Budi Santoso', $html);
        $this->assertStringContainsString('Olimpiade Sains Nasional', $html);
        $this->assertStringContainsString('SMA Negeri 1 Surakarta', $html);
        $this->assertStringContainsString('Ahmad Rizky', $html);
        $this->assertStringContainsString('0051234501', $html);

        $this->assertStringNotContainsString('Pilih jenis template surat resmi yang ingin diajukan', $html);
        $this->assertStringNotContainsString('Lengkapi formulir di bawah ini sesuai kebutuhan template surat', $html);
    }

    public function test_infolist_displays_rejection_reason_when_rejected(): void
    {
        $user = User::factory()->create(['name' => 'Budi Santoso', 'role' => 'gukar']);
        $template = LetterTemplate::create([
            'name' => 'Surat Izin',
            'letter_code' => 'SI',
            'content' => '<p>{{ keperluan }}</p>',
            'variables' => ['keperluan'],
            'is_active' => true,
        ]);

        $letterRequest = LetterRequest::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => 'rejected',
            'payload_data' => [
                'keperluan' => 'Acara Keluarga',
                'alasan_penolakan' => 'Data tanggal izin belum lengkap dan lampiran tidak valid.',
            ],
        ]);

        $infolist = Infolist::make();
        $infolist->record($letterRequest);
        $configured = LetterRequestResource::infolist($infolist);

        $html = $configured->toHtml();

        $this->assertStringContainsString('Ditolak', $html);
        $this->assertStringContainsString('Alasan Penolakan', $html);
        $this->assertStringContainsString('Data tanggal izin belum lengkap dan lampiran tidak valid.', $html);
    }

    public function test_infolist_renders_signed_verification_url_with_valid_hmac_signature(): void
    {
        $user = User::factory()->create(['name' => 'Budi Santoso', 'role' => 'gukar']);
        $template = LetterTemplate::create([
            'name' => 'Surat Tugas',
            'letter_code' => 'ST',
            'content' => '<p>{{ keperluan }}</p>',
            'variables' => ['keperluan'],
            'is_active' => true,
        ]);

        $letterRequest = LetterRequest::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => 'signed',
            'payload_data' => ['keperluan' => 'Dinas Luar'],
        ]);

        $infolist = Infolist::make();
        $infolist->record($letterRequest);
        $configured = LetterRequestResource::infolist($infolist);

        $html = $configured->toHtml();

        $this->assertStringContainsString($letterRequest->uuid, $html);
        $this->assertStringContainsString('sig=', $html);
        $this->assertStringContainsString(hash_hmac('sha256', $letterRequest->uuid, config('app.key')), $html);
    }
}
