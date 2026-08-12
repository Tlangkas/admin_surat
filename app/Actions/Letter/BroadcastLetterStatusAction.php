<?php

declare(strict_types=1);

namespace App\Actions\Letter;

use App\Models\LetterRequest;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Action: BroadcastLetterStatusAction
 *
 * Mengubah status LetterRequest, memberikan nomor surat otomatis,
 * menyiarkan perubahan secara realtime, dan mengirim notifikasi
 * database ke pemilik surat.
 */
final class BroadcastLetterStatusAction
{
    /**
     * Jalankan update status + broadcast + notifikasi.
     *
     * @param  LetterRequest  $letterRequest  Surat yang akan diubah statusnya.
     * @param  string         $newStatus      Status baru ('approved_admin', 'rejected', 'signed').
     */
    public function execute(LetterRequest $letterRequest, string $newStatus): void
    {
        DB::transaction(function () use ($letterRequest, $newStatus): void {
            // Berikan nomor surat otomatis sebelum status berubah (jika belum ada).
            if (in_array($newStatus, ['approved_admin', 'signed'], true)) {
                app(AssignNomorSuratAction::class)->execute($letterRequest);
            }

            // Update status di database — ini yang utama, tetap jalan walau broadcast gagal.
            $letterRequest->update(['status' => $newStatus]);

            // Broadcast event realtime.
            try {
                broadcast(new \App\Events\LetterStatusUpdated($letterRequest));
            } catch (\Throwable $e) {
                Log::warning('Broadcast gagal untuk LetterRequest {uuid}: {message}', [
                    'uuid' => $letterRequest->uuid,
                    'message' => $e->getMessage(),
                ]);
            }

            // Notifikasi database ke pemilik surat & admin.
            try {
                $this->sendNotifications($letterRequest, $newStatus);
            } catch (\Throwable $e) {
                Log::warning('Notifikasi gagal untuk LetterRequest {uuid}: {message}', [
                    'uuid' => $letterRequest->uuid,
                    'message' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * Kirim notifikasi database Filament ke pemilik surat (dan admin saat signed).
     */
    private function sendNotifications(LetterRequest $letterRequest, string $newStatus): void
    {
        $owner = $letterRequest->user;
        if (! $owner) {
            return;
        }

        $nomor = (string) ($letterRequest->payload_data['nomor_surat'] ?? $letterRequest->uuid);

        match ($newStatus) {
            'approved_admin' => $this->notify(
                $owner,
                'Surat Anda Disetujui',
                "Pengajuan surat dengan nomor {$nomor} telah disetujui admin dan menunggu tanda tangan Kepala Sekolah.",
                'heroicon-o-check-circle',
            ),
            'rejected' => $this->notify(
                $owner,
                'Surat Anda Ditolak',
                'Pengajuan surat Anda telah ditolak. Silakan periksa kembali atau hubungi admin.',
                'heroicon-o-x-circle',
            ),
            'signed' => $this->notify(
                $owner,
                'Surat Anda Ditandatangani',
                "Surat dengan nomor {$nomor} telah ditandatangani Kepala Sekolah dan PDF resmi terbit.",
                'heroicon-o-pencil-square',
            ),
            default => null,
        };

        if ($newStatus === 'signed') {
            User::query()
                ->where('role', 'admin')
                ->whereKeyNot($owner->id)
                ->get()
                ->each(function (User $admin) use ($nomor): void {
                    $this->notify(
                        $admin,
                        'Surat Telah Ditandatangani',
                        "Surat dengan nomor {$nomor} telah ditandatangani Kepala Sekolah.",
                        'heroicon-o-pencil-square',
                    );
                });
        }
    }

    private function notify(User $user, string $title, string $body, string $icon): void
    {
        Notification::make()
            ->title($title)
            ->body($body)
            ->icon($icon)
            ->success()
            ->sendToDatabase($user);
    }
}
