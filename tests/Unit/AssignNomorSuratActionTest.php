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

    private function makeTemplate(string $letterCode = 'SPD', ?string $classificationCode = 'E'): LetterTemplate
    {
        return LetterTemplate::create([
            'name' => 'Surat Tugas',
            'letter_code' => $letterCode,
            'classification_code' => $classificationCode,
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

    public function test_formats_nomor_surat_with_official_surat_2025_pattern(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        SchoolSettings::getInstance()->update(['kode_sekolah' => '29.15']);
        $template = $this->makeTemplate('ST-GUKAR', 'E');
        $request = $this->makeRequest($template);

        app(AssignNomorSuratAction::class)->execute($request);

        $bulanRomawi = AssignNomorSuratAction::toRomanMonth((int) $request->created_at->month);
        $expected = sprintf('001/29.15/E/%s/%d', $bulanRomawi, $request->created_at->year);
        $this->assertSame($expected, $request->fresh()->payload_data['nomor_surat']);
    }

    public function test_manual_nomor_surat_is_preserved(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $template = $this->makeTemplate('SK', 'C');
        $request = $this->makeRequest($template, ['nomor_surat' => 'Manual/123/2026']);

        app(AssignNomorSuratAction::class)->execute($request);

        $this->assertSame('Manual/123/2026', $request->fresh()->payload_data['nomor_surat']);
    }

    public function test_defaults_to_29_15_and_E_when_settings_or_template_are_empty(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        SchoolSettings::getInstance()->update(['kode_sekolah' => '']);
        $template = LetterTemplate::create([
            'name' => 'Tanpa Kode',
            'letter_code' => '',
            'classification_code' => null,
            'content' => '<p>{{ nomor_surat }}</p>',
            'is_active' => true,
        ]);
        $request = $this->makeRequest($template);

        app(AssignNomorSuratAction::class)->execute($request);

        $bulanRomawi = AssignNomorSuratAction::toRomanMonth((int) $request->created_at->month);
        $expected = sprintf('001/29.15/E/%s/%d', $bulanRomawi, $request->created_at->year);
        $this->assertSame($expected, $request->fresh()->payload_data['nomor_surat']);
    }

    public function test_sequence_increments_globally_across_templates_within_year(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $templateA = $this->makeTemplate('ST-GUKAR', 'E');
        $templateB = $this->makeTemplate('SP-ORTU', 'G');

        $first = $this->makeRequest($templateA);
        $second = $this->makeRequest($templateB);

        app(AssignNomorSuratAction::class)->execute($first);
        $first->update(['status' => 'approved_admin']);
        app(AssignNomorSuratAction::class)->execute($second);

        $this->assertStringStartsWith('001/29.15/E/', $first->fresh()->payload_data['nomor_surat']);
        $this->assertStringStartsWith('002/29.15/G/', $second->fresh()->payload_data['nomor_surat']);
    }

    public function test_starting_letter_number_is_respected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        SchoolSettings::getInstance()->update([
            'kode_sekolah' => '29.15',
            'starting_letter_number' => 350,
        ]);

        $template = $this->makeTemplate('SPPD', 'E');
        $request = $this->makeRequest($template);

        app(AssignNomorSuratAction::class)->execute($request);

        $bulanRomawi = AssignNomorSuratAction::toRomanMonth((int) $request->created_at->month);
        $expected = sprintf('350/29.15/E/%s/%d', $bulanRomawi, $request->created_at->year);
        $this->assertSame($expected, $request->fresh()->payload_data['nomor_surat']);
    }

    public function test_sequence_is_reset_for_different_year(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        SchoolSettings::getInstance()->update(['kode_sekolah' => '29.15']);
        $template = $this->makeTemplate('SPD', 'E');

        $old = $this->makeRequest($template);
        $old->forceFill(['created_at' => Carbon::parse('2024-03-01 10:00:00')])->save();

        $recent = $this->makeRequest($template);

        app(AssignNomorSuratAction::class)->execute($old);
        $old->update(['status' => 'approved_admin']);
        app(AssignNomorSuratAction::class)->execute($recent);

        $this->assertSame('001/29.15/E/III/2024', $old->fresh()->payload_data['nomor_surat']);

        $recentBulan = AssignNomorSuratAction::toRomanMonth((int) $recent->created_at->month);
        $this->assertSame('001/29.15/E/' . $recentBulan . '/' . $recent->created_at->year, $recent->fresh()->payload_data['nomor_surat']);
    }

    public function test_sequence_does_not_reuse_number_of_rejected_letter_that_kept_its_number(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $template = $this->makeTemplate('SPD', 'E');

        $first = $this->makeRequest($template);
        app(AssignNomorSuratAction::class)->execute($first);
        $first->update(['status' => 'approved_admin']);

        $rejected = $this->makeRequest($template);
        app(AssignNomorSuratAction::class)->execute($rejected);
        $rejected->update(['status' => 'rejected']);

        $new = $this->makeRequest($template);
        app(AssignNomorSuratAction::class)->execute($new);

        $this->assertStringStartsWith('001/', $first->fresh()->payload_data['nomor_surat']);
        $this->assertStringStartsWith('002/', $rejected->fresh()->payload_data['nomor_surat']);
        $this->assertStringStartsWith('003/', $new->fresh()->payload_data['nomor_surat']);
    }

    public function test_roman_month_conversion_for_all_twelve_months(): void
    {
        $expectedMap = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
            5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
            9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];

        foreach ($expectedMap as $month => $roman) {
            $this->assertSame($roman, AssignNomorSuratAction::toRomanMonth($month));
        }

        $this->assertSame('I', AssignNomorSuratAction::toRomanMonth(0));
        $this->assertSame('I', AssignNomorSuratAction::toRomanMonth(13));
    }
}