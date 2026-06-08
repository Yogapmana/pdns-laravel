# Panduan Lengkap Instalasi Aplikasi di Windows Menggunakan XAMPP

Aplikasi ini dibangun menggunakan Laravel (PHP 8.3+), React, Inertia.js, dan Tailwind CSS. Panduan ini dirancang khusus untuk instalasi di sistem operasi Windows menggunakan XAMPP.

## 1. Persiapan & Prasyarat (Prerequisites)

Sebelum memulai, pastikan Anda telah menginstal beberapa perangkat lunak berikut:
1. **XAMPP**: Untuk server database (MySQL) dan PHP lokal.
2. **Git**: Untuk cloning repositori (unduh dari git-scm.com).
3. **Node.js & npm**: Package manager untuk frontend JavaScript (unduh versi LTS dari nodejs.org).
4. **Composer**: Package manager khusus untuk PHP.
   - **Jika belum terinstal:** Unduh file **Composer-Setup.exe** dari [getcomposer.org/download/](https://getcomposer.org/download/).
   - Jalankan installer tersebut. Saat instalasi mencapai tahap *Settings Check* (memilih *Command-line PHP*), pastikan Anda mengarahkannya ke file `php.exe` yang ada di dalam folder XAMPP (biasanya di `C:\xampp\php\php.exe`).
   - Selesaikan instalasi, lalu buka CMD baru dan ketik `composer -v` untuk memastikan Composer berhasil diinstal.
5. **Laravel**: Anda **TIDAK PERLU** menginstal Laravel secara global di komputer Anda untuk menjalankan aplikasi ini. Selama Anda sudah memiliki *Composer*, langkah `composer install` nanti akan otomatis mengunduh _framework_ Laravel beserta semua kebutuhan aplikasi ini ke dalam folder proyek.

---

## 2. Pengecekan & Update Versi PHP (Jika Kurang dari 8.3)

Aplikasi ini mewajibkan **PHP versi 8.3 atau lebih tinggi**. XAMPP versi lama mungkin masih menggunakan PHP 8.1 atau 8.2.

**Cara mengecek versi PHP:**
Buka *Command Prompt* (CMD) dan ketik:
```bash
php -v
```

### Solusi jika PHP kurang dari 8.3:
Ada dua cara untuk memperbarui PHP di XAMPP:

**Cara A (Direkomendasikan & Paling Aman): Instal Ulang XAMPP Baru**
1. _Backup_ database Anda dari phpMyAdmin jika ada database penting.
2. _Uninstall_ XAMPP lama Anda.
3. Unduh XAMPP versi terbaru (yang mencantumkan PHP 8.3+) dari [apachefriends.org](https://www.apachefriends.org/index.html).
4. Instal XAMPP baru seperti biasa.

**Cara B: Update Folder PHP Manual (Tanpa Install Ulang XAMPP)**
1. Matikan Apache dan MySQL di XAMPP Control Panel.
2. Unduh PHP 8.3 (Thread Safe, x64) dalam format `.zip` dari [windows.php.net](https://windows.php.net/download/).
3. Buka direktori instalasi XAMPP (contoh: `C:\xampp`).
4. Ubah nama folder `php` lama menjadi `php_backup`.
5. Buat folder `php` baru dan ekstrak isi file `.zip` PHP 8.3 yang baru diunduh ke dalamnya.
6. Salin file `php.ini` dari folder `php_backup` ke folder `php` baru, **ATAU** salin `php.ini-development` di folder baru menjadi `php.ini`.
7. Sesuaikan konfigurasi `php.ini` (pastikan ekstensi `pdo_mysql`, `fileinfo`, `mbstring`, `openssl`, dan `curl` aktif dengan menghilangkan tanda `;` di depannya).
8. Buka `C:\xampp\apache\conf\extra\httpd-xampp.conf` dan pastikan konfigurasi `LoadFile` dan `LoadModule` mengarah ke file `php8ts.dll` (atau sesuai nama versi php yang didownload), lalu simpan.
9. Jalankan ulang Apache di XAMPP Control Panel.

---

## 3. Langkah Instalasi Aplikasi

Buka *Command Prompt*, *Git Bash*, atau *Terminal* (VS Code).

### Langkah 1: Clone Repositori
Masuk ke folder `htdocs` di XAMPP dan clone project.
```bash
cd C:\xampp\htdocs
git clone <url-repository-anda> nama-folder-aplikasi
cd nama-folder-aplikasi
```

### Langkah 2: Instal Dependensi PHP
Jalankan Composer untuk mengunduh semua package Laravel.
```bash
composer install
```

### Langkah 3: Setup Environment
Copy file konfigurasi environment dan sesuaikan.
```bash
copy .env.example .env
```
Buka file `.env` di teks editor, lalu sesuaikan koneksi database. Jika menggunakan MySQL bawaan XAMPP, biasanya konfigurasinya seperti ini:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database_anda
DB_USERNAME=root
DB_PASSWORD=
```
*(Catatan: Buat database kosong terlebih dahulu di phpMyAdmin dengan nama sesuai `DB_DATABASE` di atas)*.

### Langkah 4: Generate Application Key
```bash
php artisan key:generate
```

### Langkah 5: Migrasi Database & Seeding
Ini akan membuat struktur tabel dan mengisi data awal (seperti akun Admin, daftar kelas, mapel, dsb).
```bash
php artisan migrate:fresh --seed
```

### Langkah 6: Instal Dependensi Frontend (Node.js)
```bash
npm install
```

### Langkah 7: Build Assets Frontend
Karena menggunakan React dan Tailwind CSS, kita harus mem-build file asetnya.
```bash
npm run build
```

---

## 4. Menjalankan Aplikasi

Anda memiliki dua opsi untuk mengakses aplikasi ini di browser:

**Opsi 1: Menggunakan Artisan Serve (Paling Direkomendasikan saat Testing/Development)**
Di dalam folder project, jalankan:
```bash
php artisan serve
```
Akses aplikasi melalui browser di: `http://localhost:8000`

**Opsi 2: Menggunakan Apache (XAMPP Langsung)**
Jika XAMPP dan Apache sudah berjalan, Anda bisa mengaksesnya via URL htdocs.
Akses melalui browser di: `http://localhost/nama-folder-aplikasi/public`

---

## 5. Kendala yang Kemungkinan Terjadi (Troubleshooting)

### 1. `composer install` Gagal / Error Ext-intl atau Fileinfo
- **Penyebab**: Ekstensi PHP di XAMPP belum diaktifkan.
- **Solusi**: Buka `C:\xampp\php\php.ini`, cari baris `;extension=fileinfo` dan `;extension=intl`. Hapus tanda titik koma (`;`) di awalnya. Restart Apache, lalu jalankan `composer install` lagi.

### 2. Error "SQLSTATE[HY000] [1049] Unknown database"
- **Penyebab**: Database yang disebutkan di file `.env` tidak ada di MySQL.
- **Solusi**: Buka `http://localhost/phpmyadmin` dan buat database baru dengan nama persis seperti di `DB_DATABASE`.

### 3. Error saat `npm run build` atau "Tampilan halaman kosong/berantakan"
- **Penyebab**: Vite gagal mengkompilasi *assets* atau versi Node.js sudah usang.
- **Solusi**: Pastikan Anda menggunakan Node.js versi terbaru (minimal v20 LTS). Hapus folder `node_modules` dan file `package-lock.json`, lalu jalankan `npm install` dan `npm run build` kembali.

### 4. Tampilan Halaman Blank Putih di Browser tanpa Error
- **Penyebab**: Terjadi *caching* atau *asset build* tidak sinkron.
- **Solusi**:
  1. Hapus cache view: `php artisan view:clear` dan `php artisan route:clear`
  2. Pastikan file frontend dibuild ulang: `npm run build`
  3. Cek izin akses folder `storage` dan `bootstrap/cache` (walau di Windows jarang bermasalah terkait permission, hal ini terkadang terjadi akibat antivirus).

### 5. `Maximum execution time of 120 seconds exceeded`
- **Penyebab**: Proses `composer install` atau koneksi database memakan waktu terlalu lama.
- **Solusi**: Buka `php.ini`, ubah nilai `max_execution_time = 120` menjadi `max_execution_time = 300` atau lebih. Restart Apache.

### 6. Artisan serve terputus otomatis
- **Penyebab**: Port 8000 bentrok atau digunakan aplikasi lain.
- **Solusi**: Jalankan dengan port lain, contoh: `php artisan serve --port=8080`.
