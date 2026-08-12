<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Actions\Letter\BroadcastLetterStatusAction;
use App\Actions\Letter\GeneratePdfAndQrAction;
use App\Filament\Resources\LetterRequestResource\Pages;
use App\Models\LetterRequest;
use App\Models\LetterTemplate;
use App\Policies\LetterRequestPolicy;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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
                Section::make('Pilih Template Surat')
                    ->description('Pilih jenis template surat resmi yang ingin diajukan')
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
                            $cleanLabel = match ($lowerKey) {
                                'nip' => 'NIP',
                                'npsn' => 'NPSN',
                                'ttd' => 'Tanda Tangan',
                                'nomor_surat' => 'Nomor Surat',
                                'tgl_berangkat', 'tanggal_berangkat' => 'Tanggal Berangkat',
                                'tgl_kembali', 'tanggal_kembali' => 'Tanggal Kembali',
                                'nama_sekolah' => 'Nama Sekolah',
                                default => Str::headline($rawLabel),
                            };

                            if (str_contains($lowerKey, 'keperluan') || str_contains($lowerKey, 'maksud') || str_contains($lowerKey, 'keterangan')) {
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
                            Section::make('Isi Data Surat')
                                ->description('Lengkapi formulir di bawah ini sesuai kebutuhan template surat.')
                                ->schema($fields)
                                ->columns(2),
                        ];
                    }),
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
                    Tables\Actions\ViewAction::make()->label('Lihat Detail'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLetterRequests::route('/'),
            'create' => Pages\CreateLetterRequest::route('/create'),
            'edit' => Pages\EditLetterRequest::route('/{record}/edit'),
        ];
    }
}
