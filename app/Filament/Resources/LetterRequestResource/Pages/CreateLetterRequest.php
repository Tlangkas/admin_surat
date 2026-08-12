<?php

declare(strict_types=1);

namespace App\Filament\Resources\LetterRequestResource\Pages;

use App\Filament\Resources\LetterRequestResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Halaman: Buat Pengajuan Surat Baru
 */
class CreateLetterRequest extends CreateRecord
{
    protected static string $resource = LetterRequestResource::class;

    public function getTitle(): string
    {
        return 'Buat Pengajuan Surat Baru';
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Ajukan Surat');
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()
            ->label('Simpan & Buat Lainnya');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Batal');
    }

    /** Dipanggil sebelum data disimpan ke database. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->validateFormData($data);

        $data['user_id'] = Auth::id();
        $data['status'] = 'pending';

        $user = Auth::user();
        if ($user && $user->isGukar()) {
            $profile = $user->getProfileData();
            $currentPayload = $data['payload_data'] ?? [];
            foreach ($profile as $key => $val) {
                if (! isset($currentPayload[$key]) || $currentPayload[$key] === '') {
                    $currentPayload[$key] = $val;
                }
            }
            $data['payload_data'] = $currentPayload;
        }

        return $data;
    }

    /** Validasi payload_data berdasarkan template variables. */
    protected function validateFormData(array $data): void
    {
        $templateId = $data['template_id'] ?? null;
        if (! $templateId) {
            return;
        }

        $template = \App\Models\LetterTemplate::find($templateId);
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

    /** Pre-fill form saat halaman pertama kali dimuat. */
    public function mount(): void
    {
        parent::mount();

        $user = Auth::user();
        if ($user && $user->isGukar()) {
            $this->form->fill([
                'payload_data' => $user->getProfileData(),
            ]);
        }
    }
}
