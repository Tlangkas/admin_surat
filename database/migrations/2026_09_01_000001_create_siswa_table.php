<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Siswa Table
 *
 * Tabel data siswa sekolah (nama, nisn, kelas, jurusan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siswa', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');
            $table->string('nisn', 30)->unique();
            $table->string('kelas');
            $table->string('jurusan');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siswa');
    }
};
