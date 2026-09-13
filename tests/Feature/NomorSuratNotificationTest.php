<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Letter\BroadcastLetterStatusAction;
use App\Models\LetterRequest;
use App\Models\LetterTemplate;
use App\Models\SchoolSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NomorSuratNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeTemplate(string $letterCode = 'SPD'): LetterTemplate
    {
        return LetterTemplate::create([
            'name' => 'Surat Tugas',
            'letter_code' => $letterCode,
            'content' => '<p>Nomor: {{ nomor_surat }}</p>',
            'variables' => ['nomor_surat'],
            'is_active' => true,
        ]);
    }

    private function makeRequest(User $gukar, LetterTemplate $template, array $payload = []): LetterRequest
    {
        return LetterRequest::create([
            'user_id' => $gukar->id,
            'template_id' => $template->id,
            'status' => 'pending',
            'payload_data' => $payload,
        ]);
    }

    public function test_nomor_surat_auto_generated_on_approval(): void
    {
        SchoolSettings::getInstance()->update(['kode_sekolah' => '29.15']);

        $gukar = User::factory()->create(['role' => 'gukar']);
        $template = $this->makeTemplate('SPD');
        $request = $this->makeRequest($gukar, $template);

        $this->actingAs(User::factory()->create(['role' => 'admin']));
        app(BroadcastLetterStatusAction::class)->execute($request, 'approved_admin');

        $request->refresh();
        $this->assertEquals('approved_admin', $request->status);
        $bulanRomawi = \App\Actions\Letter\AssignNomorSuratAction::toRomanMonth((int) $request->created_at->month);
        $this->assertSame(
            sprintf('001/29.15/E/%s/%d', $bulanRomawi, $request->created_at->year),
            $request->payload_data['nomor_surat']
        );
    }

    public function test_nomor_surat_sequence_increments_per_template_and_year(): void
    {
        SchoolSettings::getInstance()->update(['kode_sekolah' => '29.15']);

        $gukar = User::factory()->create(['role' => 'gukar']);
        $template = $this->makeTemplate('SPD');

        $first = $this->makeRequest($gukar, $template);
        $second = $this->makeRequest($gukar, $template);

        $this->actingAs(User::factory()->create(['role' => 'admin']));

        app(BroadcastLetterStatusAction::class)->execute($first, 'approved_admin');
        app(BroadcastLetterStatusAction::class)->execute($second, 'approved_admin');

        $this->assertStringStartsWith('001/29.15/', $first->fresh()->payload_data['nomor_surat']);
        $this->assertStringStartsWith('002/29.15/', $second->fresh()->payload_data['nomor_surat']);
    }

    public function test_manual_nomor_surat_is_preserved(): void
    {
        $gukar = User::factory()->create(['role' => 'gukar']);
        $template = $this->makeTemplate('SK');
        $request = $this->makeRequest($gukar, $template, ['nomor_surat' => 'Manual/123/2026']);

        $this->actingAs(User::factory()->create(['role' => 'admin']));
        app(BroadcastLetterStatusAction::class)->execute($request, 'approved_admin');

        $this->assertSame('Manual/123/2026', $request->fresh()->payload_data['nomor_surat']);
    }

    public function test_owner_receives_database_notification_on_approval(): void
    {
        $gukar = User::factory()->create(['role' => 'gukar']);
        $template = $this->makeTemplate();
        $request = $this->makeRequest($gukar, $template);

        $this->actingAs(User::factory()->create(['role' => 'admin']));
        app(BroadcastLetterStatusAction::class)->execute($request, 'approved_admin');

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $gukar->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_owner_receives_notification_on_rejection(): void
    {
        $gukar = User::factory()->create(['role' => 'gukar']);
        $template = $this->makeTemplate();
        $request = $this->makeRequest($gukar, $template);

        $this->actingAs(User::factory()->create(['role' => 'admin']));
        app(BroadcastLetterStatusAction::class)->execute($request, 'rejected');

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $gukar->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_admins_are_notified_when_letter_is_signed(): void
    {
        $gukar = User::factory()->create(['role' => 'gukar']);
        $admin = User::factory()->create(['role' => 'admin']);
        $kepsek = User::factory()->create(['role' => 'kepsek']);
        $template = $this->makeTemplate();
        $request = $this->makeRequest($gukar, $template);
        $request->update(['status' => 'approved_admin']);

        $this->actingAs($kepsek);
        app(BroadcastLetterStatusAction::class)->execute($request, 'signed');

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $gukar->id,
            'notifiable_type' => User::class,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $admin->id,
            'notifiable_type' => User::class,
        ]);
    }
}
