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
     * Pastikan halaman edit SELALU membuka formulir No-Code Visual Form Builder
     * sama persis seperti saat pembuatan template baru.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var \App\Models\LetterTemplate $record */
        $record = $this->getRecord();

        $data['use_advanced_html'] = (bool) ($record->use_advanced_html ?? false);

        // Judul resmi
        $data['title_text'] = $record->title_text ?: strtoupper((string) $record->name);

        // Paragraf pembuka
        $data['opening_text'] = $record->opening_text ?: 'Yang bertanda tangan di bawah ini Kepala Sekolah menerangkan bahwa:';

        // Paragraf antara
        $data['middle_text'] = $record->middle_text !== null ? $record->middle_text : 'Diberikan tugas untuk melaksanakan kegiatan dengan rincian sebagai berikut:';

        // Paragraf penutup
        $data['closing_text'] = $record->closing_text ?: 'Demikian surat ini dibuat untuk dipergunakan sebagaimana mestinya dan dilaksanakan dengan penuh tanggung jawab.';

        // Identitas fields
        if (! empty($record->identity_fields) && is_array($record->identity_fields)) {
            $data['identity_fields'] = $record->identity_fields;
        } else {
            // Deteksi dari variables yang ada di template
            $vars = $record->variables ?? [];
            $identityKeys = ['nama', 'nama_siswa', 'nip', 'jabatan', 'alamat', 'sekolah', 'nisn', 'kelas', 'jurusan'];
            $matched = array_values(array_intersect($identityKeys, $vars));
            $data['identity_fields'] = ! empty($matched) ? $matched : ['nama', 'nip', 'jabatan'];
        }

        // Detail fields
        if (! empty($record->detail_fields) && is_array($record->detail_fields)) {
            $data['detail_fields'] = $record->detail_fields;
        } else {
            // Deteksi dari variables yang ada di template
            $vars = $record->variables ?? [];
            $detailKeys = ['daftar_peserta', 'nama_kegiatan', 'tujuan', 'alamat_tujuan', 'keperluan', 'tanggal_berangkat', 'tanggal_kembali', 'keterangan'];
            $matched = array_values(array_intersect($detailKeys, $vars));
            $data['detail_fields'] = ! empty($matched) ? $matched : ['tujuan', 'keperluan', 'tanggal_berangkat', 'tanggal_kembali'];
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('import_docx')
                ->label('Perbarui dari Word (.docx)')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->modalHeading('Perbarui Template dari Dokumen Word (.docx)')
                ->modalDescription('Unggah dokumen .docx baru untuk memperbarui isian formulir template ini.')
                ->form([
                    \Filament\Forms\Components\FileUpload::make('file')
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
                        $fullPath = \Illuminate\Support\Facades\Storage::disk('local')->path((string) $file);
                    }

                    try {
                        $parser = new \App\Services\DocxTemplateParser();
                        $parsed = $parser->parse($fullPath);
                        $this->form->fill($parsed);

                        \Filament\Notifications\Notification::make()
                            ->title('Dokumen Berhasil Diekstrak')
                            ->body('Formulir telah diperbarui dengan data dari berkas Word.')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Gagal Mengekstrak Dokumen')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    } finally {
                        @unlink($fullPath);
                    }
                }),

            Actions\DeleteAction::make(),
        ];
    }
}
