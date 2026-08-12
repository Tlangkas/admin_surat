<?php

declare(strict_types=1);

namespace App\Filament\Resources\LetterTemplateResource\Pages;

use App\Filament\Resources\LetterTemplateResource;
use App\Helpers\TemplateCompiler;
use Filament\Resources\Pages\CreateRecord;

class CreateLetterTemplate extends CreateRecord
{
    protected static string $resource = LetterTemplateResource::class;

    public function getTitle(): string
    {
        return 'Buat Template Surat Baru';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Jika tidak memakai mode HTML lanjutan, kompilasi otomatis dari form isian visual No-Code
        if (empty($data['use_advanced_html']) || empty($data['content'])) {
            $compiled = TemplateCompiler::compile($data);
            $data['content'] = $compiled['content'];
            $data['variables'] = $compiled['variables'];
        }

        return $data;
    }
}
