<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Actions\Letter\GeneratePdfAndQrAction;
use App\Models\LetterRequest;
use App\Models\LetterTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GeneratePdfActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_pdf_and_qr_action_creates_pdf_file_and_updates_status(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['role' => 'gukar']);
        $template = LetterTemplate::create([
            'name' => 'Surat Jalan Test',
            'content' => '<h1>Surat Tugas {{ nama }}</h1><p>Tujuan: {{ tujuan }}</p>',
            'variables' => ['nama', 'tujuan'],
            'is_active' => true,
        ]);

        $letterRequest = LetterRequest::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => 'approved_admin',
            'payload_data' => [
                'nama' => 'Budi Santoso',
                'tujuan' => 'Jakarta',
            ],
        ]);

        $action = new GeneratePdfAndQrAction();
        $updatedRecord = $action->execute($letterRequest);

        $this->assertEquals('signed', $updatedRecord->status);
        $this->assertNotNull($updatedRecord->pdf_path);
        Storage::disk('local')->assertExists('public/' . $updatedRecord->pdf_path);
    }

    public function test_generate_pdf_action_rejects_pending_letter(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['role' => 'gukar']);
        $template = LetterTemplate::create([
            'name' => 'Surat Pending Test',
            'content' => '<h1>{{ nama }}</h1>',
            'variables' => ['nama'],
            'is_active' => true,
        ]);

        $letterRequest = LetterRequest::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => 'pending',
            'payload_data' => ['nama' => 'Budi'],
        ]);

        $this->expectException(\RuntimeException::class);
        app(GeneratePdfAndQrAction::class)->execute($letterRequest);

        $this->assertEquals('pending', $letterRequest->fresh()->status);
    }

    public function test_render_content_replaces_all_placeholder_variations(): void
    {
        $user = User::factory()->create(['role' => 'gukar']);
        $template = LetterTemplate::create([
            'name' => 'Test Variasi Placeholder',
            'content' => 'Nomor: {{ $nomor_surat }} | Nama: {{nama}} | Jabatan: {{  jabatan  }}',
            'variables' => ['nomor_surat', 'nama', 'jabatan'],
            'is_active' => true,
        ]);

        $letterRequest = LetterRequest::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => 'pending',
            'payload_data' => [
                'nomor_surat' => '421/001/SPD/2026',
                'nama' => 'Ahmad',
                'jabatan' => 'Guru IPA',
            ],
        ]);

        $rendered = $letterRequest->renderContent();

        $this->assertStringContainsString('Nomor: 421/001/SPD/2026', $rendered);
        $this->assertStringContainsString('Nama: Ahmad', $rendered);
        $this->assertStringContainsString('Jabatan: Guru IPA', $rendered);
        $this->assertStringNotContainsString('{{', $rendered);
    }
}
