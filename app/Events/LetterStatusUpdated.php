<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\LetterRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: LetterStatusUpdated
 *
 * Dikirim saat status LetterRequest berubah (approved_admin / rejected / signed).
 * Broadcast ke private channel user.{userId} agar hanya pemilik surat yang menerima.
 *
 * Frontend saat ini tidak mendengarkan event ini (Echo dimatikan),
 * tapi event tetap dikirim untuk keperluan logging/audit.
 */
class LetterStatusUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param  LetterRequest  $letterRequest  Surat yang statusnya berubah.
     */
    public function __construct(
        public LetterRequest $letterRequest
    ) {}

    /**
     * Broadcast ke private channel milik pengaju surat.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->letterRequest->user_id),
        ];
    }

    /** Nama event yang didengarkan oleh frontend Echo. */
    public function broadcastAs(): string
    {
        return 'letter.status.updated';
    }

    /** Data yang dikirim ke frontend. */
    public function broadcastWith(): array
    {
        return [
            'uuid' => $this->letterRequest->uuid,
            'status' => $this->letterRequest->status,
        ];
    }
}
