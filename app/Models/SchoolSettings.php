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
        'alamat',
        'kode_pos',
        'telepon',
        'email',
        'website',
        'kota_kabupaten',
        'kop_line_1',
        'kop_line_2',
        'kode_sekolah',
        'kepala_sekolah_nama',
        'kepala_sekolah_nip',
        'kepala_sekolah_jabatan',
        'logo_path',
        'ttd_kepsek_path',
    ];

    protected function casts(): array
    {
        return [];
    }

    /**
     * Ambil instance singleton (create jika belum ada).
     */
    public static function getInstance(): self
    {
        return self::firstOrCreate([], [
            'nama_sekolah' => 'NAMA SEKOLAH',
            'alamat' => 'Alamat Sekolah',
            'kota_kabupaten' => 'Surakarta',
            'kop_line_1' => 'PEMERINTAH KOTA SURAKARTA',
            'kop_line_2' => 'DINAS PENDIDIKAN',
            'kode_sekolah' => '421',
            'kepala_sekolah_nama' => 'NAMA KEPALA SEKOLAH',
            'kepala_sekolah_nip' => '',
            'kepala_sekolah_jabatan' => 'Kepala Sekolah',
        ]);
    }

    /**
     * Cari absolute file path untuk logo sekolah.
     */
    public function getLogoFullPath(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        try {
            if (Storage::disk('public')->exists($this->logo_path)) {
                return Storage::disk('public')->path($this->logo_path);
            }
        } catch (\Throwable) {
        }

        try {
            if (Storage::disk('local')->exists($this->logo_path)) {
                return Storage::disk('local')->path($this->logo_path);
            }
        } catch (\Throwable) {
        }

        $candidates = [
            storage_path('app/public/' . $this->logo_path),
            storage_path('app/' . $this->logo_path),
            public_path('storage/' . $this->logo_path),
            public_path($this->logo_path),
        ];

        foreach ($candidates as $path) {
            if (file_exists($path) && is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Ambil logo sebagai data URI Base64 (sangat andal untuk DomPDF & web).
     */
    public function getLogoBase64(): ?string
    {
        $fullPath = $this->getLogoFullPath();
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
     * Cari absolute file path untuk TTD kepala sekolah.
     */
    public function getTtdKepsekFullPath(): ?string
    {
        if (! $this->ttd_kepsek_path) {
            return null;
        }

        try {
            if (Storage::disk('public')->exists($this->ttd_kepsek_path)) {
                return Storage::disk('public')->path($this->ttd_kepsek_path);
            }
        } catch (\Throwable) {
        }

        try {
            if (Storage::disk('local')->exists($this->ttd_kepsek_path)) {
                return Storage::disk('local')->path($this->ttd_kepsek_path);
            }
        } catch (\Throwable) {
        }

        $candidates = [
            storage_path('app/public/' . $this->ttd_kepsek_path),
            storage_path('app/' . $this->ttd_kepsek_path),
            public_path('storage/' . $this->ttd_kepsek_path),
            public_path($this->ttd_kepsek_path),
        ];

        foreach ($candidates as $path) {
            if (file_exists($path) && is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Ambil TTD kepala sekolah sebagai data URI Base64.
     */
    public function getTtdKepsekBase64(): ?string
    {
        $fullPath = $this->getTtdKepsekFullPath();
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
}