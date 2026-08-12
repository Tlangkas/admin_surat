<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Exports\TemplateKaryawanExport;
use App\Filament\Resources\KaryawanResource\Pages;
use App\Imports\KaryawanImport;
use App\Models\Karyawan;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;

/**
 * Resource: KaryawanResource
 *
 * Mengelola data Guru & Karyawan dengan fitur pembuatan akun login 1-klik.
 */
class KaryawanResource extends Resource
{
    protected static ?string $model = Karyawan::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Guru & Karyawan';

    protected static ?string $pluralLabel = 'Guru & Karyawan';

    protected static ?string $slug = 'guru-karyawan';

    public static function canAccess(): bool
    {
        if (auth()->guest()) {
            return false;
        }

        return auth()->user()->isAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Data Guru & Karyawan')
                    ->description('Lengkapi identitas guru & karyawan sekolah.')
                    ->schema([
                        Forms\Components\TextInput::make('nama')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('nip')
                            ->label('NIP')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(30),
                        Forms\Components\TextInput::make('jabatan')
                            ->label('Jabatan')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(['default' => 1, 'md' => 2]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex()
                    ->color('gray')
                    ->size('sm'),
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Lengkap')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nip')
                    ->label('NIP')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jabatan')
                    ->label('Jabatan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Akun Login')
                    ->default('Belum Ada Akun')
                    ->badge()
                    ->color(fn ($state) => $state !== 'Belum Ada Akun' ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ditambahkan')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->headerActions([
                Action::make('downloadTemplate')
                    ->label('Download Template Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function () {
                        return Excel::download(new TemplateKaryawanExport, 'template-import-guru-karyawan.xlsx');
                    }),

                Action::make('importExcel')
                    ->label('Import Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->label('File Excel')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                            ])
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $file = $data['file'];

                        $headingErrors = [];
                        try {
                            $headings = (new HeadingRowImport)->toArray($file)[0][0] ?? [];
                            $expected = ['nama', 'nip', 'jabatan'];
                            $missing = array_diff($expected, array_map('strtolower', $headings));
                            if (! empty($missing)) {
                                $headingErrors[] = 'Format kolom tidak sesuai. Pastikan ada kolom: Nama, NIP, Jabatan.';
                            }
                        } catch (\Throwable $e) {
                            $headingErrors[] = 'File tidak dapat dibaca. Pastikan format .xlsx.';
                        }

                        if (! empty($headingErrors)) {
                            Notification::make()
                                ->title('Gagal Import')
                                ->body(implode("\n", $headingErrors))
                                ->danger()
                                ->send();

                            return;
                        }

                        try {
                            Excel::import(new KaryawanImport, $file);
                            Notification::make()
                                ->title('Import Berhasil')
                                ->body('Data guru & karyawan berhasil diimport.')
                                ->success()
                                ->send();
                        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                            $errors = $e->errors();
                            $messages = collect($errors)->flatten()->take(5)->implode("\n");
                            Notification::make()
                                ->title('Import Gagal')
                                ->body($messages)
                                ->danger()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Import Gagal')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Action::make('create_account')
                    ->label('Buatkan Akun Login')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->visible(fn (Karyawan $record): bool => empty($record->user_id) && ! User::where('name', $record->nama)->exists())
                    ->requiresConfirmation()
                    ->action(function (Karyawan $record): void {
                        $cleanNip = preg_replace('/[^0-9]/', '', $record->nip);
                        $email = (! empty($cleanNip) ? $cleanNip : 'gukar_' . $record->id) . '@sekolah.sch.id';

                        if (User::where('email', $email)->exists()) {
                            Notification::make()
                                ->title('Gagal Membuat Akun')
                                ->body("Email {$email} sudah terpakai akun lain. Perbaiki NIP atau hubungi admin.")
                                ->danger()
                                ->send();

                            return;
                        }

                        try {
                            $user = User::create([
                                'name' => $record->nama,
                                'email' => $email,
                                'password' => Hash::make('password'),
                                'role' => 'gukar',
                            ]);

                            $record->update(['user_id' => $user->id]);

                            Notification::make()
                                ->title('Akun Login Berhasil Dibuat')
                                ->body("Email: {$email} | Password: password")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Gagal Membuat Akun')
                                ->body('Terjadi kesalahan saat membuat akun: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKaryawan::route('/'),
            'create' => Pages\CreateKaryawan::route('/create'),
            'edit' => Pages\EditKaryawan::route('/{record}/edit'),
        ];
    }
}
