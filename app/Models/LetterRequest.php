<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Model LetterRequest — pengajuan surat oleh user (gukar).
 */
class LetterRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'template_id',
        'status',
        'payload_data',
        'pdf_path',
    ];

    /** Generate UUID otomatis sebelum record baru dibuat. */
    protected static function booted(): void
    {
        static::creating(function (LetterRequest $letterRequest): void {
            if (empty($letterRequest->uuid)) {
                $letterRequest->uuid = (string) Str::uuid();
            }
            if (empty($letterRequest->user_id) && Auth::check()) {
                $letterRequest->user_id = Auth::id();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'payload_data' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(LetterTemplate::class, 'template_id');
    }

    // -- Helper status -------------------------------------------------------------------

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApprovedAdmin(): bool
    {
        return $this->status === 'approved_admin';
    }

    public function isSigned(): bool
    {
        return $this->status === 'signed';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /** URL publik untuk verifikasi surat via QR Code (signed URL). */
    public function verificationUrl(): string
    {
        $uuid = $this->uuid;
        $signature = hash_hmac('sha256', $uuid, config('app.key'));

        return route('letter.verify', ['uuid' => $uuid, 'sig' => $signature]);
    }

    /**
     * Render HTML template dengan menyaring tag outer HTML, Kop ganda, TTD ganda,
     * serta mengganti placeholder variabel (seperti {{ $nama }}, {{nama}})
     * dengan data dari payload_data.
     */
    public function renderContent(): string
    {
        $content = $this->template->content ?? '';
        $payload = $this->payload_data ?? [];

        // Fallback sekolah / nama_sekolah jika kosong di payload
        $schoolSettings = SchoolSettings::getInstance();
        $namaSekolah = $schoolSettings?->nama_sekolah ?? '';
        if (empty($payload['sekolah']) && ! empty($namaSekolah)) {
            $payload['sekolah'] = $namaSekolah;
        }
        if (empty($payload['nama_sekolah']) && ! empty($namaSekolah)) {
            $payload['nama_sekolah'] = $namaSekolah;
        }

        // Hapus elemen Kop ganda jika ada di dalam template content
        $content = preg_replace('/<div\s+class=["\']kop["\']>.*?<\/div>/s', '', $content);
        $content = preg_replace('/<div\s+class=["\']header-table["\']>.*?<\/table>/s', '', $content);

        // Hapus elemen TTD ganda jika ada di dalam template content
        $content = preg_replace('/<div\s+class=["\']ttd["\']>.*?<\/div>/s', '', $content);

        // Hapus tag outer HTML/head/body jika ada
        $content = preg_replace('/<!DOCTYPE.*?>/i', '', $content);
        $content = preg_replace('/<html.*?>/i', '', $content);
        $content = preg_replace('/<\/html>/i', '', $content);
        $content = preg_replace('/<head.*?>.*?<\/head>/s', '', $content);
        $content = preg_replace('/<body.*?>/i', '', $content);
        $content = preg_replace('/<\/body>/i', '', $content);

        // Ganti placeholder variabel dengan multiline string formatting yang aman (nl2br(e(...)))
        $rendered = preg_replace_callback('/\{\{\s*\$?\s*([a-zA-Z0-9_]+)\s*\}\}/', function ($matches) use ($payload) {
            $key = $matches[1];
            if (array_key_exists($key, $payload) && $payload[$key] !== null) {
                $val = e((string) $payload[$key]);

                return nl2br($val);
            }

            return '';
        }, trim($content));

        // Format tabel agar memiliki class table-data resmi
        return preg_replace_callback('/<table(\s+[^>]*)?>/i', function ($matches) {
            $attrs = $matches[1] ?? '';
            if (str_contains($attrs, 'class=')) {
                if (preg_match('/class=["\']([^"\']*table-data[^"\']*)["\']/', $attrs)) {
                    return $matches[0];
                }

                return preg_replace('/class=["\']([^"\']*)["\']/', 'class="table-data $1"', $matches[0]);
            }

            return '<table class="table-data"' . $attrs . '>';
        }, $rendered);
    }
}
