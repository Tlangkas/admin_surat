<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\LetterRequestResource;
use App\Filament\Resources\LetterRequestResource\Pages\CreateLetterRequest;
use App\Filament\Resources\LetterRequestResource\Pages\EditLetterRequest;
use App\Models\LetterRequest;
use App\Models\LetterTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LetterRequestDateInputTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test parsing Indonesian date string back to Carbon instance.
     */
    public function test_parse_indonesian_date_helper(): void
    {
        // 1. Standard Date format
        $date = LetterRequestResource::parseIndonesianDate('13 Agustus 2026');
        $this->assertNotNull($date);
        $this->assertEquals('2026-08-13', $date->format('Y-m-d'));

        // 2. Another Date format (leap year)
        $date2 = LetterRequestResource::parseIndonesianDate('29 Februari 2024');
        $this->assertNotNull($date2);
        $this->assertEquals('2024-02-29', $date2->format('Y-m-d'));

        // 3. DateTime format with WIB
        $dateTime = LetterRequestResource::parseIndonesianDate('13 Agustus 2026 09:00 WIB');
        $this->assertNotNull($dateTime);
        $this->assertEquals('2026-08-13 09:00:00', $dateTime->format('Y-m-d H:i:s'));

        // 4. Null and invalid formats
        $this->assertNull(LetterRequestResource::parseIndonesianDate(null));
        $this->assertNull(LetterRequestResource::parseIndonesianDate(''));
        $this->assertNull(LetterRequestResource::parseIndonesianDate('Bukan Tanggal'));
    }

    /**
     * Test LetterRequest renders content correctly with date and time payload variables.
     */
    public function test_letter_request_renders_content_with_date_and_time_variables(): void
    {
        $user = User::factory()->create(['role' => 'gukar']);

        $template = LetterTemplate::create([
            'name' => 'Surat Tugas Acara',
            'content' => '<p>Berangkat: {{ tanggal_berangkat }} | Kembali: {{ tanggal_kembali }} | Waktu: {{ waktu_pelaksanaan }}</p>',
            'variables' => ['tanggal_berangkat', 'tanggal_kembali', 'waktu_pelaksanaan'],
            'is_active' => true,
        ]);

        $letterRequest = LetterRequest::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => 'pending',
            'payload_data' => [
                'tanggal_berangkat' => '13 Agustus 2026',
                'tanggal_kembali' => '15 Agustus 2026',
                'waktu_pelaksanaan' => '13 Agustus 2026 08:30 WIB',
            ],
        ]);

        $rendered = $letterRequest->renderContent();

        $this->assertStringContainsString('Berangkat: 13 Agustus 2026', $rendered);
        $this->assertStringContainsString('Kembali: 15 Agustus 2026', $rendered);
        $this->assertStringContainsString('Waktu: 13 Agustus 2026 08:30 WIB', $rendered);
        $this->assertStringNotContainsString('{{', $rendered);
    }

    /**
     * Test Livewire create form handles date variables properly.
     */
    public function test_livewire_create_letter_request_with_date_and_time_fields(): void
    {
        $user = User::factory()->create(['role' => 'gukar']);
        $this->actingAs($user);

        $template = LetterTemplate::create([
            'name' => 'Surat Izin Cuti',
            'content' => '<p>Mulai: {{ tanggal_mulai }} | Selesai: {{ tanggal_selesai }}</p>',
            'variables' => ['tanggal_mulai', 'tanggal_selesai'],
            'is_active' => true,
        ]);

        Livewire::test(CreateLetterRequest::class)
            ->set('data.template_id', (string) $template->id)
            ->fillForm([
                'template_id' => (string) $template->id,
                'payload_data' => [
                    'tanggal_mulai' => '2026-08-17',
                    'tanggal_selesai' => '2026-08-20',
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('letter_requests', [
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => 'pending',
        ]);

        $created = LetterRequest::where('template_id', $template->id)->latest('id')->first();
        $this->assertNotNull($created);
        $this->assertEquals('17 Agustus 2026', $created->payload_data['tanggal_mulai']);
        $this->assertEquals('20 Agustus 2026', $created->payload_data['tanggal_selesai']);
    }

    /**
     * Test Livewire edit form populates and saves date fields seamlessly.
     */
    public function test_livewire_edit_letter_request_with_date_fields(): void
    {
        $user = User::factory()->create(['role' => 'gukar']);
        $this->actingAs($user);

        $template = LetterTemplate::create([
            'name' => 'Surat Izin Cuti',
            'content' => '<p>Mulai: {{ tanggal_mulai }} | Selesai: {{ tanggal_selesai }}</p>',
            'variables' => ['tanggal_mulai', 'tanggal_selesai'],
            'is_active' => true,
        ]);

        $record = LetterRequest::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => 'pending',
            'payload_data' => [
                'tanggal_mulai' => '17 Agustus 2026',
                'tanggal_selesai' => '20 Agustus 2026',
            ],
        ]);

        Livewire::test(EditLetterRequest::class, ['record' => $record->getKey()])
            ->assertSet('data.payload_data.tanggal_mulai', '2026-08-17')
            ->assertSet('data.payload_data.tanggal_selesai', '2026-08-20')
            ->fillForm([
                'payload_data' => [
                    'tanggal_mulai' => '2026-08-18',
                    'tanggal_selesai' => '2026-08-21',
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $record->refresh();
        $this->assertEquals('18 Agustus 2026', $record->payload_data['tanggal_mulai']);
        $this->assertEquals('21 Agustus 2026', $record->payload_data['tanggal_selesai']);
    }
}
