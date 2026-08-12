<?php

declare(strict_types=1);

namespace App\Actions\Letter;

use App\Models\LetterRequest;
use App\Models\SchoolSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Action: GeneratePdfAndQrAction
 *
 * Membuat PDF surat resmi dengan QR Code verifikasi.
 */
final class GeneratePdfAndQrAction
{
    /**
     * Eksekusi generate PDF + QR Code.
     *
     * @param  LetterRequest  $letterRequest  Surat yang akan ditandatangani.
     * @return LetterRequest  Record yang sudah di-update (fresh).
     */
    public function execute(LetterRequest $letterRequest): LetterRequest
    {
        if (! $letterRequest->isApprovedAdmin()) {
            throw new \RuntimeException('Surat harus disetujui admin sebelum dapat ditandatangani.');
        }

        return DB::transaction(function () use ($letterRequest): LetterRequest {
            $content = $letterRequest->renderContent();

            // Generate QR Code SVG dari URL verifikasi (signed URL dengan HMAC).
            $qrCodeSvg = QrCode::format('svg')
                ->size(200)
                ->errorCorrection('M')
                ->generate($letterRequest->verificationUrl());

            // Ambil setting sekolah untuk PDF.
            $settings = SchoolSettings::getInstance();

            // Render PDF menggunakan view khusus.
            $pdf = Pdf::loadView('pdfs.letter', [
                'content' => $content,
                'qrCodeSvg' => $qrCodeSvg,
                'letterRequest' => $letterRequest,
                'settings' => $settings,
            ]);

            // Simpan file PDF.
            $filename = sprintf('letter_%s_%s.pdf', $letterRequest->uuid, now()->format('YmdHis'));
            Storage::disk('local')->put('public/pdfs/' . $filename, $pdf->output());

            // Update record dan set status signed.
            $letterRequest->update([
                'pdf_path' => 'pdfs/' . $filename,
                'status' => 'signed',
            ]);

            return $letterRequest->fresh();
        });
    }
}
