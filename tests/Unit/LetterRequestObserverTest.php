<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\LetterRequest;
use App\Models\LetterStatusLog;
use App\Models\LetterTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterRequestObserverTest extends TestCase
{
    use RefreshDatabase;

    private function makeRequest(): LetterRequest
    {
        $user = User::factory()->create(['role' => 'gukar']);
        $template = LetterTemplate::create(['name' => 'T', 'content' => '<p>{{ nama }}</p>', 'is_active' => true]);

        return LetterRequest::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => 'pending',
            'payload_data' => [],
        ]);
    }

    public function test_log_is_created_when_letter_is_created(): void
    {
        $request = $this->makeRequest();

        $log = LetterStatusLog::query()->first();
        $this->assertNotNull($log);
        $this->assertSame($request->id, $log->letter_request_id);
        $this->assertNull($log->from_status);
        $this->assertSame('pending', $log->to_status);
        $this->assertSame('Pengajuan surat baru dibuat oleh pemohon', $log->note);
    }

    public function test_log_is_created_when_status_changes_with_authenticated_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $request = $this->makeRequest();
        $request->update(['status' => 'approved_admin']);

        $log = LetterStatusLog::query()->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame($request->id, $log->letter_request_id);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('pending', $log->from_status);
        $this->assertSame('approved_admin', $log->to_status);
    }

    public function test_no_log_is_created_when_status_is_unchanged(): void
    {
        $request = $this->makeRequest();
        $countBefore = LetterStatusLog::count();

        $request->update(['payload_data' => ['nama' => 'Budi']]);

        $this->assertSame($countBefore, LetterStatusLog::count());
    }

    public function test_log_records_system_user_when_no_authenticated_user(): void
    {
        $request = $this->makeRequest();
        $request->update(['status' => 'approved_admin']);

        $log = LetterStatusLog::query()->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertNull($log->user_id);
    }

    public function test_default_note_for_pending_to_approved_admin(): void
    {
        $request = $this->makeRequest();
        $request->update(['status' => 'approved_admin']);

        $this->assertSame('Disetujui oleh admin', LetterStatusLog::query()->latest('id')->first()->note);
    }

    public function test_default_note_for_pending_to_rejected(): void
    {
        $request = $this->makeRequest();
        $request->update(['status' => 'rejected']);

        $this->assertSame('Ditolak oleh admin', LetterStatusLog::query()->latest('id')->first()->note);
    }

    public function test_default_note_for_approved_to_signed(): void
    {
        $request = $this->makeRequest();
        $request->update(['status' => 'approved_admin']);
        $request->update(['status' => 'signed']);

        $this->assertSame('Ditandatangani oleh Kepala Sekolah', LetterStatusLog::query()->latest('id')->first()->note);
    }

    public function test_default_note_for_approved_to_rejected(): void
    {
        $request = $this->makeRequest();
        $request->update(['status' => 'approved_admin']);
        $request->update(['status' => 'rejected']);

        $this->assertSame(
            'Ditolak oleh Kepala Sekolah setelah disetujui admin',
            LetterStatusLog::query()->latest('id')->first()->note
        );
    }

    public function test_default_note_for_unknown_transition(): void
    {
        $request = $this->makeRequest();
        $request->update(['status' => 'signed']);

        $this->assertSame('Status berubah dari pending ke signed', LetterStatusLog::query()->latest('id')->first()->note);
    }
}