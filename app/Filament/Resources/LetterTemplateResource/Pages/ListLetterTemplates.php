<?php

declare(strict_types=1);

namespace App\Filament\Resources\LetterTemplateResource\Pages;

use App\Filament\Resources\LetterTemplateResource;
use App\Models\LetterTemplate;
use App\Services\DocxTemplateParser;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListLetterTemplates extends ListRecords
{
    protected static string $resource = LetterTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('upload_docx')
                ->label('Upload File Word (.docx)')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->modalHeading('Upload Template dari Dokumen Word (.docx)')
                ->modalDescription('Unggah berkas Word (.docx). Sistem akan membaca tata letak, mendeteksi judul surat, dan mengekstrak variabel isian otomatis.')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('Pilih Berkas Word (.docx)')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                        ->disk('local')
                        ->directory('temp-uploads')
                        ->required(),

                    Forms\Components\Radio::make('mode')
                        ->label('Metode Pemrosesan')
                        ->options([
                            'editor' => 'Buka di Editor Formulir (Ditinjau & Disunting Terlebih Dahulu)',
                            'direct' => 'Simpan Langsung ke Database (Otomatis Aktif)',
                        ])
                        ->default('editor')
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

                        if ($data['mode'] === 'direct') {
                            $template = LetterTemplate::create([
                                'name' => $parsed['name'],
                                'title_text' => $parsed['title_text'],
                                'letter_code' => $parsed['letter_code'],
                                'opening_text' => $parsed['opening_text'],
                                'middle_text' => $parsed['middle_text'],
                                'closing_text' => $parsed['closing_text'],
                                'identity_fields' => $parsed['identity_fields'],
                                'detail_fields' => $parsed['detail_fields'],
                                'use_advanced_html' => $parsed['use_advanced_html'],
                                'content' => $parsed['content'],
                                'variables' => $parsed['variables'],
                                'is_active' => true,
                            ]);

                            Notification::make()
                                ->title('Template Berhasil Diimpor')
                                ->body("Template \"{$template->name}\" ({$template->letter_code}) telah berhasil ditambahkan.")
                                ->success()
                                ->send();
                        } else {
                            session(['imported_template_docx' => $parsed]);
                            $this->redirect(LetterTemplateResource::getUrl('create'));
                        }
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Gagal Memproses Dokumen')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    } finally {
                        @unlink($fullPath);
                    }
                }),

            Actions\CreateAction::make(),
        ];
    }
}
