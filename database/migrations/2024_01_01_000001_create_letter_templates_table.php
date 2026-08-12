<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Letter Templates Table
 *
 * Tabel untuk menyimpan template surat (HTML + variabel).
 * Konten dirender ke PDF via DomPDF dengan placeholder {{ variable }}.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letter_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name');                          // Nama template (e.g. "Surat Jalan")
            $table->longText('content');                     // HTML template untuk DomPDF
            $table->json('variables')->nullable();            // Daftar variable untuk form
            $table->boolean('is_active')->default(true);      // Status aktif
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_templates');
    }
};
