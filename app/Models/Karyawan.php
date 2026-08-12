<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model Karyawan — data guru/karyawan (nama, nip, jabatan).
 *
 * Terpisah dari model User. Satu karyawan bisa di-link ke satu akun User
 * melalui kolom `user_id` (opsional). Digunakan untuk auto-fill data
 * pengaju saat membuat surat.
 */
class Karyawan extends Model
{
    use SoftDeletes;

    protected $table = 'karyawan';

    protected $fillable = [
        'user_id',
        'nama',
        'nip',
        'jabatan',
    ];

    /** Relasi ke akun User yang terhubung (opsional). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
