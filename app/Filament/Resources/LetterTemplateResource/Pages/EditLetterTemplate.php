<?php

declare(strict_types=1);

namespace App\Filament\Resources\LetterTemplateResource\Pages;

use App\Filament\Resources\LetterTemplateResource;
use App\Helpers\TemplateCompiler;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLetterTemplate extends EditRecord
{
    protected static string $resource = LetterTemplateResource::class;

    public function getTitle(): string
    {
        return 'Edit Template Surat';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Jika tidak memakai mode HTML lanjutan, kompilasi otomatis dari form isian visual No-Code
        if (empty($data['use_advanced_html']) || empty($data['content'])) {
            $compiled = TemplateCompiler::compile($data);
            $data['content'] = $compiled['content'];
            $data['variables'] = $compiled['variables'];
        }

        return $data;
    }

    /**
     * Restore isian No-Code Builder ke form saat edit.
     *
     * Untuk template legacy (dibuat sebelum kolom builder dipersist), isian
     * builder kosong — paksa mode HTML lanjutan agar konten yang sudah ada
     * tidak terhapus oleh kompilasi ulang yang tidak disengaja.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        $hasBuilderState = $record->title_text !== null
            || $record->opening_text !== null
            || $record->middle_text !== null
            || $record->closing_text !== null
            || ! empty($record->identity_fields)
            || ! empty($record->detail_fields);

        if (! $record->use_advanced_html && $hasBuilderState) {
            $data['use_advanced_html'] = false;
            $data['title_text'] = $record->title_text;
            $data['opening_text'] = $record->opening_text;
            $data['middle_text'] = $record->middle_text;
            $data['closing_text'] = $record->closing_text;
            $data['identity_fields'] = $record->identity_fields ?? [];
            $data['detail_fields'] = $record->detail_fields ?? [];
        } elseif (! $hasBuilderState && ! empty($record->content)) {
            // Template legacy (konten sudah ada, tanpa isian builder): paksa mode
            // HTML lanjutan agar konten yang ada tidak terhapus saat disimpan.
            $data['use_advanced_html'] = true;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
