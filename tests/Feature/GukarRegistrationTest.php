<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\Auth\RegisterGukar;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GukarRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_gukar_registration_page_is_accessible(): void
    {
        $response = $this->get('/admin/register');

        $response->assertStatus(200);
        $response->assertSee('Pendaftaran Akun Guru & Karyawan');
    }

    public function test_gukar_cannot_register_with_non_existent_nip(): void
    {
        Livewire::test(RegisterGukar::class)
            ->fillForm([
                'nip' => '999999999999999999',
                'email' => 'test@sekolah.sch.id',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->call('register')
            ->assertHasFormErrors(['nip']);

        $this->assertDatabaseMissing('users', ['email' => 'test@sekolah.sch.id']);
    }

    public function test_gukar_cannot_register_if_nip_already_has_active_account(): void
    {
        $existingUser = User::factory()->create([
            'name' => 'Budi Santoso',
            'role' => 'gukar',
        ]);

        $karyawan = Karyawan::create([
            'user_id' => $existingUser->id,
            'nama' => 'Budi Santoso',
            'nip' => '198501012010011001',
            'jabatan' => 'Guru Matematika',
        ]);

        Livewire::test(RegisterGukar::class)
            ->fillForm([
                'nip' => $karyawan->nip,
                'email' => 'budi2@sekolah.sch.id',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->call('register')
            ->assertHasFormErrors(['nip']);
    }

    public function test_gukar_can_successfully_register_and_auto_link_karyawan_record(): void
    {
        $unlinkedKaryawan = Karyawan::create([
            'user_id' => null,
            'nama' => 'Sri Wahyuni S.Pd',
            'nip' => '199505052020012005',
            'jabatan' => 'Guru Kimia',
        ]);

        Livewire::test(RegisterGukar::class)
            ->fillForm([
                'nip' => '199505052020012005',
                'email' => 'sri.wahyuni@sekolah.sch.id',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->call('register')
            ->assertHasNoFormErrors()
            ->assertRedirect('/admin/pengajuan-surat');

        $this->assertDatabaseHas('users', [
            'name' => 'Sri Wahyuni S.Pd',
            'email' => 'sri.wahyuni@sekolah.sch.id',
            'role' => 'gukar',
        ]);

        $createdUser = User::where('email', 'sri.wahyuni@sekolah.sch.id')->first();
        $this->assertNotNull($createdUser);
        $this->assertEquals($createdUser->id, $unlinkedKaryawan->fresh()->user_id);
        $this->assertAuthenticatedAs($createdUser);
    }

    public function test_gukar_cannot_register_when_karyawan_name_matches_existing_user(): void
    {
        User::factory()->create([
            'name' => 'Budi Santoso',
            'role' => 'gukar',
        ]);

        $karyawan = Karyawan::create([
            'user_id' => null,
            'nama' => 'Budi Santoso',
            'nip' => '198501012010011003',
            'jabatan' => 'Guru Olahraga',
        ]);

        Livewire::test(RegisterGukar::class)
            ->fillForm([
                'nip' => $karyawan->nip,
                'email' => 'budi.duplikat@sekolah.sch.id',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->call('register')
            ->assertHasFormErrors(['nip']);

        $this->assertDatabaseMissing('users', ['email' => 'budi.duplikat@sekolah.sch.id']);
    }
}
