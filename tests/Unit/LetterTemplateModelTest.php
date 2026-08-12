<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\LetterRequest;
use App\Models\LetterTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterTemplateModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_variables_are_auto_extracted_on_create(): void
    {
        $template = LetterTemplate::create([
            'name' => 'Auto Extract',
            'content' => '<p>Nomor {{ nomor_surat }} untuk {{ nama }} ({{ nama }}) dengan {{ $jabatan }}</p>',
            'is_active' => true,
        ]);

        $this->assertSame(['nomor_surat', 'nama', 'jabatan'], $template->variables);
    }

    public function test_variables_are_re_extracted_when_content_is_updated(): void
    {
        $template = LetterTemplate::create([
            'name' => 'Re Extract',
            'content' => '<p>{{ nama }}</p>',
            'is_active' => true,
        ]);

        $this->assertSame(['nama'], $template->variables);

        $template->update(['content' => '<p>{{ nama }} bekerja di {{ sekolah }}</p>']);

        $this->assertSame(['nama', 'sekolah'], $template->fresh()->variables);
    }

    public function test_variables_are_kept_when_content_is_empty(): void
    {
        $template = LetterTemplate::create([
            'name' => 'Kosong',
            'content' => '',
            'variables' => [],
            'is_active' => true,
        ]);

        $this->assertSame([], $template->variables);
    }

    public function test_variables_are_cleared_when_all_placeholders_are_removed(): void
    {
        $template = LetterTemplate::create([
            'name' => 'Bersihkan Placeholder',
            'content' => '<p>{{ nama }} di {{ sekolah }}</p>',
            'variables' => ['nama', 'sekolah'],
            'is_active' => true,
        ]);

        $this->assertSame(['nama', 'sekolah'], $template->variables);

        $template->update(['content' => '<p>Tanpa variabel sama sekali</p>']);

        $this->assertSame([], $template->fresh()->variables);
    }

    public function test_letter_requests_relation_returns_only_owned_requests(): void
    {
        $user = User::factory()->create(['role' => 'gukar']);
        $template = LetterTemplate::create([
            'name' => 'Relasi',
            'content' => '<p>{{ nama }}</p>',
            'is_active' => true,
        ]);

        LetterRequest::create(['user_id' => $user->id, 'template_id' => $template->id, 'status' => 'pending', 'payload_data' => []]);
        LetterRequest::create(['user_id' => $user->id, 'template_id' => $template->id, 'status' => 'pending', 'payload_data' => []]);

        $this->assertCount(2, $template->letterRequests);
    }
}