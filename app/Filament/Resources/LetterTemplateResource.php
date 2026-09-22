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

    protected static ?string $navigationGroup = 'Layanan Surat';

    protected static ?int $navigationSort = 2;

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
                            ->options(collect(TemplatePresets::getPresets())->mapWithKeys(fn ($item, $key) => [$key => $item['name']]))
                            ->placeholder('Pilih preset instan...')
                            ->live()
                            ->afterStateUpdated(function (?string $state, Forms\Set $set): void {
                                if (! $state) {
                                    return;
                                }

                                $presets = TemplatePresets::getPresets();
                                if (isset($presets[$state])) {
                                    $p = $presets[$state];
                                    $set('name', $p['name']);
                                    $set('letter_code', $p['letter_code'] ?? 'SURAT');
                                    $set('classification_code', $p['classification_code'] ?? 'E');
                                    $set('title_text', $p['title_text'] ?? strtoupper($p['name']));
                                    $set('opening_text', $p['opening_text']);
                                    $set('identity_fields', $p['identity_fields'] ?? ['nama', 'nip', 'jabatan']);
                                    $set('middle_text', $p['middle_text'] ?? '');
                                    $set('detail_fields', $p['detail_fields'] ?? ['keperluan']);
                                    $set('closing_text', $p['closing_text']);

                                    Notification::make()
                                        ->title('Preset Berhasil Dipasang')
                                        ->body('Formulir visual terisi dengan: ' . $p['name'])
                                        ->success()
                                        ->send();
                                }
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
                            ->label('Kode Surat (Singkatan)')
                            ->placeholder('Contoh: ST-GUKAR, DISPEN, SPPD, SP-ORTU')
                            ->helperText('Singkatan identitas template surat')
                            ->maxLength(20)
                            ->dehydrated(),

                        Forms\Components\TextInput::make('classification_code')
                            ->label('Kode Klasifikasi Penomoran')
                            ->placeholder('Contoh: E, G, C, X')
                            ->default('E')
                            ->helperText('Kode klasifikasi nomor surat: E (Dinas/Umum), G (Kesiswaan/BK), C (SK/Keputusan), X (Keuangan)')
                            ->maxLength(10)
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
                            ->label('2. Pilih Data Identitas yang Ditampilkan')
                            ->options([
                                'nama' => 'Nama',
                                'nama_siswa' => 'Nama Siswa',
                                'nip' => 'NIP',
                                'nis' => 'NIS',
                                'nisn' => 'NISN',
                                'tempat_tanggal_lahir' => 'Tempat, Tanggal Lahir',
                                'jenis_kelamin' => 'Jenis Kelamin',
                                'kelas' => 'Kelas',
                                'jurusan' => 'Jurusan',
                                'jabatan' => 'Jabatan',
                                'mata_pelajaran' => 'Mata Pelajaran',
                                'alamat' => 'Alamat',
                                'sekolah' => 'Unit Kerja',
                                'nama_orang_tua' => 'Nama Orang Tua',
                                'pekerjaan_orang_tua' => 'Pekerjaan Orang Tua',
                                'no_hp' => 'Nomor Telepon',
                            ])
                            ->default(['nama', 'nip', 'jabatan'])
                            ->columns(2)
                            ->required(),

                        Forms\Components\Textarea::make('middle_text')
                            ->label('3. Paragraf Penjelas (Opsional)')
                            ->placeholder('Contoh: Diberikan tugas untuk melaksanakan kegiatan dengan rincian:')
                            ->default('Diberikan tugas untuk melaksanakan kegiatan dengan rincian sebagai berikut:')
                            ->rows(2),

                        Forms\Components\CheckboxList::make('detail_fields')
                            ->label('4. Pilih Rincian Keperluan yang Ditampilkan di Surat')
                            ->options([
                                'daftar_peserta' => '📋 Tabel Daftar Peserta',
                                'nama_kegiatan' => 'Nama Kegiatan',
                                'tujuan' => 'Tujuan',
                                'alamat_tujuan' => 'Alamat Tujuan',
                                'keperluan' => 'Keperluan',
                                'tempat' => 'Tempat Pelaksanaan',
                                'hari_tanggal' => 'Hari, Tanggal',
                                'waktu' => 'Waktu Pelaksanaan',
                                'tanggal_berangkat' => 'Tanggal Berangkat',
                                'tanggal_kembali' => 'Tanggal Kembali',
                                'transportasi' => 'Transportasi',
                                'pejabat_pemberi_perintah' => 'Pejabat Pemberi Perintah',
                                'pangkat_golongan' => 'Pangkat dan Golongan',
                                'tingkat_biaya' => 'Tingkat Biaya',
                                'beban_anggaran' => 'Pembebanan Anggaran',
                                'lama_perjalanan' => 'Lama Perjalanan',
                                'bentuk_pelanggaran' => 'Bentuk Pelanggaran',
                                'poin_pelanggaran' => 'Poin Pelanggaran',
                                'tindakan_pembinaan' => 'Tindakan Pembinaan',
                                'dudi_mitra' => 'Mitra Industri',
                                'sekolah_tujuan' => 'Sekolah Tujuan',
                                'alasan_pindah' => 'Alasan Pindah',
                                'tahun_lulus' => 'Tahun Lulus',
                                'no_ijazah' => 'Nomor Ijazah',
                                'nama_siswa' => 'Nama Siswa',
                                'keterangan' => 'Keterangan',
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

                Tables\Columns\TextColumn::make('letter_code')
                    ->label('Kode')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('classification_code')
                    ->label('Klasifikasi')
                    ->badge()
                    ->color('success')
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
            ])
            ->emptyStateHeading('Belum Ada Template Surat')
            ->emptyStateDescription('Buat template surat baru atau gunakan preset instan yang tersedia.')
            ->emptyStateIcon('heroicon-o-document-text');
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
