<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model Siswa — data siswa sekolah (nama, nisn, kelas, jurusan).
 */
class Siswa extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'siswa';

    protected $fillable = [
        'nama',
        'nisn',
        'kelas',
        'jurusan',
    ];
}
