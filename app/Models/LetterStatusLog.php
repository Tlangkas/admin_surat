<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model LetterStatusLog — audit trail perubahan status surat.
 *
 * Dibuat otomatis via Observer saat status LetterRequest berubah.
 */
class LetterStatusLog extends Model
{
    protected $table = 'letter_status_logs';

    protected $fillable = [
        'letter_request_id',
        'user_id',
        'from_status',
        'to_status',
        'note',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [];
    }

    /** Relasi ke surat yang berubah statusnya. */
    public function letterRequest(): BelongsTo
    {
        return $this->belongsTo(LetterRequest::class, 'letter_request_id');
    }

    /** Relasi ke user yang melakukan aksi (null jika sistem). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}