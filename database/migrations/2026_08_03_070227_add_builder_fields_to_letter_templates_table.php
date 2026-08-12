<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom No-Code Builder + letter_code ke tabel letter_templates.
     *
     * Kolom builder dipersist agar isian visual dapat di-restore saat edit,
     * mencegah konten template terhapus oleh kompilasi ulang yang tidak sengaja.
     */
    public function up(): void
    {
        Schema::table('letter_templates', function (Blueprint $table) {
            $table->string('letter_code')->nullable()->after('name');
            $table->string('title_text', 255)->nullable()->after('letter_code');
            $table->text('opening_text')->nullable()->after('title_text');
            $table->text('middle_text')->nullable()->after('opening_text');
            $table->text('closing_text')->nullable()->after('middle_text');
            $table->json('identity_fields')->nullable()->after('closing_text');
            $table->json('detail_fields')->nullable()->after('identity_fields');
            $table->boolean('use_advanced_html')->default(false)->after('detail_fields');
        });
    }

    public function down(): void
    {
        Schema::table('letter_templates', function (Blueprint $table) {
            $table->dropColumn([
                'letter_code',
                'title_text',
                'opening_text',
                'middle_text',
                'closing_text',
                'identity_fields',
                'detail_fields',
                'use_advanced_html',
            ]);
        });
    }
};
