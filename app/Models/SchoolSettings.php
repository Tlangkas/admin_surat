<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
     * Path logo lengkap untuk tampilan.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        return asset('storage/' . $this->logo_path);
    }

    /**
     * Path tanda tangan kepala sekolah lengkap untuk tampilan.
     */
    public function getTtdKepsekUrlAttribute(): ?string
    {
        if (!$this->ttd_kepsek_path) {
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