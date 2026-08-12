<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Actions\Letter\BroadcastLetterStatusAction;
use App\Models\LetterRequest;
use App\Models\LetterTemplate;
use App\Models\SchoolSettings;
use App\Models\User;
use Illuminate\Broadcasting\PendingBroadcast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use ReflectionMethod;
use Tests\TestCase;

class BroadcastLetterStatusActionTest extends TestCase
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

    private function makeRequest(User $gukar, LetterTemplate $template, string $status = 'pending'): LetterRequest
    {
        return LetterRequest::create([
            'user_id' => $gukar->id,
            'template_id' => $template->id,
            'status' => $status,
            'payload_data' => [],
        ]);
    }

    public function test_approval_updates_status_and_notifies_owner(): void
    {
        SchoolSettings::getInstance()->update(['kode_sekolah' => '421']);
        $gukar = User::factory()->create(['role' => 'gukar']);
        $request = $this->makeRequest($gukar, $this->makeTemplate());

        $this->actingAs(User::factory()->create(['role' => 'admin']));
        app(BroadcastLetterStatusAction::class)->execute($request, 'approved_admin');

        $this->assertSame('approved_admin', $request->fresh()->status);
        $notification = $gukar->fresh()->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame('Surat Anda Disetujui', $notification->data['title']);
    }

    public function test_rejection_updates_status_and_notifies_owner(): void
    {
        SchoolSettings::getInstance()->update(['kode_sekolah' => '421']);
        $gukar = User::factory()->create(['role' => 'gukar']);
        $request = $this->makeRequest($gukar, $this->makeTemplate());

        $this->actingAs(User::factory()->create(['role' => 'admin']));
        app(BroadcastLetterStatusAction::class)->execute($request, 'rejected');

        $this->assertSame('rejected', $request->fresh()->status);
        $notification = $gukar->fresh()->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame('Surat Anda Ditolak', $notification->data['title']);
    }

    public function test_signed_updates_status_and_notifies_owner_and_all_admins(): void
    {
        SchoolSettings::getInstance()->update(['kode_sekolah' => '421']);
        $gukar = User::factory()->create(['role' => 'gukar']);
        $adminOne = User::factory()->create(['role' => 'admin']);
        $adminTwo = User::factory()->create(['role' => 'admin']);
        $kepsek = User::factory()->create(['role' => 'kepsek']);
        $request = $this->makeRequest($gukar, $this->makeTemplate(), 'approved_admin');

        $this->actingAs($kepsek);
        app(BroadcastLetterStatusAction::class)->execute($request, 'signed');

        $this->assertSame('signed', $request->fresh()->status);

        $this->assertSame('Surat Anda Ditandatangani', $gukar->fresh()->notifications()->first()->data['title']);
        $this->assertSame('Surat Telah Ditandatangani', $adminOne->fresh()->notifications()->first()->data['title']);
        $this->assertSame('Surat Telah Ditandatangani', $adminTwo->fresh()->notifications()->first()->data['title']);
        $this->assertCount(0, $kepsek->fresh()->notifications);
    }

    public function test_broadcast_failure_does_not_break_status_update(): void
    {
        SchoolSettings::getInstance()->update(['kode_sekolah' => '421']);
        $gukar = User::factory()->create(['role' => 'gukar']);
        $request = $this->makeRequest($gukar, $this->makeTemplate());

        Event::listen(PendingBroadcast::class, function (): void {
            throw new \RuntimeException('Reverb tidak tersedia');
        });
        Log::shouldReceive('warning')->once();

        $this->actingAs(User::factory()->create(['role' => 'admin']));
        app(BroadcastLetterStatusAction::class)->execute($request, 'approved_admin');

        $this->assertSame('approved_admin', $request->fresh()->status);
        $this->assertNotNull($gukar->fresh()->notifications()->first());
    }

    public function test_send_notifications_skips_silently_when_owner_is_null(): void
    {
        $request = new LetterRequest(['status' => 'rejected', 'payload_data' => [], 'uuid' => 'abc']);

        $this->assertNull($request->user);

        $action = app(BroadcastLetterStatusAction::class);
        $method = new ReflectionMethod($action, 'sendNotifications');
        $method->invoke($action, $request, 'rejected');

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_unknown_status_still_updates_status_without_notifying(): void
    {
        SchoolSettings::getInstance()->update(['kode_sekolah' => '421']);
        $gukar = User::factory()->create(['role' => 'gukar']);
        $request = $this->makeRequest($gukar, $this->makeTemplate());

        app(BroadcastLetterStatusAction::class)->execute($request, 'weird_status');

        $this->assertSame('weird_status', $request->fresh()->status);
        $this->assertCount(0, $gukar->fresh()->notifications);
    }
}