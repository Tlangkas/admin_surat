<?php

declare(strict_types=1);

namespace Tests\Unit\Filament;

use App\Filament\Resources\LetterRequestResource\Pages\EditLetterRequest;
use App\Models\LetterRequest;
use App\Models\LetterTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class EditLetterRequestPageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $owner;
    private User $otherGukar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->owner = User::factory()->create(['role' => 'gukar']);
        $this->otherGukar = User::factory()->create(['role' => 'gukar']);
    }

    private function makeRequest(string $status, ?User $owner = null): LetterRequest
    {
        $template = LetterTemplate::create([
            'name' => 'Surat Tugas',
            'content' => '<p>{{ nama }} | {{ keperluan }}</p>',
            'variables' => ['nama', 'keperluan'],
            'is_active' => true,
        ]);

        return LetterRequest::create([
            'user_id' => (($owner ?? $this->owner))->id,
            'template_id' => $template->id,
            'status' => $status,
            'payload_data' => ['nama' => 'Dewi', 'keperluan' => 'Dinas'],
        ]);
    }

    public function test_mount_allows_pending_owner_to_edit(): void
    {
        $request = $this->makeRequest('pending');
        $this->actingAs($this->owner);

        Livewire::test(EditLetterRequest::class, ['record' => $request->id])
            ->assertOk();
    }

    public function test_mount_aborts_when_request_is_not_pending(): void
    {
        $request = $this->makeRequest('approved_admin');
        $this->actingAs($this->admin);

        Livewire::test(EditLetterRequest::class, ['record' => $request->id])
            ->assertForbidden();
    }

    public function test_mutate_form_data_before_fill_aborts_when_gukar_is_not_the_owner(): void
    {
        $request = $this->makeRequest('pending');
        $this->actingAs($this->owner);

        $page = Livewire::test(EditLetterRequest::class, ['record' => $request->id])->instance();

        // Ganti user aktif dari luar supaya guard ownership tercapai pada pemanggilan berikutnya.
        $this->actingAs($this->otherGukar);
        $method = new ReflectionMethod(EditLetterRequest::class, 'mutateFormDataBeforeFill');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $method->invoke($page, []);
    }

    public function test_validate_form_data_throws_when_required_variable_is_empty(): void
    {
        $request = $this->makeRequest('pending');
        $this->actingAs($this->owner);

        $page = Livewire::test(EditLetterRequest::class, ['record' => $request->id])->instance();
        $method = new ReflectionMethod(EditLetterRequest::class, 'validateFormData');

        try {
            $method->invoke($page, ['payload_data' => ['nama' => '', 'keperluan' => 'Dinas']]);
            $this->fail('ValidationException seharusnya dilempar.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('payload_data.nama', $e->errors());
        }
    }

    public function test_validate_form_data_passes_when_all_required_variables_are_filled(): void
    {
        $request = $this->makeRequest('pending');
        $this->actingAs($this->owner);

        $page = Livewire::test(EditLetterRequest::class, ['record' => $request->id])->instance();
        $method = new ReflectionMethod(EditLetterRequest::class, 'validateFormData');

        $method->invoke($page, ['payload_data' => ['nama' => 'Dewi', 'keperluan' => 'Dinas']]);

        $this->assertTrue(true);
    }
}