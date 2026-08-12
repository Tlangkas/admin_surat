<?php

declare(strict_types=1);

namespace Tests\Unit\Filament;

use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateUserPageTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    public function test_after_create_creates_karyawan_record_for_gukar(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Budi Baru',
                'email' => 'budi.baru@test.dev',
                'role' => 'gukar',
                'nip' => '198501012010011002',
                'jabatan' => 'Guru IPA',
                'password' => 'password123',
            ])
            ->call('create');

        $user = User::where('email', 'budi.baru@test.dev')->firstOrFail();

        $this->assertDatabaseHas('karyawan', [
            'user_id' => $user->id,
            'nama' => 'Budi Baru',
            'nip' => '198501012010011002',
            'jabatan' => 'Guru IPA',
        ]);
    }

    public function test_after_create_does_not_create_karyawan_for_non_gukar(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Kepsek Baru',
                'email' => 'kepsek.baru@test.dev',
                'role' => 'kepsek',
                'password' => 'password123',
            ])
            ->call('create');

        $user = User::where('email', 'kepsek.baru@test.dev')->firstOrFail();

        $this->assertDatabaseMissing('karyawan', ['user_id' => $user->id]);
    }
}