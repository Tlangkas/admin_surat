<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\SchoolSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolSettingsPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_abilities_are_admin_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kepsek = User::factory()->create(['role' => 'kepsek']);
        $gukar = User::factory()->create(['role' => 'gukar']);
        $settings = SchoolSettings::getInstance();

        foreach (['viewAny' => SchoolSettings::class, 'view' => $settings, 'update' => $settings] as $ability => $argument) {
            $this->assertTrue($admin->can($ability, $argument), "admin should {$ability}");

            $this->assertFalse($kepsek->can($ability, $argument), "kepsek should not {$ability}");
            $this->assertFalse($gukar->can($ability, $argument), "gukar should not {$ability}");
        }
    }
}