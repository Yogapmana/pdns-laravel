# PDNS — Pengolahan Data Nilai Siswa

Sistem informasi akademik berbasis web untuk pengelolaan nilai siswa di SMAN 7 Solo. Aplikasi ini memungkinkan admin mengelola data akademik, guru menginput dan memvalidasi nilai, serta siswa melihat nilai dan mencetak rapor digital.

![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel)
![React](https://img.shields.io/badge/React-19-61DAFB?logo=react)
![Inertia](https://img.shields.io/badge/Inertia-v3-9CF538?logo=inertia)
![Tailwind](https://img.shields.io/badge/Tailwind-v4-06B6D4?logo=tailwindcss)
![Tests](https://img.shields.io/badge/Tests-180+-22863A?logo=pest)

## Fitur

### Admin
- Dashboard dengan visualisasi data akademik (donut chart, bar chart, sortable lists)
- CRUD siswa, guru, kelas, dan mata pelajaran
- Manajemen akun (buat akun admin, reset password, toggle aktif/nonaktif)
- Laporan multi-kelas dengan export PDF, CSV, HTML, dan XLSX
- Manajemen nilai — buka kunci nilai yang sudah di-finalisasi guru (audit trail immutable)
- Notifikasi in-app untuk perubahan akun

### Guru
- Dashboard dengan statistik nilai dan status mengajar per kelas/mapel
- Input nilai dengan kalkulasi real-time (Tugas 30%, UTS 30%, UAS 40%)
- Validasi final untuk mengunci nilai per kombinasi kelas dan mata pelajaran
- Rekap nilai dengan filter kelas dan mata pelajaran
- Notifikasi pengingat untuk nilai yang belum diinput atau masih draft

### Siswa
- Dashboard dengan ringkasan akademik dan tombol cetak rapor
- Halaman nilai pribadi (read-only) dengan visualisasi performa per mata pelajaran
- Statistik akademik dengan filter interaktif (status kelulusan, komponen nilai, mata pelajaran)
- Cetak rapor digital dalam format PDF

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 13, PHP 8.5 |
| Frontend | React 19, Inertia.js v3, TypeScript |
| Styling | Tailwind CSS v4, shadcn/ui |
| Database | MySQL 8.0 |
| Auth | Laravel Fortify (username-based) |
| PDF | barryvdh/laravel-dompdf |
| Spreadsheet | openspout/openspout |
| Testing | Pest PHP v4 |
| Code Quality | Laravel Pint, ESLint, Prettier |

## Prerequisites

- PHP 8.5+
- Composer
- Node.js 20+
- MySQL 8.0 (or SQLite for development)

## Quick Start

### 1. Clone dan Install Dependencies

```bash
git clone <repository-url> pdns
cd pdns

composer install
npm install
```

### 2. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` dan sesuaikan konfigurasi database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pdns
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Database Migration dan Seeding

```bash
php artisan migrate --seed
```

### 4. Build Frontend dan Jalankan

```bash
# Development (hot reload)
composer run dev

# Atau build untuk production
npm run build
php artisan serve
```

Aplikasi tersedia di `http://localhost:8000`.

## Default Credentials

Setelah menjalankan `php artisan migrate --seed`, gunakan kredensial berikut:

| Role | Username | Password |
|---|---|---|
| Admin | `admin` | `admin123` |
| Guru | `sariwahyuni` | `guru123` |
| Guru | `budihartono` | `guru123` |
| Guru | `riniastuti` | `guru123` |
| Guru | `jokosantoso` | `guru123` |
| Siswa | `00001` – `00029` | `siswa123` |

Username guru diturunkan dari nama lengkap (lowercase, tanpa honorific). Username siswa sama dengan NIS.

## Database Schema

```
users
├── id (PK)
├── username (unique)
├── name
├── role (admin|guru|siswa)
├── is_active
└── password

siswa
├── nis (PK, string)
├── user_id (FK → users, nullable)
├── nama_siswa
└── kelas_id (FK → kelas)

guru
├── id (PK)
├── user_id (FK → users, nullable)
└── nama_guru

kelas
├── id (PK)
└── nama (unique)

mata_pelajaran
├── id (PK)
└── nama (unique)

guru_mengajar
├── id (PK)
├── id_guru (FK → guru)
├── kelas_id (FK → kelas)
└── mata_pelajaran_id (FK → mata_pelajaran)

nilai
├── id (PK)
├── nis (FK → siswa)
├── id_guru (FK → guru)
├── kelas_id (FK → kelas)
├── mata_pelajaran_id (FK → mata_pelajaran)
├── nilai_tugas, nilai_uts, nilai_uas
├── nilai_akhir (computed)
├── status_lulus (Lulus|Tidak Lulus)
└── status_validasi (Draft|Final)

nilai_unlock_log
├── id (PK)
├── id_admin (FK → users)
├── id_guru (FK → guru)
├── kelas_id, mata_pelajaran_id
├── affected_rows
├── reason
└── created_at (immutable, no updated_at)

notifications
├── id (PK)
├── user_id (FK → users)
├── type, title, body
├── link (nullable)
└── read_at (nullable)
```

## Perhitungan Nilai

```
nilai_akhir = (0.30 × nilai_tugas) + (0.30 × nilai_uts) + (0.40 × nilai_uas)
status_lulus = nilai_akhir >= 70 ? "Lulus" : "Tidak Lulus"
```

KKM default: **70**. Bobot dan KKM dapat dikonfigurasi di `app/Models/Nilai.php`.

## Development

### Menjalankan Development Server

```bash
composer run dev
```

Ini akan menjalankan secara bersamaan:
- PHP development server (`localhost:8000`)
- Queue listener
- Pail log viewer
- Vite dev server (hot reload)

### Running Tests

```bash
# All tests
php artisan test

# Specific test file
php artisan test tests/Feature/AcceptanceTest.php

# Filter by name
php artisan test --filter=login
```

### Code Quality

```bash
# PHP formatting
vendor/bin/pint

# TypeScript type check
npm run types:check

# ESLint
npm run lint

# Prettier check
npm run format:check

# Run all checks (CI)
composer run ci:check
```

## Project Structure

```
app/
├── Console/Commands/          # Artisan commands (notification cleanup, etc.)
├── Http/
│   ├── Controllers/
│   │   ├── Admin/             # Admin controllers (dashboard, siswa, guru, etc.)
│   │   ├── Guru/              # Guru controllers (dashboard, nilai, rekap)
│   │   └── Siswa/             # Siswa controllers (dashboard, nilai, rapor)
│   ├── Middleware/            # Role-based access control
│   └── Requests/              # Form request validation
├── Models/                    # Eloquent models (User, Siswa, Guru, Nilai, etc.)
├── Notifications/             # Notification dispatcher service
└── Observers/                 # Eloquent observers (GradeObserver)

resources/js/
├── components/                # Shared React components
│   ├── dashboard/             # Dashboard-specific components
│   └── ui/                    # shadcn/ui components
├── hooks/                     # Custom React hooks (useFlashToast, useInertiaSearch)
├── layouts/                   # Inertia layouts
└── pages/                     # Inertia page components
    ├── admin/                 # Admin pages
    ├── guru/                  # Guru pages
    └── siswa/                 # Siswa pages

routes/
└── web.php                    # All route definitions

tests/
└── Feature/                   # Pest feature tests (180+ assertions)
```

## Security

- **Role-based middleware**: Setiap route dilindungi middleware `role:admin,guru,siswa`
- **Account deactivation**: User nonaktif otomatis logout dan ditolak akses
- **Data isolation**: Siswa hanya melihat nilainya sendiri, guru hanya mengelola nilai yang diajar
- **Immutable audit log**: `nilai_unlock_log` tidak memiliki `updated_at`, hanya append
- **Password confirmation**: Reset password memerlukan konfirmasi
- **Self-disable prevention**: Admin tidak bisa menonaktifkan akun sendiri

## License

MIT
