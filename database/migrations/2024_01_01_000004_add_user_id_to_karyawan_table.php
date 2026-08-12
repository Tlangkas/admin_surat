<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add User ID to Karyawan Table
 *
 * Menambahkan relasi opsional antara karyawan dan user.
 * Satu karyawan bisa di-link ke satu akun login (nullable).
 * Jika user dihapus, user_id menjadi null (nullOnDelete).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('karyawan', function (Blueprint $table): void {
            $table->foreignId('user_id')
                ->nullable()                          // Tidak semua karyawan punya akun login
                ->constrained('users')
                ->nullOnDelete()                       // Jika user dihapus, user_id jadi null
                ->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('karyawan', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
