<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Karyawan;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
