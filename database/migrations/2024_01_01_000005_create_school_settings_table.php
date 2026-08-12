<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('school_settings')) {
            Schema::create('school_settings', function (Blueprint $table): void {
                $table->id();
                $table->string('nama_sekolah')->default('NAMA SEKOLAH');
                $table->string('npsn')->nullable();
                $table->string('alamat')->default('Alamat Sekolah');
                $table->string('kode_pos')->nullable();
                $table->string('telepon')->nullable();
                $table->string('email')->nullable();
                $table->string('website')->nullable();
                $table->string('kepala_sekolah_nama')->default('NAMA KEPALA SEKOLAH');
                $table->string('kepala_sekolah_nip')->nullable();
                $table->string('kepala_sekolah_jabatan')->default('Kepala Sekolah');
                $table->string('logo_path')->nullable();
                $table->string('ttd_kepsek_path')->nullable();
                $table->timestamps();
            });

            if (\Illuminate\Support\Facades\DB::table('school_settings')->count() === 0) {
                \Illuminate\Support\Facades\DB::table('school_settings')->insert([
                    'nama_sekolah' => 'SEKOLAH MENENGAH ATAS NEGERI 1 CONTOH',
                    'npsn' => '12345678',
                    'alamat' => 'Jl. Contoh No. 123, Kelurahan Contoh, Kecamatan Contoh, Kota Contoh 12345',
                    'kode_pos' => '12345',
                    'telepon' => '(021) 1234567',
                    'email' => 'smansa1contoh@sch.id',
                    'website' => 'https://smansa1contoh.sch.id',
                    'kepala_sekolah_nama' => 'Dr. Budi Santoso, M.Pd.',
                    'kepala_sekolah_nip' => '197001012000121001',
                    'kepala_sekolah_jabatan' => 'Kepala Sekolah',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('school_settings');
    }
};