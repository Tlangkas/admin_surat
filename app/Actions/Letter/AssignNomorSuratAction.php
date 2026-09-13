<?php

declare(strict_types=1);

namespace App\Actions\Letter;

use App\Models\LetterRequest;
use App\Models\SchoolSettings;

/**
 * Action: AssignNomorSuratAction
 *
 * Memberikan nomor surat otomatis dengan format standar baku SURAT 2025:
 *   {urutan:03d}/{kode_lembaga}/{kode_klasifikasi}/{bulan_romawi}/{tahun}
 * Contoh: 001/29.15/E/IX/2026 atau 354/29.15/G/I/2026
 *
 * Nomor surat mengikuti Satu Buku Agenda Terpusat per Tahun dan menghormati
 * starting_letter_number dari SchoolSettings jika dikonfigurasi.
 * Nomor surat yang sudah diisi manual oleh pengaju akan tetap dihormati (tidak ditimpa).
 */
final class AssignNomorSuratAction
{
    /**
     * Konversi angka bulan (1-12) ke angka Romawi.
     */
    public static function toRomanMonth(int $month): string
    {
        return match ($month) {
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
            default => 'I',
        };
    }

    public function execute(LetterRequest $letterRequest): void
    {
        $payload = $letterRequest->payload_data ?? [];

        if (! empty(trim((string) ($payload['nomor_surat'] ?? '')))) {
            return;
        }

        $settings = SchoolSettings::getInstance();
        $kodeSekolah = strtoupper(trim((string) ($settings->kode_sekolah ?? '29.15')));
        $kodeSekolah = $kodeSekolah !== '' ? $kodeSekolah : '29.15';

        $classificationCode = strtoupper(trim((string) ($letterRequest->template?->classification_code ?? '')));
        if ($classificationCode === '') {
            $letterCode = strtoupper(trim((string) ($letterRequest->template?->letter_code ?? '')));
            $classificationCode = in_array($letterCode, ['E', 'G', 'C', 'X'], true) ? $letterCode : 'E';
        }

        $date = $letterRequest->created_at ?? now();
        $year = (int) $date->year;
        $bulanRomawi = self::toRomanMonth((int) $date->month);

        $startNumber = max(1, (int) ($settings->starting_letter_number ?? 1));

        // Agenda surat keluar terpusat (seluruh jenis surat berbagi urutan agenda per tahun)
        $countExisting = LetterRequest::query()
            ->whereYear('created_at', $year)
            ->whereNotNull('payload_data->nomor_surat')
            ->where('id', '!=', $letterRequest->id)
            ->get()
            ->filter(fn (LetterRequest $existing): bool => ! empty(trim((string) ($existing->payload_data['nomor_surat'] ?? ''))))
            ->count();

        $seq = $startNumber + $countExisting;

        $nomor = sprintf('%03d/%s/%s/%s/%d', $seq, $kodeSekolah, $classificationCode, $bulanRomawi, $year);

        $payload['nomor_surat'] = $nomor;
        $letterRequest->update(['payload_data' => $payload]);
    }
}
