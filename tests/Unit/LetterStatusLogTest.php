<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\LetterRequest;
use App\Models\LetterStatusLog;
use App\Models\LetterTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterStatusLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_relations_resolve_letter_request_and_user(): void
    {
        $user = User::factory()->create(['role' => 'gukar']);
        $admin = User::factory()->create(['role' => 'admin']);
        $template = LetterTemplate::create(['name' => 'T', 'content' => '<p>{{ nama }}</p>', 'is_active' => true]);
        $request = LetterRequest::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => 'pending',
            'payload_data' => [],
        ]);

        $log = LetterStatusLog::create([
            'letter_request_id' => $request->id,
            'user_id' => $admin->id,
            'from_status' => 'pending',
            'to_status' => 'approved_admin',
        ]);

        $this->assertTrue($log->letterRequest->is($request));
        $this->assertTrue($log->user->is($admin));
    }
}