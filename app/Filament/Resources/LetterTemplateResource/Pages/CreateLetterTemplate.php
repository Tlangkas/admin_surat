<?php

declare(strict_types=1);

namespace App\Filament\Resources\LetterTemplateResource\Pages;

use App\Filament\Resources\LetterTemplateResource;
use App\Helpers\TemplateCompiler;
use App\Services\DocxTemplateParser;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateLetterTemplate extends CreateRecord
{
    protected static string $resource = LetterTemplateResource::class;

    public function getTitle(): string
    {
        return 'Buat Template Surat Baru';
    }

    public function mount(): void
    {
        parent::mount();

        if (session()->has('imported_template_docx')) {
            $data = session()->pull('imported_template_docx');
            $this->form->fill($data);

            Notification::make()
                ->title('Data Word (.docx) Berhasil Dimuat')
                ->body('Silakan periksa dan sesuaikan isian formulir sebelum menyimpan.')
                ->info()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('import_docx')
                ->label('Ekstrak dari Word (.docx)')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->modalHeading('Ekstrak Template dari Dokumen Word (.docx)')
                ->modalDescription('Unggah dokumen .docx untuk mengisi otomatis seluruh kolom pada formulir ini.')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('Pilih Berkas Word (.docx)')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                        ->disk('local')
                        ->directory('temp-uploads')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $file = is_array($data['file']) ? (reset($data['file']) ?: '') : $data['file'];
                    if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile || $file instanceof \Illuminate\Http\UploadedFile) {
                        $fullPath = $file->getRealPath();
                    } else {
                        $fullPath = Storage::disk('local')->path((string) $file);
                    }

                    try {
                        $parser = new DocxTemplateParser();
                        $parsed = $parser->parse($fullPath);
                        $this->form->fill($parsed);

                        Notification::make()
                            ->title('Dokumen Berhasil Diekstrak')
                            ->body('Formulir telah terisi otomatis dari dokumen Word.')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Gagal Mengekstrak Dokumen')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    } finally {
                        @unlink($fullPath);
                    }
                }),
        ];
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
