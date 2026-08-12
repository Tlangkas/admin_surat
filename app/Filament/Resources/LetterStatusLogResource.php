<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\LetterStatusLogResource\Pages;
use App\Models\LetterStatusLog;
use Filament\Forms;
use Filament\Forms\Form;
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

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Audit Status Surat';

    protected static ?string $pluralLabel = 'Audit Status Surat';

    protected static ?string $slug = 'audit-status-surat';

    protected static ?string $navigationGroup = 'Pengawasan';

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
        $query = parent::getEloquentQuery()->with(['letterRequest', 'user']);
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
                Forms\Components\TextInput::make('letterRequest.uuid')
                    ->label('UUID Surat')
                    ->disabled(),
                Forms\Components\TextInput::make('user.name')
                    ->label('Pelaku Perubahan')
                    ->disabled(),
                Forms\Components\TextInput::make('from_status')
                    ->label('Status Asal')
                    ->disabled(),
                Forms\Components\TextInput::make('to_status')
                    ->label('Status Tujuan')
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Audit')
                    ->dateTime('d M Y, H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('letterRequest.payload_data.nomor_surat')
                    ->label('Nomor Surat')
                    ->default('-')
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pelaku Perubahan (Aktor)')
                    ->default('Sistem')
                    ->searchable(),

                Tables\Columns\TextColumn::make('from_status')
                    ->label('Dari Status')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'approved_admin' => 'Disetujui Admin',
                        'signed' => 'Ditandatangani',
                        'rejected' => 'Ditolak',
                        null => 'Draft Baru',
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
                Tables\Actions\ViewAction::make()->label('Detail Audit'),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->paginated([15, 30, 50]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLetterStatusLogs::route('/'),
        ];
    }
}
