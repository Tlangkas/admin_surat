<?php

declare(strict_types=1);

namespace App\Filament\Resources\LetterRequestResource\Pages;

use App\Filament\Resources\LetterRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Halaman: Edit Pengajuan Surat
 */
class EditLetterRequest extends EditRecord
{
    protected static string $resource = LetterRequestResource::class;

    public function getTitle(): string
    {
        return 'Edit Pengajuan Surat';
    }

    protected function getSaveFormAction(): Actions\Action
    {
        return parent::getSaveFormAction()
            ->label('Simpan Perubahan');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->validateFormData($data);

        return $data;
    }

    protected function getCancelFormAction(): Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('Batal');
    }

    /** Validasi akses sebelum form diisi. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        if (! $record->isPending()) {
            abort(403, 'Hanya surat dengan status Menunggu yang bisa diedit.');
        }

        $user = Auth::user();
        if ($user->isGukar() && $record->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses untuk mengedit surat ini.');
        }

        return $data;
    }

    /** Validasi payload_data berdasarkan template variables. */
    protected function validateFormData(array $data): void
    {
        $record = $this->getRecord();
        $template = $record->template;

        if (! $template || ! $template->variables) {
            return;
        }

        $variables = $template->variables;
        $payload = $data['payload_data'] ?? [];

        foreach ($variables as $varKey => $variable) {
            if (is_array($variable)) {
                $key = $variable['key'] ?? $variable['label'] ?? (is_string($varKey) ? $varKey : null);
                $label = $variable['label'] ?? $key;
                $required = $variable['required'] ?? true;
            } else {
                $key = is_string($varKey) && ! is_numeric($varKey) ? $varKey : (string) $variable;
                $label = is_string($variable) && is_numeric((string) $varKey) ? Str::headline($variable) : (string) $variable;
                $required = true;
            }

            $cleanKey = trim((string) $key);
            if (empty($cleanKey)) {
                continue;
            }

            if ($required && (! isset($payload[$cleanKey]) || trim((string) $payload[$cleanKey]) === '')) {
                $cleanLabel = trim((string) $label);
                throw ValidationException::withMessages([
                    "payload_data.{$cleanKey}" => "Field {$cleanLabel} wajib diisi.",
                ]);
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Hapus')
                ->visible(fn (): bool => $this->getRecord()->isPending()),
        ];
    }
}
