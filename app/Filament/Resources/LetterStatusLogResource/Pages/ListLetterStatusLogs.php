<?php

declare(strict_types=1);

namespace App\Filament\Resources\LetterStatusLogResource\Pages;

use App\Filament\Resources\LetterStatusLogResource;
use Filament\Resources\Pages\ListRecords;

class ListLetterStatusLogs extends ListRecords
{
    protected static string $resource = LetterStatusLogResource::class;
}