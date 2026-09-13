<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add Performance Indexes
 *
 * Menambahkan index pada kolom-kolom yang sering difilter, di-search, dan di-sort
 * pada letter_requests, letter_templates, siswa, dan karyawan untuk mempercepat
 * query Filament resource, overview widget, dan dropdown options.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letter_requests', function (Blueprint $table): void {
            $table->index(['status', 'deleted_at'], 'idx_lr_status_deleted');
            $table->index('created_at', 'idx_lr_created_at');
        });

        Schema::table('letter_templates', function (Blueprint $table): void {
            $table->index('letter_code', 'idx_lt_letter_code');
            $table->index(['is_active', 'deleted_at'], 'idx_lt_active_deleted');
        });

        Schema::table('siswa', function (Blueprint $table): void {
            $table->index('nama', 'idx_siswa_nama');
            $table->index('kelas', 'idx_siswa_kelas');
            $table->index('deleted_at', 'idx_siswa_deleted_at');
        });

        Schema::table('karyawan', function (Blueprint $table): void {
            $table->index('nama', 'idx_karyawan_nama');
            $table->index('deleted_at', 'idx_karyawan_deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('letter_requests', function (Blueprint $table): void {
            $table->dropIndex('idx_lr_status_deleted');
            $table->dropIndex('idx_lr_created_at');
        });

        Schema::table('letter_templates', function (Blueprint $table): void {
            $table->dropIndex('idx_lt_letter_code');
            $table->dropIndex('idx_lt_active_deleted');
        });

        Schema::table('siswa', function (Blueprint $table): void {
            $table->dropIndex('idx_siswa_nama');
            $table->dropIndex('idx_siswa_kelas');
            $table->dropIndex('idx_siswa_deleted_at');
        });

        Schema::table('karyawan', function (Blueprint $table): void {
            $table->dropIndex('idx_karyawan_nama');
            $table->dropIndex('idx_karyawan_deleted_at');
        });
    }
};
