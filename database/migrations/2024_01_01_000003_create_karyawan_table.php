<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Karyawan Table
 *
 * Tabel data guru/karyawan yang terpisah dari users.
 * Kolom user_id ditambahkan di migrasi terpisah (add_user_id_to_karyawan_table).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('karyawan', function (Blueprint $table): void {
            $table->id();
            $table->string('nama');                 // Nama lengkap karyawan
            $table->string('nip', 30)->unique();    // NIP (Nomor Induk Pegawai)
            $table->string('jabatan');              // Jabatan (Guru, TU, dll)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('karyawan');
    }
};
