<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Karyawan;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var \App\Models\User $user */
        $user = $this->getRecord();

        if ($user->isGukar()) {
            $karyawan = $user->karyawan;
            if (! $karyawan) {
                $karyawan = Karyawan::where('user_id', $user->id)
                    ->orWhere('nama', $user->name)
                    ->first();
            }

            if ($karyawan) {
                $data['nip'] = $karyawan->nip;
                $data['jabatan'] = $karyawan->jabatan;
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = $this->getRecord();

        if (! auth()->user()?->isAdmin()) {
            $data['role'] = $user->role;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var \App\Models\User $user */
        $user = $this->record;

        if ($user->isGukar()) {
            Karyawan::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nama' => $user->name,
                    'nip' => $this->data['nip'] ?? '',
                    'jabatan' => $this->data['jabatan'] ?? '',
                ]
            );
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn (): bool => auth()->user()?->isAdmin() && auth()->id() !== $this->record->id),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
