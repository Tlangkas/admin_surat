<?php

declare(strict_types=1);

namespace App\Actions\Letter;

use App\Models\LetterRequest;
use App\Models\SchoolSettings;
use Barryvdh\DomPDF\Facade\Pdf as DomPdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Spatie\LaravelPdf\Facades\Pdf as SpatiePdf;

/**
 * Action: GeneratePdfAndQrAction
 *
 * Membuat PDF surat resmi dengan QR Code verifikasi.
 * Mendukung Spatie Laravel PDF (Chromium/Browsershot) sebagai engine modern
 * dengan graceful fallback otomatis ke DomPDF.
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
        if (! $letterRequest->isApprovedAdmin() && ! $letterRequest->isSigned()) {
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

            $viewData = [
                'content' => $content,
                'qrCodeSvg' => $qrCodeSvg,
                'letterRequest' => $letterRequest,
                'settings' => $settings,
            ];

            $filename = sprintf('letter_%s_%s.pdf', $letterRequest->uuid, now()->format('YmdHis'));
            $pdfContent = $this->renderPdfContent($viewData);

            // Simpan file PDF.
            Storage::disk('local')->put('public/pdfs/' . $filename, $pdfContent);

            // Update record dan set status signed.
            $letterRequest->update([
                'pdf_path' => 'pdfs/' . $filename,
                'status' => 'signed',
            ]);

            return $letterRequest->fresh();
        });
    }

    /**
     * Render PDF content menggunakan Spatie Laravel PDF jika tersedia dan dikonfigurasi, atau DomPDF sebagai fallback.
     *
     * @param  array<string, mixed>  $viewData
     */
    private function renderPdfContent(array $viewData): string
    {
        if (config('pdf.driver') === 'spatie' && class_exists(SpatiePdf::class)) {
            try {
                $tempPath = tempnam(sys_get_temp_dir(), 'pdf_') . '.pdf';
                SpatiePdf::view('pdfs.letter', $viewData)
                    ->format('a4')
                    ->save($tempPath);

                $content = @file_get_contents($tempPath);
                @unlink($tempPath);

                if ($content !== false && strlen($content) > 0) {
                    return $content;
                }
            } catch (\Throwable $e) {
                Log::warning('Spatie PDF rendering fallback ke DomPDF: ' . $e->getMessage());
            }
        }

        // Default & Graceful Fallback: DomPDF
        $pdf = DomPdf::loadView('pdfs.letter', $viewData);

        return $pdf->output();
    }
}
