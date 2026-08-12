# E-Surat Sekolah — AGENTS.md

## Serving

- **PHP 8.2.12** — `php artisan` CLI working (resolved container binding hook in AppServiceProvider). Run migrations with `php artisan migrate`.
- Use **XAMPP Apache** with vhost `e-surat.local` (configured in `C:\xampp\apache\conf\extra\httpd-vhosts.conf`, host entry in `C:\Windows\System32\drivers\etc\hosts`).
- Alternative: `php -S localhost:8000 -t public server.php` (router script handles `/livewire/update`).

## Database

- MySQL `e_surat` (MariaDB 10.4.32 via XAMPP). Collation set to `utf8mb4_general_ci` in `.env` (MariaDB does not support `utf8mb4_0900_ai_ci`).
- Tables: `users` (+ role column), `letter_templates`, `letter_requests`, `notifications`, `jobs`, `cache`, `sessions`.
- Seeder creates 3 users (`admin@sekolah.sch.id`, `kepsek@sekolah.sch.id`, `gukar@sekolah.sch.id`) all password `password`.
- Tests: `php artisan test` (SQLite in-memory via phpunit.xml — aman, tidak menyentuh DB dev).

## Architecture

- **No manual controllers.** CRUD only through Filament Resources (`app/Filament/Resources/`).
- Business logic in **`app/Actions/Letter/`**: `GeneratePdfAndQrAction` (dompdf + QR code), `BroadcastLetterStatusAction` (status update + Reverb broadcast).
- Letter status workflow: `pending` → `approved_admin` (admin) → `signed` (kepsek, generates PDF) or `rejected` (admin).
- Events: `LetterStatusUpdated` broadcasts on private channel `user.{userId}`.
- Models: `User` (FilamentUser, role helpers), `LetterTemplate` (json `variables` cast), `LetterRequest` (uuid auto, json `payload_data`, status helpers).
- Routes: `/` → redirect to `/admin`, `/letter/verify/{uuid}` (public verification page), `channels.php` defines `user.{userId}` broadcast channel.

## Filament v3 Specifics

- Panel at `/admin`, login, database notifications, broadcasting enabled.
- Resources: `LetterTemplateResource` (RichEditor + KeyValue variables), `LetterRequestResource` (table actions with role-based visibility).
- Asset publishing: Filament JS/CSS/Alpine component files copied to `public/` manually. If adding new Filament plugins, copy their dist files to `public/js/filament/{package}/` and `public/css/filament/{package}/`.

## Key Config

- Queue: `database` driver in `.env` (requires `jobs` table).
- Broadcasting: `reverb` driver (not configured — REVERB_APP_ID/KEY/SECRET empty in `.env`; safe to ignore.
- PDF generation: `config/dompdf.php` (remote enabled, utf8). PDFs stored on `pdfs` disk (`storage/app/pdfs/`).
- Storage link: `public/storage` symlink exists (created manually).
- Session: file driver. Cache: file driver.
- Vite: configured but not required (Filament assets pre-published manually).

## Testing

- PHPUnit di root. Ada feature/unit test (registrasi gukar, letter request, verifikasi, PDF, template edit, nomor surat & notifikasi).
- `phpunit.xml` memakai SQLite in-memory (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`) — aman dijalankan tanpa menyentuh DB dev.

## Gotchas

- `php artisan` CLI sekarang berfungsi. Gunakan `php artisan migrate`, `php artisan test`, `php artisan make:migration`, dst.
- Filament login form uses Livewire. Apache handles routing natively via `.htaccess`. When using built-in server, the `server.php` router script is required.
- WebSocket errors (`ws://localhost:8080/`) in browser console are expected — Reverb not running. Does not affect functionality.
