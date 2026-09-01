<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\LetterTemplateResource\Pages;
use App\Helpers\TemplateCompiler;
use App\Helpers\TemplatePresets;
use App\Models\LetterTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

/**
 * Resource: LetterTemplateResource
 *
 * Mengelola template surat resmi dengan Visual No-Code Template Builder (Tanpa HTML).
 */
class LetterTemplateResource extends Resource
{
    protected static ?string $model = LetterTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Template Surat';

    protected static ?string $pluralLabel = 'Template Surat';

    protected static ?string $slug = 'template-surat';

    public static function getPolicyClass(): string
    {
        return \App\Policies\LetterTemplatePolicy::class;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Preset Template Instan (1-Klik)')
                    ->description('Pilih preset surat di bawah ini untuk mengisi seluruh formulir visual secara otomatis.')
                    ->schema([
                        Forms\Components\Select::make('load_preset')
                            ->label('Pilih Preset Siap Pakai (Opsional)')
                            ->options([
                                'surat_tugas' => 'Surat Tugas Perjalanan Dinas',
                                'surat_keterangan' => 'Surat Keterangan Aktif Mengajar',
                                'surat_izin' => 'Surat Izin Permohonan Resmi',
                            ])
                            ->placeholder('Pilih preset instan...')
                            ->live()
                            ->afterStateUpdated(function (?string $state, Forms\Set $set): void {
                                if (! $state) {
                                    return;
                                }

                                if ($state === 'surat_tugas') {
                                    $set('name', 'Surat Tugas Perjalanan Dinas');
                                    $set('letter_code', 'SPD');
                                    $set('title_text', 'SURAT TUGAS');
                                    $set('opening_text', 'Yang bertanda tangan di bawah ini Kepala Sekolah menerangkan bahwa:');
                                    $set('identity_fields', ['nama', 'nip', 'jabatan']);
                                    $set('middle_text', 'Diberikan tugas untuk melaksanakan perjalanan dinas dengan rincian sebagai berikut:');
                                    $set('detail_fields', ['tujuan', 'keperluan', 'tanggal_berangkat', 'tanggal_kembali']);
                                    $set('closing_text', 'Demikian Surat Tugas ini dibuat untuk dipergunakan sebagaimana mestinya dan dilaksanakan dengan penuh tanggung jawab.');
                                } elseif ($state === 'surat_dispensasi_siswa') {
                                    $set('name', 'Surat Dispensasi Siswa');
                                    $set('letter_code', 'DISPEN');
                                    $set('title_text', 'SURAT DISPENSASI SISWA');
                                    $set('opening_text', 'Yang bertanda tangan di bawah ini Kepala Sekolah memberikan dispensasi / izin meninggalkan Kegiatan Belajar Mengajar (KBM) kepada:');
                                    $set('identity_fields', ['nama', 'nip', 'jabatan']);
                                    $set('middle_text', 'Untuk mengikuti kegiatan / perlombaan dengan rincian:');
                                    $set('detail_fields', ['nama_kegiatan', 'tujuan', 'tanggal_berangkat', 'tanggal_kembali', 'daftar_peserta']);
                                    $set('closing_text', 'Demikian Surat Dispensasi ini diberikan agar yang bersangkutan dapat melaksanakan tugas dengan sebaik-baiknya.');
                                } elseif ($state === 'surat_tugas_siswa') {
                                    $set('name', 'Surat Tugas / Rekomendasi Siswa');
                                    $set('letter_code', 'ST-SISWA');
                                    $set('title_text', 'SURAT TUGAS KONTINGEN SISWA');
                                    $set('opening_text', 'Yang bertanda tangan di bawah ini Kepala Sekolah menugaskan Guru Pembimbing dan Kontingen Siswa berikut:');
                                    $set('identity_fields', ['nama', 'nip', 'jabatan']);
                                    $set('middle_text', 'Untuk mewakili sekolah dalam agenda kegiatan / kejuaraan:');
                                    $set('detail_fields', ['nama_kegiatan', 'tujuan', 'tanggal_berangkat', 'tanggal_kembali', 'daftar_peserta']);
                                    $set('closing_text', 'Demikian Surat Tugas ini diterbitkan untuk dipergunakan sebagaimana mestinya.');
                                } elseif ($state === 'surat_keterangan') {
                                    $set('name', 'Surat Keterangan Aktif');
                                    $set('letter_code', 'SK');
                                    $set('title_text', 'SURAT KETERANGAN');
                                    $set('opening_text', 'Yang bertanda tangan di bawah ini Kepala Sekolah menerangkan dengan sebenarnya bahwa:');
                                    $set('identity_fields', ['nama', 'nip', 'jabatan', 'sekolah']);
                                    $set('middle_text', 'Adalah benar merupakan Guru / Karyawan aktif yang saat ini masih bertugas pada unit kerja kami.');
                                    $set('detail_fields', ['keperluan']);
                                    $set('closing_text', 'Demikian Surat Keterangan ini dibuat dengan sebenarnya agar dapat dipergunakan sebagaimana mestinya.');
                                } elseif ($state === 'surat_izin') {
                                    $set('name', 'Surat Izin Permohonan');
                                    $set('letter_code', 'SI');
                                    $set('title_text', 'SURAT IZIN');
                                    $set('opening_text', 'Yang bertanda tangan di bawah ini Kepala Sekolah memberikan izin kepada:');
                                    $set('identity_fields', ['nama', 'nip', 'jabatan']);
                                    $set('middle_text', 'Untuk tidak hadir bertugas / melaksanakan kegiatan dengan rincian:');
                                    $set('detail_fields', ['keperluan', 'tujuan', 'tanggal_berangkat', 'tanggal_kembali']);
                                    $set('closing_text', 'Demikian Surat Izin ini diberikan untuk dapat dipergunakan sebagaimana mestinya.');
                                }

                                Notification::make()
                                    ->title('Preset Berhasil Dipasang')
                                    ->body('Formulir visual telah terisi otomatis.')
                                    ->success()
                                    ->send();
                            }),
                    ]),

                Forms\Components\Section::make('Informasi Dasar Template')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Template Surat')
                            ->placeholder('Contoh: Surat Dispensasi Siswa')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('title_text')
                            ->label('Judul Resmi di Cetakan Surat')
                            ->placeholder('Contoh: SURAT DISPENSASI SISWA')
                            ->default('SURAT TUGAS')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('letter_code')
                            ->label('Kode Surat')
                            ->placeholder('Contoh: SPD, DISPEN, SK, SI')
                            ->helperText('Kode jenis surat untuk nomor surat otomatis, mis. 421/001/DISPEN/2026')
                            ->maxLength(20)
                            ->dehydrated(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true),

                        Forms\Components\Toggle::make('use_advanced_html')
                            ->label('Mode HTML Tingkat Lanjut (Opsional)')
                            ->helperText('Aktifkan hanya jika Anda ingin mengedit kode HTML secara manual')
                            ->default(false)
                            ->live(),
                    ])
                    ->columns(['default' => 1, 'md' => 2]),

                Forms\Components\Section::make('Isi Surat No-Code (Tanpa HTML)')
                    ->description('Cukup ketik kalimat pembuka, centang data identitas & rincian kegiatan, dan kalimat penutup. Sistem akan menyusun tata letak surat resmi secara otomatis.')
                    ->hidden(fn (Forms\Get $get): bool => (bool) $get('use_advanced_html'))
                    ->schema([
                        Forms\Components\Textarea::make('opening_text')
                            ->label('1. Paragraf Pembuka Surat')
                            ->default('Yang bertanda tangan di bawah ini Kepala Sekolah menerangkan bahwa:')
                            ->rows(2)
                            ->required(),

                        Forms\Components\CheckboxList::make('identity_fields')
                            ->label('2. Pilih Identitas Pengaju / Pembimbing yang Ditampilkan')
                            ->options([
                                'nama' => 'Nama Lengkap Pengaju / Pembimbing',
                                'nip' => 'NIP (Nomor Induk Pegawai)',
                                'jabatan' => 'Jabatan / Unit Kerja',
                                'alamat' => 'Alamat Tempat Tinggal / Domisili',
                                'sekolah' => 'Nama Sekolah / Institusi',
                                'nisn' => 'NISN Siswa (Jika surat perorangan siswa)',
                                'kelas' => 'Kelas Siswa',
                                'jurusan' => 'Jurusan Siswa',
                            ])
                            ->default(['nama', 'nip', 'jabatan'])
                            ->columns(2)
                            ->required(),

                        Forms\Components\Textarea::make('middle_text')
                            ->label('3. Paragraf Penjelas / Antara (Opsional)')
                            ->placeholder('Contoh: Diberikan tugas untuk melaksanakan kegiatan dengan rincian:')
                            ->default('Diberikan tugas untuk melaksanakan kegiatan dengan rincian sebagai berikut:')
                            ->rows(2),

                        Forms\Components\CheckboxList::make('detail_fields')
                            ->label('4. Pilih Rincian Keperluan / Tabel Peserta yang Ditampilkan di Surat')
                            ->options([
                                'daftar_peserta' => '📋 Tabel Daftar Peserta (Multi-Peserta / Siswa)',
                                'nama_kegiatan' => 'Nama Agenda / Kegiatan / Lomba',
                                'tujuan' => 'Tujuan Dinas / Tempat Pelaksanaan',
                                'alamat_tujuan' => 'Alamat Tujuan / Lokasi Kegiatan',
                                'keperluan' => 'Maksud / Keperluan',
                                'tanggal_berangkat' => 'Tanggal Berangkat',
                                'tanggal_kembali' => 'Tanggal Kembali',
                                'keterangan' => 'Keterangan Tambahan',
                            ])
                            ->default(['tujuan', 'keperluan', 'tanggal_berangkat', 'tanggal_kembali'])
                            ->columns(2),

                        Forms\Components\Textarea::make('closing_text')
                            ->label('5. Paragraf Penutup Surat')
                            ->default('Demikian surat ini dibuat untuk dipergunakan sebagaimana mestinya dan dilaksanakan dengan penuh tanggung jawab.')
                            ->rows(2)
                            ->required(),
                    ]),

                Forms\Components\Section::make('Editor HTML Tingkat Lanjut')
                    ->visible(fn (Forms\Get $get): bool => (bool) $get('use_advanced_html'))
                    ->schema([
                        Forms\Components\RichEditor::make('content')
                            ->label('Konten Kode HTML Template')
                            ->helperText('Gunakan format variabel {{ nama_variabel }} untuk data dinamis.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withoutTrashed())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Template')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('letter_requests_count')
                    ->label('Jumlah Pengajuan')
                    ->counts('letterRequests')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('variables')
                    ->label('Variabel Form')
                    ->badge()
                    ->separator(',')
                    ->color('gray')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : (string) $state),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status Aktif')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Pratinjau PDF')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->action(function (LetterTemplate $record): void {
                        $dummyRequest = new \App\Models\LetterRequest([
                            'uuid' => (string) \Illuminate\Support\Str::uuid(),
                            'status' => 'signed',
                            'created_at' => now(),
                            'payload_data' => [
                                'nomor_surat' => '421/001/SPD/' . date('Y'),
                                'nama' => 'Budi Santoso, S.Pd',
                                'nip' => '198501012010011001',
                                'jabatan' => 'Guru Mata Pelajaran',
                                'sekolah' => 'SMK Negeri 1 Jakarta',
                                'tujuan' => 'Dinas Pendidikan Provinsi',
                                'keperluan' => 'Koordinasi Kurikulum Nasional',
                                'tanggal_berangkat' => '01 Agustus 2026',
                                'tanggal_kembali' => '03 Agustus 2026',
                                'keterangan' => 'Peserta Utama Workshop',
                            ],
                        ]);

                        $dummyRequest->setRelation('template', $record);

                        $content = $dummyRequest->renderContent();
                        $settings = \App\Models\SchoolSettings::getInstance();

                        $qrCodeSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                            ->size(200)
                            ->generate($dummyRequest->verificationUrl());

                        $pdf = Pdf::loadView('pdfs.letter', [
                            'content' => $content,
                            'qrCodeSvg' => $qrCodeSvg,
                            'letterRequest' => $dummyRequest,
                            'settings' => $settings,
                        ]);

                        $filename = 'preview_template_' . $record->id . '_' . now()->format('YmdHis') . '.pdf';
                        \App\Filament\Resources\LetterRequestResource::cleanupPreviewFiles();
                        Storage::disk('local')->put('public/pdfs/preview/' . $filename, $pdf->output());

                        $url = asset('storage/pdfs/preview/' . $filename);
                        Notification::make()
                            ->title('Pratinjau Template Siap')
                            ->body('Klik tombol di bawah untuk melihat hasil render PDF.')
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('open')
                                    ->label('Buka PDF')
                                    ->url($url)
                                    ->openUrlInNewTab(),
                            ])
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLetterTemplates::route('/'),
            'create' => Pages\CreateLetterTemplate::route('/create'),
            'edit' => Pages\EditLetterTemplate::route('/{record}/edit'),
        ];
    }
}
