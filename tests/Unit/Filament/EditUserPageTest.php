<?php

declare(strict_types=1);

namespace Tests\Unit\Filament;

use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EditUserPageTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        return $admin;
    }

    public function test_mutate_form_data_before_fill_prefills_nip_and_jabatan(): void
    {
        $this->actingAsAdmin();
        $gukar = User::factory()->create(['role' => 'gukar', 'name' => 'Dewi']);
        Karyawan::create([
            'user_id' => $gukar->id,
            'nama' => 'Dewi',
            'nip' => '199001012015012001',
            'jabatan' => 'Guru Bahasa',
        ]);

        Livewire::test(EditUser::class, ['record' => $gukar->id])
            ->assertSet('data.nip', '199001012015012001')
            ->assertSet('data.jabatan', 'Guru Bahasa');
    }

    public function test_mutate_form_data_before_fill_resolves_karyawan_by_name_when_unlinked(): void
    {
        $this->actingAsAdmin();
        $gukar = User::factory()->create(['role' => 'gukar', 'name' => 'Budi Santoso']);
        Karyawan::create([
            'user_id' => null,
            'nama' => 'Budi Santoso',
            'nip' => '198501012010011001',
            'jabatan' => 'Guru Matematika',
        ]);

        Livewire::test(EditUser::class, ['record' => $gukar->id])
            ->assertSet('data.nip', '198501012010011001')
            ->assertSet('data.jabatan', 'Guru Matematika');
    }

    public function test_after_save_updates_karyawan_record_for_gukar(): void
    {
        $this->actingAsAdmin();
        $gukar = User::factory()->create(['role' => 'gukar', 'name' => 'Dewi']);
        Karyawan::create([
            'user_id' => $gukar->id,
            'nama' => 'Dewi',
            'nip' => 'OLD-NIP',
            'jabatan' => 'Guru Lama',
        ]);

        Livewire::test(EditUser::class, ['record' => $gukar->id])
            ->fillForm([
                'name' => 'Dewi Lestari',
                'email' => $gukar->email,
                'role' => 'gukar',
                'nip' => 'NEW-NIP',
                'jabatan' => 'Guru Baru',
            ])
            ->call('save');

        $this->assertDatabaseHas('karyawan', [
            'user_id' => $gukar->id,
            'nama' => 'Dewi Lestari',
            'nip' => 'NEW-NIP',
            'jabatan' => 'Guru Baru',
        ]);
    }

    public function test_non_admin_cannot_change_role_of_own_account(): void
    {
        $kepsek = User::factory()->create(['role' => 'kepsek', 'name' => 'Kepsek Satu']);

        $this->actingAs($kepsek);

        Livewire::test(EditUser::class, ['record' => $kepsek->id])
            ->fillForm([
                'name' => 'Kepsek Satu',
                'email' => $kepsek->email,
                'role' => 'admin',
            ])
            ->call('save');

        $this->assertSame('kepsek', $kepsek->fresh()->role);
    }

    public function test_gukar_cannot_access_user_edit_page(): void
    {
        $gukar = User::factory()->create(['role' => 'gukar', 'name' => 'Gukar Satu']);

        $this->actingAs($gukar);

        $this->get('/admin/pengguna/' . $gukar->id . '/edit')->assertForbidden();
    }
}