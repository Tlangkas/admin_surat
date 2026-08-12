<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom kop surat yang dapat dikonfigurasi ke tabel school_settings.
     *
     * - kota_kabupaten  : nama kota/kabupaten untuk baris tanggal & tempat surat.
     * - kop_line_1      : baris pertama kop (mis. PEMERINTAH KOTA SURAKARTA).
     * - kop_line_2      : baris kedua kop (mis. DINAS PENDIDIKAN).
     * - kode_sekolah    : kode instansi untuk nomor surat otomatis (mis. 421).
     */
    public function up(): void
    {
        Schema::table('school_settings', function (Blueprint $table) {
            $table->string('kota_kabupaten')->nullable()->after('website');
            $table->string('kop_line_1')->nullable()->after('kota_kabupaten');
            $table->string('kop_line_2')->nullable()->after('kop_line_1');
            $table->string('kode_sekolah')->nullable()->after('kop_line_2');
        });
    }

    public function down(): void
    {
        Schema::table('school_settings', function (Blueprint $table) {
            $table->dropColumn(['kota_kabupaten', 'kop_line_1', 'kop_line_2', 'kode_sekolah']);
        });
    }
};
