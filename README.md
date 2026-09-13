# 📄 E-Surat Sekolah — Digital Letter Management System

> **Sistem Pengajuan & Pengelolaan Surat Resmi Sekolah Berbasis Web**  
> Dilengkapi No-Code Visual Template Builder, Penomoran Otomatis, Tanda Tangan Digital Resmi (PDF & QR Code Verification), Notifikasi Real-Time (Laravel Reverb), serta Manajemen Peran (Admin, Kepala Sekolah, Guru & Karyawan).

---

## 📋 DAFTAR ISI

1. [📌 Ringkasan & Fitur Utama](#-ringkasan--fitur-utama)
2. [🚀 Panduan Jalankan Cepat (Quick Start)](#-panduan-jalankan-cepat-quick-start)
3. [⚙️ Instalasi & Konfigurasi Lengkap (One-Time Setup)](#️-instalasi--konfigurasi-lengkap-one-time-setup)
4. [⚡ Konfigurasi & Penggunaan Laravel Reverb (Real-Time WebSocket)](#-konfigurasi--penggunaan-laravel-reverb-real-time-websocket)
5. [🔑 Akun Login Default & Matriks Hak Akses](#-akun-login-default--matriks-hak-akses)
6. [👥 Panduan Lengkap Penggunaan per Peran (Role)](#-panduan-lengkap-penggunaan-per-peran-role)
   - [👨‍🏫 Panduan Guru & Karyawan (Gukar)](#-panduan-guru--karyawan-gukar)
   - [👨‍💼 Panduan Administrator (Admin)](#-panduan-administrator-admin)
   - [✍️ Panduan Kepala Sekolah (Kepsek)](#-panduan-kepala-sekolah-kepsek)
7. [🔄 Alur Status Surat & Audit Trail (Workflow)](#-alur-status-surat--audit-trail-workflow)
8. [🔍 Sistem Verifikasi Keaslian Surat via QR Code](#-sistem-verifikasi-keaslian-surat-via-qr-code)
9. [🛠️ Troubleshooting & FAQ (Pemecahan Masalah)](#-troubleshooting--faq-pemecahan-masalah)
10. [💾 Pemeliharaan, Backup & Log File](#-pemeliharaan-backup--log-file)
11. [🧪 Menjalankan Test Otomatis](#-menjalankan-test-otomatis)
12. [📁 Struktur File Proyek: Frontend vs Backend](#-struktur-file-proyek-frontend-vs-backend)

---

## 📌 Ringkasan & Fitur Utama

E-Surat Sekolah dirancang untuk memodernisasi dan mempercepat alur administrasi persuratan sekolah secara digital, efisien, transparan, dan aman.

### ✨ Fitur Unggulan:
- **Registrasi Mandiri Gukar Terintegrasi:** Guru & TU dapat mendaftar mandiri dengan verifikasi otomatis terhadap NIP terdaftar di Master Karyawan.
- **No-Code Visual Template Builder:** Admin dapat membuat dan mengedit template surat langsung melalui editor visual tanpa perlu pengkodean HTML.
- **Preset Template Instan:** Tersedia preset siap pakai untuk *Surat Tugas*, *Surat Keterangan*, dan *Surat Izin*.
- **Penomoran Surat Otomatis:** Nomor surat di-generate otomatis berdasarkan kode template, bulan, dan tahun saat disetujui Admin.
- **Penandatanganan Digital Kepsek:** Kepala Sekolah menandatangani pengajuan secara digital, yang secara otomatis menghasilkan dokumen PDF resmi lengkap dengan stempel dan QR Code keaslian.
- **Notifikasi Real-Time (Laravel Reverb WebSocket):** Penyiaran status surat dan notifikasi popup secara langsung (instant broadcast) tanpa perlu mereload halaman web.
- **Verifikasi Publik Keaslian via QR Code:** Memindai QR Code di PDF akan membuka halaman verifikasi resmi berteknologi *Signed URL HMAC SHA256* untuk mencegah pemalsuan dokumen.
- **Audit Trail Perubahan Status:** Setiap perubahan status surat dari pengajuan hingga penandatanganan dicatat dalam sistem log audit (siapa, kapan, IP, dan catatan).

---

## 🚀 Panduan Jalankan Cepat (Quick Start)

Jika aplikasi sudah pernah diinstal dan dikonfigurasi di komputer Anda, ikuti langkah sederhana ini untuk menjalankan aplikasi harian:

### 1. Buka XAMPP Control Panel
- Jalankan XAMPP Control Panel (`C:\xampp\xampp-control.exe`).
- Klik **Start** pada modul **Apache** dan **MySQL** (pastikan keduanya berwarna hijau).

### 2. Akses Aplikasi di Browser
Buka browser pilihan Anda dan akses salah satu alamat berikut:
- **URL Domain VirtualHost (Rekomendasi):**  
  👉 **[http://e-surat.local/admin](http://e-surat.local/admin)**
- **URL Server Development (Laravel Artisan Serve):**  
  Jalankan perintah berikut di terminal folder proyek:  
  ```bash
  php artisan serve --host=0.0.0.0 --port=8000
  ```
  *(Parameter `--host=0.0.0.0` wajib disertakan agar aplikasi dapat diakses baik dari `localhost` komputer server maupun alamat IP LAN/WiFi dari HP atau komputer lain)*  
  👉 **[http://localhost:8000/admin](http://localhost:8000/admin)** atau **`http://[IP_SERVER]:8000/admin`**

- **Alternatif PHP Built-in Server:**  
  `php -S 0.0.0.0:8000 -t public server.php`

> 💡 *Jika domain `e-surat.local` belum aktif atau muncul error `DNS_PROBE_FINISHED_NXDOMAIN`, klik 2x file [tambah_hosts.bat](file:///c:/xampp/htdocs/admin_surat-main/tambah_hosts.bat) di folder proyek untuk mengaktifkannya secara otomatis.*

---

## ⚙️ Instalasi & Konfigurasi Lengkap (One-Time Setup)

Lakukan langkah-langkah di bawah ini hanya saat memasang aplikasi untuk pertama kali di perangkat/server baru:

### Langkah 1: Pengaktifan Ekstensi PHP di XAMPP
Buka file `C:\xampp\php\php.ini` menggunakan Notepad, lalu hapus tanda titik koma (`;`) di depan garis ekstensi berikut:
```ini
extension=gd
extension=intl
extension=zip
extension=pdo_mysql
extension=mbstring
extension=fileinfo
extension=dom
extension=openssl
```
> ⚠️ **Penting:** Ekstensi `gd` dibutuhkan untuk pemrosesan gambar QR Code, `intl` untuk format penanggalan/karakter, dan `zip` untuk ekspor/impor berkas Excel. Simpan file dan restart Apache.

### Langkah 2: Pemasangan Package Dependency
Buka Command Prompt atau PowerShell di folder proyek (`C:\xampp\htdocs\admin_surat-main`), lalu jalankan:
```bash
composer install
```

### Langkah 3: Konfigurasi File Environment (`.env`)
Pastikan file `.env` sudah ada di root folder proyek. Jika belum ada, buat salinannya dari `.env.example`:
```bash
copy .env.example .env
```
Buka file `.env` dan pastikan konfigurasi database dan URL disesuaikan:
```ini
APP_NAME="E-Surat Sekolah"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://e-surat.local

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=e_surat
DB_USERNAME=root
DB_PASSWORD=
DB_COLLATION=utf8mb4_general_ci

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

### Langkah 4: Generate Key Aplikasi & Link Storage
```bash
php artisan key:generate
php artisan storage:link
```
*(Perintah `storage:link` wajib dijalankan agar file PDF surat resmi yang dihasilkan dapat diakses dan diunduh dari browser).*

### Langkah 5: Pembuatan Database & Seeding Data
1. Buka phpMyAdmin di browser: `http://localhost/phpmyadmin`
2. Klik tab **Databases** ➔ Buat database baru bernama `e_surat` dengan collation `utf8mb4_general_ci`.
3. Jalankan migrasi tabel dan seeding data awal di terminal:
```bash
php artisan migrate --seed
```

### Langkah 6: Konfigurasi Apache VirtualHost (`e-surat.local`)
1. Buka file `C:\xampp\apache\conf\extra\httpd-vhosts.conf` dan tambahkan blok berikut:
```apache
<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs"
    ServerName localhost
    <Directory "C:/xampp/htdocs">
        Options Indexes FollowSymLinks Includes ExecCGI
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs/admin_surat-main/public"
    ServerName e-surat.local
    <Directory "C:/xampp/htdocs/admin_surat-main/public">
        Options Indexes FollowSymLinks Includes ExecCGI
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```
2. Klik 2x file [tambah_hosts.bat](file:///c:/xampp/htdocs/admin_surat-main/tambah_hosts.bat) untuk mendaftarkan `127.0.0.1 e-surat.local` ke sistem Windows.
3. Restart modul Apache pada XAMPP Control Panel (Stop lalu Start).

---

## ⚡ Konfigurasi & Penggunaan Laravel Reverb (Real-Time WebSocket)

Aplikasi E-Surat Sekolah mendukung fitur **Real-Time Event Broadcasting** menggunakan **Laravel Reverb** (server WebSocket bawaan Laravel yang sangat cepat dan efisien).

### 🔔 Perbedaan Mode Standar vs Real-Time Reverb:
- **Mode Standar (Tanpa Reverb):** Notifikasi tersimpan secara aman di database. Guru/Admin melihat pembaruan status surat saat menavigasi halaman atau menguji tombol notifikasi lonceng.
- **Mode Real-Time (Dengan Reverb):** Saat Admin menyetujui surat atau Kepsek menandatangani surat, pemberitahuan popup langsung muncul secara instan di layar pemohon **tanpa perlu mereload halaman**.

---

### 🚀 Cara Mengaktifkan Laravel Reverb:

#### 1. Tambahkan Kredensial Reverb di `.env`:
Buka file `.env` dan tambahkan variabel berikut:
```ini
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=100001
REVERB_APP_KEY=esurat_reverb_key
REVERB_APP_SECRET=esurat_reverb_secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

#### 2. Bersihkan Cache Konfigurasi:
```bash
php artisan config:clear
```

#### 3. Jalankan Server Reverb WebSocket:
Buka tab Command Prompt / Terminal baru di folder proyek, lalu jalankan:
```bash
php artisan reverb:start
```
*Server Reverb akan berjalan di `http://127.0.0.1:8080` dan siap menerima event broadcasting secara langsung.*

> ℹ️ **Catatan Keamanan & Error Konsol Browser:**  
> Jika Reverb **TIDAK** dijalankan, konsol browser mungkin menampilkan pesan `WebSocket connection to ws://localhost:8080/ failed`. Hal ini **100% AMAN & NORMAL**; seluruh fitur aplikasi (pengajuan, persetujuan, dan unduh PDF) tetap berfungsi normal karena notifikasi tersimpan otomatis di database.

---

## 🔑 Akun Login Default & Matriks Hak Akses

Setelah seeder dijalankan (`php artisan migrate --seed`), sistem menyediakan akun pengguna sampel untuk setiap peran:

> 🔐 **Password Default Semua Akun:** `password`

### 📋 Daftar Akun Pengguna Sampel:

| Email | Peran (Role) | Nama Pengguna | NIP Terhubung |
|---|---|---|---|
| `admin@sekolah.sch.id` | **Admin** | Administrator Utama | - |
| `kepsek@sekolah.sch.id` | **Kepsek** | Drs. H. Ahmad Dahlan, M.Pd | 196805121994031002 |
| `gukar@sekolah.sch.id` | **Gukar** | Budi Santoso, S.Pd | 198503152010011003 |
| `siti.aminah@sekolah.sch.id` | **Gukar** | Siti Aminah, S.Si | 199004122015022001 |
| `ahmad.dahlan@sekolah.sch.id` | **Gukar** | Ahmad Dahlan, S.Pd | 198811202014031001 |

---

### 🛡️ Matriks Hak Akses Berdasarkan Peran:

| Fitur / Menu Aplikasi | Admin | Kepsek | Gukar |
|---|:---:|:---:|:---:|
| **Dashboard Statistik** | ✅ Full | ✅ Full | ❌ Sembunyi |
| **Registrasi Mandiri (Verifikasi NIP)** | ❌ (Publik) | ❌ (Publik) | ✅ Akses |
| **Mengajukan Surat Baru** | ❌ (Bantu Gukar) | ❌ | ✅ Akses Mandiri |
| **Melihat Daftar Surat Pengajuan** | ✅ Semua User | ✅ Semua User | 🔒 Hanya Milik Sendiri |
| **Setujui / Tolak Surat (Tahap 1)** | ✅ Ya | ❌ | ❌ |
| **Tandatangani Surat Digital (Generate PDF)** | ❌ | ✅ Ya | ❌ |
| **Unduh Berkas PDF Surat Resmi** | ✅ | ✅ | ✅ (Jika Signed) |
| **Kelola Template Surat (Visual Builder)** | ✅ Ya | 🔒 Lihat Saja | ❌ |
| **Kelola & Import Karyawan (Excel)** | ✅ Ya | 🔒 Lihat Saja | ❌ |
| **Kelola Akun & Peran Pengguna** | ✅ Ya | ❌ | ❌ |
| **Pengaturan Sekolah & Kop Surat** | ✅ Ya | 🔒 Lihat Saja | ❌ |
| **Audit Log Perubahan Status** | ✅ Ya | ✅ Ya | ❌ |

---

## 👥 Panduan Lengkap Penggunaan per Peran (Role)

### 👨‍🏫 Panduan Guru & Karyawan (Gukar)

#### 1. Registrasi Akun Mandiri (Bagi Guru/TU Baru):
1. Akses URL Registrasi: `http://e-surat.local/admin/register`
2. **Tahap 1 (Verifikasi NIP):** Masukkan NIP Anda yang sudah terdaftar pada Master Data Karyawan sekolah (Contoh NIP Uji Coba: `199007202018012004`).
3. **Tahap 2 (Detail Akun):** Masukkan alamat email aktif dan kata sandi minimal 8 karakter beserta konfirmasinya.
4. **Tahap 3 (Konfirmasi & Aktifkan):** Periksa kecocokan nama dan jabatan Anda, lalu klik **Aktifkan & Daftar Akun Gukar**.
5. Akun Anda akan otomatis aktif dan terhubung langsung dengan data identitas pegawai Anda.

#### 2. Mengajukan Surat Pengajuan Baru:
1. Login dengan email & password gukar Anda.
2. Buka menu **Pengajuan Surat** ➔ Klik tombol **Buat Pengajuan Surat Baru**.
3. **Pilih Template Surat:** (Contoh: *Surat Tugas*, *Surat Keterangan*, *Surat Izin*).
4. Formulir dinamis akan muncul secara otomatis. Kolom data diri (Nama, NIP, Jabatan, Unit Kerja) terisi otomatis dari profil Anda.
5. Lengkapi variabel pengajuan yang diperlukan (seperti *Tujuan Tugas*, *Maksud Keperluan*, *Tanggal Pelaksanaan*, dll.).
6. Klik tombol **Ajukan Surat**. Surat tersimpan dengan status `Menunggu (Pending)`.

#### 3. Melacak Status & Mengunduh PDF Surat:
- Pantau status pengajuan pada tabel list pengajuan atau periksa lonceng **Notifikasi System** di sudut kanan atas panel.
- Alur perubahan status: `Menunggu` ➔ `Disetujui Admin` ➔ `Ditandatangani`.
- Setelah status berubah menjadi **Ditandatangani**, tombol **Download PDF** akan muncul di tabel pengajuan. Klik tombol tersebut untuk mengunduh dokumen PDF resmi.

---

### 👨‍💼 Panduan Administrator (Admin)

#### 1. Memproses Pengajuan Surat (Verifikasi Tahap 1):
1. Buka menu **Pengajuan Surat**. Anda dapat melihat seluruh pengajuan dari semua Guru & Karyawan.
2. Klik tombol **Setujui** pada pengajuan yang berstatus *Pending*:
   - Sistem akan secara otomatis meng-generate **Nomor Surat Resmi** berdasarkan format template, bulan, dan tahun berjalan.
   - Status surat berubah menjadi `Disetujui Admin`. Pemohon akan mendapatkan notifikasi otomatis.
3. Klik tombol **Tolak** jika berkas atau syarat pengajuan tidak memenuhi ketentuan (Anda dapat menyertakan alasan penolakan).

#### 2. Mengelola Template Surat (No-Code Visual Builder):
1. Buka menu **Template Surat**.
2. Klik **Buat Template Baru** atau Edit template yang sudah ada.
3. **Menggunakan Preset Instan:** Klik pilihan preset (*Surat Tugas*, *Surat Keterangan*, atau *Surat Izin*) untuk mengisi struktur template secara otomatis.
4. **Variabel Dynamic Placeholder:** Anda dapat menyisipkan variabel placeholder dengan format `{nama_variabel}` (contoh: `{tujuan_tugas}`, `{tanggal_kegiatan}`). Variabel ini akan otomatis berubah menjadi input form saat Guru mengajukan surat.
5. Atur **Kode Surat** (misal: `SPD`, `SK`, `SI`) yang akan digunakan sebagai penomoran otomatis.

#### 3. Kelola & Import Data Karyawan (Excel Batch):
1. Buka menu **Data Karyawan**.
2. Untuk mengimpor data dalam jumlah banyak sekaligus:
   - Klik tombol **Import Karyawan**.
   - Unduh format file Excel dengan mengklik tombol **Unduh Template Excel**.
   - Isi kolom Excel (Nama, NIP, Jabatan), lalu unggah berkas tersebut. Data master karyawan akan ter-update otomatis.

#### 4. Mengatur Identitas Sekolah & Kop Surat (School Settings):
1. Buka menu **Pengaturan Sekolah**.
2. Isi identitas resmi sekolah: Nama Sekolah, NPSN, Alamat Lengkap, Kota/Kabupaten, Kode Pos, Nomor Telepon, Email, dan Website.
3. Unggah **Logo Sekolah** dan **Stempel Resmi** (format PNG transparan direkomendasikan).
4. Data ini akan otomatis dicetak pada Kop Surat di setiap dokumen PDF resmi.

---

### ✍️ Panduan Kepala Sekolah (Kepsek)

#### 1. Menandatangani Surat Digital (Tahap Akhir):
1. Buka menu **Pengajuan Surat**.
2. Filter atau cari surat yang berstatus **Disetujui Admin**.
3. Klik tombol **Tandatangani Surat**:
   - Sistem akan secara otomatis membuat berkas **PDF Surat Resmi** lengkap dengan Kop Sekolah, isi variabel, Stempel, dan **QR Code Keaslian**.
   - Berkas PDF disimpan secara aman di dalam folder storage server.
   - Status pengajuan berubah menjadi `Ditandatangani (Signed)`.
   - Notifikasi otomatis dikirimkan ke pemohon (Gukar) dan tim Admin.

#### 2. Memeriksa Pratinjau Document PDF:
- Kepsek dapat mengklik tombol **Preview PDF** sebelum menandatangani untuk mengecek kerapian tata letak dokumen.

---

## 🔄 Alur Status Surat & Audit Trail (Workflow)

Setiap pengajuan surat melewati tahapan status yang ketat dan terekam secara permanen dalam **Audit Trail Log** (`letter_status_logs`):

```
       [ GUKAR AJUKAN SURAT ]
                 │
                 ▼
     ┌──────────────────────┐
     │   Status: pending    │  (Menunggu verifikasi Admin)
     └───────────┬──────────┘
                 │
        ┌────────┴────────┐
        ▼                 ▼
 ┌──────────────┐  ┌──────────────┐
 │   APPROVED   │  │   REJECTED   │ (Ditolak oleh Admin)
 └──────┬───────┘  └──────────────┘
        │ (Nomor Surat Terbit Otomatis)
        ▼
 ┌──────────────┐
 │   APPROVED   │  (Menunggu Tanda Tangan Kepsek)
 └──────┬───────┘
        │
        ├─────────────────┐
        ▼                 ▼
 ┌──────────────┐  ┌──────────────┐
 │    SIGNED    │  │   REJECTED   │ (Ditolak oleh Kepsek)
 └──────────────┘  └──────────────┘
 (PDF Terbit & QR Code Aktif)
```

> 📜 **Audit Trail Log mencatat:** Pengubah Status, Status Asal ➔ Status Baru, Waktu Perubahan, Alasan/Catatan, Alamat IP, serta User Agent Browser.

---

## 🔍 Sistem Verifikasi Keaslian Surat via QR Code

Untuk menjamin keaslian dokumen dan mencegah pemalsuan surat fisik/PDF:

1. **Struktur QR Code di PDF:**  
   Setiap berkas PDF yang telah ditandatangani memuat **QR Code** unik di bagian bawah dokumen.
2. **Teknologi Enkripsi (Signed HMAC SHA256):**  
   QR Code berisi URL verifikasi terenkripsi yang dilengkapi tanda tangan HMAC berbasis `APP_KEY` aplikasi.
   *Contoh URL:* `http://e-surat.local/letter/verify/550e8400-e29b-41d4-a716-446655440000?sig=a1b2c3d4...`
3. **Prosedur Pemindaian:**  
   - Siapa pun (masyarakat, instansi luar, penerima surat) dapat memindai QR Code tersebut menggunakan kamera HP atau scanner QR.
   - Browser HP akan langsung membuka **Halaman Verifikasi Keaslian Publik**.
4. **Hasil Verifikasi:**  
   - Jika dokumen ASLI dan VALID: Menampilkan lencana **DOKUMEN RESMI TERVERIFIKASI**, dilengkapi rincian Nomor Surat, Tanggal Penandatanganan, Nama Pemohon, Jabatan, dan Ringkasan Isi Surat.
   - Jika URL diubah atau dipalsukan: Sistem secara otomatis menampilkan pesan error `403 Forbidden (Tanda tangan QR Code tidak valid)`.
5. **Proteksi Keamanan (Rate Limiting):**  
   Halaman verifikasi dilindungi proteksi pembatasan akses (*throttle max 60 request/menit per IP*) untuk mencegah serangan spam.

---

## 🛠️ Troubleshooting & FAQ (Pemecahan Masalah)

### ❓ 1. Kenapa muncul error `DNS_PROBE_FINISHED_NXDOMAIN` saat membuka `http://e-surat.local`?
**Penyebab:** Domain lokal `e-surat.local` belum terdaftar di file `hosts` Windows.  
**Solusi:**  
Buka folder proyek `C:\xampp\htdocs\admin_surat-main`, lalu **klik 2x file [tambah_hosts.bat](file:///c:/xampp/htdocs/admin_surat-main/tambah_hosts.bat)**. Saat jendela konfirmasi Windows (UAC) muncul, klik **Yes/Ya**. Setelah itu restart Apache di XAMPP.

---

### ❓ 2. Kenapa muncul error `Class "Illuminate\Foundation\Application" not found`?
**Penyebab:** Dependency composer belum terpasang atau file autoloader belum di-generate.  
**Solusi:**  
Buka Command Prompt di folder proyek, lalu jalankan:
```bash
composer install
```

---

### ❓ 3. Kenapa muncul error `ext-intl / ext-gd / ext-zip is missing from your system`?
**Penyebab:** Ekstensi PHP di XAMPP masih dalam keadaan dinonaktifkan (diawali titik koma `;`).  
**Solusi:**  
1. Buka file `C:\xampp\php\php.ini`.
2. Hapus tanda titik koma `;` pada baris `extension=gd`, `extension=intl`, dan `extension=zip`.
3. Simpan file `php.ini` lalu restart modul Apache di XAMPP Control Panel.

---

### ❓ 4. Kenapa konsol browser menampilkan warning `WebSocket connection to ws://localhost:8080 failed`?
**Penyebab:** Server Laravel Reverb WebSocket (`php artisan reverb:start`) belum dijalankan.  
**Solusi:**  
Pesan warning ini **WAJAR & AMAN**. Jika Anda ingin mengaktifkan notifikasi real-time WebSocket, jalankan `php artisan reverb:start` di terminal. Jika tidak dijalankan, aplikasi tetap berfungsi 100% normal karena notifikasi otomatis disimpan di database.

---

### ❓ 5. Kenapa muncul error `SQLSTATE[HY000] [2002] Connection refused`?
**Penyebab:** Service database MySQL di XAMPP belum dijalankan.  
**Solusi:**  
Buka XAMPP Control Panel, lalu klik tombol **Start** pada baris **MySQL**.

---

### ❓ 6. Kenapa tombol Download PDF menghasilkan error 404 (File Not Found)?
**Penyebab:** Symlink direktori storage public belum dibuat di folder `public/storage`.  
**Solusi:**  
Jalankan perintah berikut di terminal folder proyek:
```bash
php artisan storage:link
```

---

### ❓ 7. Bagaimana jika tampilan web berantakan atau perubahan `.env` tidak berefek?
**Penyebab:** Konfigurasi atau tampilan tersimpan di dalam cache Laravel.  
**Solusi:**  
Jalankan perintah pembersihan cache komprehensif berikut:
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

---

## 💾 Pemeliharaan, Backup & Log File

### 📁 Lokasi Penyimpanan Berkas PDF:
- **Dokumen Surat Resmi Ditandatangani:** `storage/app/public/pdfs/letter_{uuid}_{timestamp}.pdf`
- **Dokumen Pratinjau (Preview):** `storage/app/public/pdfs/preview/preview_{uuid}_{timestamp}.pdf`

### 🗄️ Backup & Restore Database (MySQL / MariaDB):
- **Membuat Backup Database (`mysqldump`):**
  ```bash
  C:\xampp\mysql\bin\mysqldump -u root e_surat > backup_esurat.sql
  ```
- **Mengembalikan Data Backup (Restore):**
  ```bash
  C:\xampp\mysql\bin\mysql -u root e_surat < backup_esurat.sql
  ```

### ⚙️ Menjalankan Worker Antrian (Queue):
Aplikasi ini menggunakan driver queue `database`. Jika di masa mendatang terdapat pemrosesan pengiriman email/job secara latar belakang (asinkron), jalankan worker antrian berikut:
```bash
php artisan queue:work
```

---

## 🧪 Menjalankan Test Otomatis

Aplikasi E-Surat Sekolah dilengkapi dengan suite **Automated Testing (Unit Test & Feature Test)** berbasis PHPUnit.

Testing menggunakan database **SQLite In-Memory** (terkonfigurasi pada `phpunit.xml`), sehingga **100% AMAN** dan tidak akan mengubah atau merusak data pada database MySQL XAMPP Anda.

### Perintah Menjalankan Seluruh Testing:
```bash
php artisan test
```

### Menjalankan Testing Spesifik:
```bash
# Menjalankan seluruh Unit Test
php artisan test tests/Unit

# Menjalankan seluruh Feature Test
php artisan test tests/Feature

# Menjalankan satu file test spesifik
php artisan test tests/Feature/LetterRequestTest.php
```

---

## 📁 Struktur File Proyek: Frontend vs Backend

Aplikasi **E-Surat Sekolah** menerapkan pemisahan arsitektur (*Separation of Concerns*) yang tegas antara lapisan **Frontend** (Presentasi & Tampilan) dan **Backend** (Logika Bisnis & Layanan):

### 🎨 1. Lapisan Frontend (Tampilan & Interaksi Pengguna)
- **Filament Resources (UI Panel Admin)** (`app/Filament/Resources/`):
  - [LetterRequestResource.php](file:///d:/e%20surat/app/Filament/Resources/LetterRequestResource.php) — UI Formulir Pengajuan Surat dinamis & Tabel pengajuan.
  - [LetterTemplateResource.php](file:///d:/e%20surat/app/Filament/Resources/LetterTemplateResource.php) — UI Visual No-Code Template Builder & Tabel template.
  - [KaryawanResource.php](file:///d:/e%20surat/app/Filament/Resources/KaryawanResource.php) — UI Tabel Guru/Karyawan & Modal Import Excel.
  - [SiswaResource.php](file:///d:/e%20surat/app/Filament/Resources/SiswaResource.php) — UI Tabel Data Siswa & Modal Import Excel.
  - [UserResource.php](file:///d:/e%20surat/app/Filament/Resources/UserResource.php) — UI Manajemen Pengguna & Akun Login.
  - [LetterStatusLogResource.php](file:///d:/e%20surat/app/Filament/Resources/LetterStatusLogResource.php) — UI Tabel Audit Trail Log Status Surat.
- **Filament Pages & Widgets** (`app/Filament/Pages/` & `app/Filament/Widgets/`):
  - [SchoolSettingsPage.php](file:///d:/e%20surat/app/Filament/Pages/SchoolSettingsPage.php) — Formulir konfigurasi identitas sekolah, gap kop, & logo studio.
  - [RegisterGukar.php](file:///d:/e%20surat/app/Filament/Pages/Auth/RegisterGukar.php) — UI Registrasi Mandiri Pegawai (Multi-step Wizard).
  - [LetterStatsWidget.php](file:///d:/e%20surat/app/Filament/Widgets/LetterStatsWidget.php) — Widget visual kartu statistik persuratan di Dashboard.
- **Blade Views & PDF Templates** (`resources/views/`):
  - [school-settings.blade.php](file:///d:/e%20surat/resources/views/filament/pages/school-settings.blade.php) — Komponen Alpine.js kanvas pratinjau Kop Surat real-time.
  - [letter-verify.blade.php](file:///d:/e%20surat/resources/views/letter-verify.blade.php) — Halaman publik hasil scan QR Code keaslian surat.
  - [letter.blade.php](file:///d:/e%20surat/resources/views/pdfs/letter.blade.php) — Layout cetak dokumen resmi PDF (Kop, Isi, Stempel, QR).
- **Asset Klien**:
  - `resources/css/` & `resources/js/` — Styling kustom dan script klien.
  - `public/css/filament/` & `public/js/filament/` — Bundel asset terdistribusi Filament & Alpine.js.

### ⚙️ 2. Lapisan Backend (Logika Bisnis & Pemrosesan Data)
- **Actions (Logika Bisnis / Action Pattern)** (`app/Actions/Letter/`):
  - [AssignNomorSuratAction.php](file:///d:/e%20surat/app/Actions/Letter/AssignNomorSuratAction.php) — Penomoran surat otomatis tahunan format SURAT 2025 (`001/29.15/E/IX/2026`).
  - [GeneratePdfAndQrAction.php](file:///d:/e%20surat/app/Actions/Letter/GeneratePdfAndQrAction.php) — Engine kompilasi PDF resmi via DomPDF dan QR Code HMAC SHA256.
  - [BroadcastLetterStatusAction.php](file:///d:/e%20surat/app/Actions/Letter/BroadcastLetterStatusAction.php) — Transisi status surat, pencatatan log audit, dan broadcast WebSocket Reverb.
- **Layanan Dokumen & Parser** (`app/Services/`):
  - [DocxTemplateParser.php](file:///d:/e%20surat/app/Services/DocxTemplateParser.php) — Ekstraksi dan parser dokumen Word (.docx) SURAT 2025 menjadi template HTML.
- **Helpers & Kompiler Template** (`app/Helpers/`):
  - [TemplateCompiler.php](file:///d:/e%20surat/app/Helpers/TemplateCompiler.php) — Kompilasi placeholder menjadi label resmi baku Indonesia tanpa garis miring.
  - [TemplatePresets.php](file:///d:/e%20surat/app/Helpers/TemplatePresets.php) — Pustaka 11 template bawaan sekolah (Surat Tugas, SK, SPPD, dll).
- **Policies (Kebijakan Otorisasi)** (`app/Policies/`):
  - [LetterRequestPolicy.php](file:///d:/e%20surat/app/Policies/LetterRequestPolicy.php), [LetterTemplatePolicy.php](file:///d:/e%20surat/app/Policies/LetterTemplatePolicy.php), [KaryawanPolicy.php](file:///d:/e%20surat/app/Policies/KaryawanPolicy.php), [SiswaPolicy.php](file:///d:/e%20surat/app/Policies/SiswaPolicy.php), [SchoolSettingsPolicy.php](file:///d:/e%20surat/app/Policies/SchoolSettingsPolicy.php), [UserPolicy.php](file:///d:/e%20surat/app/Policies/UserPolicy.php).
- **Models (Eloquent ORM & Data Layer)** (`app/Models/`):
  - [LetterRequest.php](file:///d:/e%20surat/app/Models/LetterRequest.php), [LetterTemplate.php](file:///d:/e%20surat/app/Models/LetterTemplate.php), [SchoolSettings.php](file:///d:/e%20surat/app/Models/SchoolSettings.php), [Karyawan.php](file:///d:/e%20surat/app/Models/Karyawan.php), [Siswa.php](file:///d:/e%20surat/app/Models/Siswa.php), [User.php](file:///d:/e%20surat/app/Models/User.php), [LetterStatusLog.php](file:///d:/e%20surat/app/Models/LetterStatusLog.php).
- **Observers & Events** (`app/Observers/` & `app/Events/`):
  - [LetterRequestObserver.php](file:///d:/e%20surat/app/Observers/LetterRequestObserver.php), [LetterStatusUpdated.php](file:///d:/e%20surat/app/Events/LetterStatusUpdated.php).
- **Imports & Exports (Excel)** (`app/Imports/` & `app/Exports/`):
  - Import dan Export Excel data Guru/Karyawan dan Siswa.
- **Database & Routing**:
  - `database/migrations/` (Skema tabel) & `database/seeders/` (Seeder data awal).
  - `routes/web.php` (Rute publik verifikasi HMAC QR Code) & `routes/channels.php` (Otorisasi WebSocket).

---

<p align="center">
  <b>E-Surat Sekolah</b> — <i>Digital Letter Management System</i><br>
  Dikembangkan dengan standar arsitektur modern Laravel & Filament v3.
</p>
