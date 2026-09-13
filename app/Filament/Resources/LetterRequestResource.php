<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Actions\Letter\BroadcastLetterStatusAction;
use App\Actions\Letter\GeneratePdfAndQrAction;
use App\Filament\Resources\LetterRequestResource\Pages;
use App\Models\LetterRequest;
use App\Models\LetterTemplate;
use App\Models\Siswa;
use App\Policies\LetterRequestPolicy;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Resource: LetterRequestResource
 *
 * Mengelola pengajuan surat via Filament CRUD.
 * Akses: semua role (admin, kepsek, gukar).
 */
class LetterRequestResource extends Resource
{
    protected static ?string $model = LetterRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'Pengajuan Surat';

    protected static ?string $pluralLabel = 'Pengajuan Surat';

    public static function getModelLabel(): string
    {
        return 'Pengajuan Surat';
    }

    protected static ?string $slug = 'pengajuan-surat';

    protected static ?string $navigationGroup = 'Layanan Surat';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['user', 'template']);
        $user = Auth::user();

        if ($user && $user->isGukar()) {
            return $query->where('user_id', $user->id);
        }

        return $query;
    }

    protected static ?string $policy = LetterRequestPolicy::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(fn (string $operation): string => $operation === 'create' ? 'Pilih Template Surat' : 'Template Surat')
                    ->description(fn (string $operation): string => $operation === 'create' ? 'Pilih jenis template surat resmi yang ingin diajukan.' : 'Template surat resmi yang digunakan.')
                    ->schema([
                        Select::make('template_id')
                            ->label('Template Surat')
                            ->options(
                                LetterTemplate::query()
                                    ->where('is_active', true)
                                    ->pluck('name', 'id')
                            )
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, ?string $state): void {
                                if (! $state) {
                                    $set('payload_data', []);

                                    return;
                                }

                                $template = LetterTemplate::find($state);
                                if (! $template || empty($template->variables)) {
                                    $set('payload_data', []);

                                    return;
                                }

                                $user = Auth::user();
                                $profile = $user ? $user->getProfileData() : [];
                                $isGukar = $user?->isGukar() ?? false;

                                $currentPayload = $get('payload_data') ?? [];
                                $newPayload = [];

                                foreach ($template->variables as $varKey => $varVal) {
                                    if (is_array($varVal)) {
                                        $key = (string) ($varVal['key'] ?? $varKey);
                                    } else {
                                        $key = is_string($varKey) && ! is_numeric($varKey) ? $varKey : (string) $varVal;
                                    }

                                    $cleanKey = trim((string) $key);
                                    if (empty($cleanKey)) {
                                        continue;
                                    }

                                    if (isset($currentPayload[$cleanKey]) && $currentPayload[$cleanKey] !== '') {
                                        $newPayload[$cleanKey] = $currentPayload[$cleanKey];

                                        continue;
                                    }

                                    $lowerKey = strtolower($cleanKey);
                                    if ($isGukar && isset($profile[$lowerKey])) {
                                        $newPayload[$cleanKey] = $profile[$lowerKey];
                                    } elseif ($isGukar && $lowerKey === 'nama_sekolah' && isset($profile['sekolah'])) {
                                        $newPayload[$cleanKey] = $profile['sekolah'];
                                    } else {
                                        $newPayload[$cleanKey] = $currentPayload[$cleanKey] ?? '';
                                    }
                                }

                                $set('payload_data', $newPayload);
                            }),
                    ]),

                Group::make()
                    ->schema(function (Forms\Get $get): array {
                        $templateId = $get('template_id');
                        if (! $templateId) {
                            return [];
                        }

                        $template = LetterTemplate::find($templateId);
                        if (! $template || empty($template->variables)) {
                            return [
                                Section::make('Data Surat')
                                    ->description('Template ini tidak membutuhkan variabel dinamis tambahan.')
                                    ->schema([]),
                            ];
                        }

                        $fields = [];
                        foreach ($template->variables as $varKey => $varVal) {
                            if (is_array($varVal)) {
                                $key = (string) ($varVal['key'] ?? $varKey);
                                $rawLabel = (string) ($varVal['label'] ?? $key);
                            } else {
                                $key = is_string($varKey) && ! is_numeric($varKey) ? $varKey : (string) $varVal;
                                $rawLabel = is_string($varVal) && is_numeric((string) $varKey) ? (string) $varVal : (string) $varVal;
                            }

                            $cleanKey = trim((string) $key);
                            if (empty($cleanKey)) {
                                continue;
                            }

                            $lowerKey = strtolower($cleanKey);
                            $cleanLabel = self::resolveFieldLabel($cleanKey, $rawLabel);

                            if ($lowerKey === 'daftar_peserta' || $lowerKey === 'peserta') {
                                $field = Repeater::make("payload_data.{$cleanKey}")
                                    ->label('Daftar Peserta')
                                    ->schema([
                                        Select::make('siswa_id')
                                             ->label('Pilih dari Data Siswa (Opsional / Otomatisasi)')
                                            ->options(fn () => Siswa::query()->orderBy('nama')->get()->mapWithKeys(fn ($s) => [$s->id => "{$s->nama} ({$s->nisn} - {$s->kelas})"]))
                                            ->searchable()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set): void {
                                                if (! $state) {
                                                    return;
                                                }
                                                $siswa = Siswa::find($state);
                                                if ($siswa) {
                                                    $set('nama', $siswa->nama);
                                                    $set('identitas', $siswa->nisn);
                                                    $set('kelas_jabatan', "{$siswa->kelas} ({$siswa->jurusan})");
                                                }
                                            })
                                            ->columnSpan(['default' => 1, 'md' => 2]),

                                        TextInput::make('nama')
                                            ->label('Nama Peserta')
                                            ->required(),

                                        TextInput::make('identitas')
                                            ->label('Nomor Identitas (NISN atau NIP)')
                                            ->placeholder('Contoh: 0051234567 atau 1985...'),

                                        TextInput::make('kelas_jabatan')
                                            ->label('Kelas atau Jabatan')
                                            ->placeholder('Contoh: XII RPL 1 atau Guru Pendamping'),

                                        TextInput::make('peran')
                                            ->label('Peran atau Keterangan')
                                            ->placeholder('Contoh: Ketua Tim, Peserta Lomba, dll.')
                                            ->default('Peserta'),
                                    ])
                                    ->columns(['default' => 1, 'md' => 2])
                                    ->defaultItems(1)
                                    ->addActionLabel('+ Tambah Peserta')
                                    ->columnSpanFull();
                            } elseif ($lowerKey === 'jenis_kelamin' || $lowerKey === 'jk') {
                                $field = Select::make("payload_data.{$cleanKey}")
                                    ->label($cleanLabel)
                                    ->options([
                                        'Laki-laki' => 'Laki-laki',
                                        'Perempuan' => 'Perempuan',
                                    ])
                                    ->default('Laki-laki')
                                    ->required();
                            } elseif ($lowerKey === 'no_hp' || $lowerKey === 'telepon' || $lowerKey === 'telp' || $lowerKey === 'hp') {
                                $field = TextInput::make("payload_data.{$cleanKey}")
                                    ->label($cleanLabel)
                                    ->tel()
                                    ->placeholder('Contoh: 081234567890')
                                    ->required();
                            } elseif ($lowerKey === 'pejabat_pemberi_perintah') {
                                $field = TextInput::make("payload_data.{$cleanKey}")
                                    ->label($cleanLabel)
                                    ->default('Kepala Sekolah')
                                    ->required();
                            } elseif ($lowerKey === 'beban_anggaran') {
                                $field = TextInput::make("payload_data.{$cleanKey}")
                                    ->label($cleanLabel)
                                    ->default('BOS Sekolah')
                                    ->required();
                            } elseif (str_contains($lowerKey, 'waktu') || str_contains($lowerKey, 'jam') || str_contains($lowerKey, 'time')) {
                                $field = DateTimePicker::make("payload_data.{$cleanKey}")
                                    ->label($cleanLabel)
                                    ->displayFormat('d F Y H:i')
                                    ->formatStateUsing(function ($state) {
                                        if (empty($state)) {
                                            return null;
                                        }
                                        $parsed = self::parseIndonesianDate((string) $state);

                                        return $parsed ? $parsed->format('Y-m-d H:i:s') : $state;
                                    })
                                    ->dehydrateStateUsing(function ($state) {
                                        if (empty($state)) {
                                            return $state;
                                        }
                                        $parsed = self::parseIndonesianDate((string) $state);

                                        return $parsed ? $parsed->translatedFormat('j F Y H:i') . ' WIB' : $state;
                                    })
                                    ->required();
                            } elseif (str_contains($lowerKey, 'tanggal') || str_contains($lowerKey, 'tgl') || str_contains($lowerKey, 'date') || str_contains($lowerKey, 'lahir') || str_contains($lowerKey, 'berangkat') || str_contains($lowerKey, 'kembali') || str_contains($lowerKey, 'pelaksanaan') || str_contains($lowerKey, 'mulai') || str_contains($lowerKey, 'selesai') || str_contains($lowerKey, 'berlaku')) {
                                $field = DatePicker::make("payload_data.{$cleanKey}")
                                    ->label($cleanLabel)
                                    ->displayFormat('d F Y')
                                    ->formatStateUsing(function ($state) {
                                        if (empty($state)) {
                                            return null;
                                        }
                                        $parsed = self::parseIndonesianDate((string) $state);

                                        return $parsed ? $parsed->format('Y-m-d') : $state;
                                    })
                                    ->dehydrateStateUsing(function ($state) {
                                        if (empty($state)) {
                                            return $state;
                                        }
                                        $parsed = self::parseIndonesianDate((string) $state);

                                        return $parsed ? $parsed->translatedFormat('j F Y') : $state;
                                    })
                                    ->required();
                            } elseif (str_contains($lowerKey, 'keperluan') || str_contains($lowerKey, 'maksud') || str_contains($lowerKey, 'keterangan') || str_contains($lowerKey, 'alamat') || str_contains($lowerKey, 'pelanggaran') || str_contains($lowerKey, 'pembinaan') || str_contains($lowerKey, 'alasan')) {
                                $field = Textarea::make("payload_data.{$cleanKey}")
                                    ->label($cleanLabel)
                                    ->rows(3)
                                    ->required();
                            } else {
                                $field = TextInput::make("payload_data.{$cleanKey}")
                                    ->label($cleanLabel)
                                    ->required();
                            }

                            $fields[] = $field;
                        }

                        return [
                            Section::make(fn (string $operation): string => $operation === 'create' ? 'Isi Data Surat' : 'Perbarui Data Surat')
                                ->description(fn (string $operation): string => $operation === 'create' ? 'Lengkapi formulir di bawah ini sesuai kebutuhan template surat.' : 'Perbarui data isian formulir surat jika terdapat perbaikan.')
                                ->schema($fields)
                                ->columns(2),
                        ];
                    }),
            ]);
    }

    /** Resolves variable key into a clean, unambiguous Indonesian label without slashes. */
    public static function resolveFieldLabel(string $key, ?string $fallback = null): string
    {
        $cleanKey = trim($key);
        $lowerKey = strtolower($cleanKey);

        return match ($lowerKey) {
            'nama' => 'Nama',
            'nip' => 'NIP',
            'nisn' => 'NISN',
            'nis' => 'NIS',
            'ttl', 'tempat_tanggal_lahir' => 'Tempat, Tanggal Lahir',
            'jenis_kelamin', 'jk' => 'Jenis Kelamin',
            'kelas' => 'Kelas',
            'jurusan' => 'Jurusan',
            'jabatan' => 'Jabatan',
            'mata_pelajaran', 'mapel' => 'Mata Pelajaran',
            'npsn' => 'NPSN',
            'ttd' => 'Tanda Tangan',
            'nomor_surat' => 'Nomor Surat',
            'tgl_berangkat', 'tanggal_berangkat' => 'Tanggal Berangkat',
            'tgl_kembali', 'tanggal_kembali' => 'Tanggal Kembali',
            'nama_sekolah', 'sekolah' => 'Unit Kerja',
            'nama_orang_tua', 'orang_tua', 'wali' => 'Nama Orang Tua',
            'pekerjaan_orang_tua' => 'Pekerjaan Orang Tua',
            'no_hp', 'telepon', 'telp', 'hp' => 'Nomor Telepon',
            'alamat', 'alamat_domisili', 'alamat_tinggal' => 'Alamat',
            'tujuan' => 'Tujuan',
            'alamat_tujuan', 'alamat_lokasi' => 'Alamat Tujuan',
            'keperluan', 'maksud' => 'Keperluan',
            'nama_kegiatan', 'kegiatan' => 'Nama Kegiatan',
            'tempat_kegiatan', 'lokasi', 'tempat' => 'Tempat Pelaksanaan',
            'hari_tanggal' => 'Hari, Tanggal',
            'waktu', 'tanggal_pelaksanaan' => 'Waktu Pelaksanaan',
            'nama_siswa' => 'Nama Siswa',
            'transportasi' => 'Transportasi',
            'pejabat_pemberi_perintah' => 'Pejabat Pemberi Perintah',
            'pangkat_golongan' => 'Pangkat dan Golongan',
            'tingkat_biaya' => 'Tingkat Biaya',
            'beban_anggaran' => 'Pembebanan Anggaran',
            'lama_perjalanan' => 'Lama Perjalanan',
            'bentuk_pelanggaran', 'pelanggaran' => 'Bentuk Pelanggaran',
            'poin_pelanggaran' => 'Poin Pelanggaran',
            'tindakan_pembinaan' => 'Tindakan Pembinaan',
            'dudi_mitra', 'perusahaan' => 'Mitra Industri',
            'sekolah_tujuan' => 'Sekolah Tujuan',
            'alasan_pindah' => 'Alasan Pindah',
            'tahun_lulus' => 'Tahun Lulus',
            'no_ijazah' => 'Nomor Ijazah',
            'keterangan' => 'Keterangan',
            default => ! empty($fallback) ? Str::headline($fallback) : Str::headline($cleanKey),
        };
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Informasi Pengajuan Surat')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        InfolistGrid::make(['default' => 1, 'md' => 3])->schema([
                            TextEntry::make('template.name')
                                ->label('Template Surat')
                                ->badge()
                                ->color('primary'),

                            TextEntry::make('nomor_surat_display')
                                ->label('Nomor Surat')
                                ->state(fn (LetterRequest $record): string =>
                                    $record->nomor_surat ?: ($record->payload_data['nomor_surat'] ?? 'Belum Diterbitkan')
                                )
                                ->weight('bold'),

                            TextEntry::make('status')
                                ->label('Status Pengajuan')
                                ->badge()
                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                    'pending' => 'Menunggu Persetujuan Admin',
                                    'approved_admin' => 'Disetujui Admin',
                                    'signed' => 'Telah Ditandatangani',
                                    'rejected' => 'Ditolak',
                                    default => $state,
                                })
                                ->color(fn (string $state): string => match ($state) {
                                    'pending' => 'warning',
                                    'approved_admin' => 'info',
                                    'signed' => 'success',
                                    'rejected' => 'danger',
                                    default => 'gray',
                                }),
                        ]),

                        InfolistGrid::make(['default' => 1, 'md' => 3])->schema([
                            TextEntry::make('user.name')
                                ->label('Diajukan Oleh')
                                ->icon('heroicon-o-user')
                                ->helperText(fn (LetterRequest $record): ?string => $record->user?->email),

                            TextEntry::make('created_at')
                                ->label('Tanggal Pengajuan')
                                ->icon('heroicon-o-calendar')
                                ->dateTime('j F Y, H:i')
                                ->suffix(' WIB'),

                            TextEntry::make('signed_at')
                                ->label('Tanggal Ditandatangani')
                                ->icon('heroicon-o-check-badge')
                                ->dateTime('j F Y, H:i')
                                ->suffix(' WIB')
                                ->placeholder('Belum ditandatangani'),
                        ]),

                        TextEntry::make('rejection_note')
                            ->label('Alasan Penolakan')
                            ->icon('heroicon-o-exclamation-triangle')
                            ->state(fn (LetterRequest $record): ?string =>
                                $record->payload_data['alasan_penolakan']
                                ?? $record->payload_data['rejection_reason']
                                ?? $record->statusLogs()->where('to_status', 'rejected')->latest()->value('note')
                                ?? 'Surat ini telah ditolak oleh pihak sekolah.'
                            )
                            ->color('danger')
                            ->weight('bold')
                            ->visible(fn (LetterRequest $record): bool => $record->status === 'rejected')
                            ->columnSpanFull(),
                    ]),

                InfolistSection::make('Rincian Data Surat')
                    ->icon('heroicon-o-document-text')
                    ->schema(function (?LetterRequest $record): array {
                        if (! $record) {
                            return [];
                        }

                        $entries = [];
                        $payload = $record->payload_data ?? [];

                        foreach ($payload as $key => $value) {
                            $cleanKey = trim((string) $key);
                            if ($cleanKey === '' || $cleanKey === 'nomor_surat') {
                                continue;
                            }

                            $lowerKey = strtolower($cleanKey);
                            $label = self::resolveFieldLabel($cleanKey);

                            if (($lowerKey === 'daftar_peserta' || $lowerKey === 'peserta') && is_array($value)) {
                                $entries[] = RepeatableEntry::make("payload_data.{$cleanKey}")
                                    ->label('Daftar Peserta')
                                    ->schema([
                                        TextEntry::make('nama')->label('Nama Peserta'),
                                        TextEntry::make('identitas')->label('Nomor Identitas (NISN atau NIP)'),
                                        TextEntry::make('kelas_jabatan')->label('Kelas atau Jabatan'),
                                        TextEntry::make('peran')->label('Peran atau Keterangan'),
                                    ])
                                    ->columns(['default' => 1, 'md' => 4])
                                    ->columnSpanFull();
                            } elseif (! is_array($value) && $value !== '') {
                                $isLong = strlen((string) $value) > 60
                                    || str_contains($lowerKey, 'alamat')
                                    || str_contains($lowerKey, 'keperluan')
                                    || str_contains($lowerKey, 'alasan')
                                    || str_contains($lowerKey, 'keterangan')
                                    || str_contains($lowerKey, 'pelanggaran');

                                $entries[] = TextEntry::make("payload_data.{$cleanKey}")
                                    ->label($label)
                                    ->state((string) $value)
                                    ->columnSpan($isLong ? ['default' => 1, 'md' => 2] : 1);
                            }
                        }

                        return $entries;
                    })
                    ->columns(['default' => 1, 'md' => 2]),

                InfolistSection::make('Berkas & Validasi Dokumen')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        InfolistGrid::make(['default' => 1, 'md' => 2])->schema([
                            TextEntry::make('pdf_path')
                                ->label('Dokumen Surat Resmi (PDF)')
                                ->icon('heroicon-o-arrow-down-tray')
                                ->state(fn (LetterRequest $record): string =>
                                    $record->pdf_path ? 'Unduh Dokumen Surat (PDF)' : 'Dokumen PDF belum diterbitkan'
                                )
                                ->url(fn (LetterRequest $record): ?string =>
                                    $record->pdf_path ? asset('storage/' . $record->pdf_path) : null,
                                    shouldOpenInNewTab: true
                                )
                                ->color(fn (LetterRequest $record): string => $record->pdf_path ? 'primary' : 'gray'),

                            TextEntry::make('uuid')
                                ->label('Tautan Verifikasi Keaslian Surat')
                                ->icon('heroicon-o-qr-code')
                                ->state(fn (LetterRequest $record): string =>
                                    $record->isSigned() ? 'Buka Tautan Verifikasi Keaslian' : 'Tersedia setelah surat ditandatangani'
                                )
                                ->url(fn (LetterRequest $record): ?string =>
                                    $record->isSigned() ? $record->verificationUrl() : null,
                                    shouldOpenInNewTab: true
                                )
                                ->color(fn (LetterRequest $record): string => $record->isSigned() ? 'success' : 'gray')
                                ->copyable(fn (LetterRequest $record): bool => $record->isSigned())
                                ->copyableState(fn (LetterRequest $record): ?string => $record->isSigned() ? $record->verificationUrl() : null)
                                ->helperText(fn (LetterRequest $record): ?HtmlString =>
                                    $record->isSigned()
                                        ? new HtmlString('<span class="text-xs text-gray-500 dark:text-gray-400 break-all font-mono select-all block mt-1 leading-relaxed" style="word-break: break-all;">' . e($record->verificationUrl()) . '</span>')
                                        : null
                                ),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('3s')
            ->columns([
                Tables\Columns\TextColumn::make('payload_data.nomor_surat')
                    ->label('Nomor Surat')
                    ->default('-')
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('template.name')
                    ->label('Jenis Surat')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pengaju')
                    ->searchable()
                    ->sortable()
                    ->visible(fn (): bool => ! Auth::user()?->isGukar()),

                Tables\Columns\TextColumn::make('payload_data.tujuan')
                    ->label('Tujuan')
                    ->default('-')
                    ->limit(25)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'approved_admin',
                        'success' => 'signed',
                        'danger' => 'rejected',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-check-circle' => 'approved_admin',
                        'heroicon-o-pencil-square' => 'signed',
                        'heroicon-o-x-circle' => 'rejected',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'approved_admin' => 'Disetujui Admin',
                        'signed' => 'Ditandatangani',
                        'rejected' => 'Ditolak',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu',
                        'approved_admin' => 'Disetujui Admin',
                        'signed' => 'Ditandatangani',
                        'rejected' => 'Ditolak',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve_admin')
                    ->label('Setujui')
                    ->icon('heroicon-o-check')
                    ->color('info')
                    ->visible(fn (LetterRequest $record): bool =>
                        Auth::user()?->can('approveAdmin', $record))
                    ->requiresConfirmation()
                    ->action(function (LetterRequest $record): void {
                        app(BroadcastLetterStatusAction::class)->execute($record, 'approved_admin');
                        Notification::make()->title('Surat Disetujui Admin')->success()->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (LetterRequest $record): bool =>
                        Auth::user()?->can('reject', $record))
                    ->requiresConfirmation()
                    ->action(function (LetterRequest $record): void {
                        app(BroadcastLetterStatusAction::class)->execute($record, 'rejected');
                        Notification::make()->title('Surat Ditolak')->danger()->send();
                    }),

                Tables\Actions\Action::make('sign')
                    ->label('Tandatangani')
                    ->icon('heroicon-o-pencil-square')
                    ->color('success')
                    ->visible(fn (LetterRequest $record): bool =>
                        Auth::user()?->can('sign', $record))
                    ->requiresConfirmation()
                    ->action(function (LetterRequest $record): void {
                        app(GeneratePdfAndQrAction::class)->execute($record);
                        app(BroadcastLetterStatusAction::class)->execute($record, 'signed');
                        Notification::make()->title('Surat Ditandatangani dan PDF Terbit')->success()->send();
                    }),

                Tables\Actions\Action::make('preview_pdf')
                    ->label('Preview PDF')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn (LetterRequest $record): bool =>
                        Auth::user()?->can('previewPdf', $record))
                    ->action(function (LetterRequest $record): void {
                        $content = $record->renderContent();

                        $qrCodeSvg = QrCode::format('svg')
                            ->size(200)
                            ->errorCorrection('M')
                            ->generate($record->verificationUrl());

                        $settings = \App\Models\SchoolSettings::getInstance();

                        $pdf = Pdf::loadView('pdfs.letter', [
                            'content' => $content,
                            'qrCodeSvg' => $qrCodeSvg,
                            'letterRequest' => $record,
                            'settings' => $settings,
                        ]);

                        $filename = 'preview_' . $record->uuid . '_' . now()->format('YmdHis') . '.pdf';
                        self::cleanupPreviewFiles();
                        Storage::disk('local')->put('public/pdfs/preview/' . $filename, $pdf->output());

                        $url = asset('storage/pdfs/preview/' . $filename);
                        Notification::make()
                            ->title('Preview PDF Siap')
                            ->body('Klik tombol di bawah untuk membuka pratinjau surat PDF.')
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('open')
                                    ->label('Buka Preview')
                                    ->url($url)
                                    ->openUrlInNewTab(),
                            ])
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('regenerate_pdf')
                    ->label('Terbitkan Ulang PDF')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Terbitkan Ulang Dokumen PDF?')
                    ->modalDescription('PDF surat ini akan digenerate ulang menggunakan tata letak Kop Surat dan pengaturan sekolah terkini.')
                    ->visible(fn (LetterRequest $record): bool =>
                        $record->isSigned() && (Auth::user()?->isAdmin() || Auth::user()?->isKepsek()))
                    ->action(function (LetterRequest $record): void {
                        app(GeneratePdfAndQrAction::class)->execute($record);
                        Notification::make()->title('PDF Surat Berhasil Diperbarui dengan Kop Terkini')->success()->send();
                    }),

                Tables\Actions\Action::make('download_pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->url(fn (LetterRequest $record): string =>
                        $record->pdf_path ? asset('storage/' . $record->pdf_path) : '#')
                    ->openUrlInNewTab()
                    ->visible(fn (LetterRequest $record): bool =>
                        Auth::user()?->can('downloadPdf', $record)),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Lihat Detail')
                        ->modalHeading('Detail Pengajuan Surat')
                        ->modalWidth('4xl'),
                    Tables\Actions\EditAction::make()
                        ->label('Edit')
                        ->visible(fn (LetterRequest $record): bool =>
                            Auth::user()?->can('update', $record)),
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn (LetterRequest $record): bool =>
                            Auth::user()?->can('delete', $record)),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => Auth::user()?->isAdmin()),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Belum Ada Pengajuan Surat')
            ->emptyStateDescription('Klik tombol "Buat Pengajuan Surat" untuk mulai mengajukan surat resmi.')
            ->emptyStateIcon('heroicon-o-envelope')
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    /** Hapus file PDF preview yang sudah berumur lebih dari 24 jam. */
    public static function cleanupPreviewFiles(): void
    {
        try {
            $files = Storage::disk('local')->files('public/pdfs/preview');

            foreach ($files as $file) {
                $mtime = Storage::disk('local')->lastModified($file);
                if (is_int($mtime) && now()->timestamp - $mtime > 86400) {
                    Storage::disk('local')->delete($file);
                }
            }
        } catch (\Throwable $e) {
            // Abaikan: cleanup preview tidak boleh mengganggu alur utama.
        }
    }

    /** Mengonversi teks tanggal/waktu bahasa Indonesia menjadi instans Carbon untuk di-load ke form picker. */
    public static function parseIndonesianDate(?string $dateStr): ?\Carbon\Carbon
    {
        if (empty($dateStr)) {
            return null;
        }

        $months = [
            'januari' => 'january',
            'februari' => 'february',
            'maret' => 'march',
            'april' => 'april',
            'mei' => 'may',
            'juni' => 'june',
            'juli' => 'july',
            'agustus' => 'august',
            'september' => 'september',
            'oktober' => 'october',
            'november' => 'november',
            'desember' => 'december',
        ];

        $lower = strtolower($dateStr);
        
        foreach ($months as $indo => $eng) {
            if (str_contains($lower, $indo)) {
                $lower = str_replace($indo, $eng, $lower);
                break;
            }
        }

        $lower = trim(str_replace('wib', '', $lower));

        try {
            return \Carbon\Carbon::parse($lower);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLetterRequests::route('/'),
            'create' => Pages\CreateLetterRequest::route('/create'),
            'edit' => Pages\EditLetterRequest::route('/{record}/edit'),
        ];
    }
}
