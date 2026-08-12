<?php

declare(strict_types=1);

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Model User — mewakili semua pengguna sistem (admin, kepsek, gukar).
 *
 * Role disimpan di kolom `role` dengan nilai: 'admin', 'kepsek', 'gukar'.
 * Mengimplementasikan FilamentUser agar bisa login ke panel Filament.
 */
class User extends Authenticatable implements FilamentUser
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** Semua user diizinkan mengakses panel admin. */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /** Cek apakah user berperan sebagai admin. */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** Cek apakah user berperan sebagai kepala sekolah. */
    public function isKepsek(): bool
    {
        return $this->role === 'kepsek';
    }

    /** Cek apakah user berperan sebagai guru/karyawan. */
    public function isGukar(): bool
    {
        return $this->role === 'gukar';
    }

    /** Relasi: satu user bisa memiliki banyak pengajuan surat. */
    public function letterRequests()
    {
        return $this->hasMany(LetterRequest::class);
    }

    /** Relasi: data karyawan terhubung. */
    public function karyawan(): HasOne
    {
        return $this->hasOne(Karyawan::class);
    }

    /**
     * Ambil data profil gukar (nama, nip, jabatan, sekolah) untuk pre-fill payload_data.
     * Mendukung auto-matching berbasis nama jika relasi `user_id` belum terikat di DB.
     */
    public function getProfileData(): array
    {
        $karyawan = $this->karyawan;

        if (! $karyawan && $this->isGukar()) {
            $karyawan = Karyawan::where('user_id', $this->id)
                ->orWhere('nama', $this->name)
                ->first();

            if ($karyawan && empty($karyawan->user_id)) {
                $karyawan->update(['user_id' => $this->id]);
            }
        }

        $schoolSettings = SchoolSettings::getInstance();

        return [
            'nama' => $karyawan?->nama ?? $this->name ?? '',
            'nip' => $karyawan?->nip ?? '',
            'jabatan' => $karyawan?->jabatan ?? '',
            'sekolah' => $schoolSettings?->nama_sekolah ?? '',
        ];
    }
}
