<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_any_is_admin_or_kepsek_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kepsek = User::factory()->create(['role' => 'kepsek']);
        $gukar = User::factory()->create(['role' => 'gukar']);

        $this->assertTrue($admin->can('viewAny', User::class));
        $this->assertTrue($kepsek->can('viewAny', User::class));
        $this->assertFalse($gukar->can('viewAny', User::class));
    }

    public function test_view_permission(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kepsek = User::factory()->create(['role' => 'kepsek']);
        $gukar = User::factory()->create(['role' => 'gukar']);
        $otherGukar = User::factory()->create(['role' => 'gukar']);

        $this->assertTrue($admin->can('view', $gukar));
        $this->assertTrue($kepsek->can('view', $gukar));
        $this->assertTrue($gukar->can('view', $gukar));
        $this->assertFalse($gukar->can('view', $otherGukar));
    }

    public function test_create_is_admin_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kepsek = User::factory()->create(['role' => 'kepsek']);
        $gukar = User::factory()->create(['role' => 'gukar']);

        $this->assertTrue($admin->can('create', User::class));
        $this->assertFalse($kepsek->can('create', User::class));
        $this->assertFalse($gukar->can('create', User::class));
    }

    public function test_update_permission(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $gukar = User::factory()->create(['role' => 'gukar']);
        $otherGukar = User::factory()->create(['role' => 'gukar']);

        $this->assertTrue($admin->can('update', $gukar));
        $this->assertTrue($gukar->can('update', $gukar));
        $this->assertFalse($otherGukar->can('update', $gukar));
    }

    public function test_delete_is_admin_only_and_cannot_delete_self(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $adminTwo = User::factory()->create(['role' => 'admin']);
        $gukar = User::factory()->create(['role' => 'gukar']);

        $this->assertTrue($admin->can('delete', $gukar));
        $this->assertTrue($admin->can('delete', $adminTwo));
        $this->assertFalse($admin->can('delete', $admin));
        $this->assertFalse($gukar->can('delete', $admin));
    }

    public function test_reset_password_is_admin_only_and_cannot_reset_self(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $adminTwo = User::factory()->create(['role' => 'admin']);
        $gukar = User::factory()->create(['role' => 'gukar']);

        $this->assertTrue($admin->can('resetPassword', $gukar));
        $this->assertTrue($admin->can('resetPassword', $adminTwo));
        $this->assertFalse($admin->can('resetPassword', $admin));
        $this->assertFalse($gukar->can('resetPassword', $admin));
    }
}