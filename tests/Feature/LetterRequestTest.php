<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Letter\BroadcastLetterStatusAction;
use App\Filament\Resources\LetterRequestResource;
use App\Models\Karyawan;
use App\Models\LetterRequest;
use App\Models\LetterTemplate;
use App\Models\SchoolSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_and_karyawan_data_are_automatically_linked(): void
    {
        $user = User::factory()->create([
            'name' => 'Siti Aminah',
            'role' => 'gukar',
        ]);

        Karyawan::create([
            'user_id' => $user->id,
            'nama' => 'Siti Aminah',
            'nip' => '199202022018022002',
            'jabatan' => 'Guru Biologi',
        ]);

        $profileData = $user->getProfileData();

        $this->assertEquals('Siti Aminah', $profileData['nama']);
        $this->assertEquals('199202022018022002', $profileData['nip']);
        $this->assertEquals('Guru Biologi', $profileData['jabatan']);
    }

    public function test_letter_template_automatically_extracts_variables_on_save(): void
    {
        $template = LetterTemplate::create([
            'name' => 'Template Auto Extract Test',
            'content' => '<div>Nomor: {{ nomor_surat }}</div><p>Nama: {{ nama }}</p><p>NIP: {{ nip }}</p><p>Jabatan: {{ jabatan }}</p>',
            'is_active' => true,
        ]);

        $this->assertIsArray($template->variables);
        $this->assertContains('nomor_surat', $template->variables);
        $this->assertContains('nama', $template->variables);
        $this->assertContains('nip', $template->variables);
        $this->assertContains('jabatan', $template->variables);
        $this->assertCount(4, $template->variables);
    }

    public function test_gukar_profile_data_is_prefilled_on_user_profile_helper(): void
    {
        $user = User::factory()->create(['role' => 'gukar']);

        Karyawan::create([
            'user_id' => $user->id,
            'nama' => 'Budi Santoso',
            'nip' => '198501012010011001',
            'jabatan' => 'Guru Matematika',
        ]);

        $settings = SchoolSettings::getInstance();
        $settings->update([
            'nama_sekolah' => 'SMK Negeri 1 Jakarta',
        ]);

        $profileData = $user->getProfileData();

        $this->assertIsArray($profileData);
        $this->assertEquals('Budi Santoso', $profileData['nama']);
        $this->assertEquals('198501012010011001', $profileData['nip']);
        $this->assertEquals('Guru Matematika', $profileData['jabatan']);
        $this->assertEquals('SMK Negeri 1 Jakarta', $profileData['sekolah']);
    }

    public function test_gukar_can_create_letter_request_with_auto_user_id_association(): void
    {
        $gukar = User::factory()->create(['role' => 'gukar']);

        $template = LetterTemplate::create([
            'name' => 'Surat Keterangan Active',
            'content' => '<p>Konten {{ nama }}</p>',
            'variables' => ['nama'],
            'is_active' => true,
        ]);

        $this->actingAs($gukar);

        $letterRequest = LetterRequest::create([
            'template_id' => $template->id,
            'status' => 'pending',
            'payload_data' => ['nama' => $gukar->name],
        ]);

        $this->assertEquals($gukar->id, $letterRequest->user_id);
        $this->assertDatabaseHas('letter_requests', [
            'id' => $letterRequest->id,
            'user_id' => $gukar->id,
            'template_id' => $template->id,
        ]);
    }

    public function test_gukar_can_customize_prefilled_profile_data_in_payload(): void
    {
        $gukar = User::factory()->create(['role' => 'gukar']);

        Karyawan::create([
            'user_id' => $gukar->id,
            'nama' => 'Dewi Lestari',
            'nip' => '199001012015012001',
            'jabatan' => 'Guru Bahasa',
        ]);

        $this->actingAs($gukar);

        $template = LetterTemplate::create([
            'name' => 'Surat Izin',
            'content' => '<p>Surat Izin untuk {{ nama }}</p>',
            'variables' => ['nama', 'nip', 'jabatan', 'sekolah', 'keperluan'],
            'is_active' => true,
        ]);

        $customPayload = [
            'nama' => 'Dewi Lestari M.Pd',
            'nip' => '199001012015012001',
            'jabatan' => 'Ketua Laboratorium Bahasa',
            'sekolah' => 'SMA Merdeka 1',
            'keperluan' => 'Izin Penelitian Pendidikan',
        ];

        $letterRequest = LetterRequest::create([
            'template_id' => $template->id,
            'status' => 'pending',
            'payload_data' => $customPayload,
        ]);

        $this->assertEquals($customPayload, $letterRequest->fresh()->payload_data);
        $this->assertEquals('Dewi Lestari M.Pd', $letterRequest->fresh()->payload_data['nama']);
        $this->assertEquals('Ketua Laboratorium Bahasa', $letterRequest->fresh()->payload_data['jabatan']);
        $this->assertEquals('Izin Penelitian Pendidikan', $letterRequest->fresh()->payload_data['keperluan']);
    }

    public function test_letter_request_renders_content_with_escaped_multiline_and_table_styling(): void
    {
        $user = User::factory()->create(['role' => 'gukar']);

        $template = LetterTemplate::create([
            'name' => 'Surat Permohonan',
            'content' => '<h3>Surat Permohonan</h3><p>Yang bertanda tangan di bawah ini:</p><table><tr><td>Nama: {{ nama }}</td></tr></table><p>Keterangan: {{ keterangan }}</p>',
            'variables' => ['nama', 'keterangan'],
            'is_active' => true,
        ]);

        $multilineWithHtml = "Baris 1: Kebutuhan Alat\n<script>alert('xss')</script>\nBaris 3: Selesai";

        $letterRequest = LetterRequest::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => 'pending',
            'payload_data' => [
                'nama' => 'Budi Utama',
                'keterangan' => $multilineWithHtml,
            ],
        ]);

        $renderedContent = $letterRequest->renderContent();

        // 1. Placeholder substitution
        $this->assertStringContainsString('Budi Utama', $renderedContent);

        // 2. HTML escaping (<script> converted to &lt;script&gt;)
        $this->assertStringNotContainsString('<script>', $renderedContent);
        $this->assertStringContainsString('&lt;script&gt;', $renderedContent);

        // 3. Multiline formatting (newlines converted to <br />)
        $this->assertStringContainsString('<br />', $renderedContent);

        // 4. Table styling (added table-data class)
        $this->assertStringContainsString('<table class="table-data"', $renderedContent);
    }

    public function test_letter_request_verification_route_renders_payload_details(): void
    {
        $user = User::factory()->create(['role' => 'gukar']);

        $template = LetterTemplate::create([
            'name' => 'Surat Tugas Verifikasi',
            'content' => '<p>Isi surat tugas</p>',
            'variables' => ['nama', 'nip', 'jabatan', 'tugas_khusus'],
            'is_active' => true,
        ]);

        $letterRequest = LetterRequest::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => 'signed',
            'payload_data' => [
                'nama' => 'Ahmad Dahlan',
                'nip' => '197505052000031002',
                'jabatan' => 'Guru Fisika',
                'sekolah' => 'SMK Garuda 2',
                'tugas_khusus' => 'Pendamping Olimpiade Sains',
            ],
        ]);

        $verificationUrl = $letterRequest->verificationUrl();

        $response = $this->get($verificationUrl);

        $response->assertStatus(200);
        $response->assertSee('197505052000031002');
        $response->assertSee('Guru Fisika');
        $response->assertSee('Tugas Khusus');
        $response->assertSee('Pendamping Olimpiade Sains');
    }

    public function test_gukar_query_scope_restricts_access_to_own_records(): void
    {
        $gukar1 = User::factory()->create(['role' => 'gukar']);
        $gukar2 = User::factory()->create(['role' => 'gukar']);
        $admin = User::factory()->create(['role' => 'admin']);

        $template = LetterTemplate::create([
            'name' => 'Template Scope Test',
            'content' => '<p>Konten</p>',
            'variables' => [],
            'is_active' => true,
        ]);

        $request1 = LetterRequest::create([
            'user_id' => $gukar1->id,
            'template_id' => $template->id,
            'status' => 'pending',
            'payload_data' => [],
        ]);

        $request2 = LetterRequest::create([
            'user_id' => $gukar2->id,
            'template_id' => $template->id,
            'status' => 'pending',
            'payload_data' => [],
        ]);

        // Authenticated as gukar1
        $this->actingAs($gukar1);
        $scopedQuery1 = LetterRequestResource::getEloquentQuery()->get();

        $this->assertTrue($scopedQuery1->contains('id', $request1->id));
        $this->assertFalse($scopedQuery1->contains('id', $request2->id));

        // Authenticated as admin (unrestricted)
        $this->actingAs($admin);
        $scopedQueryAdmin = LetterRequestResource::getEloquentQuery()->get();

        $this->assertTrue($scopedQueryAdmin->contains('id', $request1->id));
        $this->assertTrue($scopedQueryAdmin->contains('id', $request2->id));
    }

    public function test_letter_status_transition_triggers_audit_log_observer(): void
    {
        $user = User::factory()->create(['role' => 'gukar']);
        $admin = User::factory()->create(['role' => 'admin']);

        $template = LetterTemplate::create([
            'name' => 'Surat Keterangan',
            'content' => '<p>Konten</p>',
            'variables' => [],
            'is_active' => true,
        ]);

        $letterRequest = LetterRequest::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => 'pending',
            'payload_data' => [],
        ]);

        $this->actingAs($admin);

        app(BroadcastLetterStatusAction::class)->execute($letterRequest, 'approved_admin');

        $this->assertEquals('approved_admin', $letterRequest->fresh()->status);
        $this->assertDatabaseHas('letter_status_logs', [
            'letter_request_id' => $letterRequest->id,
            'user_id' => $admin->id,
            'from_status' => 'pending',
            'to_status' => 'approved_admin',
        ]);
    }

    public function test_admin_can_approve_pending_letter_policy(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $gukar = User::factory()->create(['role' => 'gukar']);

        $template = LetterTemplate::create([
            'name' => 'Surat Tugas',
            'content' => '<p>Test</p>',
            'variables' => [],
            'is_active' => true,
        ]);

        $letterRequest = LetterRequest::create([
            'user_id' => $gukar->id,
            'template_id' => $template->id,
            'status' => 'pending',
            'payload_data' => [],
        ]);

        $this->assertTrue($admin->can('approveAdmin', $letterRequest));
        $this->assertTrue($admin->can('reject', $letterRequest));
        $this->assertFalse($gukar->can('approveAdmin', $letterRequest));
    }

    public function test_kepsek_can_sign_approved_letter_policy(): void
    {
        $kepsek = User::factory()->create(['role' => 'kepsek']);
        $gukar = User::factory()->create(['role' => 'gukar']);

        $template = LetterTemplate::create([
            'name' => 'Surat Tugas',
            'content' => '<p>Test</p>',
            'variables' => [],
            'is_active' => true,
        ]);

        $letterRequest = LetterRequest::create([
            'user_id' => $gukar->id,
            'template_id' => $template->id,
            'status' => 'approved_admin',
            'payload_data' => [],
        ]);

        $this->assertTrue($kepsek->can('sign', $letterRequest));
        $this->assertTrue($kepsek->can('previewPdf', $letterRequest));
        $this->assertFalse($gukar->can('sign', $letterRequest));
    }
}
