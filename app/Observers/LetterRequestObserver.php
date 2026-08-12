<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\LetterRequest;
use App\Models\LetterStatusLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Observer untuk LetterRequest — mencatat audit trail saat status berubah.
 */
class LetterRequestObserver
{
    /**
     * Handle the LetterRequest "updated" event.
     */
    public function updated(LetterRequest $letterRequest): void
    {
        if (!$letterRequest->isDirty('status')) {
            return;
        }

        $user = Auth::user();

        LetterStatusLog::create([
            'letter_request_id' => $letterRequest->id,
            'user_id' => $user?->id,
            'from_status' => $letterRequest->getOriginal('status'),
            'to_status' => $letterRequest->status,
            'note' => $this->getDefaultNote($letterRequest->getOriginal('status'), $letterRequest->status),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Generate catatan otomatis berdasarkan transisi status.
     */
    private function getDefaultNote(?string $from, string $to): string
    {
        return match (true) {
            $from === 'pending' && $to === 'approved_admin' => 'Disetujui oleh admin',
            $from === 'pending' && $to === 'rejected' => 'Ditolak oleh admin',
            $from === 'approved_admin' && $to === 'signed' => 'Ditandatangani oleh Kepala Sekolah',
            $from === 'approved_admin' && $to === 'rejected' => 'Ditolak oleh Kepala Sekolah setelah disetujui admin',
            default => "Status berubah dari {$from} ke {$to}",
        };
    }
}