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
     * Handle the LetterRequest "created" event.
     * Mencatat entri awal riwayat audit saat pengajuan surat pertama kali dibuat.
     */
    public function created(LetterRequest $letterRequest): void
    {
        $user = Auth::user() ?? $letterRequest->user;

        LetterStatusLog::create([
            'letter_request_id' => $letterRequest->id,
            'user_id' => $user?->id,
            'from_status' => null,
            'to_status' => $letterRequest->status ?? 'pending',
            'note' => match ($letterRequest->status) {
                'signed' => 'Surat langsung diterbitkan dan ditandatangani',
                'approved_admin' => 'Surat dibuat dan disetujui admin',
                'rejected' => 'Surat dibuat dengan status ditolak',
                default => 'Pengajuan surat baru dibuat oleh pemohon',
            },
            'ip_address' => Request::ip() ?: '127.0.0.1',
            'user_agent' => Request::userAgent() ?: 'System',
        ]);
    }

    /**
     * Handle the LetterRequest "updated" event.
     */
    public function updated(LetterRequest $letterRequest): void
    {
        if (! $letterRequest->isDirty('status')) {
            return;
        }

        $user = Auth::user();
        $rejectionReason = $letterRequest->payload_data['alasan_penolakan'] ?? null;

        LetterStatusLog::create([
            'letter_request_id' => $letterRequest->id,
            'user_id' => $user?->id,
            'from_status' => $letterRequest->getOriginal('status'),
            'to_status' => $letterRequest->status,
            'note' => $this->getDefaultNote(
                $letterRequest->getOriginal('status'),
                $letterRequest->status,
                is_string($rejectionReason) ? $rejectionReason : null
            ),
            'ip_address' => Request::ip() ?: '127.0.0.1',
            'user_agent' => Request::userAgent() ?: 'System',
        ]);
    }

    /**
     * Generate catatan otomatis berdasarkan transisi status.
     */
    private function getDefaultNote(?string $from, string $to, ?string $reason = null): string
    {
        return match (true) {
            $from === 'pending' && $to === 'approved_admin' => 'Disetujui oleh admin',
            $from === 'pending' && $to === 'rejected' => $reason ? "Ditolak oleh admin: {$reason}" : 'Ditolak oleh admin',
            $from === 'approved_admin' && $to === 'signed' => 'Ditandatangani oleh Kepala Sekolah',
            $from === 'approved_admin' && $to === 'rejected' => $reason ? "Ditolak oleh Kepala Sekolah: {$reason}" : 'Ditolak oleh Kepala Sekolah setelah disetujui admin',
            default => "Status berubah dari {$from} ke {$to}",
        };
    }
}