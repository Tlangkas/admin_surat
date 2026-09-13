<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LetterRequest;
use App\Models\LetterTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verification_page_denies_access_without_signature(): void
    {
        $user = User::factory()->create(['role' => 'gukar']);
        $template = LetterTemplate::create([
            'name' => 'Template Test',
            'content' => '<p>Surat Test</p>',
            'variables' => ['nama'],
            'is_active' => true,
        ]);

        $letterRequest = LetterRequest::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => 'signed',
            'payload_data' => ['nama' => 'Test User'],
        ]);

        $response = $this->get('/letter/verify/' . $letterRequest->uuid);

        $response->assertStatus(403);
    }

    public function test_verification_page_allows_access_with_valid_signature(): void
    {
        $user = User::factory()->create(['role' => 'gukar']);
        $template = LetterTemplate::create([
            'name' => 'Template Test',
            'content' => '<p>Surat Test</p>',
            'variables' => ['nama'],
            'is_active' => true,
        ]);

        $letterRequest = LetterRequest::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => 'signed',
            'payload_data' => ['nama' => 'Test User'],
        ]);

        $url = $letterRequest->verificationUrl();

        $response = $this->get($url);

        $response->assertStatus(200);
        $response->assertSee('Verifikasi Keaslian Surat Digital');
        $response->assertSee($letterRequest->uuid);
    }

    public function test_verification_page_redirects_authenticated_user_to_signed_url_when_sig_missing(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $template = LetterTemplate::create([
            'name' => 'Template Test',
            'content' => '<p>Surat Test</p>',
            'variables' => ['nama'],
            'is_active' => true,
        ]);

        $letterRequest = LetterRequest::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => 'signed',
            'payload_data' => ['nama' => 'Test User'],
        ]);

        $response = $this->actingAs($user)->get('/letter/verify/' . $letterRequest->uuid);

        $response->assertRedirect($letterRequest->verificationUrl());
    }
}
