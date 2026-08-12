<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\LetterRequest;
use App\Models\LetterTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterRequestPolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $kepsek;
    private User $owner;
    private User $otherGukar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->kepsek = User::factory()->create(['role' => 'kepsek']);
        $this->owner = User::factory()->create(['role' => 'gukar']);
        $this->otherGukar = User::factory()->create(['role' => 'gukar']);
    }

    private function makeRequest(string $status = 'pending', ?User $owner = null, ?string $pdfPath = null): LetterRequest
    {
        $template = LetterTemplate::create(['name' => 'T', 'content' => '<p>{{ nama }}</p>', 'is_active' => true]);

        return LetterRequest::create([
            'user_id' => ($owner ?? $this->owner)->id,
            'template_id' => $template->id,
            'status' => $status,
            'payload_data' => [],
            'pdf_path' => $pdfPath,
        ]);
    }

    public function test_view_any_is_allowed_for_all_roles(): void
    {
        foreach ([$this->admin, $this->kepsek, $this->owner] as $user) {
            $this->assertTrue($user->can('viewAny', LetterRequest::class));
        }
    }

    public function test_view_permission(): void
    {
        $request = $this->makeRequest();

        $this->assertTrue($this->admin->can('view', $request));
        $this->assertTrue($this->kepsek->can('view', $request));
        $this->assertTrue($this->owner->can('view', $request));
        $this->assertFalse($this->otherGukar->can('view', $request));
    }

    public function test_create_permission(): void
    {
        $this->assertTrue($this->admin->can('create', LetterRequest::class));
        $this->assertTrue($this->owner->can('create', LetterRequest::class));
        $this->assertFalse($this->kepsek->can('create', LetterRequest::class));
    }

    public function test_update_permission_for_pending_records(): void
    {
        $request = $this->makeRequest('pending');

        $this->assertTrue($this->admin->can('update', $request));
        $this->assertTrue($this->owner->can('update', $request));
        $this->assertFalse($this->otherGukar->can('update', $request));
        $this->assertFalse($this->kepsek->can('update', $request));
    }

    public function test_update_is_denied_when_not_pending(): void
    {
        $request = $this->makeRequest('approved_admin');

        $this->assertFalse($this->admin->can('update', $request));
        $this->assertFalse($this->owner->can('update', $request));
    }

    public function test_delete_permission_for_pending_records(): void
    {
        $request = $this->makeRequest('pending');

        $this->assertTrue($this->admin->can('delete', $request));
        $this->assertTrue($this->owner->can('delete', $request));
        $this->assertFalse($this->otherGukar->can('delete', $request));

        $signed = $this->makeRequest('signed');
        $this->assertFalse($this->admin->can('delete', $signed));
        $this->assertFalse($this->owner->can('delete', $signed));
    }

    public function test_approve_admin_permission(): void
    {
        $pending = $this->makeRequest('pending');
        $this->assertTrue($this->admin->can('approveAdmin', $pending));
        $this->assertFalse($this->kepsek->can('approveAdmin', $pending));
        $this->assertFalse($this->owner->can('approveAdmin', $pending));

        $approved = $this->makeRequest('approved_admin');
        $this->assertFalse($this->admin->can('approveAdmin', $approved));
    }

    public function test_reject_permission(): void
    {
        $pending = $this->makeRequest('pending');
        $this->assertTrue($this->admin->can('reject', $pending));
        $this->assertFalse($this->kepsek->can('reject', $pending));
        $this->assertFalse($this->owner->can('reject', $pending));

        $approved = $this->makeRequest('approved_admin');
        $this->assertTrue($this->admin->can('reject', $approved));
        $this->assertTrue($this->kepsek->can('reject', $approved));
        $this->assertFalse($this->owner->can('reject', $approved));

        $signed = $this->makeRequest('signed');
        $this->assertFalse($this->admin->can('reject', $signed));
        $this->assertFalse($this->kepsek->can('reject', $signed));
    }

    public function test_sign_permission(): void
    {
        $approved = $this->makeRequest('approved_admin');
        $this->assertTrue($this->kepsek->can('sign', $approved));
        $this->assertTrue($this->admin->can('sign', $approved));
        $this->assertFalse($this->owner->can('sign', $approved));

        $pending = $this->makeRequest('pending');
        $this->assertFalse($this->kepsek->can('sign', $pending));
    }

    public function test_preview_pdf_permission(): void
    {
        $pending = $this->makeRequest('pending');
        $this->assertFalse($this->admin->can('previewPdf', $pending));

        $approved = $this->makeRequest('approved_admin');
        $this->assertTrue($this->admin->can('previewPdf', $approved));
        $this->assertTrue($this->kepsek->can('previewPdf', $approved));
        $this->assertTrue($this->owner->can('previewPdf', $approved));
        $this->assertFalse($this->otherGukar->can('previewPdf', $approved));

        $signed = $this->makeRequest('signed');
        $this->assertTrue($this->owner->can('previewPdf', $signed));
    }

    public function test_download_pdf_permission(): void
    {
        $approved = $this->makeRequest('approved_admin');
        $this->assertFalse($this->admin->can('downloadPdf', $approved));

        $signedNoPdf = $this->makeRequest('signed');
        $this->assertFalse($this->owner->can('downloadPdf', $signedNoPdf));

        $signedWithPdf = $this->makeRequest('signed', pdfPath: 'pdfs/letter.pdf');
        $this->assertTrue($this->owner->can('downloadPdf', $signedWithPdf));
        $this->assertTrue($this->admin->can('downloadPdf', $signedWithPdf));
        $this->assertFalse($this->otherGukar->can('downloadPdf', $signedWithPdf));
    }

    public function test_restore_and_force_delete_are_admin_only(): void
    {
        $request = $this->makeRequest();

        $this->assertTrue($this->admin->can('restore', $request));
        $this->assertTrue($this->admin->can('forceDelete', $request));

        $this->assertFalse($this->kepsek->can('restore', $request));
        $this->assertFalse($this->kepsek->can('forceDelete', $request));
        $this->assertFalse($this->owner->can('restore', $request));
        $this->assertFalse($this->owner->can('forceDelete', $request));
    }
}