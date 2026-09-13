<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Helpers\TemplatePresets;
use App\Models\LetterTemplate;
use Illuminate\Database\Seeder;

/**
 * Seeder: LetterTemplate2025Seeder
 *
 * Mendaftarkan / memperbarui seluruh template surat resmi dari koleksi SURAT 2025
 * ke dalam tabel `letter_templates` menggunakan metode upsert aman.
 */
class LetterTemplate2025Seeder extends Seeder
{
    public function run(): void
    {
        $presets = TemplatePresets::getPresets();

        foreach ($presets as $key => $preset) {
            LetterTemplate::updateOrCreate(
                ['letter_code' => $preset['letter_code']],
                [
                    'name' => $preset['name'],
                    'classification_code' => $preset['classification_code'] ?? 'E',
                    'title_text' => $preset['title_text'] ?? strtoupper($preset['name']),
                    'opening_text' => $preset['opening_text'] ?? 'Yang bertanda tangan di bawah ini menerangkan bahwa:',
                    'middle_text' => $preset['middle_text'] ?? '',
                    'closing_text' => $preset['closing_text'] ?? 'Demikian surat ini dibuat untuk dipergunakan sebagaimana mestinya.',
                    'identity_fields' => $preset['identity_fields'] ?? ['nama', 'nip', 'jabatan'],
                    'detail_fields' => $preset['detail_fields'] ?? ['keperluan'],
                    'use_advanced_html' => false,
                    'content' => $preset['content'],
                    'is_active' => true,
                ]
            );
        }
    }
}
