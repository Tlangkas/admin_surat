<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KaryawanPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_any_is_admin_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kepsek = User::factory()->create(['role' => 'kepsek']);
        $gukar = User::factory()->create(['role' => 'gukar']);

        $this->assertTrue($admin->can('viewAny', Karyawan::class));
        $this->assertFalse($kepsek->can('viewAny', Karyawan::class));
        $this->assertFalse($gukar->can('viewAny', Karyawan::class));
    }

    public function test_view_permission_is_admin_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kepsek = User::factory()->create(['role' => 'kepsek']);
        $gukar = User::factory()->create(['role' => 'gukar']);

        $karyawan = Karyawan::create([
            'nama' => 'Budi Santoso, S.Pd',
            'nip' => '198501012010011001',
            'jabatan' => 'Guru Matematika',
        ]);

        $this->assertTrue($admin->can('view', $karyawan));
        $this->assertFalse($kepsek->can('view', $karyawan));
        $this->assertFalse($gukar->can('view', $karyawan));
    }

    public function test_create_is_admin_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kepsek = User::factory()->create(['role' => 'kepsek']);
        $gukar = User::factory()->create(['role' => 'gukar']);

        $this->assertTrue($admin->can('create', Karyawan::class));
        $this->assertFalse($kepsek->can('create', Karyawan::class));
        $this->assertFalse($gukar->can('create', Karyawan::class));
    }

    public function test_update_is_admin_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kepsek = User::factory()->create(['role' => 'kepsek']);
        $gukar = User::factory()->create(['role' => 'gukar']);

        $karyawan = Karyawan::create([
            'nama' => 'Siti Nurhaliza, S.Pd',
            'nip' => '198801012012012002',
            'jabatan' => 'Guru Bahasa Inggris',
        ]);

        $this->assertTrue($admin->can('update', $karyawan));
        $this->assertFalse($kepsek->can('update', $karyawan));
        $this->assertFalse($gukar->can('update', $karyawan));
    }

    public function test_delete_is_admin_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kepsek = User::factory()->create(['role' => 'kepsek']);
        $gukar = User::factory()->create(['role' => 'gukar']);

        $karyawan = Karyawan::create([
            'nama' => 'Agus Priyono',
            'nip' => '199001012015011003',
            'jabatan' => 'Staff Tata Usaha',
        ]);

        $this->assertTrue($admin->can('delete', $karyawan));
        $this->assertFalse($kepsek->can('delete', $karyawan));
        $this->assertFalse($gukar->can('delete', $karyawan));
    }
}
