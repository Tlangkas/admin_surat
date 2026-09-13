<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\LetterStatusLogResource\Pages;
use App\Models\LetterStatusLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Resource: LetterStatusLogResource
 *
 * Menampilkan audit trail riwayat perubahan status pengajuan surat.
 * Read-only (tanpa Create/Edit/Delete).
 * Akses navigasi: Admin & Kepsek saja.
 */
class LetterStatusLogResource extends Resource
{
    protected static ?string $model = LetterStatusLog::class;

    protected static ?string $modelLabel = 'Audit Status Surat';

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Audit Status Surat';

    protected static ?string $pluralLabel = 'Audit Status Surat';

    protected static ?string $slug = 'audit-status-surat';

    protected static ?string $navigationGroup = 'Pengaturan & Audit';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return $user && ($user->isAdmin() || $user->isKepsek());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['letterRequest.template', 'user']);
        $user = Auth::user();

        if ($user && $user->isGukar()) {
            return $query->whereHas('letterRequest', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nomor_surat')
                    ->label('Nomor Surat')
                    ->formatStateUsing(fn (?LetterStatusLog $record): string => $record?->letterRequest?->nomor_surat ?? '-')
                    ->disabled(),
                Forms\Components\TextInput::make('jenis_surat')
                    ->label('Jenis Surat')
                    ->formatStateUsing(fn (?LetterStatusLog $record): string => $record?->letterRequest?->template?->name ?? '-')
                    ->disabled(),
                Forms\Components\TextInput::make('uuid')
                    ->label('UUID Surat')
                    ->formatStateUsing(fn (?LetterStatusLog $record): string => $record?->letterRequest?->uuid ?? '-')
                    ->disabled(),
                Forms\Components\TextInput::make('user_name')
                    ->label('Pelaku Perubahan')
                    ->formatStateUsing(fn (?LetterStatusLog $record): string => $record?->user?->name ?? 'Sistem')
                    ->disabled(),
                Forms\Components\TextInput::make('from_status')
                    ->label('Status Asal')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'approved_admin' => 'Disetujui Admin',
                        'signed' => 'Ditandatangani',
                        'rejected' => 'Ditolak',
                        null => 'Draft Baru',
                        default => (string) $state,
                    })
                    ->disabled(),
                Forms\Components\TextInput::make('to_status')
                    ->label('Status Tujuan')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'approved_admin' => 'Disetujui Admin',
                        'signed' => 'Ditandatangani',
                        'rejected' => 'Ditolak',
                        default => (string) $state,
                    })
                    ->disabled(),
                Forms\Components\Textarea::make('note')
                    ->label('Catatan Audit')
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('ip_address')
                    ->label('Alamat IP')
                    ->disabled(),
                Forms\Components\TextInput::make('user_agent')
                    ->label('User Agent')
                    ->disabled(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Dokumen Surat')
                    ->columns(2)
                    ->schema([
                        Infolists\Components\TextEntry::make('nomor_surat')
                            ->label('Nomor Surat')
                            ->state(fn (LetterStatusLog $record): string => $record->letterRequest?->nomor_surat ?? '-')
                            ->fontFamily('mono')
                            ->weight('bold')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('jenis_surat')
                            ->label('Jenis Surat')
                            ->state(fn (LetterStatusLog $record): string => $record->letterRequest?->template?->name ?? '-'),
                        Infolists\Components\TextEntry::make('uuid')
                            ->label('UUID Surat')
                            ->state(fn (LetterStatusLog $record): string => $record->letterRequest?->uuid ?? '-')
                            ->fontFamily('mono')
                            ->copyable()
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Riwayat Perubahan Status')
                    ->columns(2)
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Waktu Perubahan')
                            ->dateTime('d F Y, H:i:s WIB'),
                        Infolists\Components\TextEntry::make('user_name')
                            ->label('Pelaku Perubahan (Aktor)')
                            ->state(fn (LetterStatusLog $record): string => $record->user?->name ?? 'Sistem')
                            ->badge()
                            ->color('gray'),
                        Infolists\Components\TextEntry::make('from_status')
                            ->label('Status Asal')
                            ->badge()
                            ->color('gray')
                            ->default('Draft Baru')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'pending' => 'Menunggu',
                                'approved_admin' => 'Disetujui Admin',
                                'signed' => 'Ditandatangani',
                                'rejected' => 'Ditolak',
                                'Draft Baru', null => 'Draft Baru',
                                default => (string) $state,
                            }),
                        Infolists\Components\TextEntry::make('to_status')
                            ->label('Status Tujuan')
                            ->badge()
                            ->colors([
                                'warning' => 'pending',
                                'info' => 'approved_admin',
                                'success' => 'signed',
                                'danger' => 'rejected',
                            ])
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'pending' => 'Menunggu',
                                'approved_admin' => 'Disetujui Admin',
                                'signed' => 'Ditandatangani',
                                'rejected' => 'Ditolak',
                                default => $state,
                            }),
                        Infolists\Components\TextEntry::make('note')
                            ->label('Catatan Audit')
                            ->default('-')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Informasi Keamanan & Jaringan')
                    ->columns(2)
                    ->schema([
                        Infolists\Components\TextEntry::make('ip_address')
                            ->label('Alamat IP')
                            ->default('127.0.0.1')
                            ->fontFamily('mono'),
                        Infolists\Components\TextEntry::make('user_agent')
                            ->label('User Agent / Peramban')
                            ->default('System'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Audit')
                    ->dateTime('d M Y, H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('nomor_surat')
                    ->label('Nomor Surat')
                    ->state(fn (LetterStatusLog $record): string => $record->letterRequest?->nomor_surat ?? '-')
                    ->default('-')
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('letterRequest', function (Builder $q) use ($search): Builder {
                            return $q->where('payload_data->nomor_surat', 'like', "%{$search}%");
                        });
                    }),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pelaku Perubahan (Aktor)')
                    ->default('Sistem')
                    ->searchable(),

                Tables\Columns\TextColumn::make('from_status')
                    ->label('Dari Status')
                    ->badge()
                    ->color('gray')
                    ->default('Draft Baru')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'approved_admin' => 'Disetujui Admin',
                        'signed' => 'Ditandatangani',
                        'rejected' => 'Ditolak',
                        'Draft Baru', null => 'Draft Baru',
                        default => (string) $state,
                    }),

                Tables\Columns\TextColumn::make('to_status')
                    ->label('Ke Status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'approved_admin',
                        'success' => 'signed',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'approved_admin' => 'Disetujui Admin',
                        'signed' => 'Ditandatangani',
                        'rejected' => 'Ditolak',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('note')
                    ->label('Catatan Audit')
                    ->default('-')
                    ->limit(30)
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('to_status')
                    ->label('Status Tujuan')
                    ->options([
                        'pending' => 'Menunggu',
                        'approved_admin' => 'Disetujui Admin',
                        'signed' => 'Ditandatangani',
                        'rejected' => 'Ditolak',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail Audit')
                    ->modalHeading('Rincian Log Audit Status Surat'),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Belum Ada Log Audit Status Surat')
            ->emptyStateDescription('Riwayat dan audit trail perubahan status surat keluar akan tercatat otomatis di sini.')
            ->emptyStateIcon('heroicon-o-clock')
            ->paginated([15, 30, 50]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLetterStatusLogs::route('/'),
        ];
    }
}
