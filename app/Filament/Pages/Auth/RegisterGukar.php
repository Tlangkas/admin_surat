<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Models\Karyawan;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Register;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Halaman: RegisterGukar
 *
 * Pendaftaran Mandiri Guru & Karyawan dengan validasi 3 tahap:
 * 1. Validasi NIP di master karyawan (harus terdaftar & belum punya akun)
 * 2. Input Email & Password
 * 3. Ringkasan Verifikasi & Aktivasi Akun
 */
class RegisterGukar extends Register
{
    public ?array $data = [];

    public function getTitle(): string
    {
        return 'Pendaftaran Mandiri Gukar';
    }

    public function getHeading(): string
    {
        return 'Pendaftaran Akun Guru & Karyawan';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Validasi NIP')
                        ->description('Verifikasi NIP sekolah')
                        ->icon('heroicon-o-identification')
                        ->schema([
                            Forms\Components\TextInput::make('nip')
                                ->label('NIP (Nomor Induk Pegawai)')
                                ->placeholder('Masukkan NIP terdaftar...')
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (?string $state, Forms\Set $set): void {
                                    if (! $state) {
                                        $set('verified_karyawan_info', null);

                                        return;
                                    }

                                    $cleanNip = trim($state);
                                    $karyawan = Karyawan::where('nip', $cleanNip)->first();

                                    if (! $karyawan) {
                                        $set('verified_karyawan_info', '❌ NIP tidak ditemukan di data master sekolah.');

                                        return;
                                    }

                                    if ($karyawan->user_id || User::where('name', $karyawan->nama)->exists()) {
                                        $set('verified_karyawan_info', '⚠️ NIP ini sudah memiliki akun login terdaftar.');

                                        return;
                                    }

                                    $jabatanText = ! empty($karyawan->jabatan) ? " ({$karyawan->jabatan})" : '';
                                    $set('verified_karyawan_info', "✅ Data Ditemukan: {$karyawan->nama}{$jabatanText}");
                                }),

                            Forms\Components\Placeholder::make('verified_karyawan_info')
                                ->label('Status Verifikasi NIP')
                                ->content(fn (Forms\Get $get): string => $get('verified_karyawan_info') ?? 'Masukkan NIP untuk memeriksa data.'),
                        ]),

                    Wizard\Step::make('Buat Akun')
                        ->description('Email & Kata Sandi')
                        ->icon('heroicon-o-key')
                        ->schema([
                            Forms\Components\TextInput::make('email')
                                ->label('Alamat Email')
                                ->email()
                                ->required()
                                ->unique(table: 'users', column: 'email'),

                            Forms\Components\TextInput::make('password')
                                ->label('Kata Sandi')
                                ->password()
                                ->required()
                                ->minLength(8)
                                ->same('password_confirmation'),

                            Forms\Components\TextInput::make('password_confirmation')
                                ->label('Konfirmasi Kata Sandi')
                                ->password()
                                ->required(),
                        ]),

                    Wizard\Step::make('Verifikasi')
                        ->description('Konfirmasi Data')
                        ->icon('heroicon-o-check-circle')
                        ->schema([
                            Forms\Components\Placeholder::make('summary')
                                ->label('Ringkasan Identitas Pendaftar')
                                ->content(function (Forms\Get $get): string {
                                    $nip = trim((string) $get('nip'));
                                    $email = trim((string) $get('email'));
                                    $karyawan = Karyawan::where('nip', $nip)->first();

                                    if (! $karyawan) {
                                        return 'Mohon selesaikan Tahap 1 (Validasi NIP).';
                                    }

                                    $jabatanVal = ! empty($karyawan->jabatan) ? $karyawan->jabatan : '-';

                                    return "Nama: {$karyawan->nama}\nNIP: {$karyawan->nip}\nJabatan: {$jabatanVal}\nEmail Akun: {$email}";
                                }),
                        ]),
                ])
                    ->submitAction(
                        Forms\Components\Actions\Action::make('submit_registration')
                            ->label('Aktifkan & Daftar Akun Gukar')
                            ->icon('heroicon-o-user-plus')
                            ->color('primary')
                            ->action('register')
                    ),
            ])
            ->statePath('data');
    }

    public function register(): ?RegistrationResponse
    {
        $data = $this->form->getState();

        $nip = trim((string) ($data['nip'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        $karyawan = Karyawan::where('nip', $nip)->first();

        if (! $karyawan) {
            throw ValidationException::withMessages([
                'data.nip' => 'NIP tidak ditemukan di data master sekolah.',
            ]);
        }

        if ($karyawan->user_id) {
            throw ValidationException::withMessages([
                'data.nip' => 'NIP ini sudah terdaftar dan memiliki akun login.',
            ]);
        }

        if (User::where('name', $karyawan->nama)->exists()) {
            throw ValidationException::withMessages([
                'data.nip' => 'Data dengan nama ini sudah memiliki akun login terdaftar.',
            ]);
        }

        $user = User::create([
            'name' => $karyawan->nama,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'gukar',
        ]);

        $karyawan->update(['user_id' => $user->id]);

        auth()->login($user);

        Notification::make()
            ->title('Pendaftaran Akun Berhasil!')
            ->body("Selamat datang, {$user->name}. Akun Anda telah aktif.")
            ->success()
            ->send();

        return app(RegistrationResponse::class);
    }
}
