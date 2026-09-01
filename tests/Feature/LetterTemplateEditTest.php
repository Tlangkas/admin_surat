<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\LetterTemplateResource\Pages\EditLetterTemplate;
use App\Models\LetterTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LetterTemplateEditTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        return $admin;
    }

    public function test_template_edit_defaults_to_visual_builder(): void
    {
        $this->actingAsAdmin();

        $template = LetterTemplate::create([
            'name' => 'Surat Tugas Karyawan',
            'content' => '<p>Isi {{ nama }}</p>',
            'variables' => ['nama'],
            'is_active' => true,
        ]);

        $component = Livewire::test(EditLetterTemplate::class, ['record' => $template->getKey()]);

        // Default selalu membuka No-Code Visual Form Builder
        $component->assertSet('data.use_advanced_html', false);
        $component->assertSet('data.title_text', 'SURAT TUGAS KARYAWAN');
        $component->assertSet('data.identity_fields', ['nama']);

        // Simulasi simpan dari visual builder
        $component->call('save');

        $template->refresh();
        $this->assertFalse($template->use_advanced_html);
        $this->assertStringContainsString('SURAT TUGAS KARYAWAN', $template->content);
    }

    public function test_explicit_advanced_html_template_preserved_on_edit(): void
    {
        $this->actingAsAdmin();

        $template = LetterTemplate::create([
            'name' => 'Custom HTML Template',
            'content' => '<div class="custom">Isi {{ nama }}</div>',
            'variables' => ['nama'],
            'use_advanced_html' => true,
            'is_active' => true,
        ]);

        $component = Livewire::test(EditLetterTemplate::class, ['record' => $template->getKey()]);

        $component->assertSet('data.use_advanced_html', true);
        $component->assertSet('data.content', '<div class="custom">Isi {{ nama }}</div>');

        $component->call('save');

        $template->refresh();
        $this->assertSame('<div class="custom">Isi {{ nama }}</div>', $template->content);
    }

    public function test_builder_fields_restored_on_edit(): void
    {
        $this->actingAsAdmin();

        $template = LetterTemplate::create([
            'name' => 'Builder',
            'letter_code' => 'SPD',
            'content' => '<p>compiled</p>',
            'title_text' => 'SURAT TUGAS',
            'opening_text' => 'Pembuka',
            'middle_text' => null,
            'closing_text' => 'Penutup',
            'identity_fields' => ['nama', 'nip'],
            'detail_fields' => ['keperluan'],
            'use_advanced_html' => false,
        ]);

        $component = Livewire::test(EditLetterTemplate::class, ['record' => $template->getKey()]);

        $component->assertSet('data.use_advanced_html', false);
        $component->assertSet('data.title_text', 'SURAT TUGAS');
        $component->assertSet('data.opening_text', 'Pembuka');
        $component->assertSet('data.closing_text', 'Penutup');
        $component->assertSet('data.identity_fields', ['nama', 'nip']);
        $component->assertSet('data.detail_fields', ['keperluan']);
    }

    public function test_builder_template_save_recompiles_and_persists_builder_state(): void
    {
        $this->actingAsAdmin();

        $template = LetterTemplate::create([
            'name' => 'Builder',
            'letter_code' => 'SPD',
            'content' => '<p>compiled</p>',
            'title_text' => 'SURAT TUGAS',
            'opening_text' => 'Pembuka',
            'middle_text' => null,
            'closing_text' => 'Penutup',
            'identity_fields' => ['nama', 'nip'],
            'detail_fields' => ['keperluan'],
            'use_advanced_html' => false,
        ]);

        $component = Livewire::test(EditLetterTemplate::class, ['record' => $template->getKey()]);
        $component->set('data.opening_text', 'Pembuka Baru');
        $component->call('save');

        $template->refresh();
        $this->assertSame('Pembuka Baru', $template->opening_text);
        $this->assertStringContainsString('Pembuka Baru', $template->content);
    }
}
