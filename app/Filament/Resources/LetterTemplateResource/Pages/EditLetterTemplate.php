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
            $identityKeys = ['nama', 'nip', 'jabatan', 'alamat', 'sekolah', 'nisn', 'kelas', 'jurusan'];
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
            Actions\DeleteAction::make(),
        ];
    }
}
