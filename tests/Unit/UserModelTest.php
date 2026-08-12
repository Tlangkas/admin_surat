<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Karyawan;
use App\Models\LetterRequest;
use App\Models\LetterTemplate;
use App\Models\SchoolSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_helpers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $kepsek = User::factory()->create(['role' => 'kepsek']);
        $gukar = User::factory()->create(['role' => 'gukar']);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isKepsek());
        $this->assertFalse($admin->isGukar());

        $this->assertTrue($kepsek->isKepsek());
        $this->assertTrue($gukar->isGukar());
    }

    public function test_can_access_panel_is_always_true(): void
    {
        $user = User::factory()->create(['role' => 'gukar']);

        $this->assertTrue($user->canAccessPanel(new \Filament\Panel()));
    }

    public function test_get_profile_data_returns_linked_karyawan_fields(): void
    {
        $user = User::factory()->create(['role' => 'gukar', 'name' => 'Budi']);
        Karyawan::create([
            'user_id' => $user->id,
            'nama' => 'Budi',
            'nip' => '198501012010011001',
            'jabatan' => 'Guru Matematika',
        ]);
        SchoolSettings::getInstance()->update(['nama_sekolah' => 'SMK Contoh']);

        $profile = $user->getProfileData();

        $this->assertSame('Budi', $profile['nama']);
        $this->assertSame('198501012010011001', $profile['nip']);
        $this->assertSame('Guru Matematika', $profile['jabatan']);
        $this->assertSame('SMK Contoh', $profile['sekolah']);
    }

    public function test_get_profile_data_auto_links_karyawan_by_name_without_user_id(): void
    {
        $user = User::factory()->create(['role' => 'gukar', 'name' => 'Dewi']);
        $karyawan = Karyawan::create([
            'user_id' => null,
            'nama' => 'Dewi',
            'nip' => '199001012015012001',
            'jabatan' => 'Guru Bahasa',
        ]);

        $profile = $user->getProfileData();

        $this->assertSame('Dewi', $profile['nama']);
        $this->assertSame('199001012015012001', $profile['nip']);

        $karyawan->refresh();
        $this->assertSame($user->id, $karyawan->user_id);
    }

    public function test_get_profile_data_falls_back_to_user_name_when_no_karyawan(): void
    {
        $user = User::factory()->create(['role' => 'gukar', 'name' => 'Solo User']);
        SchoolSettings::getInstance()->update(['nama_sekolah' => 'Sekolah X']);

        $profile = $user->getProfileData();

        $this->assertSame('Solo User', $profile['nama']);
        $this->assertSame('', $profile['nip']);
        $this->assertSame('', $profile['jabatan']);
        $this->assertSame('Sekolah X', $profile['sekolah']);
    }

    public function test_relations(): void
    {
        $user = User::factory()->create(['role' => 'gukar']);
        $karyawan = Karyawan::create(['user_id' => $user->id, 'nama' => 'A', 'nip' => '1', 'jabatan' => 'Guru']);
        $template = LetterTemplate::create(['name' => 'T', 'content' => '<p>{{ nama }}</p>', 'is_active' => true]);
        LetterRequest::create(['user_id' => $user->id, 'template_id' => $template->id, 'status' => 'pending', 'payload_data' => []]);

        $this->assertTrue($user->karyawan->is($karyawan));
        $this->assertCount(1, $user->letterRequests);
    }
}