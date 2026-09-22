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

            // Periksa apakah template ditujukan untuk identitas siswa
            $template = \App\Models\LetterTemplate::find($data['template_id'] ?? null);
            $allVarKeys = [];
            if ($template && is_array($template->variables)) {
                foreach ($template->variables as $vK => $vV) {
                    $k = is_array($vV) ? ($vV['key'] ?? $vK) : ($vV ?? $vK);
                    $allVarKeys[] = strtolower(trim((string) $k));
                }
            }
            $hasNip = in_array('nip', $allVarKeys, true);
            $hasNisn = in_array('nisn', $allVarKeys, true) || in_array('nis', $allVarKeys, true);
            $templateTitle = strtolower($template?->title_text ?? $template?->name ?? '');
            $isStudentLetter = ($hasNisn && ! $hasNip)
                || (str_contains($templateTitle, 'siswa') && ! $hasNip)
                || (str_contains($templateTitle, 'panggilan orang tua') && ! $hasNip)
                || (str_contains($templateTitle, 'dispensasi') && ! $hasNip);

            foreach ($profile as $key => $val) {
                if ($key === 'nama' && $isStudentLetter) {
                    continue;
                }
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

            if ($required) {
                $isEmpty = false;
                if (! isset($payload[$cleanKey])) {
                    $isEmpty = true;
                } elseif (is_array($payload[$cleanKey])) {
                    $isEmpty = empty($payload[$cleanKey]);
                } elseif (trim((string) $payload[$cleanKey]) === '') {
                    $isEmpty = true;
                }

                if ($isEmpty) {
                    $cleanLabel = trim((string) $label);
                    throw ValidationException::withMessages([
                        "payload_data.{$cleanKey}" => "Field {$cleanLabel} wajib diisi.",
                    ]);
                }
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
