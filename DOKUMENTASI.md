# DOKUMENTASI APLIKASI E-SURAT SEKOLAH

## 📋 DAFTAR ISI

1. [Arsitektur Aplikasi](#1-arsitektur-aplikasi)
2. [Struktur Database](#2-struktur-database)
3. [Models (Model Data)](#3-models-model-data)
4. [Filament Resources (CRUD)](#4-filament-resources-crud)
5. [Actions (Logika Bisnis)](#5-actions-logika-bisnis)
6. [Events & Broadcasting](#6-events--broadcasting)
7. [Routes (Rute)](#7-routes-rute)
8. [Views (Tampilan)](#8-views-tampilan)
9. [Imports & Exports (Excel)](#9-imports--exports-excel)
10. [Migrations (Migrasi Database)](#10-migrations-migrasi-database)
11. [Seeders (Data Awal)](#11-seeders-data-awal)
12. [Panel Configuration](#12-panel-configuration)
13. [Alur Kerja (Workflow)](#13-alur-kerja-workflow)
14. [Konfigurasi & Environment](#14-konfigurasi--environment)
15. [Cara Menjalankan](#15-cara-menjalankan)

---

## 1. ARSITEKTUR APLIKASI

### 1.1 Stack Teknologi

| Komponen | Teknologi | Versi |
|----------|-----------|-------|
| Framework | Laravel | 11.x |
| Admin Panel | Filament | v3 |
| Database | MariaDB (via XAMPP) | 10.4.32 |
| PDF | barryvdh/laravel-dompdf | - |
| QR Code | simplesoftwareio/simple-qrcode | - |
| Excel | maatwebsite/excel | 3.1 |
| Broadcasting | laravel/reverb | - |
| CSS/JS | Tailwind CSS (via CDN) | - |

### 1.2 Pola Arsitektur

```
┌─────────────────────────────────────────────────────────────┐
│                     WEB BROWSER                             │
├─────────────────────────────────────────────────────────────┤
│                   FILAMENT v3 PANEL                         │
│  (Tanpa manual controller - semua CRUD via Filament)        │
├─────────────────────────────────────────────────────────────┤
│                    ACTIONS (Bisnis)                         │
│  app/Actions/Letter/                                        │
├─────────────────────────────────────────────────────────────┤
│                    MODELS (Data)                            │
│  app/Models/                                                │
├─────────────────────────────────────────────────────────────┤
│                 DATABASE (MariaDB)                          │
└─────────────────────────────────────────────────────────────┘
```

### 1.3 Aturan Penting

- **TIDAK BOLEH** membuat HTTP Controllers standar atau Blade views manual untuk fitur CRUD. Semua CRUD WAJIB menggunakan Filament v3 Resources.
- Logika bisnis WAJIB dipisahkan ke dalam `app/Actions/` (Action Pattern).
- Kode WAJIB menggunakan PHP 8.3 Strict Typing (`declare(strict_types=1)` dan return types eksplisit).

---

## 2. STRUKTUR DATABASE

### 2.1 Tabel `users`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | bigint, PK, auto-increment | ID unik user |
| name | string(255) | Nama lengkap |
| email | string(255), unique | Email login |
| password | string(255) | Password (hashed) |
| role | enum('admin','kepsek','gukar') | Role user |
| timestamps | created_at, updated_at | Waktu dibuat/diubah |

**Relasi:** One-to-many ke `letter_requests` (user_id)

### 2.2 Tabel `karyawan`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | bigint, PK, auto-increment | ID unik karyawan |
| user_id | bigint, FK → users.id, nullable | Link ke akun user (opsional) |
| nama | string(255) | Nama lengkap karyawan |
| nip | string(30), unique | NIP (Nomor Induk Pegawai) |
| jabatan | string(255) | Jabatan (Guru, TU, dll) |
| timestamps | created_at, updated_at | Waktu dibuat/diubah |

**Relasi:** Belongs-to ke `users` (user_id)

### 2.3 Tabel `letter_templates`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | bigint, PK, auto-increment | ID unik template |
| name | string(255) | Nama template (e.g., "Surat Jalan") |
| content | longText | HTML template untuk DomPDF |
| variables | json, nullable | Daftar variabel untuk form |
| is_active | boolean, default: true | Status aktif template |
| timestamps | created_at, updated_at | Waktu dibuat/diubah |

**Relasi:** One-to-many ke `letter_requests` (template_id)

### 2.4 Tabel `letter_requests`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | bigint, PK, auto-increment | ID unik request |
| uuid | uuid, unique | UUID publik (untuk QR) |
| user_id | bigint, FK → users.id | Pengaju surat |
| template_id | bigint, FK → letter_templates | Template yang digunakan |
| status | string(30), default: 'pending' | Status (lihat status workflow) |
| payload_data | json, nullable | Data isian surat (key-value) |
| pdf_path | string(255), nullable | Path file PDF yang sudah ditandatangani |
| timestamps | created_at, updated_at | Waktu dibuat/diubah |

**Status Workflow:**
```
pending → approved_admin (disetujui admin) → signed (ditandatangani kepsek)
pending → rejected (ditolak admin)
```

**Relasi:**
- Belongs-to ke `users` (user_id)
- Belongs-to ke `letter_templates` (template_id)

---

## 3. MODELS (MODEL DATA)

### 3.1 `app/Models/User.php`

```php
class User extends Authenticatable implements FilamentUser
```

**Fungsi:** Model user dengan autentikasi Filament.

**Method penting:**
- `canAccessPanel(Panel $panel): bool` — Semua user bisa akses panel Filament
- `isAdmin(): bool` — Cek role admin
- `isKepsek(): bool` — Cek role kepsek
- `isGukar(): bool` — Cek role gukar
- `letterRequests()` — HasMany relasi ke LetterRequest

**Fillable:** name, email, password, role
**Hidden:** password, remember_token

### 3.2 `app/Models/Karyawan.php`

```php
class Karyawan extends Model
```

**Fungsi:** Model data guru/karyawan yang terpisah dari user.

**Method penting:**
- `user(): BelongsTo` — Relasi ke User

**Fillable:** user_id, nama, nip, jabatan
**Table:** 'karyawan' (nama tabel eksplisit)

### 3.3 `app/Models/LetterTemplate.php`

```php
class LetterTemplate extends Model
```

**Fungsi:** Model template surat dengan konten HTML untuk DomPDF.

**Method penting:**
- `letterRequests(): HasMany` — Relasi ke LetterRequest

**Cast:**
- `variables` → array (JSON otomatis di-decode)
- `is_active` → boolean

**Fillable:** name, content, variables, is_active

### 3.4 `app/Models/LetterRequest.php`

```php
class LetterRequest extends Model
```

**Fungsi:** Model pengajuan surat dengan UUID otomatis.

**Method penting:**
- `static booted()` — Auto-generate UUID saat creating
- `user(): BelongsTo` — Relasi ke User
- `template(): BelongsTo` — Relasi ke LetterTemplate
- `isPending(): bool` — Cek status 'pending'
- `isApprovedAdmin(): bool` — Cek status 'approved_admin'
- `isSigned(): bool` — Cek status 'signed'
- `isRejected(): bool` — Cek status 'rejected'
- `verificationUrl(): string` — URL verifikasi berbasis UUID

**Cast:**
- `payload_data` → array (JSON otomatis di-decode)

**Fillable:** uuid, user_id, template_id, status, payload_data, pdf_path

---

## 4. FILAMENT RESOURCES (CRUD)

### 4.1 `app/Filament/Resources/KaryawanResource.php`

**Fungsi:** CRUD Data Guru & Karyawan. Hanya bisa diakses oleh **admin**.

**Form Field:**
- `nama` — TextInput, required, max 255 karakter
- `nip` — TextInput, required, unique, max 30 karakter
- `jabatan` — TextInput, required, max 255 karakter

**Table Columns:**
- No (rowIndex)
- Nama (searchable)
- NIP (searchable)
- Jabatan (searchable)
- Ditambahkan (datetime, toggleable)

**Header Actions:**
- **Download Template Excel** — Download file template .xlsx (kolom: Nama, NIP, Jabatan)
- **Import Excel** — Upload file .xlsx, validasi heading row, import data

**Table Actions:**
- Edit
- Delete

**Bulk Actions:**
- Delete

**Access Control:** `canAccess()` — hanya admin

**Halaman:**
- `/admin/guru-karyawan` — List
- `/admin/guru-karyawan/create` — Create
- `/admin/guru-karyawan/{record}/edit` — Edit

### 4.2 `app/Filament/Resources/LetterTemplateResource.php`

**Fungsi:** CRUD Template Surat. Bisa diakses oleh **admin** dan **kepsek**.

**Access Control:** `canAccess()` — admin atau kepsek

**Halaman:**
- `/admin/template-surat` — List
- `/admin/template-surat/create` — Create
- `/admin/template-surat/{record}/edit` — Edit

### 4.3 `app/Filament/Resources/LetterRequestResource.php`

**Fungsi:** CRUD Pengajuan Surat ("Surat Jalan"). Bisa diakses oleh **semua role**.

**Form Field (Create/Edit):**
- `template_id` — Select (relasi ke template), required, live update
- `payload_data` — KeyValue editor, ditampilkan setelah pilih template

**Table Columns:**
- UUID (searchable, toggleable)
- Pengaju (user.name, searchable)
- Template (template.name)
- Status — badge dengan warna:
  - pending → warning (Menunggu)
  - approved_admin → info (Disetujui Admin)
  - signed → success (Ditandatangani)
  - rejected → danger (Ditolak)
- Diajukan (created_at, sortable)

**Table Actions (Tombol Langsung):**
| Tombol | Kondisi | Role |
|--------|---------|------|
| **Setujui** | status pending | admin |
| **Tolak** | status pending | admin |
| **Tandatangani** | status approved_admin | kepsek |
| **Download PDF** | status signed + punya file | semua |

**Dropdown (⋮):**
- Lihat Detail — semua role
- Edit — status pending (gukar pemilik surat atau admin)
- Hapus — status pending (gukar pemilik surat atau admin)

**Bulk Actions:**
- Delete

**Filter:**
- Status (pending, approved_admin, signed, rejected)

**Halaman:**
- `/admin/pengajuan-surat` — List
- `/admin/pengajuan-surat/create` — Create
- `/admin/pengajuan-surat/{record}/edit` — Edit

**Label:** Model = "Surat Jalan", tombol Create = "Buat Surat Jalan"

### 4.4 Pages Khusus

#### `CreateLetterRequest.php`
- `mutateFormDataBeforeCreate()` — Set user_id (auth) + status 'pending' + auto-fill payload_data dari data karyawan yang login
- `mount()` — Pre-fill form payload_data dengan data karyawan (nama, nip, jabatan)

#### `EditLetterRequest.php`
- `mutateFormDataBeforeFill()` — Validasi: hanya pending bisa diedit, hanya pemilik atau admin
- Delete header action — hanya visible jika status pending

#### `ListLetterRequests.php`
- Header action: "Buat Surat Jalan"

#### `ListKaryawan.php`
- Header action: "Tambah Karyawan"

---

## 5. ACTIONS (LOGIKA BISNIS)

### 5.1 `app/Actions/Letter/BroadcastLetterStatusAction.php`

**Fungsi:** Mengubah status surat dan menyiarkan perubahan via broadcast.

```php
class BroadcastLetterStatusAction
{
    public function execute(LetterRequest $letterRequest, string $newStatus): void
}
```

**Alur:**
1. Update status di database
2. Broadcast event `LetterStatusUpdated` (try-catch, gagal silent)

**Catatan:** Broadcast gagal tidak menghentikan proses — status tetap terupdate.

### 5.2 `app/Actions/Letter/GeneratePdfAndQrAction.php`

**Fungsi:** Membuat PDF surat dengan QR Code dan menyimpannya.

```php
class GeneratePdfAndQrAction
{
    public function execute(LetterRequest $letterRequest): LetterRequest
}
```

**Alur:**
1. Ambil template content dari `$letterRequest->template->content`
2. Ganti placeholder `{{ variable }}` dengan nilai dari `payload_data`
3. Generate QR Code SVG dari `$letterRequest->verificationUrl()` (size=200, errorCorrection=M)
4. Render PDF via DomPDF menggunakan view `pdfs.letter`
5. Simpan PDF ke `storage/app/public/pdfs/` dengan nama `letter_{uuid}_{timestamp}.pdf`
6. Update `pdf_path` di database
7. Set status menjadi **signed**

**Method private:**
- `renderContent(string $template, array $payload): string` — Mengganti placeholder `{{ key }}` dengan nilai (escape HTML)

---

## 6. EVENTS & BROADCASTING

### 6.1 `app/Events/LetterStatusUpdated.php`

```php
class LetterStatusUpdated implements ShouldBroadcast
```

**Fungsi:** Event broadcast realtime saat status surat berubah.

**Channel:** `user.{userId}` (Private Channel)
**Event Name:** `letter.status.updated`
**Data yang dikirim:** uuid, status

**Authorization:** Hanya pemilik surat yang bisa subscribe ke channel `user.{userId}`.

**Catatan:** Broadcasting dinonaktifkan di panel (tidak ada Echo di frontend). Broadcast driver di-set ke `log` untuk development.

### 6.2 `routes/channels.php`

```php
Broadcast::channel('user.{userId}', function (User $user, int $userId): bool {
    return (int) $user->id === $userId;
});
```

---

## 7. ROUTES (RUTE)

### 7.1 `routes/web.php`

| Rute | Method | Fungsi |
|------|--------|--------|
| `/` | GET | Redirect ke `/admin` |
| `/letter/verify/{uuid}` | GET | Halaman publik verifikasi surat via UUID |

### 7.2 Filament Routes (Otomatis)

| Rute | Fungsi |
|------|--------|
| `/admin` | Dashboard Filament |
| `/admin/login` | Login |
| `/admin/pengajuan-surat` | List Surat Jalan |
| `/admin/pengajuan-surat/create` | Buat Surat Jalan |
| `/admin/pengajuan-surat/{record}/edit` | Edit Surat Jalan |
| `/admin/template-surat` | List Template Surat |
| `/admin/template-surat/create` | Buat Template |
| `/admin/template-surat/{record}/edit` | Edit Template |
| `/admin/guru-karyawan` | List Guru & Karyawan |
| `/admin/guru-karyawan/create` | Tambah Karyawan |
| `/admin/guru-karyawan/{record}/edit` | Edit Karyawan |
| `/livewire/update` | Livewire endpoint (untuk AJAX) |

---

## 8. VIEWS (TAMPILAN)

### 8.1 `resources/views/pdfs/letter.blade.php`

**Fungsi:** Template PDF surat yang di-render oleh DomPDF.

**Layout:**
- Kop surat (instansi dinamis via HTML template content)
- Konten surat dari `{!! $content !!}` (HTML yang sudah diproses)
- Footer dengan tanda tangan Kepala Sekolah
- QR Code terintegrasi untuk verifikasi

**CSS Khusus PDF:**
- Font: Times New Roman
- Fixed positioning untuk QR Code (kanan bawah)
- `page-break-after: always` untuk multi halaman

### 8.2 `resources/views/letter-verify.blade.php`

**Fungsi:** Halaman publik verifikasi surat (diakses via QR Code scan).

**URL:** `GET /letter/verify/{uuid}`

**Informasi yang ditampilkan:**
- UUID Surat
- Nama Pengaju
- Template yang digunakan
- Status (dengan badge berwarna)
- Tanggal pengajuan
- Tombol download PDF (jika status signed)
- Footer verifikasi

**CSS:** Tailwind CSS via CDN

---

## 9. IMPORTS & EXPORTS (EXCEL)

### 9.1 `app/Exports/TemplateKaryawanExport.php`

**Fungsi:** Generate file Excel template untuk import data karyawan.

**Class:** `TemplateKaryawanExport` implements `WithHeadings, WithTitle`

**Kolom yang dihasilkan:** Nama, NIP, Jabatan
**Sheet Title:** "Data Guru & Karyawan"

### 9.2 `app/Imports/KaryawanImport.php`

**Fungsi:** Import data karyawan dari file Excel.

**Class:** `KaryawanImport` implements `ToModel, WithHeadingRow, WithValidation`

**Aturan Validasi:**
- `nama` — required, string, max 255
- `nip` — required, string, max 30, unique di tabel karyawan
- `jabatan` — required, string, max 255

**Custom Messages:**
- Pesan error dalam Bahasa Indonesia

---

## 10. MIGRATIONS (MIGRASI DATABASE)

### 10.1 `0001_01_01_000000_create_users_table.php`

Tabel `users` dengan kolom standar Laravel + kolom `role`.

### 10.2 `0001_01_01_000001_create_cache_table.php`

Tabel `cache` untuk Laravel cache system.

### 10.3 `0001_01_01_000002_create_jobs_table.php`

Tabel `jobs` untuk queue system.

### 10.4 `2024_01_01_000001_create_letter_templates_table.php`

Tabel `letter_templates`:
- `name` string
- `content` longText (HTML)
- `variables` json (nullable)
- `is_active` boolean (default true)

### 10.5 `2024_01_01_000002_create_letter_requests_table.php`

Tabel `letter_requests`:
- `uuid` uuid (unique)
- `user_id` FK → users (cascade delete)
- `template_id` FK → letter_templates
- `status` string(30) default 'pending'
- `payload_data` json (nullable)
- `pdf_path` string (nullable)

### 10.6 `2024_01_01_000003_create_karyawan_table.php`

Tabel `karyawan`:
- `nama` string
- `nip` string(30) unique
- `jabatan` string

### 10.7 `2024_01_01_000004_add_user_id_to_karyawan_table.php`

Menambahkan kolom `user_id` (FK → users, nullable, nullOnDelete) ke tabel `karyawan`.

---

## 11. SEEDERS (DATA AWAL)

### 11.1 `database/seeders/DatabaseSeeder.php`

Memanggil LetterSeeder setelah membuat 3 user default.

### 11.2 `database/seeders/LetterSeeder.php`

**Data yang dibuat:**

**Template:**
- Nama: "Surat Jalan"
- Kop: PEMERINTAH KOTA SURAKARTA / DINAS PENDIDIKAN
- 13 variabel: nomor_surat, nama, nip, jabatan, sekolah, tujuan, keperluan, tanggal_berangkat, tanggal_kembali, tempat, tanggal_surat, kepala_sekolah, nip_kepala_sekolah

**5 Sample Letter Requests:**
1. **Signed** — Tujuan: Dinas Pendidikan, Keperluan: Sosialisasi Kurikulum Merdeka
2. **Approved Admin** — Tujuan: UPT Perpustakaan, Keperluan: Studi Banding
3. **Pending** — Tujuan: Balai Diklat, Keperluan: Pelatihan Media Digital
4. **Rejected** — Tujuan: Museum Radyapustaka, Keperluan: Kunjungan Edukasi
5. **Pending** — Tujuan: Kemenag, Keperluan: Koordinasi Kegiatan Keagamaan

Semua atas nama user `gukar@sekolah.sch.id` (id=3) menggunakan template Surat Jalan (id=1).

---

## 12. PANEL CONFIGURATION

### 12.1 `app/Providers/Filament/AdminPanelProvider.php`

**Konfigurasi Panel:**
- ID: 'admin'
- Path: 'admin'
- Login: enabled
- Primary Color: Indigo
- Resources: auto-discover dari `app/Filament/Resources/`
- Database Notifications: enabled
- Broadcasting: disabled (untuk menghindari error WebSocket)
- Middleware: EncryptCookies, AddQueuedCookies, StartSession, AuthenticateSession, ShareErrorsFromSession, VerifyCsrfToken, SubstituteBindings, DisableBladeIconComponents, DispatchServingFilamentEvent
- Auth Middleware: Authenticate

**Home URL Redirect (setelah login):**
- Gukar → `/admin/pengajuan-surat`
- Admin/Kepsek → `/admin`

### 12.2 `config/filament.php`

- Broadcasting Echo config untuk Reverb (tidak aktif karena broadcasting panel disabled)

---

## 13. ALUR KERJA (WORKFLOW)

### 13.1 Flow Lengkap

```
┌──────────┐    ┌──────────┐    ┌──────────┐
│  GUKAR   │    │  ADMIN   │    │  KEPSEK  │
└────┬─────┘    └────┬─────┘    └────┬─────┘
     │               │               │
     │ Login         │               │
     ├──────────────►│               │
     │               │               │
     │ Buat Surat    │               │
     │ (pending)     │               │
     ├──────────────►│               │
     │               │               │
     │               │ Setujui /    │
     │               │ Tolak        │
     │               ├───┐          │
     │               │   │          │
     │               │◄──┘          │
     │               │  (rejected)  │
     │               │              │
     │               │  (approved)  │
     │               ├─────────────►│
     │               │              │
     │               │              │ Tandatangani
     │               │              │ (generate PDF+QR)
     │               │              ├───┐
     │               │              │   │
     │               │◄─────────────┘   │
     │               │  (signed)        │
     │               │                  │
     │ Download PDF  │                  │
     │◄──────────────┤                  │
     │               │                  │
```

### 13.2 Status Transitions

| Aksi | Dari Status | Ke Status | Dilakukan Oleh |
|------|-------------|-----------|----------------|
| Submit | - | pending | gukar (otomatis) |
| Setujui | pending | approved_admin | admin |
| Tolak | pending | rejected | admin |
| Tandatangani | approved_admin | signed | kepsek |

---

## 14. KONFIGURASI & ENVIRONMENT

### 14.1 `.env` Key Settings

| Key | Nilai | Keterangan |
|-----|-------|------------|
| APP_URL | http://localhost:8000 | URL aplikasi |
| DB_DATABASE | e_surat | Nama database |
| DB_USERNAME | root | User MySQL |
| DB_PASSWORD | (kosong) | Password MySQL |
| BROADCAST_DRIVER | log | Driver broadcast (log, tidak ada koneksi) |
| QUEUE_CONNECTION | database | Queue driver |
| FILESYSTEM_DISK | local | Disk storage default |

### 14.2 Storage

| Direktori | Fungsi |
|-----------|--------|
| `storage/app/public/pdfs/` | File PDF yang sudah ditandatangani |
| `public/storage/` | Symlink (junction) → `storage/app/public/` |

### 14.3 Akses Role & Menu

| Menu | Admin | Kepsek | Gukar |
|------|-------|--------|-------|
| Dashboard | ✅ | ✅ | ✅ |
| Pengajuan Surat | ✅ | ✅ | ✅ |
| Template Surat | ✅ | ✅ | ❌ |
| Guru & Karyawan | ✅ | ❌ | ❌ |

---

## 15. CARA MENJALANKAN

### 15.1 Via XAMPP (Rekomendasi)

1. Pastikan XAMPP Apache + MySQL running
2. Konfigurasi vhost `D:\xampp\apache\conf\extra\httpd-vhosts.conf`:
   ```
   <VirtualHost *:80>
       DocumentRoot "D:/e surat/public"
       ServerName e-surat.local
   </VirtualHost>
   ```
3. Hosts entry di `C:\Windows\System32\drivers\etc\hosts`:
   ```
   127.0.0.1 e-surat.local
   ```
4. Buka `http://e-surat.local`

### 15.2 Via PHP Built-in Server

```bash
php -S localhost:8000 -t public server.php
```

Buka `http://localhost:8000`

### 15.3 User Login (Seeder)

| Email | Password | Role |
|-------|----------|------|
| admin@sekolah.sch.id | password | Admin |
| kepsek@sekolah.sch.id | password | Kepala Sekolah |
| gukar@sekolah.sch.id | password | Guru/Karyawan |

### 15.4 Menjalankan Seeder Ulang

```bash
php C:\Users\MR\AppData\Local\Temp\opencode\migrate_and_seed_karyawan.php
```

### 15.5 Catatan Penting

- **`php artisan` CLI tidak berfungsi** di PHP 8.2.12 (Symfony 7 compatibility issue). Gunakan PHP scripts standalone di `C:\Users\MR\AppData\Local\Temp\opencode\`.
- **WebSocket errors di console browser** sudah diatasi — broadcasting panel dinonaktifkan, driver broadcast = log.
- **Filament assets** sudah dicopy manual ke `public/`. Jika menambah plugin Filament, copy file dist-nya.
- **Storage symlink** sudah dibuat manual (junction `public/storage` → `storage/app/public`).