<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Model SchoolSettings — konfigurasi sekolah (singleton).
 *
 * Hanya boleh ada 1 record di tabel ini.
 * Digunakan untuk: kop surat PDF, halaman verifikasi publik, header aplikasi.
 */
class SchoolSettings extends Model
{
    protected $table = 'school_settings';

    protected $fillable = [
        'nama_sekolah',
        'npsn',
        'akreditasi',
        'alamat',
        'kode_pos',
        'telepon',
        'email',
        'website',
        'kota_kabupaten',
        'kop_line_1',
        'kop_line_2',
        'kode_sekolah',
        'starting_letter_number',
        'kepala_sekolah_nama',
        'kepala_sekolah_nip',
        'kepala_sekolah_jabatan',
        'logo_path',
        'logo_width',
        'logo_offset_x',
        'logo_offset_y',
        'logo_valign',
        'logo_kanan_path',
        'logo_kanan_width',
        'logo_kanan_offset_x',
        'logo_kanan_offset_y',
        'logo_kanan_valign',
        'kop_gap',
        'ttd_kepsek_path',
        'verification_base_url',
    ];

    protected function casts(): array
    {
        return [
            'starting_letter_number' => 'integer',
            'logo_width' => 'integer',
            'logo_offset_x' => 'integer',
            'logo_offset_y' => 'integer',
            'logo_kanan_width' => 'integer',
            'logo_kanan_offset_x' => 'integer',
            'logo_kanan_offset_y' => 'integer',
            'kop_gap' => 'integer',
        ];
    }

    private static ?self $instanceCache = null;

    protected static function booted(): void
    {
        static::saved(function (): void {
            static::$instanceCache = null;
        });

        static::deleted(function (): void {
            static::$instanceCache = null;
        });
    }

    /**
     * Clear cached instance manually (useful for tests and refreshes).
     */
    public static function clearInstanceCache(): void
    {
        static::$instanceCache = null;
    }

    /**
     * Ambil instance singleton (create jika belum ada) dengan in-memory memoization.
     */
    public static function getInstance(): self
    {
        if (static::$instanceCache !== null && ! app()->runningUnitTests()) {
            return static::$instanceCache;
        }

        $instance = self::firstOrCreate([], [
            'nama_sekolah' => 'NAMA SEKOLAH',
            'alamat' => 'Alamat Sekolah',
            'kota_kabupaten' => 'Surakarta',
            'kop_line_1' => 'PEMERINTAH KOTA SURAKARTA',
            'kop_line_2' => 'DINAS PENDIDIKAN',
            'kode_sekolah' => '29.15',
            'starting_letter_number' => 1,
            'kepala_sekolah_nama' => 'NAMA KEPALA SEKOLAH',
            'kepala_sekolah_nip' => '',
            'kepala_sekolah_jabatan' => 'Kepala Sekolah',
            'logo_width' => 75,
            'logo_offset_x' => 0,
            'logo_offset_y' => 0,
            'logo_valign' => 'middle',
            'logo_kanan_width' => 75,
            'logo_kanan_offset_x' => 0,
            'logo_kanan_offset_y' => 0,
            'logo_kanan_valign' => 'middle',
            'kop_gap' => 10,
        ]);

        if (! app()->runningUnitTests()) {
            static::$instanceCache = $instance;
        }

        return $instance;
    }

    /**
     * Base URL untuk link verifikasi surat (QR Code).
     *
     * Prioritas: field admin `verification_base_url` → fallback `APP_URL` dari .env.
     * Trailing slash dihapus agar konsisten saat digabung dengan path.
     */
    public function getVerificationBaseUrl(): string
    {
        $customUrl = trim((string) $this->verification_base_url);

        if ($customUrl !== '') {
            return rtrim($customUrl, '/');
        }

        return rtrim((string) config('app.url'), '/');
    }

    /**
     * Cari absolute file path untuk logo sekolah utama (kiri).
     */
    public function getLogoFullPath(): ?string
    {
        return $this->resolveFileFullPath($this->logo_path);
    }

    /**
     * Ambil logo utama sebagai data URI Base64 (sangat andal untuk DomPDF & web).
     */
    public function getLogoBase64(): ?string
    {
        return $this->convertFileToBase64($this->getLogoFullPath());
    }

    /**
     * Cari absolute file path untuk logo sekolah sekunder (kanan).
     */
    public function getLogoKananFullPath(): ?string
    {
        return $this->resolveFileFullPath($this->logo_kanan_path);
    }

    /**
     * Ambil logo kanan sebagai data URI Base64.
     */
    public function getLogoKananBase64(): ?string
    {
        return $this->convertFileToBase64($this->getLogoKananFullPath());
    }

    /**
     * Cari absolute file path untuk TTD kepala sekolah.
     */
    public function getTtdKepsekFullPath(): ?string
    {
        return $this->resolveFileFullPath($this->ttd_kepsek_path);
    }

    /**
     * Ambil TTD kepala sekolah sebagai data URI Base64.
     */
    public function getTtdKepsekBase64(): ?string
    {
        return $this->convertFileToBase64($this->getTtdKepsekFullPath());
    }

    /**
     * Helper resolver path file internal.
     */
    private function resolveFileFullPath(?string $relativePath): ?string
    {
        if (! $relativePath) {
            return null;
        }

        try {
            if (Storage::disk('public')->exists($relativePath)) {
                return Storage::disk('public')->path($relativePath);
            }
        } catch (\Throwable) {
        }

        try {
            if (Storage::disk('local')->exists($relativePath)) {
                return Storage::disk('local')->path($relativePath);
            }
        } catch (\Throwable) {
        }

        $candidates = [
            storage_path('app/public/' . $relativePath),
            storage_path('app/' . $relativePath),
            public_path('storage/' . $relativePath),
            public_path($relativePath),
        ];

        foreach ($candidates as $path) {
            if (file_exists($path) && is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Helper konversi file gambar ke Base64 data URI.
     */
    private function convertFileToBase64(?string $fullPath): ?string
    {
        if (! $fullPath || ! file_exists($fullPath)) {
            return null;
        }

        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        $content = @file_get_contents($fullPath);
        if ($content === false) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }

    /**
     * Path logo lengkap untuk tampilan web.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return asset('storage/' . $this->logo_path);
    }

    /**
     * Path logo kanan lengkap untuk tampilan web.
     */
    public function getLogoKananUrlAttribute(): ?string
    {
        if (! $this->logo_kanan_path) {
            return null;
        }

        return asset('storage/' . $this->logo_kanan_path);
    }

    /**
     * Path tanda tangan kepala sekolah lengkap untuk tampilan web.
     */
    public function getTtdKepsekUrlAttribute(): ?string
    {
        if (! $this->ttd_kepsek_path) {
            return null;
        }

        return asset('storage/' . $this->ttd_kepsek_path);
    }

    /**
     * Alamat lengkap untuk kop surat.
     */
    public function getAlamatLengkapAttribute(): string
    {
        $parts = [$this->alamat];
        if ($this->kode_pos) {
            $parts[] = $this->kode_pos;
        }

        return implode(', ', $parts);
    }

    /**
     * Kontak lengkap untuk kop surat.
     */
    public function getKontakLengkapAttribute(): string
    {
        $parts = [];
        if ($this->telepon) {
            $parts[] = 'Telp. ' . $this->telepon;
        }
        if ($this->email) {
            $parts[] = $this->email;
        }
        if ($this->website) {
            $parts[] = $this->website;
        }

        return implode(' | ', $parts);
    }

    /**
     * Format status akreditasi untuk tampilan kop surat.
     * Mengembalikan teks langsung jika sudah memuat kata 'akreditasi',
     * atau menambahkan prefix 'Akreditasi: ' jika hanya berupa nilai/huruf.
     */
    public function getFormattedAkreditasiAttribute(): ?string
    {
        if (empty($this->akreditasi)) {
            return null;
        }

        $trimmed = trim($this->akreditasi);
        if ($trimmed === '') {
            return null;
        }

        if (stripos($trimmed, 'akreditasi') !== false) {
            return $trimmed;
        }

        return 'Akreditasi: ' . $trimmed;
    }
}