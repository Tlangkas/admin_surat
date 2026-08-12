<?php

declare(strict_types=1);

namespace App\Actions\Letter;

use App\Models\LetterRequest;
use App\Models\SchoolSettings;

/**
 * Action: AssignNomorSuratAction
 *
 * Memberikan nomor surat otomatis dengan format baku:
 *   {kode_sekolah}/{urutan:03d}/{kode_jenis}/{tahun}
 * Contoh: 421/001/SPD/2026
 *
 * Nomor surat yang sudah diisi manual oleh pengaju akan tetap dihormati
 * (tidak ditimpa).
 */
final class AssignNomorSuratAction
{
    public function execute(LetterRequest $letterRequest): void
    {
        $payload = $letterRequest->payload_data ?? [];

        if (! empty(trim((string) ($payload['nomor_surat'] ?? '')))) {
            return;
        }

        $settings = SchoolSettings::getInstance();
        $kodeSekolah = strtoupper(trim((string) ($settings->kode_sekolah ?? '421')));
        $kodeSekolah = $kodeSekolah !== '' ? $kodeSekolah : '421';

        $letterCode = strtoupper(trim((string) ($letterRequest->template?->letter_code ?? '')));
        $letterCode = $letterCode !== '' ? $letterCode : 'SK';

        $year = $letterRequest->created_at?->year ?? now()->year;

        $seq = LetterRequest::query()
            ->where('template_id', $letterRequest->template_id)
            ->whereYear('created_at', $year)
            ->whereNotNull('payload_data->nomor_surat')
            ->where('id', '!=', $letterRequest->id)
            ->get()
            ->filter(fn (LetterRequest $existing): bool => ! empty(trim((string) ($existing->payload_data['nomor_surat'] ?? ''))))
            ->count() + 1;

        $nomor = sprintf('%s/%03d/%s/%d', $kodeSekolah, $seq, $letterCode, $year);

        $payload['nomor_surat'] = $nomor;
        $letterRequest->update(['payload_data' => $payload]);
    }
}
