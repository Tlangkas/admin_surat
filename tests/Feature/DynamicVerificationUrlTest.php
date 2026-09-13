<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LetterRequest;
use App\Models\LetterTemplate;
use App\Models\SchoolSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test URL verifikasi dinamis:
 * - Custom base URL dari SchoolSettings
 * - Fallback ke APP_URL jika kosong
 * - Artisan command regenerasi PDF
 */
class DynamicVerificationUrlTest extends TestCase
{
    use RefreshDatabase;

    private function createSignedLetterRequest(): LetterRequest
    {
        $user = User::factory()->create(['role' => 'admin']);
        $template = LetterTemplate::create([
            'name' => 'Surat Test',
            'content' => '<p>{{ nama }}</p>',
            'is_active' => true,
            'variables' => ['nama' => 'Nama Lengkap'],
        ]);

        return LetterRequest::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => 'signed',
            'uuid' => 'test-uuid-123',
            'payload_data' => ['nama' => 'Budi Santoso'],
        ]);
    }

    public function test_verification_url_uses_app_url_when_no_custom_url_set(): void
    {
        SchoolSettings::clearInstanceCache();
        $settings = SchoolSettings::getInstance();
        $settings->update(['verification_base_url' => null]);
        SchoolSettings::clearInstanceCache();

        $letter = $this->createSignedLetterRequest();
        $url = $letter->verificationUrl();

        $expectedBase = rtrim((string) config('app.url'), '/');
        $this->assertStringStartsWith($expectedBase . '/letter/verify/', $url);
        $this->assertStringContainsString('test-uuid-123', $url);
        $this->assertStringContainsString('sig=', $url);
    }

    public function test_verification_url_uses_custom_base_url_from_settings(): void
    {
        SchoolSettings::clearInstanceCache();
        $settings = SchoolSettings::getInstance();
        $settings->update(['verification_base_url' => 'http://192.168.1.100:8000']);
        SchoolSettings::clearInstanceCache();

        $letter = $this->createSignedLetterRequest();
        $url = $letter->verificationUrl();

        $this->assertStringStartsWith('http://192.168.1.100:8000/letter/verify/', $url);
        $this->assertStringContainsString('test-uuid-123', $url);
        $this->assertStringContainsString('sig=', $url);
    }

    public function test_verification_url_trims_trailing_slash_from_custom_url(): void
    {
        SchoolSettings::clearInstanceCache();
        $settings = SchoolSettings::getInstance();
        $settings->update(['verification_base_url' => 'http://10.0.0.5:8000/']);
        SchoolSettings::clearInstanceCache();

        $letter = $this->createSignedLetterRequest();
        $url = $letter->verificationUrl();

        // Tidak boleh ada double slash: http://10.0.0.5:8000//letter/verify/...
        $this->assertStringStartsWith('http://10.0.0.5:8000/letter/verify/', $url);
        $this->assertStringNotContainsString('8000//letter', $url);
    }

    public function test_verification_url_fallback_when_custom_url_is_empty_string(): void
    {
        SchoolSettings::clearInstanceCache();
        $settings = SchoolSettings::getInstance();
        $settings->update(['verification_base_url' => '   ']);
        SchoolSettings::clearInstanceCache();

        $letter = $this->createSignedLetterRequest();
        $url = $letter->verificationUrl();

        $expectedBase = rtrim((string) config('app.url'), '/');
        $this->assertStringStartsWith($expectedBase . '/letter/verify/', $url);
    }

    public function test_get_verification_base_url_returns_custom_url(): void
    {
        SchoolSettings::clearInstanceCache();
        $settings = SchoolSettings::getInstance();
        $settings->update(['verification_base_url' => 'https://esurat.sekolah.sch.id']);
        SchoolSettings::clearInstanceCache();

        $result = SchoolSettings::getInstance()->getVerificationBaseUrl();
        $this->assertEquals('https://esurat.sekolah.sch.id', $result);
    }

    public function test_get_verification_base_url_returns_app_url_as_fallback(): void
    {
        SchoolSettings::clearInstanceCache();
        $settings = SchoolSettings::getInstance();
        $settings->update(['verification_base_url' => null]);
        SchoolSettings::clearInstanceCache();

        $result = SchoolSettings::getInstance()->getVerificationBaseUrl();
        $expectedBase = rtrim((string) config('app.url'), '/');
        $this->assertEquals($expectedBase, $result);
    }

    public function test_artisan_regenerate_command_exists(): void
    {
        $this->artisan('letters:regenerate-pdfs')
            ->assertSuccessful();
    }

    public function test_artisan_regenerate_command_regenerates_signed_letters(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');

        SchoolSettings::clearInstanceCache();
        $settings = SchoolSettings::getInstance();
        $settings->update(['verification_base_url' => 'http://192.168.10.55:8000']);
        SchoolSettings::clearInstanceCache();

        $letter = $this->createSignedLetterRequest();
        $payload = $letter->payload_data ?? [];
        $payload['nomor_surat'] = '001/TEST/2026';
        $letter->update(['payload_data' => $payload]);

        $this->artisan('letters:regenerate-pdfs')
            ->expectsOutputToContain('Memproses 1 surat...')
            ->expectsOutputToContain('001/TEST/2026')
            ->expectsOutputToContain('Selesai: 1 berhasil, 0 gagal.')
            ->assertSuccessful();

        $letter->refresh();
        $this->assertNotNull($letter->pdf_path);
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists('public/' . $letter->pdf_path);
    }

    public function test_school_settings_page_saves_verification_base_url_and_can_regenerate(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);

        SchoolSettings::clearInstanceCache();
        $settings = SchoolSettings::getInstance();
        $settings->update([
            'nama_sekolah' => 'SMA Harapan Bangsa',
            'alamat' => 'Jl. Pendidikan No. 10',
            'verification_base_url' => null,
        ]);
        SchoolSettings::clearInstanceCache();

        $this->actingAs($admin);

        \Livewire\Livewire::test(\App\Filament\Pages\SchoolSettingsPage::class)
            ->assertSuccessful()
            ->set('data.verification_base_url', 'http://172.16.0.10:8000')
            ->call('save')
            ->assertHasNoErrors();

        $settings = SchoolSettings::getInstance();
        $this->assertEquals('http://172.16.0.10:8000', $settings->fresh()->verification_base_url);
        $this->assertEquals('http://172.16.0.10:8000', $settings->getVerificationBaseUrl());

        // Test regenerateAllPdfs method from page
        \Livewire\Livewire::test(\App\Filament\Pages\SchoolSettingsPage::class)
            ->call('regenerateAllPdfs')
            ->assertNotified('Regenerasi PDF selesai');
    }
}
