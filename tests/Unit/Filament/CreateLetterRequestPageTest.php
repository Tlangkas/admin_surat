<?php

declare(strict_types=1);

namespace Tests\Unit\Filament;

use App\Filament\Resources\LetterRequestResource\Pages\CreateLetterRequest;
use App\Models\Karyawan;
use App\Models\LetterTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class CreateLetterRequestPageTest extends TestCase
{
    use RefreshDatabase;

    private function makeGukarWithProfile(): User
    {
        $gukar = User::factory()->create(['role' => 'gukar', 'name' => 'Dewi Lestari']);
        Karyawan::create([
            'user_id' => $gukar->id,
            'nama' => 'Dewi Lestari',
            'nip' => '199001012015012001',
            'jabatan' => 'Guru Bahasa',
        ]);

        return $gukar;
    }

    private function makeTemplate(): LetterTemplate
    {
        return LetterTemplate::create([
            'name' => 'Surat Izin',
            'content' => '<p>{{ nama }} | {{ keperluan }}</p>',
            'variables' => ['nama', 'keperluan'],
            'is_active' => true,
        ]);
    }

    public function test_mount_prefills_gukar_profile_into_payload_data(): void
    {
        $gukar = $this->makeGukarWithProfile();
        $this->actingAs($gukar);

        Livewire::test(CreateLetterRequest::class)
            ->assertSet('data.payload_data.nama', 'Dewi Lestari')
            ->assertSet('data.payload_data.nip', '199001012015012001')
            ->assertSet('data.payload_data.jabatan', 'Guru Bahasa');
    }

    public function test_mount_does_not_prefill_for_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        Livewire::test(CreateLetterRequest::class)
            ->assertSet('data.payload_data', null);
    }

    public function test_mutate_form_data_before_create_sets_user_and_pending_status(): void
    {
        $gukar = $this->makeGukarWithProfile();
        $template = $this->makeTemplate();
        $this->actingAs($gukar);

        $page = Livewire::test(CreateLetterRequest::class)->instance();
        $method = new ReflectionMethod(CreateLetterRequest::class, 'mutateFormDataBeforeCreate');
        $data = $method->invoke($page, [
            'template_id' => $template->id,
            'payload_data' => ['nama' => 'Dewi Lestari', 'keperluan' => 'Izin Penelitian'],
        ]);

        $this->assertSame($gukar->id, $data['user_id']);
        $this->assertSame('pending', $data['status']);
        $this->assertSame('Dewi Lestari', $data['payload_data']['nama']);
        $this->assertSame('199001012015012001', $data['payload_data']['nip']);
        $this->assertSame('Izin Penelitian', $data['payload_data']['keperluan']);
    }

    public function test_validate_form_data_throws_when_required_variable_is_empty(): void
    {
        $gukar = User::factory()->create(['role' => 'gukar']);
        $template = $this->makeTemplate();
        $this->actingAs($gukar);

        $page = Livewire::test(CreateLetterRequest::class)->instance();
        $method = new ReflectionMethod(CreateLetterRequest::class, 'validateFormData');

        try {
            $method->invoke($page, [
                'template_id' => $template->id,
                'payload_data' => ['nama' => ''],
            ]);
            $this->fail('ValidationException seharusnya dilempar.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('payload_data.nama', $e->errors());
        }
    }

    public function test_mutate_form_data_before_create_rejects_when_required_variable_missing(): void
    {
        $gukar = User::factory()->create(['role' => 'gukar']);
        $template = $this->makeTemplate();
        $this->actingAs($gukar);

        $page = Livewire::test(CreateLetterRequest::class)->instance();
        $method = new ReflectionMethod(CreateLetterRequest::class, 'mutateFormDataBeforeCreate');

        $this->expectException(ValidationException::class);

        $method->invoke($page, [
            'template_id' => $template->id,
            'payload_data' => ['nama' => 'Dewi'],
        ]);
    }

    public function test_validate_form_data_passes_when_all_required_variables_are_filled(): void
    {
        $gukar = User::factory()->create(['role' => 'gukar']);
        $template = $this->makeTemplate();
        $this->actingAs($gukar);

        $page = Livewire::test(CreateLetterRequest::class)->instance();
        $method = new ReflectionMethod(CreateLetterRequest::class, 'validateFormData');

        $method->invoke($page, [
            'template_id' => $template->id,
            'payload_data' => ['nama' => 'Dewi', 'keperluan' => 'Dinas'],
        ]);

        $this->assertTrue(true);
    }
}