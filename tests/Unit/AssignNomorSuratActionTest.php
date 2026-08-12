<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Actions\Letter\AssignNomorSuratAction;
use App\Models\LetterRequest;
use App\Models\LetterTemplate;
use App\Models\SchoolSettings;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignNomorSuratActionTest extends TestCase
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

    private function makeRequest(LetterTemplate $template, array $payload = []): LetterRequest
    {
        return LetterRequest::create([
            'template_id' => $template->id,
            'status' => 'pending',
            'payload_data' => $payload,
        ]);
    }

    public function test_formats_nomor_surat_with_school_letter_code_and_year(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        SchoolSettings::getInstance()->update(['kode_sekolah' => '007']);
        $template = $this->makeTemplate('SPD');
        $request = $this->makeRequest($template);

        app(AssignNomorSuratAction::class)->execute($request);

        $expected = sprintf('007/001/SPD/%d', $request->created_at->year);
        $this->assertSame($expected, $request->fresh()->payload_data['nomor_surat']);
    }

    public function test_manual_nomor_surat_is_preserved(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $template = $this->makeTemplate('SK');
        $request = $this->makeRequest($template, ['nomor_surat' => 'Manual/123/2026']);

        app(AssignNomorSuratAction::class)->execute($request);

        $this->assertSame('Manual/123/2026', $request->fresh()->payload_data['nomor_surat']);
    }

    public function test_defaults_to_421_and_sk_when_settings_or_template_are_empty(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        SchoolSettings::getInstance()->update(['kode_sekolah' => '']);
        $template = LetterTemplate::create([
            'name' => 'Tanpa Kode',
            'letter_code' => '',
            'content' => '<p>{{ nomor_surat }}</p>',
            'is_active' => true,
        ]);
        $request = $this->makeRequest($template);

        app(AssignNomorSuratAction::class)->execute($request);

        $expected = sprintf('421/001/SK/%d', $request->created_at->year);
        $this->assertSame($expected, $request->fresh()->payload_data['nomor_surat']);
    }

    public function test_sequence_increments_per_template(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $template = $this->makeTemplate('SPD');
        $first = $this->makeRequest($template);
        $second = $this->makeRequest($template);

        app(AssignNomorSuratAction::class)->execute($first);
        $first->update(['status' => 'approved_admin']);
        app(AssignNomorSuratAction::class)->execute($second);

        $this->assertStringContainsString('/001/', $first->fresh()->payload_data['nomor_surat']);
        $this->assertStringContainsString('/002/', $second->fresh()->payload_data['nomor_surat']);
    }

    public function test_sequence_is_independent_between_templates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $templateA = $this->makeTemplate('SPD');
        $templateB = $this->makeTemplate('SK');
        $requestA = $this->makeRequest($templateA);
        $requestB = $this->makeRequest($templateB);

        app(AssignNomorSuratAction::class)->execute($requestA);
        $requestA->update(['status' => 'approved_admin']);
        app(AssignNomorSuratAction::class)->execute($requestB);

        $this->assertSame('421/001/SPD/' . $requestA->created_at->year, $requestA->fresh()->payload_data['nomor_surat']);
        $this->assertSame('421/001/SK/' . $requestB->created_at->year, $requestB->fresh()->payload_data['nomor_surat']);
    }

    public function test_sequence_is_reset_for_different_year(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        SchoolSettings::getInstance()->update(['kode_sekolah' => '421']);
        $template = $this->makeTemplate('SPD');
        $old = $this->makeRequest($template);
        $old->forceFill(['created_at' => Carbon::parse('2024-03-01 10:00:00')])->save();

        $recent = $this->makeRequest($template);

        app(AssignNomorSuratAction::class)->execute($old);
        $old->update(['status' => 'approved_admin']);
        app(AssignNomorSuratAction::class)->execute($recent);

        $this->assertSame('421/001/SPD/2024', $old->fresh()->payload_data['nomor_surat']);
        $this->assertSame('421/001/SPD/' . $recent->created_at->year, $recent->fresh()->payload_data['nomor_surat']);
    }

    public function test_sequence_does_not_reuse_number_of_rejected_letter_that_kept_its_number(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $template = $this->makeTemplate('SPD');

        $first = $this->makeRequest($template);
        app(AssignNomorSuratAction::class)->execute($first);
        $first->update(['status' => 'approved_admin']);

        $rejected = $this->makeRequest($template);
        app(AssignNomorSuratAction::class)->execute($rejected);
        $rejected->update(['status' => 'rejected']);

        $new = $this->makeRequest($template);
        app(AssignNomorSuratAction::class)->execute($new);

        $this->assertStringContainsString('/001/', $first->fresh()->payload_data['nomor_surat']);
        $this->assertStringContainsString('/002/', $rejected->fresh()->payload_data['nomor_surat']);
        $this->assertStringContainsString('/003/', $new->fresh()->payload_data['nomor_surat']);
    }
}