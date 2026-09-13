<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Exports\TemplateSiswaExport;
use App\Filament\Resources\SiswaResource\Pages;
use App\Imports\SiswaImport;
use App\Models\Siswa;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;

/**
 * Resource: SiswaResource
 *
 * Mengelola data Siswa (Nama, NISN, Kelas, Jurusan).
 * Fitur: CRUD, Download Template Excel, Import Excel.
 */
class SiswaResource extends Resource
{
    protected static ?string $model = Siswa::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Data Siswa';

    protected static ?string $pluralLabel = 'Data Siswa';

    protected static ?string $slug = 'data-siswa';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        if (auth()->guest()) {
            return false;
        }

        return auth()->user()->isAdmin() || auth()->user()->isKepsek();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identitas Siswa')
                    ->description('Lengkapi data identitas siswa, kelas, dan jurusan.')
                    ->schema([
                        Forms\Components\TextInput::make('nama')
                            ->label('Nama Lengkap Siswa')
                            ->placeholder('Contoh: Ahmad Subagja')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('nisn')
                            ->label('NISN (Nomor Induk Siswa Nasional)')
                            ->placeholder('Contoh: 0051234567')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(30),

                        Forms\Components\TextInput::make('kelas')
                            ->label('Kelas')
                            ->placeholder('Contoh: X RPL 1, XII TKJ 2')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('jurusan')
                            ->label('Jurusan / Kompetensi Keahlian')
                            ->placeholder('Contoh: Rekayasa Perangkat Lunak, IPA, IPS')
                            ->required()
                            ->maxLength(150),
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
                    ->rowIndex(),

                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nisn')
                    ->label('NISN')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('kelas')
                    ->label('Kelas')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('jurusan')
                    ->label('Jurusan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ditambahkan')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kelas')
                    ->label('Filter Kelas')
                    ->options(fn () => Siswa::query()->distinct()->pluck('kelas', 'kelas')->toArray()),

                Tables\Filters\SelectFilter::make('jurusan')
                    ->label('Filter Jurusan')
                    ->options(fn () => Siswa::query()->distinct()->pluck('jurusan', 'jurusan')->toArray()),
            ])
            ->headerActions([
                Action::make('downloadTemplate')
                    ->label('Download Template Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function () {
                        return Excel::download(new TemplateSiswaExport, 'template-import-data-siswa.xlsx');
                    }),

                Action::make('importExcel')
                    ->label('Import Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->label('File Excel (.xlsx)')
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
                            $expected = ['nama', 'nisn', 'kelas', 'jurusan'];
                            $missing = array_diff($expected, array_map('strtolower', $headings));
                            if (! empty($missing)) {
                                $headingErrors[] = 'Format kolom tidak sesuai. Pastikan ada kolom: Nama, NISN, Kelas, Jurusan.';
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
                            Excel::import(new SiswaImport, $file);
                            Notification::make()
                                ->title('Import Berhasil')
                                ->body('Data siswa berhasil diimport.')
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->isAdmin()),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Belum Ada Data Siswa')
            ->emptyStateDescription('Tambahkan data siswa secara manual atau gunakan fitur impor file Excel.')
            ->emptyStateIcon('heroicon-o-academic-cap');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSiswas::route('/'),
            'create' => Pages\CreateSiswa::route('/create'),
            'edit' => Pages\EditSiswa::route('/{record}/edit'),
        ];
    }
}
