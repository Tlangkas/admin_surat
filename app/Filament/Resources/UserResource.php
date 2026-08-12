<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Policies\UserPolicy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

/**
 * Resource: UserResource
 *
 * Mengelola Pengguna Aplikasi (Admin, Kepsek, Guru & Karyawan).
 * Otomatis menghubungkan akun login Gukar dengan profil Karyawan (NIP & Jabatan).
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Pengguna';

    protected static ?string $pluralLabel = 'Pengguna';

    protected static ?string $slug = 'pengguna';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 2;

    public static function getPolicyClass(): string
    {
        return UserPolicy::class;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pengguna & Profil Gukar')
                    ->description('Kelola detail akun login serta NIP & Jabatan untuk Guru & Karyawan.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Alamat Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Select::make('role')
                            ->label('Role Akses')
                            ->options([
                                'admin' => 'Administrator',
                                'kepsek' => 'Kepala Sekolah',
                                'gukar' => 'Guru & Karyawan',
                            ])
                            ->required()
                            ->live()
                            ->disabled(fn (): bool => ! auth()->user()?->isAdmin())
                            ->dehydrated(fn (): bool => auth()->user()?->isAdmin() ?? false),
                        Forms\Components\TextInput::make('nip')
                            ->label('NIP (Nomor Induk Pegawai)')
                            ->placeholder('198501012010011001')
                            ->visible(fn (Forms\Get $get): bool => $get('role') === 'gukar')
                            ->required(fn (Forms\Get $get): bool => $get('role') === 'gukar'),
                        Forms\Components\TextInput::make('jabatan')
                            ->label('Jabatan')
                            ->placeholder('Guru Mata Pelajaran')
                            ->visible(fn (Forms\Get $get): bool => $get('role') === 'gukar')
                            ->required(fn (Forms\Get $get): bool => $get('role') === 'gukar'),
                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255)
                            ->helperText(fn (string $operation): ?string => $operation === 'edit' ? 'Kosongkan jika tidak ingin mengubah password.' : null),
                    ])
                    ->columns(['default' => 1, 'md' => 2]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Lengkap')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Alamat Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Role Akses')
                    ->badge()
                    ->colors([
                        'danger' => 'admin',
                        'warning' => 'kepsek',
                        'info' => 'gukar',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'admin' => 'Administrator',
                        'kepsek' => 'Kepala Sekolah',
                        'gukar' => 'Guru & Karyawan',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('karyawan.nip')
                    ->label('NIP Terhubung')
                    ->fontFamily('mono')
                    ->default('-')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Role')
                    ->options([
                        'admin' => 'Administrator',
                        'kepsek' => 'Kepala Sekolah',
                        'gukar' => 'Guru & Karyawan',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (User $record): bool => auth()->user()?->isAdmin() && auth()->id() !== $record->id),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->isAdmin()),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
