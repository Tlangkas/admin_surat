<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KaryawanModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_relation_returns_linked_account(): void
    {
        $user = User::factory()->create(['role' => 'gukar']);
        $karyawan = Karyawan::create([
            'user_id' => $user->id,
            'nama' => 'Budi',
            'nip' => '198501012010011001',
            'jabatan' => 'Guru',
        ]);

        $this->assertTrue($karyawan->user->is($user));
    }

    public function test_user_relation_is_null_for_unlinked_karyawan(): void
    {
        $karyawan = Karyawan::create([
            'user_id' => null,
            'nama' => 'Solo',
            'nip' => '199001012015012001',
            'jabatan' => 'Staf',
        ]);

        $this->assertNull($karyawan->user);
    }
}