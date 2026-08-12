<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Letter Requests Table
 *
 * Tabel pengajuan surat (LetterRequest).
 * Status workflow: pending → approved_admin → signed / rejected.
 * UUID digenerate otomatis oleh model untuk keperluan verifikasi QR.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letter_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();                  // UUID publik untuk QR Code
            $table->foreignId('user_id')                     // Pengaju surat
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('template_id')                  // Template yang digunakan
                ->constrained('letter_templates');
            $table->string('status', 30)->default('pending'); // Status workflow
            $table->json('payload_data')->nullable();         // Isian surat (key-value)
            $table->string('pdf_path')->nullable();           // Path file PDF setelah ditandatangani
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_requests');
    }
};
