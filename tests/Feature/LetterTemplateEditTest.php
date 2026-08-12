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

    public function test_legacy_template_content_preserved_on_edit(): void
    {
        $this->actingAsAdmin();

        $template = LetterTemplate::create([
            'name' => 'Legacy',
            'content' => '<p>Isi {{ nama }}</p>',
            'variables' => ['nama'],
            'is_active' => true,
        ]);

        $component = Livewire::test(EditLetterTemplate::class, ['record' => $template->getKey()]);

        // Template tanpa isian builder → dipaksa mode HTML lanjutan agar konten tidak terhapus.
        $component->assertSet('data.use_advanced_html', true);
        $component->assertSet('data.content', '<p>Isi {{ nama }}</p>');

        // Simulasi simpan: konten harus tetap utuh.
        $component->call('save');

        $template->refresh();
        $this->assertSame('<p>Isi {{ nama }}</p>', $template->content);
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
