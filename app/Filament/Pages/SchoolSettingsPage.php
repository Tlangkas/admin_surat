<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\SchoolSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\HtmlString;

/**
 * Halaman Pengaturan Sekolah (Settings Page).
 *
 * Hanya accessible oleh admin.
 * Mengelola konfigurasi sekolah yang digunakan di:
 * - Kop surat PDF & Visual Logo Studio
 * - Halaman verifikasi publik
 * - Header aplikasi
 */
class SchoolSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Pengaturan Sekolah';

    protected static ?string $navigationGroup = 'Pengaturan & Audit';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.school-settings';

    protected static ?string $title = 'Pengaturan Sekolah & Kop Surat';

    /** Hanya admin yang bisa akses. */
    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public ?array $data = [];

    // Dedicated Livewire properties untuk koordinat visual studio
    public bool $has_logo_kanan = false;
    public int $logo_width = 75;
    public int $logo_offset_x = 0;
    public int $logo_offset_y = 0;
    public string $logo_valign = 'middle';

    public int $logo_kanan_width = 75;
    public int $logo_kanan_offset_x = 0;
    public int $logo_kanan_offset_y = 0;
    public string $logo_kanan_valign = 'middle';

    public int $kop_gap = 10;

    public function updatedHasLogoKanan(mixed $value): void
    {
        $this->data['has_logo_kanan'] = (bool) $value;
    }

    public function updatedKopGap(mixed $value): void
    {
        $this->data['kop_gap'] = (int) ($value ?? 10);
    }

    public function mount(): void
    {
        $settings = SchoolSettings::getInstance();
        $this->form->fill($settings->toArray());

        $this->has_logo_kanan = (bool) ($settings->has_logo_kanan ?? false);
        $this->logo_width = (int) ($settings->logo_width ?? 75);
        $this->logo_offset_x = (int) ($settings->logo_offset_x ?? 0);
        $this->logo_offset_y = (int) ($settings->logo_offset_y ?? 0);
        $this->logo_valign = (string) ($settings->logo_valign ?? 'middle');

        $this->logo_kanan_width = (int) ($settings->logo_kanan_width ?? 75);
        $this->logo_kanan_offset_x = (int) ($settings->logo_kanan_offset_x ?? 0);
        $this->logo_kanan_offset_y = (int) ($settings->logo_kanan_offset_y ?? 0);
        $this->logo_kanan_valign = (string) ($settings->logo_kanan_valign ?? 'middle');

        $this->kop_gap = (int) ($settings->kop_gap ?? 10);
        $this->data['kop_gap'] = $this->kop_gap;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Pengaturan URL Verifikasi Surat')
                    ->description('Alamat server yang digunakan pada QR Code surat. Isi dengan IP atau domain server agar QR Code bisa di-scan dari perangkat lain.')
                    ->schema([
                        TextInput::make('verification_base_url')
                            ->label('URL Verifikasi (Base URL)')
                            ->placeholder('Contoh: http://192.168.1.100:8000')
                            ->helperText('Jika dikosongkan, otomatis menggunakan APP_URL dari file .env (' . config('app.url') . ')')
                            ->url()
                            ->maxLength(500)
                            ->live(debounce: 500),
                        Placeholder::make('preview_verification_url')
                            ->label('Preview URL Verifikasi Aktif')
                            ->content(function ($get): HtmlString {
                                $customUrl = trim((string) $get('verification_base_url'));
                                $baseUrl = $customUrl !== '' ? rtrim($customUrl, '/') : rtrim((string) config('app.url'), '/');

                                return new HtmlString(
                                    '<code class="text-sm">' . e($baseUrl . '/letter/verify/{uuid}?sig={hmac}') . '</code>'
                                );
                            }),
                    ])->columns(1),

                Section::make('Identitas Sekolah')
                    ->schema([
                        TextInput::make('nama_sekolah')
                            ->label('Nama Sekolah')
                            ->required()
                            ->maxLength(255)
                            ->live(debounce: 500),
                        TextInput::make('npsn')
                            ->label('NPSN')
                            ->maxLength(20)
                            ->live(debounce: 500),
                        TextInput::make('akreditasi')
                            ->label('Status Akreditasi')
                            ->placeholder('Contoh: Terakreditasi "A" atau A')
                            ->helperText('Status akreditasi sekolah untuk dicetak pada baris identitas Kop Surat')
                            ->maxLength(100)
                            ->live(debounce: 500),
                        Textarea::make('alamat')
                            ->label('Alamat Lengkap')
                            ->rows(3)
                            ->required()
                            ->live(debounce: 500),
                        TextInput::make('kode_pos')
                            ->label('Kode Pos')
                            ->maxLength(10)
                            ->live(debounce: 500),
                        TextInput::make('telepon')
                            ->label('Nomor Telepon')
                            ->maxLength(20)
                            ->live(debounce: 500),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255)
                            ->live(debounce: 500),
                        TextInput::make('website')
                            ->label('Website')
                            ->url()
                            ->maxLength(255)
                            ->live(debounce: 500),
                    ])->columns(2),

                Section::make('Teks Instansi Kop Surat')
                    ->description('Teks instansi yang ditampilkan di baris atas kop surat.')
                    ->schema([
                        TextInput::make('kota_kabupaten')
                            ->label('Kota / Kabupaten')
                            ->placeholder('Contoh: Surakarta')
                            ->maxLength(255)
                            ->live(debounce: 500),
                        TextInput::make('kop_line_1')
                            ->label('Baris Kop 1 (Instansi Induk)')
                            ->placeholder('Contoh: PEMERINTAH KOTA SURAKARTA')
                            ->maxLength(255)
                            ->live(debounce: 500),
                        TextInput::make('kop_line_2')
                            ->label('Baris Kop 2 (Dinas / Cabang)')
                            ->placeholder('Contoh: DINAS PENDIDIKAN')
                            ->maxLength(255)
                            ->live(debounce: 500),
                        TextInput::make('kode_sekolah')
                            ->label('Kode Lembaga / Sekolah (Nomor Surat)')
                            ->placeholder('Contoh: 29.15')
                            ->default('29.15')
                            ->helperText('Kode lembaga dalam nomor surat resmi, mis. 001/29.15/E/IX/2026')
                            ->maxLength(20),
                        TextInput::make('starting_letter_number')
                            ->label('Nomor Urut Awal Tahun Ini')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->helperText('Nomor urut awal buku agenda keluar (default 1, atau sesuaikan jika melanjutkan dari buku agenda manual)'),
                    ])->columns(2),

                Section::make('Upload Berkas Logo')
                    ->description('Unggah berkas logo sekolah di bawah ini. Anda dapat mengatur ukuran dan posisi secara visual langsung pada kanvas di atas.')
                    ->schema([
                        Toggle::make('has_logo_kanan')
                            ->label('Gunakan Logo Sekunder (Kanan)')
                            ->helperText('Aktifkan jika kop surat memiliki logo kedua di sisi kanan. Jika dinonaktifkan, kop surat hanya memakai 1 logo utama di kiri.')
                            ->live()
                            ->afterStateUpdated(function ($state): void {
                                $this->has_logo_kanan = (bool) $state;
                            })
                            ->columnSpanFull(),

                        FileUpload::make('logo_path')
                            ->label('Logo Utama (Kiri)')
                            ->image()
                            ->disk('public')
                            ->directory('school-settings')
                            ->visibility('public')
                            ->maxSize(1024)
                            ->imagePreviewHeight('90')
                            ->helperText('Format: PNG/JPG (Transparan disarankan).'),

                        FileUpload::make('logo_kanan_path')
                            ->label('Logo Sekunder (Kanan)')
                            ->image()
                            ->disk('public')
                            ->directory('school-settings')
                            ->visibility('public')
                            ->maxSize(1024)
                            ->imagePreviewHeight('90')
                            ->helperText('Format: PNG/JPG (Transparan disarankan).')
                            ->visible(fn ($get): bool => (bool) ($get('has_logo_kanan') ?? $this->has_logo_kanan)),

                        TextInput::make('kop_gap')
                            ->label('Jarak / Celah Logo ke Teks (px)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(80)
                            ->default(10)
                            ->suffix('px')
                            ->helperText('Jarak horizontal antara logo dan teks kop surat (default: 10px). Pengaturan ini sinkron dengan slider Visual Studio di atas.')
                            ->live(debounce: 300)
                            ->afterStateUpdated(function ($state): void {
                                $this->kop_gap = (int) ($state ?? 10);
                            })
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Tanda Tangan & Pejabat Sekolah')
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

    public function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Pengaturan & Posisi Logo')
                ->submit('save'),
            Action::make('regenerate_pdfs')
                ->label('Regenerasi Semua PDF Surat')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Regenerasi Semua PDF Surat?')
                ->modalDescription('Semua PDF surat yang sudah ditandatangani akan di-generate ulang dengan URL verifikasi terbaru. Proses ini tidak bisa dibatalkan.')
                ->modalSubmitActionLabel('Ya, Regenerasi Sekarang')
                ->action('regenerateAllPdfs'),
        ];
    }

    public function resetLogoSettings(): void
    {
        $this->logo_width = 75;
        $this->logo_offset_x = 0;
        $this->logo_offset_y = 0;
        $this->logo_valign = 'middle';

        $this->logo_kanan_width = 75;
        $this->logo_kanan_offset_x = 0;
        $this->logo_kanan_offset_y = 0;
        $this->logo_kanan_valign = 'middle';

        $this->kop_gap = 10;
        $this->data['kop_gap'] = 10;

        Notification::make()
            ->title('Posisi, ukuran, dan jarak logo dikembalikan ke standar')
            ->info()
            ->send();
    }

    public function save(): void
    {
        $formData = $this->form->getState();

        $payload = array_merge($formData, [
            'has_logo_kanan' => $this->has_logo_kanan,
            'logo_width' => $this->logo_width,
            'logo_offset_x' => $this->logo_offset_x,
            'logo_offset_y' => $this->logo_offset_y,
            'logo_valign' => $this->logo_valign,
            'logo_kanan_width' => $this->logo_kanan_width,
            'logo_kanan_offset_x' => $this->logo_kanan_offset_x,
            'logo_kanan_offset_y' => $this->logo_kanan_offset_y,
            'logo_kanan_valign' => $this->logo_kanan_valign,
            'kop_gap' => $this->kop_gap,
        ]);

        $settings = SchoolSettings::getInstance();
        $settings->update($payload);

        Notification::make()
            ->title('Pengaturan sekolah & tata letak logo berhasil disimpan')
            ->success()
            ->send();

        $this->form->fill($settings->fresh()->toArray());
    }

    /** Regenerasi semua PDF surat signed dengan URL verifikasi terbaru. */
    public function regenerateAllPdfs(): void
    {
        $exitCode = Artisan::call('letters:regenerate-pdfs');
        $output = trim(Artisan::output());

        if ($exitCode === 0) {
            Notification::make()
                ->title('Regenerasi PDF selesai')
                ->body($output)
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Regenerasi PDF gagal')
                ->body($output)
                ->danger()
                ->send();
        }
    }
}