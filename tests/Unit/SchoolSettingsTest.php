<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\SchoolSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $instance = SchoolSettings::getInstance();

        $this->assertInstanceOf(SchoolSettings::class, $instance);
        $this->assertDatabaseCount('school_settings', 1);
        $this->assertSame('NAMA SEKOLAH', $instance->nama_sekolah);
        $this->assertSame('421', $instance->kode_sekolah);
        $this->assertSame('PEMERINTAH KOTA SURAKARTA', $instance->kop_line_1);
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

    public function test_ttd_kepsek_url_attribute(): void
    {
        $settings = SchoolSettings::getInstance();

        $this->assertNull($settings->ttd_kepsek_url);

        $settings->update(['ttd_kepsek_path' => 'ttd/kepsek.png']);
        $this->assertStringContainsString('storage/ttd/kepsek.png', $settings->ttd_kepsek_url);
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
}