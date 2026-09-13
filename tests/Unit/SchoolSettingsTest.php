<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\SchoolSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SchoolSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_instance_returns_single_existing_record(): void
    {
        $instance = SchoolSettings::getInstance();

        $this->assertInstanceOf(SchoolSettings::class, $instance);
        $this->assertDatabaseCount('school_settings', 1);

        $instance->update(['nama_sekolah' => 'SMK Nusantara']);

        $this->assertSame('SMK Nusantara', SchoolSettings::getInstance()->nama_sekolah);
        $this->assertDatabaseCount('school_settings', 1);
    }

    public function test_get_instance_creates_one_record_with_defaults_when_table_is_empty(): void
    {
        SchoolSettings::query()->delete();
        SchoolSettings::clearInstanceCache();

        $instance = SchoolSettings::getInstance();

        $this->assertInstanceOf(SchoolSettings::class, $instance);
        $this->assertDatabaseCount('school_settings', 1);
        $this->assertSame('NAMA SEKOLAH', $instance->nama_sekolah);
        $this->assertSame('29.15', $instance->kode_sekolah);
        $this->assertSame(1, $instance->starting_letter_number);
        $this->assertSame('PEMERINTAH KOTA SURAKARTA', $instance->kop_line_1);
        $this->assertSame(75, $instance->logo_width);
        $this->assertSame(0, $instance->logo_offset_x);
        $this->assertSame(0, $instance->logo_offset_y);
        $this->assertSame('middle', $instance->logo_valign);
        $this->assertSame(10, $instance->kop_gap);
    }

    public function test_logo_customization_fields_stored_and_retrieved(): void
    {
        $settings = SchoolSettings::getInstance();
        $settings->update([
            'logo_width' => 95,
            'logo_offset_x' => 12,
            'logo_offset_y' => -5,
            'logo_valign' => 'top',
            'logo_kanan_width' => 85,
            'logo_kanan_offset_x' => -8,
            'logo_kanan_offset_y' => 4,
            'logo_kanan_valign' => 'bottom',
            'kop_gap' => 20,
        ]);

        $refreshed = SchoolSettings::getInstance();
        $this->assertSame(95, $refreshed->logo_width);
        $this->assertSame(12, $refreshed->logo_offset_x);
        $this->assertSame(-5, $refreshed->logo_offset_y);
        $this->assertSame('top', $refreshed->logo_valign);
        $this->assertSame(85, $refreshed->logo_kanan_width);
        $this->assertSame(-8, $refreshed->logo_kanan_offset_x);
        $this->assertSame(4, $refreshed->logo_kanan_offset_y);
        $this->assertSame('bottom', $refreshed->logo_kanan_valign);
        $this->assertSame(20, $refreshed->kop_gap);
    }

    public function test_logo_url_attribute_returns_null_when_empty(): void
    {
        $settings = SchoolSettings::getInstance();

        $this->assertNull($settings->logo_url);
    }

    public function test_logo_url_attribute_returns_storage_path_when_set(): void
    {
        $settings = SchoolSettings::getInstance();
        $settings->update(['logo_path' => 'logos/logo.png']);

        $this->assertStringContainsString('storage/logos/logo.png', $settings->logo_url);
    }

    public function test_logo_base64_returns_data_uri_when_file_exists(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('logos/test.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));

        $settings = SchoolSettings::getInstance();
        $settings->update(['logo_path' => 'logos/test.png']);

        $this->assertNotNull($settings->getLogoBase64());
        $this->assertStringStartsWith('data:image/png;base64,', $settings->getLogoBase64());
    }

    public function test_logo_kanan_base64_returns_data_uri_when_file_exists(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('logos/logo_kanan.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));

        $settings = SchoolSettings::getInstance();
        $settings->update(['logo_kanan_path' => 'logos/logo_kanan.png']);

        $this->assertNotNull($settings->getLogoKananBase64());
        $this->assertStringStartsWith('data:image/png;base64,', $settings->getLogoKananBase64());
        $this->assertStringContainsString('storage/logos/logo_kanan.png', $settings->logo_kanan_url);
    }

    public function test_ttd_kepsek_url_attribute(): void
    {
        $settings = SchoolSettings::getInstance();

        $this->assertNull($settings->ttd_kepsek_url);

        $settings->update(['ttd_kepsek_path' => 'ttd/kepsek.png']);
        $this->assertStringContainsString('storage/ttd/kepsek.png', $settings->ttd_kepsek_url);
    }

    public function test_ttd_kepsek_base64_returns_data_uri_when_file_exists(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('ttd/kepsek.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));

        $settings = SchoolSettings::getInstance();
        $settings->update(['ttd_kepsek_path' => 'ttd/kepsek.png']);

        $this->assertNotNull($settings->getTtdKepsekBase64());
        $this->assertStringStartsWith('data:image/png;base64,', $settings->getTtdKepsekBase64());
    }

    public function test_alamat_lengkap_combines_alamat_and_kode_pos(): void
    {
        $settings = SchoolSettings::getInstance();
        $settings->update(['alamat' => 'Jl. Merdeka No. 1', 'kode_pos' => '57100']);

        $this->assertSame('Jl. Merdeka No. 1, 57100', $settings->alamat_lengkap);
    }

    public function test_alamat_lengkap_without_kode_pos(): void
    {
        $settings = SchoolSettings::getInstance();
        $settings->update(['alamat' => 'Jl. Merdeka No. 1', 'kode_pos' => '']);

        $this->assertSame('Jl. Merdeka No. 1', $settings->alamat_lengkap);
    }

    public function test_kontak_lengkap_joins_available_contacts_with_separator(): void
    {
        $settings = SchoolSettings::getInstance();
        $settings->update([
            'telepon' => '0271-123456',
            'email' => 'info@sekolah.sch.id',
            'website' => 'www.sekolah.sch.id',
        ]);

        $this->assertSame(
            'Telp. 0271-123456 | info@sekolah.sch.id | www.sekolah.sch.id',
            $settings->kontak_lengkap
        );
    }

    public function test_kontak_lengkap_returns_empty_when_no_contacts(): void
    {
        $settings = SchoolSettings::getInstance();
        $settings->update(['telepon' => '', 'email' => '', 'website' => '']);

        $this->assertSame('', $settings->kontak_lengkap);
    }

    public function test_akreditasi_field_stored_and_retrieved(): void
    {
        $settings = SchoolSettings::getInstance();
        $settings->update(['akreditasi' => 'Terakreditasi "A"']);

        $this->assertSame('Terakreditasi "A"', $settings->fresh()->akreditasi);
    }

    public function test_formatted_akreditasi_handles_raw_grade_and_full_text(): void
    {
        $settings = SchoolSettings::getInstance();

        // 1. Single grade letter automatically prefixed
        $settings->akreditasi = 'A';
        $this->assertSame('Akreditasi: A', $settings->formatted_akreditasi);

        $settings->akreditasi = 'B (Baik)';
        $this->assertSame('Akreditasi: B (Baik)', $settings->formatted_akreditasi);

        // 2. Full text with 'akreditasi' or 'terakreditasi' kept as is
        $settings->akreditasi = 'Terakreditasi "A"';
        $this->assertSame('Terakreditasi "A"', $settings->formatted_akreditasi);

        $settings->akreditasi = 'Akreditasi Unggul';
        $this->assertSame('Akreditasi Unggul', $settings->formatted_akreditasi);

        // 3. Null or empty string returns null
        $settings->akreditasi = null;
        $this->assertNull($settings->formatted_akreditasi);

        $settings->akreditasi = '   ';
        $this->assertNull($settings->formatted_akreditasi);
    }
}