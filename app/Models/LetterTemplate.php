<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model LetterTemplate — template surat yang berisi HTML untuk DomPDF.
 *
 * - `content`: HTML lengkap dengan placeholder {{ variable }}.
 * - `variables`: daftar nama variable yang diekstrak otomatis saat disimpan.
 * - `is_active`: status aktif/nonaktif template.
 */
class LetterTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'letter_code',
        'content',
        'variables',
        'is_active',
        'title_text',
        'opening_text',
        'middle_text',
        'closing_text',
        'identity_fields',
        'detail_fields',
        'use_advanced_html',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_active' => 'boolean',
            'identity_fields' => 'array',
            'detail_fields' => 'array',
            'use_advanced_html' => 'boolean',
        ];
    }

    /**
     * Booted lifecycle observer:
     * Secara otomatis mengekstrak variabel placeholder {{ nama_variabel }} dari `content`
     * saat model disimpan (saving event).
     */
    protected static function booted(): void
    {
        static::saving(function (LetterTemplate $template): void {
            preg_match_all('/\{\{\s*\$?\s*([a-zA-Z0-9_]+)\s*\}\}/', (string) $template->content, $matches);
            $extracted = array_values(array_unique(array_filter($matches[1] ?? [])));

            $template->variables = $extracted;
        });
    }

    /** Relasi: satu template bisa dipakai di banyak pengajuan surat. */
    public function letterRequests(): HasMany
    {
        return $this->hasMany(LetterRequest::class, 'template_id');
    }
}
