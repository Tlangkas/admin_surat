<?php

declare(strict_types=1);

namespace Tests\Unit\Filament;

use App\Filament\Pages\SchoolSettingsPage;
use App\Models\LetterRequest;
use App\Models\LetterTemplate;
use App\Models\SchoolSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        return $admin;
    }

    public function test_admin_can_mount_school_settings_page(): void
    {
        $this->actingAsAdmin();

        Livewire::test(SchoolSettingsPage::class)
            ->assertSuccessful()
            ->assertSet('has_logo_kanan', false);
    }

    public function test_toggle_has_logo_kanan_syncs_state_and_saves_to_database(): void
    {
        $this->actingAsAdmin();

        Livewire::test(SchoolSettingsPage::class)
            ->set('has_logo_kanan', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(SchoolSettings::getInstance()->fresh()->has_logo_kanan);

        Livewire::test(SchoolSettingsPage::class)
            ->set('has_logo_kanan', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse(SchoolSettings::getInstance()->fresh()->has_logo_kanan);
    }

    public function test_letter_pdf_view_renders_single_logo_when_has_logo_kanan_is_false(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('logos/logo_kiri.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));
        Storage::disk('public')->put('logos/logo_kanan.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));

        $settings = SchoolSettings::getInstance();
        $settings->update([
            'logo_path' => 'logos/logo_kiri.png',
            'logo_kanan_path' => 'logos/logo_kanan.png',
            'has_logo_kanan' => false,
        ]);

        $user = User::factory()->create(['role' => 'gukar']);
        $template = LetterTemplate::create([
            'name' => 'Surat Tugas',
            'content' => '<p>Surat Tugas</p>',
            'variables' => [],
            'is_active' => true,
        ]);

        $letterRequest = LetterRequest::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => 'pending',
            'payload_data' => [],
        ]);

        $html = view('pdfs.letter', [
            'letterRequest' => $letterRequest,
            'settings' => $settings->fresh(),
            'content' => '<p>Test Konten</p>',
            'qrCodeSvg' => '',
        ])->render();

        $this->assertStringContainsString('alt="Logo Utama"', $html);
        $this->assertStringNotContainsString('alt="Logo Sekunder"', $html);
    }

    public function test_letter_pdf_view_renders_dual_logo_when_has_logo_kanan_is_true(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('logos/logo_kiri.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));
        Storage::disk('public')->put('logos/logo_kanan.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));

        $settings = SchoolSettings::getInstance();
        $settings->update([
            'logo_path' => 'logos/logo_kiri.png',
            'logo_kanan_path' => 'logos/logo_kanan.png',
            'has_logo_kanan' => true,
        ]);

        $user = User::factory()->create(['role' => 'gukar']);
        $template = LetterTemplate::create([
            'name' => 'Surat Tugas',
            'content' => '<p>Surat Tugas</p>',
            'variables' => [],
            'is_active' => true,
        ]);

        $letterRequest = LetterRequest::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => 'pending',
            'payload_data' => [],
        ]);

        $html = view('pdfs.letter', [
            'letterRequest' => $letterRequest,
            'settings' => $settings->fresh(),
            'content' => '<p>Test Konten</p>',
            'qrCodeSvg' => '',
        ])->render();

        $this->assertStringContainsString('alt="Logo Utama"', $html);
        $this->assertStringContainsString('alt="Logo Sekunder"', $html);
    }
}
