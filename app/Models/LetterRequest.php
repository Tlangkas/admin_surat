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

    /** Ambil nomor surat dari payload_data jika ada. */
    public function getNomorSuratAttribute(): ?string
    {
        return $this->payload_data['nomor_surat'] ?? null;
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
        $baseUrl = SchoolSettings::getInstance()->getVerificationBaseUrl();

        return $baseUrl . '/letter/verify/' . $uuid . '?sig=' . $signature;
    }

    /**
     * Render HTML template dengan menyaring tag outer HTML, Kop ganda, TTD ganda,
     * serta mengganti placeholder variabel (seperti {{ $nama }}, {{nama}}, {{daftar_peserta}})
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

        // Ganti placeholder variabel dengan multiline string formatting yang aman atau tabel peserta
        $rendered = preg_replace_callback('/\{\{\s*\$?\s*([a-zA-Z0-9_]+)\s*\}\}/', function ($matches) use ($payload) {
            $key = $matches[1];

            if (! array_key_exists($key, $payload) || $payload[$key] === null) {
                return '';
            }

            // Render khusus untuk multi-peserta (daftar_peserta atau peserta)
            if (($key === 'daftar_peserta' || $key === 'peserta') && (is_array($payload[$key]) || is_string($payload[$key]))) {
                $pesertaList = is_array($payload[$key]) ? $payload[$key] : json_decode((string) $payload[$key], true);
                if (is_array($pesertaList) && ! empty($pesertaList)) {
                    $tableHtml = [];
                    $tableHtml[] = '<table class="table-data" style="width: 100%; border-collapse: collapse; margin: 12px 0 16px 0; font-size: 10.5pt;">';
                    $tableHtml[] = '  <thead>';
                    $tableHtml[] = '    <tr style="background-color: #f1f5f9; text-align: left;">';
                    $tableHtml[] = '      <th style="border: 1px solid #94a3b8; padding: 6px 8px; width: 35px; text-align: center;">No</th>';
                    $tableHtml[] = '      <th style="border: 1px solid #94a3b8; padding: 6px 8px;">Nama Peserta</th>';
                    $tableHtml[] = '      <th style="border: 1px solid #94a3b8; padding: 6px 8px; width: 125px;">NISN / NIP</th>';
                    $tableHtml[] = '      <th style="border: 1px solid #94a3b8; padding: 6px 8px; width: 120px;">Kelas / Jabatan</th>';
                    $tableHtml[] = '      <th style="border: 1px solid #94a3b8; padding: 6px 8px; width: 130px;">Peran / Keterangan</th>';
                    $tableHtml[] = '    </tr>';
                    $tableHtml[] = '  </thead>';
                    $tableHtml[] = '  <tbody>';
                    $no = 1;
                    foreach ($pesertaList as $item) {
                        if (! is_array($item)) {
                            continue;
                        }
                        $nama = e((string) ($item['nama'] ?? ''));
                        $identitas = e((string) ($item['identitas'] ?? $item['nisn'] ?? $item['nip'] ?? '-'));
                        $kelasJabatan = e((string) ($item['kelas_jabatan'] ?? $item['kelas'] ?? $item['jabatan'] ?? '-'));
                        $peran = e((string) ($item['peran'] ?? $item['keterangan'] ?? '-'));

                        $tableHtml[] = '    <tr>';
                        $tableHtml[] = '      <td style="border: 1px solid #cbd5e1; padding: 5px 8px; text-align: center;">' . $no++ . '</td>';
                        $tableHtml[] = '      <td style="border: 1px solid #cbd5e1; padding: 5px 8px; font-weight: bold;">' . $nama . '</td>';
                        $tableHtml[] = '      <td style="border: 1px solid #cbd5e1; padding: 5px 8px; font-family: monospace;">' . $identitas . '</td>';
                        $tableHtml[] = '      <td style="border: 1px solid #cbd5e1; padding: 5px 8px;">' . $kelasJabatan . '</td>';
                        $tableHtml[] = '      <td style="border: 1px solid #cbd5e1; padding: 5px 8px;">' . $peran . '</td>';
                        $tableHtml[] = '    </tr>';
                    }
                    $tableHtml[] = '  </tbody>';
                    $tableHtml[] = '</table>';

                    return implode("\n", $tableHtml);
                }
            }

            if (is_array($payload[$key])) {
                return '';
            }

            $val = e((string) $payload[$key]);

            return nl2br($val);
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
