<?php

declare(strict_types=1);

namespace App\Filament\Resources\SiswaResource\Pages;

use App\Filament\Resources\SiswaResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

/**
 * Halaman: Tambah Data Siswa Baru
 */
class CreateSiswa extends CreateRecord
{
    protected static string $resource = SiswaResource::class;

    public function getTitle(): string
    {
        return 'Tambah Data Siswa Baru';
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Simpan Data Siswa');
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
