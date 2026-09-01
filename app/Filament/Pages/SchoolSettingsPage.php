<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\SchoolSettings;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Halaman Pengaturan Sekolah (Settings Page).
 *
 * Hanya accessible oleh admin.
 * Mengelola konfigurasi sekolah yang digunakan di:
 * - Kop surat PDF
 * - Halaman verifikasi publik
 * - Header aplikasi
 */
class SchoolSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Pengaturan Sekolah';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.school-settings';

    protected static ?string $title = 'Pengaturan Sekolah';

    /** Hanya admin yang bisa akses. */
    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public ?array $data = [];

    public function mount(): void
    {
        $settings = SchoolSettings::getInstance();
        $this->form->fill($settings->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Identitas Sekolah')
                    ->schema([
                        TextInput::make('nama_sekolah')
                            ->label('Nama Sekolah')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('npsn')
                            ->label('NPSN')
                            ->maxLength(20),
                        Textarea::make('alamat')
                            ->label('Alamat Lengkap')
                            ->rows(3)
                            ->required(),
                        TextInput::make('kode_pos')
                            ->label('Kode Pos')
                            ->maxLength(10),
                        TextInput::make('telepon')
                            ->label('Nomor Telepon')
                            ->maxLength(20),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('website')
                            ->label('Website')
                            ->url()
                            ->maxLength(255),
                    ])->columns(2),

                Section::make('Kop Surat & Penomoran')
                    ->description('Baris instansi pada kop surat PDF dan kode untuk nomor surat otomatis.')
                    ->schema([
                        TextInput::make('kota_kabupaten')
                            ->label('Kota / Kabupaten')
                            ->placeholder('Contoh: Surakarta')
                            ->maxLength(255),
                        TextInput::make('kop_line_1')
                            ->label('Baris Kop 1')
                            ->placeholder('Contoh: PEMERINTAH KOTA SURAKARTA')
                            ->maxLength(255),
                        TextInput::make('kop_line_2')
                            ->label('Baris Kop 2')
                            ->placeholder('Contoh: DINAS PENDIDIKAN')
                            ->maxLength(255),
                        TextInput::make('kode_sekolah')
                            ->label('Kode Sekolah')
                            ->placeholder('Contoh: 421')
                            ->helperText('Digit awal nomor surat, mis. 421/001/SPD/2026')
                            ->maxLength(10),
                    ])->columns(2),

                Section::make('Kepala Sekolah')
                    ->schema([
                        TextInput::make('kepala_sekolah_nama')
                            ->label('Nama Kepala Sekolah')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('kepala_sekolah_nip')
                            ->label('NIP Kepala Sekolah')
                            ->maxLength(30),
                        TextInput::make('kepala_sekolah_jabatan')
                            ->label('Jabatan')
                            ->default('Kepala Sekolah')
                            ->maxLength(100),
                    ])->columns(2),

                Section::make('Logo & Tanda Tangan')
                    ->schema([
                        FileUpload::make('logo_path')
                            ->label('Logo Sekolah')
                            ->image()
                            ->disk('public')
                            ->directory('school-settings')
                            ->visibility('public')
                            ->maxSize(1024)
                            ->imagePreviewHeight('100')
                            ->helperText('Format: PNG/JPG, max 1MB. Rasio 1:1 disarankan.'),
                        FileUpload::make('ttd_kepsek_path')
                            ->label('Tanda Tangan Kepala Sekolah')
                            ->image()
                            ->disk('public')
                            ->directory('school-settings')
                            ->visibility('public')
                            ->maxSize(1024)
                            ->imagePreviewHeight('80')
                            ->helperText('Format: PNG dengan background transparan disarankan, max 1MB.'),
                    ])->columns(2),
            ])
            ->statePath('data')
            ->model(SchoolSettings::class);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $settings = SchoolSettings::getInstance();
        $settings->update($data);

        Notification::make()
            ->title('Pengaturan sekolah berhasil disimpan')
            ->success()
            ->send();

        // Refresh form
        $this->form->fill($settings->fresh()->toArray());
    }
}