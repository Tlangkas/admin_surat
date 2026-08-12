<?php

declare(strict_types=1);

namespace App\Filament\Resources\LetterRequestResource\Pages;

use App\Filament\Resources\LetterRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * Halaman: Daftar Pengajuan Surat
 */
class ListLetterRequests extends ListRecords
{
    protected static string $resource = LetterRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Pengajuan Surat'),
        ];
    }
}
