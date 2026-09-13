<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Letter\GeneratePdfAndQrAction;
use App\Models\LetterRequest;
use Illuminate\Console\Command;

/**
 * Artisan command untuk me-regenerate semua PDF surat yang berstatus signed.
 *
 * Digunakan setelah URL verifikasi diubah dari admin agar QR Code
 * di dalam PDF mengarah ke alamat server yang benar.
 *
 * Perintah: php artisan letters:regenerate-pdfs
 */
class RegenerateSignedLetterPdfs extends Command
{
    protected $signature = 'letters:regenerate-pdfs';

    protected $description = 'Regenerasi semua PDF surat signed dengan URL verifikasi terbaru';

    public function handle(GeneratePdfAndQrAction $action): int
    {
        $signedLetters = LetterRequest::where('status', 'signed')->get();

        if ($signedLetters->isEmpty()) {
            $this->info('Tidak ada surat signed yang perlu di-regenerasi.');

            return self::SUCCESS;
        }

        $this->info("Memproses {$signedLetters->count()} surat...");

        $successCount = 0;
        $failCount = 0;

        foreach ($signedLetters as $letterRequest) {
            $nomor = $letterRequest->nomor_surat ?: $letterRequest->uuid;
            try {
                $action->execute($letterRequest);
                $successCount++;
                $this->line("  ✓ {$nomor} ({$letterRequest->uuid})");
            } catch (\Throwable $e) {
                $failCount++;
                $this->error("  ✗ {$nomor} ({$letterRequest->uuid}): {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Selesai: {$successCount} berhasil, {$failCount} gagal.");

        return $failCount > 0 ? self::FAILURE : self::SUCCESS;
    }
}
