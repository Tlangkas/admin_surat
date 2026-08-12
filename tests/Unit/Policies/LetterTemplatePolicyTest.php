<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\LetterTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterTemplatePolicyTest extends TestCase
{
    use RefreshDatabase;

    private function makeTemplate(): LetterTemplate
    {
        return LetterTemplate::create([
            'name' => 'Template',
            'content' => '<p>{{ nama }}</p>',
            'variables' => ['nama'],
            'is_active' => true,
        ]);
    }

    public function test_view_any_and_view_are_admin_or_kepsek_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kepsek = User::factory()->create(['role' => 'kepsek']);
        $gukar = User::factory()->create(['role' => 'gukar']);
        $template = $this->makeTemplate();

        $this->assertTrue($admin->can('viewAny', LetterTemplate::class));
        $this->assertTrue($kepsek->can('viewAny', LetterTemplate::class));
        $this->assertFalse($gukar->can('viewAny', LetterTemplate::class));

        $this->assertTrue($admin->can('view', $template));
        $this->assertTrue($kepsek->can('view', $template));
        $this->assertFalse($gukar->can('view', $template));
    }

    public function test_create_update_delete_restore_force_delete_are_admin_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kepsek = User::factory()->create(['role' => 'kepsek']);
        $gukar = User::factory()->create(['role' => 'gukar']);
        $template = $this->makeTemplate();

        foreach (['create' => LetterTemplate::class, 'update' => $template, 'delete' => $template, 'restore' => $template, 'forceDelete' => $template] as $ability => $argument) {
            $this->assertTrue($admin->can($ability, $argument), "admin should {$ability}");

            $this->assertFalse($kepsek->can($ability, $argument), "kepsek should not {$ability}");
            $this->assertFalse($gukar->can($ability, $argument), "gukar should not {$ability}");
        }
    }
}