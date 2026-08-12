<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\LetterRequest;
use App\Models\LetterTemplate;
use App\Models\SchoolSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterRequestModelTest extends TestCase
{
    use RefreshDatabase;

    private function makeRequest(array $attributes = [], bool $withUserId = true): LetterRequest
    {
        $template = LetterTemplate::create([
            'name' => 'Template Model',
            'content' => '<p>{{ nama }}</p>',
            'is_active' => true,
        ]);

        $defaults = [
            'template_id' => $template->id,
            'status' => 'pending',
            'payload_data' => [],
        ];

        if ($withUserId) {
            $defaults['user_id'] = User::factory()->create(['role' => 'gukar'])->id;
        }

        return LetterRequest::create(array_merge($defaults, $attributes));
    }

    public function test_uuid_is_auto_generated_on_create(): void
    {
        $request = $this->makeRequest();

        $this->assertNotNull($request->uuid);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $request->uuid);
    }

    public function test_user_id_is_auto_assigned_when_authenticated(): void
    {
        $user = User::factory()->create(['role' => 'gukar']);
        $this->actingAs($user);

        $request = $this->makeRequest([], false);

        $this->assertSame($user->id, $request->user_id);
    }

    public function test_status_helpers(): void
    {
        $user = User::factory()->create(['role' => 'gukar']);

        $pending = $this->makeRequest(['user_id' => $user->id, 'status' => 'pending']);
        $approved = $this->makeRequest(['user_id' => $user->id, 'status' => 'approved_admin']);
        $signed = $this->makeRequest(['user_id' => $user->id, 'status' => 'signed']);
        $rejected = $this->makeRequest(['user_id' => $user->id, 'status' => 'rejected']);

        $this->assertTrue($pending->isPending());
        $this->assertFalse($pending->isApprovedAdmin());
        $this->assertFalse($pending->isSigned());
        $this->assertFalse($pending->isRejected());

        $this->assertTrue($approved->isApprovedAdmin());
        $this->assertTrue($signed->isSigned());
        $this->assertTrue($rejected->isRejected());
    }

    public function test_user_and_template_relations_are_loaded(): void
    {
        $user = User::factory()->create(['role' => 'gukar']);
        $template = LetterTemplate::create([
            'name' => 'Relasi',
            'content' => '<p>Konten</p>',
            'is_active' => true,
        ]);

        $request = $this->makeRequest(['user_id' => $user->id, 'template_id' => $template->id]);

        $this->assertTrue($request->user->is($user));
        $this->assertTrue($request->template->is($template));
    }

    public function test_verification_url_contains_route_with_valid_hmac_signature(): void
    {
        $user = User::factory()->create(['role' => 'gukar']);
        $request = $this->makeRequest(['user_id' => $user->id]);

        $url = $request->verificationUrl();

        $this->assertStringContainsString('/letter/verify/' . $request->uuid, $url);

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $this->assertArrayHasKey('sig', $query);

        $expectedSignature = hash_hmac('sha256', $request->uuid, config('app.key'));
        $this->assertTrue(hash_equals($expectedSignature, (string) $query['sig']));
    }

    public function test_render_content_falls_back_to_school_name_for_sekolah(): void
    {
        $user = User::factory()->create(['role' => 'gukar']);
        SchoolSettings::getInstance()->update(['nama_sekolah' => 'SDN Contoh 01']);

        $template = LetterTemplate::create([
            'name' => 'Fallback Sekolah',
            'content' => '<p>Sekolah: {{ sekolah }}</p>',
            'is_active' => true,
        ]);

        $request = $this->makeRequest(['user_id' => $user->id, 'template_id' => $template->id, 'payload_data' => ['nama' => 'Budi']]);

        $this->assertStringContainsString('Sekolah: SDN Contoh 01', $request->renderContent());
    }

    public function test_render_content_removes_duplicate_kop_ttd_and_outer_html(): void
    {
        $user = User::factory()->create(['role' => 'gukar']);
        SchoolSettings::getInstance()->update(['nama_sekolah' => '' ]);

        $template = LetterTemplate::create([
            'name' => 'Konten Kotor',
            'content' => <<<'HTML'
<!DOCTYPE html>
<html>
<head><title>Kop</title></head>
<body>
<div class="kop"><h2>PEMERINTAH</h2></div>
<div class="header-table"><table><tr><td>kop</td></tr></table></div>
<div class="ttd"><p>ttd kepsek</p></div>
<p>Isi surat: {{ nama }}</p>
</body>
</html>
HTML,
            'variables' => ['nama'],
            'is_active' => true,
        ]);

        $request = $this->makeRequest(['user_id' => $user->id, 'template_id' => $template->id, 'payload_data' => ['nama' => 'Siti']]);

        $rendered = $request->renderContent();

        $this->assertStringContainsString('Isi surat: Siti', $rendered);
        $this->assertStringNotContainsString('<!DOCTYPE', $rendered);
        $this->assertStringNotContainsString('<html', $rendered);
        $this->assertStringNotContainsString('<head', $rendered);
        $this->assertStringNotContainsString('<body', $rendered);
        $this->assertStringNotContainsString('class="kop"', $rendered);
        $this->assertStringNotContainsString('class="header-table"', $rendered);
        $this->assertStringNotContainsString('class="ttd"', $rendered);
        $this->assertStringNotContainsString('ttd kepsek', $rendered);
        $this->assertStringNotContainsString('PEMERINTAH', $rendered);
    }

    public function test_render_content_replaces_unknown_variables_with_empty_string(): void
    {
        $user = User::factory()->create(['role' => 'gukar']);
        $template = LetterTemplate::create([
            'name' => 'Unknown Vars',
            'content' => '<p>Halo {{ tidak_diketahui }} dan {{ nama }}</p>',
            'is_active' => true,
        ]);

        $request = $this->makeRequest(['user_id' => $user->id, 'template_id' => $template->id, 'payload_data' => ['nama' => 'Ahmad']]);

        $rendered = $request->renderContent();

        $this->assertStringContainsString('Halo  dan Ahmad', $rendered);
        $this->assertStringNotContainsString('{{', $rendered);
    }

    public function test_render_content_adds_table_data_class(): void
    {
        $user = User::factory()->create(['role' => 'gukar']);
        $template = LetterTemplate::create([
            'name' => 'Table Class',
            'content' => '<table><tr><td>{{ nama }}</td></tr></table>',
            'is_active' => true,
        ]);

        $request = $this->makeRequest(['user_id' => $user->id, 'template_id' => $template->id, 'payload_data' => ['nama' => 'Sari']]);

        $this->assertStringContainsString('<table class="table-data">', $request->renderContent());
    }

    public function test_render_content_escapes_html_and_converts_newlines(): void
    {
        $user = User::factory()->create(['role' => 'gukar']);
        $template = LetterTemplate::create([
            'name' => 'Escape Multiline',
            'content' => '<p>{{ keterangan }}</p>',
            'is_active' => true,
        ]);

        $request = $this->makeRequest([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'payload_data' => ['keterangan' => "Baris 1\n<script>alert(1)</script>"],
        ]);

        $rendered = $request->renderContent();

        $this->assertStringContainsString('&lt;script&gt;', $rendered);
        $this->assertStringNotContainsString('<script>', $rendered);
        $this->assertStringContainsString('<br />', $rendered);
    }
}