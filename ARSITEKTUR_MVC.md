# Arsitektur MVC & Pembagian Kerja Modul

Dokumen ini menjelaskan arsitektur Model-View-Controller (MVC) dari aplikasi **PDNS** (Pengolahan Data Nilai Siswa) untuk setiap modul, termasuk pembagian kerja antara backend (Laravel) dan frontend (Inertia + React).

---

## Daftar Isi

1. [Arsitektur Umum](#1-arsitektur-umum)
2. [Modul Autentikasi](#2-modul-autentikasi)
3. [Modul Admin Dashboard](#3-modul-admin-dashboard)
4. [Modul Admin Manajemen Siswa](#4-modul-admin-manajemen-siswa)
5. [Modul Admin Manajemen Guru](#5-modul-admin-manajemen-guru)
6. [Modul Admin Kelas](#6-modul-admin-kelas)
7. [Modul Admin Mata Pelajaran](#7-modul-admin-mata-pelajaran)
8. [Modul Admin Manajemen Akun](#8-modul-admin-manajemen-akun)
9. [Modul Admin Laporan](#9-modul-admin-laporan)
10. [Modul Admin Manajemen Nilai](#10-modul-admin-manajemen-nilai)
11. [Modul Guru Dashboard](#11-modul-guru-dashboard)
12. [Modul Guru Input Nilai](#12-modul-guru-input-nilai)
13. [Modul Guru Rekap](#13-modul-guru-rekap)
14. [Modul Siswa Dashboard](#14-modul-siswa-dashboard)
15. [Modul Siswa Nilai](#15-modul-siswa-nilai)
16. [Modul Siswa Statistik](#16-modul-siswa-statistik)
17. [Modul Siswa Rapor](#17-modul-siswa-rapor)
18. [Diagram Relasi Model](#18-diagram-relasi-model)
19. [Ringkasan Otorisasi](#19-ringkasan-otorisasi)

---

## 1. Arsitektur Umum

### Stack Teknologi

| Layer | Teknologi | Penjelasan |
|-------|-----------|------------|
| Backend | Laravel 13, PHP 8.5 | Framework MVC, routing, ORM, autentikasi |
| Frontend | React 19, Inertia.js v3, TypeScript | SPA tanpa API terpisah, komponen UI |
| Styling | Tailwind CSS v4, shadcn/ui | Utility-first CSS, komponen UI siap pakai |
| Database | MySQL 8.0 | Penyimpanan data relasional |
| Autentikasi | Laravel Fortify | Login berbasis username, role-based access |
| PDF | barryvdh/laravel-dompdf | Generate rapor dan laporan PDF |
| Testing | Pest PHP v4 | Unit test dan feature test |

### Pola Arsitektur

Aplikasi ini menggunakan pola **Inertia.js** yang menggabungkan server-side routing Laravel dengan client-side rendering React tanpa REST API terpisah. Untuk navigasi frontend, digunakan **Laravel Wayfinder** yang menghasilkan fungsi TypeScript type-safe dari route Laravel.

```
Browser Request
     │
     ▼
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Laravel    │────▶│   Inertia   │────▶│   React     │
│   Router +   │     │   Adapter   │     │   Pages     │
│   Controller │     │             │     │             │
└─────────────┘     └─────────────┘     └─────────────┘
     │                     │                    │
     ▼                     ▼                    ▼
  Controller          Inertia::render()     Komponen React
  memproses            mengirim props        menerima props
  request              ke frontend           dan render UI
```

**Alur kerja:**
1. Browser mengirim request ke route Laravel
2. Controller memproses request (query database, validasi, dll.)
3. Controller mengembalikan `Inertia::render('page', $props)`
4. Inertia mengirim props sebagai JSON ke React
5. React merender halaman dengan props yang diterima
6. User interaction memicu Inertia visit yang mengulang siklus dari langkah 1

### Cara Kerja Inertia.js

Bayangkan Inertia sebagai "penghubung" antara Laravel dan React tanpa perlu membuat REST API terpisah. In real world:

1. **Tanpa Inertia**, Anda harus membuat API endpoints (GET/POST/PUT/DELETE), lalu frontend fetch data dari API, lalu render. Butuh 2x pekerjaan.
2. **Dengan Inertia**, controller langsung render halaman React. Tidak ada API terpisah. Laravel langsung kirim data ke React.

**Analogi sederhana:**
- **Tanpa Inertia**: Pekerja RESTO → tulis pesanan di kertas → kirim ke dapur → dapur masak → kirim balik ke pelayan → pelayan sajikan ke meja
- **Dengan Inertia**: Pekerja RESTO → langsung bilang ke dapur → dapur langsung sajikan ke meja

```php
// Controller langsung render React page
public function index(): Response
{
    $siswa = Siswa::paginate(15);

    return Inertia::render('admin/siswa/index', [
        'siswa' => $siswa,  // props dikirim langsung ke React
    ]);
}
```

```tsx
// React menerima props dan render
export default function SiswaIndex({ siswa }) {
    return (
        <div>
            {siswa.data.map(s => <div>{s.nama_siswa}</div>)}
        </div>
    );
}
```

### Cara Kerja Wayfinder

Wayfinder adalah tools yang menghasilkan fungsi TypeScript dari route Laravel. Ini mencegah typo URL dan memudahkan refactoring.

**Analogi:**
- **Tanpa Wayfinder**: Anda harus hapal semua URL (`/admin/siswa`, `/admin/guru/create`). Jika URL berubah, semua file harus dicari dan diganti manual.
- **Dengan Wayfinder**: Cukup panggil fungsi `index.url()`. Jika URL berubah, cukup regenerate dan semua file otomatis menyesuaikan.

```tsx
// SEBELUM (hardcoded URL) - rawan typo
<Link href="/admin/siswa/create">
router.delete(`/admin/siswa/${nis}`)

// SESUDAH (Wayfinder) - type-safe
import { index, create, destroy } from '@/routes/admin/siswa'
<Link href={create.url()}>
router.delete(destroy.url(nis))
```

**Cara kerja Wayfinder:**
1. Route Laravel didefinisikan di `routes/web.php` dengan nama unik (contoh: `admin.siswa.index`)
2. `php artisan wayfinder:generate` membaca semua route dan menghasilkan file TypeScript di `resources/js/routes/`
3. Frontend mengimpor fungsi dari file tersebut untuk navigasi
4. Jika route berubah, cukup regenerate dan semua import otomatis benar

```php
// routes/web.php
Route::get('/admin/siswa', [SiswaController::class, 'index'])->name('admin.siswa.index');
Route::get('/admin/siswa/create', [SiswaController::class, 'create'])->name('admin.siswa.create');
Route::post('/admin/siswa', [SiswaController::class, 'store'])->name('admin.siswa.store');
```

```tsx
// resources/js/routes/admin/siswa/index.ts (auto-generated)
export const index = () => ({ url: '/admin/siswa', method: 'get' })
export const create = () => ({ url: '/admin/siswa/create', method: 'get' })
export const store = () => ({ url: '/admin/siswa', method: 'post' })
```

### Struktur Direktori MVC

```
app/
├── Http/
│   ├── Controllers/        ← CONTROLLER (logika bisnis)
│   │   ├── Admin/          ← Controller untuk role admin
│   │   ├── Guru/           ← Controller untuk role guru
│   │   └── Siswa/          ← Controller untuk role siswa
│   ├── Middleware/          ← Otentikasi & otorisasi
│   └── Requests/           ← Validasi form (Form Request)
├── Models/                 ← MODEL (Eloquent ORM + relasi)
├── Observers/              ← Event listener Eloquent
└── Notifications/          ← Dispatcher notifikasi

resources/js/
├── components/             ← Komponen React yang dapat digunakan kembali
│   ├── dashboard/          ← Komponen spesifik dashboard
│   └── ui/                 ← Komponen UI dasar (shadcn/ui)
├── hooks/                  ← Custom React hooks
├── layouts/                ← Layout Inertia
└── pages/                  ← VIEW (halaman Inertia/React)
    ├── admin/              ← Halaman untuk role admin
    ├── guru/               ← Halaman untuk role guru
    ├── siswa/              ← Halaman untuk role siswa
    └── auth/               ← Halaman login

routes/
└── web.php                 ← Routing untuk semua halaman

tests/
└── Feature/                ← Feature test (Pest PHP)
```

---

## 2. Modul Autentikasi

### Model: `User`

| Aspek | Detail |
|-------|--------|
| **File** | `app/Models/User.php` |
| **Tabel** | `users` |
| **Konstanta** | `ROLE_ADMIN = 'admin'`, `ROLE_GURU = 'guru'`, `ROLE_SISWA = 'siswa'` |
| **Relasi** | `siswa()` HasOne → Siswa, `guru()` HasOne → Guru |
| **Metode Kunci** | `hasRole(...$roles): bool`, `dashboardRoute(): string` |

**Metode `dashboardRoute()`:**
```
match($this->role) {
    'admin'  → 'admin.dashboard'
    'guru'   → 'guru.dashboard'
    'siswa'  → 'siswa.dashboard'
    default  → 'login'
}
```

### Controller: Fortify Login

| Aspek | Detail |
|-------|--------|
| **File** | `app/Providers/FortifyServiceProvider.php` |
| **Login View** | `Inertia::render('auth/login')` |
| **Rate Limiting** | 5 percobaan per menit per username + IP |

### Middleware: `EnsureUserHasRole`

| Aspek | Detail |
|-------|--------|
| **File** | `app/Http/Middleware/EnsureUserHasRole.php` |
| **Alias** | `role` (didafarkan di `bootstrap/app.php`) |
| **Logika** | 3 langkah: (1) terautentikasi? (2) is_active? (3) role cocok? |
| **Akun Nonaktif** | Paksa logout + abort 403 |

### Routes

| Method | URI | Nama | Middleware |
|--------|-----|------|-----------|
| GET | `/` | `home` | none |
| GET | `/redirect-by-role` | `redirect-by-role` | `auth`, `role:admin,guru,siswa` |

### View: `auth/login.tsx`

| Aspek | Detail |
|-------|--------|
| **File** | `resources/js/pages/auth/login.tsx` |
| **Props** | `{ status?: string }` |
| **Fitur UI** | Form username/password, toggle show/hide password, checkbox "Ingat Saya", error alerts, processing state |

### Cara Kerja Modul Autentikasi

**Bagaimana login bekerja dari awal sampai akhir:**

```
┌──────────────┐    POST /login     ┌──────────────────┐
│   Login Page │  ───────────────►  │  Fortify auth    │
│  (username,  │   {username,       │  → set session   │
│   password)  │    password}       │  → 302 redirect  │
└──────────────┘                    └────────┬─────────┘
                                             ▼
                                ┌────────────────────────┐
                                │  /redirect-by-role     │
                                │  match(role) → route() │
                                └────────┬───────────────┘
                                         ▼
                           ┌──────────────────────────────────┐
                           │ admin.dashboard                  │
                           │ guru.dashboard                   │
                           │ siswa.dashboard                  │
                           └──────────────────────────────────┘
```

**Penjelasan langkah demi langkah:**

1. **Login Page** → User mengisi form username + password
2. **Fortify** → Laravel Fortify memproses login (cek username di DB, verify password hash, buat session)
3. **Redirect** → Jika berhasil, redirect ke `/redirect-by-role`
4. **Role Check** → Middleware `EnsureUserHasRole` cek: (a) user terautentikasi? (b) akun aktif? (c) role cocok?
5. **Dashboard** → Berdasarkan role, redirect ke dashboard yang sesuai

**Pembagian kerja:**
- **Backend**: Fortify handle auth logic, `EnsureUserHasRole` handle otorisasi, `User::dashboardRoute()` handle redirect
- **Frontend**: React render form login, handle flash messages (status/error), tampilkan processing state

### Tests

| File | Skenario |
|------|----------|
| `AcceptanceTest.php` | Login admin/guru/siswa valid → redirect ke dashboard yang benar |
| `AcceptanceTest.php` | Login password salah / username kosong → ditolak |
| `AcceptanceTest.php` | Root `/` redirect ke `/login` untuk guest, ke dashboard untuk authenticated |

---

## 3. Modul Admin Dashboard

### Controller: `Admin\DashboardController`

| Aspek | Detail |
|-------|--------|
| **File** | `app/Http/Controllers/Admin/DashboardController.php` |
| **Metode** | `index()` — Agregasi: total siswa/guru/nilai/mapel, persentase lulus, rekap per kelas, rata-rata per mapel, top 5 siswa, siswa perhatian, tindakan penting |

**Metode Private:**
- `buildRekapPerKelas()` — Query grouped: jumlah siswa + lulus/tidak per kelas
- `buildRataRataPerMapel()` — AVG + lulus/tidak per mapel, sorted desc
- `buildTopSiswa()` — Top 5 siswa berdasarkan rata-rata nilai akhir
- `buildSiswaPerhatian()` — Siswa dengan minimal 1 mapel tidak lulus

### Route

| Method | URI | Nama | Middleware |
|--------|-----|------|-----------|
| GET | `/admin/dashboard` | `admin.dashboard` | `auth`, `role:admin` |

### Cara Kerja Modul Admin Dashboard

**Bagaimana dashboard admin menampilkan semua data statistik:**

```
Request /admin/dashboard
         │
         ▼
DashboardController::index()
         │
         ├──► query: total siswa, guru, nilai, mapel
         ├──► buildRekapPerKelas(): grouped by kelas
         ├──► buildRataRataPerMapel(): AVG per mapel
         ├──► buildTopSiswa(): top 5 by average
         ├──► buildSiswaPerhatian(): >=1 mapel tidak lulus
         └──► hitung tindakan penting
                    │
                    ▼
          Inertia::render('admin/dashboard', $props)
                    │
                    ▼
          React merender 5 komponen:
          ├── StatCards (4 cards)
          ├── KelasBarChart (expandable)
          ├── ActionChecklist
          ├── SiswaList (top 5)
          └── SiswaList (perhatian)
```

**Penjelasan:**
1. **Backend** menghitung semua statistik dalam 1 request (tidak ada lazy loading)
2. **Frontend** menerima data siap pakai dan merender komponen visual
3. **Interaktif**: Bar chart bisa di-klik untuk filter siswa per kelas
4. **Real-time**: Data di-update setiap kali dashboard dibuka (tidak cache)

**Pembagian kerja:**
- **Backend**: Agregasi data kompleks (grouped queries, calculations)
- **Frontend**: Visualisasi data (charts, cards, tables), interaksi UI

### View: `admin/dashboard.tsx`

| Aspek | Detail |
|-------|--------|
| **File** | `resources/js/pages/admin/dashboard.tsx` |
| **Props** | `{ stats, rekap_per_kelas, top_siswa, siswa_perhatian, tindakan_penting }` |

**Fitur UI:**
- 4 stat cards (Total Siswa, Guru Aktif, Persentase Lulus, Nilai Belum Lulus)
- Bar chart kelulusan per kelas (expandable)
- Action checklist (high/medium priority items)
- Leaderboard top 5 siswa berprestasi
- Daftar 5 siswa perlu perhatian

### Tests (`AcceptanceAdminDashboardTest.php`)

| Test | Verifikasi |
|------|------------|
| Stats | Total siswa, guru, nilai, mapel benar |
| Persentase | Hitungan lulus/tidak dari total nilai |
| Rekap Kelas | Jumlah siswa + persentase per kelas |
| Rata-rata Mapel | Sorted desc dengan persentase lulus |
| Top Siswa | Sorted by average desc, limit 5 |
| Siswa Perhatian | Hanya yang >=1 "Tidak Lulus", sorted by ratio desc |
| Otorisasi | Admin only (403 untuk guru/siswa) |

---

## 4. Modul Admin Manajemen Siswa

### Model: `Siswa`

| Aspek | Detail |
|-------|--------|
| **File** | `app/Models/Siswa.php` |
| **Tabel** | `siswa` |
| **Primary Key** | `nis` (string, non-incrementing) — digunakan untuk route-model binding |
| **Relasi** | `user()` BelongsTo → User, `kelas()` BelongsTo → Kelas, `nilai()` HasMany → Nilai |
| **Metode Kunci** | `getRouteKeyName(): string` return `'nis'` |

### Controller: `Admin\SiswaController`

| Metode | HTTP | Fungsi |
|--------|------|--------|
| `index()` | GET | Daftar paginasi (15/halaman) dengan search (NIS/nama) + filter kelas |
| `create()` | GET | Form tambah siswa dengan dropdown kelas |
| `store()` | POST | Buat User (username=NIS, role=siswa) + Siswa dalam transaksi |
| `edit()` | GET | Form edit siswa |
| `update()` | PUT | Update siswa (NIS immutable), opsional reset password |
| `destroy()` | DELETE | Hapus siswa + user terkait + cascade nilai |

### Request: `Admin\SiswaRequest`

| Field | Rules | Catatan |
|-------|-------|---------|
| `nis` | required, string, max:20, unique | Ignored saat update |
| `nama_siswa` | required, string, max:255 | |
| `kelas_id` | nullable, integer, exists:kelas | |
| `password` | required jika create (min:6, confirmed) | Nullable saat update |

**Override `validated()`:** Strips `nis` pada PUT/PATCH (immutable).

### Routes

| Method | URI | Nama |
|--------|-----|------|
| GET | `/admin/siswa` | `admin.siswa.index` |
| GET | `/admin/siswa/create` | `admin.siswa.create` |
| POST | `/admin/siswa` | `admin.siswa.store` |
| GET | `/admin/siswa/{siswa}/edit` | `admin.siswa.edit` |
| PUT | `/admin/siswa/{siswa}` | `admin.siswa.update` |
| DELETE | `/admin/siswa/{siswa}` | `admin.siswa.destroy` |

### Cara Kerja Modul Manajemen Siswa

**Alur CRUD Lengkap (Contoh: Tambah Siswa Baru):**

```
1. Admin klik "Tambah Siswa"
   │
   ├─► Frontend: <Link href={create.url()}> → navigate ke /admin/siswa/create
   │
   ├─► Backend: SiswaController::create() → query daftar kelas
   │
   └─► Inertia::render('admin/siswa/create', ['daftar_kelas' => ...])
            │
            ▼
      React render form dengan dropdown kelas

2. Admin isi form + klik "Simpan"
   │
   ├─► Frontend: <Form action={store.url()} method="post">
   │   → Inertia POST ke /admin/siswa
   │
   ├─► Backend: SiswaController::store(SiswaRequest)
   │   → validasi (NIS unique, password min:6)
   │   → DB::transaction: create User + Siswa
   │   → redirect()->route('admin.siswa.index')->with('success', ...)
   │
   └─► Inertia handle redirect → GET /admin/siswa
            │
            ▼
      React render index page + toast "Siswa berhasil ditambahkan"
```

**Alur Search/Filter (Partial Reload):**

```
Admin ketik "Budi" di search box
         │
         ▼
useInertiaSearch → debounce 300ms
         │
         ▼
router.get(index.url(), { search: 'Budi' }, { only: ['siswa', 'filters'] })
         │
         ▼
Backend: query WHERE nama LIKE '%Budi%' → return ONLY 'siswa' + 'filters'
         │
         ▼
React: update tabel + URL bar (tanpa full page reload)
```

**Pembagian kerja:**
- **Backend**: Validasi form, CRUD database, pagination, query optimization
- **Frontend**: Form handling, search/filter debounced, UI interaktif (drawer, modal, pagination)
- **Wayfinder**: Type-safe URL generation untuk semua navigasi

### Views

| Halaman | File | Props | Fitur UI |
|---------|------|-------|----------|
| Index | `admin/siswa/index.tsx` | `siswa` (paginated), `daftar_kelas`, `filters` | Tabel search/filter, pagination, detail drawer |
| Create | `admin/siswa/create.tsx` | `daftar_kelas` | Form NIS, nama, kelas, password |
| Edit | `admin/siswa/edit.tsx` | `siswa`, `daftar_kelas` | Form edit (NIS readonly), reset password opsional |

### Tests (`AcceptanceSiswaTest.php`)

| Test | Verifikasi |
|------|------------|
| NIS duplikat | Ditolak (unique) |
| NIS unik | Berhasil dibuat |
| Auto-create User | username=NIS, role=siswa, password hashed |
| Password < 6 | Ditolak |
| Password mismatch | Ditolak |
| Login siswa baru | Bisa login dengan NIS + password |
| Edit NIS | NIS immutable (tidak berubah) |
| Delete | Cascade ke nilai |
| Search | Filter by NIS, nama, atau kelas |

---

## 5. Modul Admin Manajemen Guru

### Model: `Guru`

| Aspek | Detail |
|-------|--------|
| **File** | `app/Models/Guru.php` |
| **Tabel** | `guru` |
| **Relasi** | `user()` BelongsTo → User, `mengajar()` HasMany → GuruMengajar, `nilai()` HasMany → Nilai |

**Metode Kunci:**
- `getAllKelasAttribute(): array` — Kelas unik yang diajar
- `getAllMapelAttribute(): array` — Mapel unik yang diajar
- `getMapelByKelas(string $kelas): array` — Mapel di kelas tertentu
- `mengajarDiKelasMapel(string $kelas, string $mapel): bool` — Cek otorisasi

### Model: `GuruMengajar`

| Aspek | Detail |
|-------|--------|
| **File** | `app/Models/GuruMengajar.php` |
| **Tabel** | `guru_mengajar` |
| **Relasi** | `guru()`, `kelas()`, `mataPelajaran()` — semua BelongsTo |

### Controller: `Admin\GuruController`

| Metode | HTTP | Fungsi |
|--------|------|--------|
| `index()` | GET | Daftar paginasi dengan search + filter kelas/mapel |
| `create()` | GET | Form dengan daftar kelas, mapel, dan `mapel_by_kelas` |
| `store()` | POST | Buat User (username unik dari nama), Guru, sync mengajar dalam transaksi |
| `edit()` | GET | Form dengan data mengajar yang sudah ada |
| `update()` | PUT | Update nama + re-sync mengajar dalam transaksi |
| `destroy()` | DELETE | RESTRICT jika punya nilai; hapus user + mengajar + guru |

**Helper Private:**
- `syncMengajar()` — Delete + recreate dengan deduplikasi
- `generateUniqueUsername()` — Lowercase, strip honorifics, append counter
- `buildMapelByKelas()` — Map kelas_id → [{id, nama}] dari pivot

### Request: `Admin\GuruRequest`

| Field | Rules | Catatan |
|-------|-------|---------|
| `nama_guru` | required, string, max:255 | |
| `mengajar` | required, array, min:1 | |
| `mengajar.*.kelas_id` | required, integer, exists:kelas | |
| `mengajar.*.mata_pelajaran_id` | required, integer, exists:mata_pelajaran | |
| `password` | required jika create (min:6, confirmed) | Nullable saat update |

**Custom Validation:**
- Tolak pasangan (kelas, mapel) duplikat
- Cek pasangan ada di pivot `kelas_mata_pelajaran`

### Routes

| Method | URI | Nama |
|--------|-----|------|
| GET | `/admin/guru` | `admin.guru.index` |
| GET | `/admin/guru/create` | `admin.guru.create` |
| POST | `/admin/guru` | `admin.guru.store` |
| GET | `/admin/guru/{guru}/edit` | `admin.guru.edit` |
| PUT | `/admin/guru/{guru}` | `admin.guru.update` |
| DELETE | `/admin/guru/{guru}` | `admin.guru.destroy` |

### Cara Kerja Modul Manajemen Guru

**Yang membuat modul ini unik:**
- Guru memiliki data **mengajar** (combo kelas + mata pelajaran)
- Saat create, admin harus pilih kombinasi kelas-mapel yang diizinkan
- Username di-auto-generate dari nama guru

**Alur Create Guru:**

```
Admin isi form nama guru + pilih kombinasi mengajar
         │
         ▼
Frontend dynamic rows: setiap baris = 1 kelas + 1 mapel
         │
         ▼
POST /admin/guru → { nama_guru, password, mengajar: [{kelas_id, mata_pelajaran_id}, ...] }
         │
         ▼
Backend: DB::transaction
  1. generateUniqueUsername(nama) → "sariwahyuni"
  2. User::create({ username, role: 'guru', password })
  3. Guru::create({ user_id, nama_guru })
  4. syncMengajar() → GuruMengajar::insert (deduplicate)
         │
         ▼
Redirect ke index + toast sukses
```

**Pembagian kerja:**
- **Backend**: Validasi kombinasi kelas-mapel di pivot, auto-generate username, transaction handling
- **Frontend**: Dynamic form rows, dropdown dependent (kelas → mapel), validation errors display
- **Model**: GuruMengajar pivot table untuk relasi many-to-many

### Views

| Halaman | File | Props | Fitur UI |
|---------|------|-------|----------|
| Index | `admin/guru/index.tsx` | `guru`, `daftar_kelas`, `daftar_mapel`, `filters` | Tabel search/filter, badge mengajar, detail drawer |
| Create | `admin/guru/create.tsx` | `daftar_kelas`, `daftar_mapel`, `mapel_by_kelas` | Form nama, password, dynamic rows mengajar |
| Edit | `admin/guru/edit.tsx` | `guru`, `daftar_kelas`, `daftar_mapel`, `mapel_by_kelas` | Form edit + mengajar rows |

### Tests (`AcceptanceGuruAkunTest.php`)

| Test | Verifikasi |
|------|------------|
| Auto-create User | Username unik dari nama guru |
| Multi mengajar | Simpan lebih dari 1 kombinasi kelas+mapel |
| Validasi min:1 | Harus minimal 1 mengajar |
| Nama duplikat | Username auto-increment (sariwahyuni2) |
| Password < 6 | Ditolak |
| Login guru baru | Bisa login dengan kredensial yang di-generate |
| Delete guru | Cascade ke user (jika tidak punya nilai) |
| Edit mengajar | Re-sync benar |
| Validasi kelas_mata_pelajaran | Tolak kombinasi yang tidak diizinkan |
| Kombinasi duplikat | Ditolak |

---

## 6. Modul Admin Kelas

### Model: `Kelas`

| Aspek | Detail |
|-------|--------|
| **File** | `app/Models/Kelas.php` |
| **Tabel** | `kelas` |
| **Relasi** | `siswa()` HasMany → Siswa, `guruMengajar()` HasMany → GuruMengajar, `mataPelajaran()` BelongsToMany → MataMelalui pivot |

**Accessors:** `jumlah_siswa`, `jumlah_guru_mengajar`, `jumlah_mapel`

**Static Helpers:** `pluckNamaOrdered()`, `pluckIdNamaOrdered()`

### Model: `KelasMataPelajaran` (Pivot)

| Aspek | Detail |
|-------|--------|
| **File** | `app/Models/KelasMataPelajaran.php` |
| **Tabel** | `kelas_mata_pelajaran` |
| **Relasi** | `kelas()`, `mataPelajaran()` — BelongsTo |

### Controller: `Admin\KelasController`

| Metode | HTTP | Fungsi |
|--------|------|--------|
| `index()` | GET | Daftar paginasi (20/halaman) dengan search + `withCount` |
| `create()` | GET | Form dengan daftar semua mapel |
| `store()` | POST | Buat kelas + sync mapel via pivot |
| `edit()` | GET | Form dengan mapel yang sudah dipilih |
| `update()` | PUT | Update nama + re-sync mapel pivot |
| `destroy()` | DELETE | Tolak jika dipakai siswa/guru; detach pivot sebelum hapus |

### Request: `Admin\KelasRequest`

| Field | Rules |
|-------|-------|
| `nama` | required, string, max:20, unique (ignore self) |
| `mata_pelajaran_id` | nullable, array |
| `mata_pelajaran_id.*` | integer, exists:mata_pelajaran |

### Routes

| Method | URI | Nama |
|--------|-----|------|
| GET | `/admin/kelas` | `admin.kelas.index` |
| GET | `/admin/kelas/create` | `admin.kelas.create` |
| POST | `/admin/kelas` | `admin.kelas.store` |
| GET | `/admin/kelas/{kela}/edit` | `admin.kelas.edit` |
| PUT | `/admin/kelas/{kela}` | `admin.kelas.update` |
| DELETE | `/admin/kelas/{kela}` | `admin.kelas.destroy` |

### Cara Kerja Modul Kelas

**Yang membuat modul ini unik:**
- Kelas memiliki relasi **many-to-many** dengan Mata Pelajaran melalui pivot `kelas_mata_pelajaran`
- Saat create kelas, admin bisa pilih mapel mana saja yang dipelajari di kelas tersebut
- Delete kelas dicek: tidak boleh dipakai siswa atau guru mengajar

**Alur Create Kelas:**

```
Admin isi nama kelas + pilih mapel (checkbox)
         │
         ▼
POST /admin/kelas → { nama: "X RPL 1", mata_pelajaran_id: [1, 2, 3] }
         │
         ▼
Backend: DB::transaction
  1. Kelas::create({ nama })
  2. sync pivot: kelas_mata_pelajaran
         │
         ▼
Redirect ke index
```

**Pembagian kerja:**
- **Backend**: Validasi nama unique, sync pivot table, cek FK constraint saat delete
- **Frontend**: Checkbox list untuk pilih mapel, search/filter kelas
- **Pivot**: Tabel `kelas_mata_pelajaran` menyimpan relasi kelas ↔ mapel

### Tests (`AcceptanceKelasMapelTest.php`)

| Test | Verifikasi |
|------|------------|
| CRUD kelas | Berhasil |
| Nama duplikat | Ditolak |
| FK protection | Tidak bisa hapus yang dipakai siswa/mengajar |
| Search | Filter by query string |
| Create dengan mapel | Simpan ke pivot |
| Edit tambah/hapus mapel | Sync pivot benar |
| Hapus kelas | Cascade ke pivot |

---

## 7. Modul Admin Mata Pelajaran

### Model: `MataPelajaran`

| Aspek | Detail |
|-------|--------|
| **File** | `app/Models/MataPelajaran.php` |
| **Tabel** | `mata_pelajaran` |
| **Relasi** | `guruMengajar()` HasMany, `nilai()` HasMany, `kelas()` BelongsToMany |

**Accessors:** `jumlah_guru_mengajar`, `jumlah_nilai`, `jumlah_kelas`

### Controller: `Admin\MataPelajaranController`

| Metode | HTTP | Fungsi |
|--------|------|--------|
| `index()` | GET | Daftar paginasi (20/halaman) dengan search |
| `create()` | GET | Form sederhana |
| `store()` | POST | Buat mapel |
| `edit()` | GET | Form edit |
| `update()` | PUT | Update mapel |
| `destroy()` | DELETE | Tolak jika dipakai guru/mengajar/nilai |

### Request: `Admin\MataPelajaranRequest`

| Field | Rules |
|-------|-------|
| `nama` | required, string, max:100, unique (ignore self) |

### Routes

| Method | URI | Nama |
|--------|-----|------|
| GET | `/admin/mata-pelajaran` | `admin.mata-pelajaran.index` |
| GET | `/admin/mata-pelajaran/create` | `admin.mata-pelajaran.create` |
| POST | `/admin/mata-pelajaran` | `admin.mata-pelajaran.store` |
| GET | `/admin/mata-pelajaran/{mata_pelajaran}/edit` | `admin.mata-pelajaran.edit` |
| PUT | `/admin/mata-pelajaran/{mata_pelajaran}` | `admin.mata-pelajaran.update` |
| DELETE | `/admin/mata-pelajaran/{mata_pelajaran}` | `admin.mata-pelajaran.destroy` |

### Cara Kerja Modul Mata Pelajaran

**Yang membuat modul ini sederhana:**
- Mata Pelajaran hanya memiliki nama, tanpa data kompleks
- Delete dicek: tidak boleh dipakai guru mengajar atau nilai

**Alur CRUD:**
```
Create: Form nama → POST → MataPelajaran::create → redirect
Delete: DELETE → cek relasi (guruMengajar, nilai) → jika aman, hapus
```

**Pembagian kerja:**
- **Backend**: Validasi nama unique, cek relasi sebelum delete
- **Frontend**: Tabel search/filter, badge jumlah guru/nilai/kelas

### Tests (`AcceptanceKelasMapelTest.php`)

| Test | Verifikasi |
|------|------------|
| CRUD mapel | Berhasil |
| Nama duplikat | Ditolak |
| FK protection | Tidak bisa hapus yang dipakai mengajar/nilai |

---

## 8. Modul Admin Manajemen Akun

### Controller: `Admin\AccountController`

| Metode | HTTP | Fungsi |
|--------|------|--------|
| `index()` | GET | Daftar akun paginasi (15/halaman) dengan search + filter role |
| `showCreateAdmin()` | GET | Form buat akun admin |
| `createAdmin()` | POST | Buat admin dengan username, name, password |
| `toggleActive()` | PATCH | Toggle `is_active`; tolak nonaktifkan diri sendiri |
| `resetPassword()` | POST | Reset password (min:6) |

### Routes

| Method | URI | Nama |
|--------|-----|------|
| GET | `/admin/akun` | `admin.accounts.index` |
| GET | `/admin/akun/create-admin` | `admin.accounts.create-admin` |
| POST | `/admin/akun/create-admin` | `admin.accounts.create-admin.store` |
| PATCH | `/admin/akun/{user}/toggle-active` | `admin.accounts.toggle-active` |
| POST | `/admin/akun/{user}/reset-password` | `admin.accounts.reset-password` |

### Cara Kerja Modul Manajemen Akun

**Yang membuat modul ini penting:**
- Mengelola semua akun pengguna (admin, guru, siswa)
- Fitur **toggle active/nonaktif** untuk control akses
- Fitur **reset password** untuk akun yang lupa password
- Admin tidak bisa nonaktifkan akun sendiri

**Alur Toggle Active:**
```
Admin klik toggle di baris akun
         │
         ▼
PATCH /admin/akun/{id}/toggle-active
         │
         ▼
Backend:
  1. Cek: apakah user mencoba nonaktifkan diri sendiri? → Tolak
  2. User::toggleActive() → flip is_active
  3. Redirect ke index
```

**Alur Reset Password:**
```
Admin klik reset password
         │
         ▼
POST /admin/akun/{id}/reset-password → { password: "new123" }
         │
         ▼
Backend:
  1. Validate: min:6, confirmed
  2. User::update(['password' => Hash::make($password)])
  3. Redirect ke index + toast sukses
```

**Pembagian kerja:**
- **Backend**: Self-disable protection, hash password, toggle active logic
- **Frontend**: Modal konfirmasi, password confirmation field, toggle switch UI

### Views

| Halaman | File | Props | Fitur UI |
|---------|------|-------|----------|
| Index | `admin/accounts/index.tsx` | `accounts`, `filters` | Tabel search/filter role, toggle active, reset password |
| Create Admin | `admin/accounts/create-admin.tsx` | none | Form username, name, password + confirmation |

### Tests (`AcceptanceAdminAkunTest.php`)

| Test | Verifikasi |
|------|------------|
| Create admin | Username + password berhasil |
| Username duplikat | Ditolak |
| Password < 6 | Ditolak |
| Password mismatch | Ditolak |
| Login admin baru | Bisa login |
| Toggle active | Flips is_active |
| Self-disable | Ditolak |
| Reset password | Berhasil |

---

## 9. Modul Admin Laporan

### Controller: `Admin\ReportController`

| Metode | HTTP | Fungsi |
|--------|------|--------|
| `index()` | GET | Halaman pemilihan kelas |
| `preview()` | GET | Preview HTML laporan per kelas |
| `exportPdf()` | GET/POST | Export PDF (A4 landscape) via DomPDF |
| `exportHtml()` | GET/POST | Download HTML standalone |
| `exportCsv()` | GET/POST | Export CSV dengan UTF-8 BOM |
| `exportXlsx()` | GET/POST | Export XLSX via openspout |

**Helper Private:**
- `validateFilter()` — Validasi `kelas[]` (required), `mata_pelajaran[]` (opsional), `sort`, `sort_type`
- `buildReportData()` — Pipeline: load siswa → load nilai → group by kelas → hitung per-mapel stats
- `flattenRowsForExport()` — Flatten sections untuk CSV/XLSX

### Cara Kerja Modul Laporan

**Yang membuat modul ini kompleks:**
- Menggabungkan data dari 4 tabel (siswa, nilai, kelas, mata_pelajaran)
- Mendukung 4 format export (PDF, HTML, CSV, XLSX)
- Filter dinamis (kelas, mapel, sorting)

**Alur Generate Laporan:**

```
1. Admin pilih kelas + filter di halaman index
   │
   ▼
POST /admin/laporan/preview → { kelas: ['X RPL 1', 'XI TKJ 2'], sort: 'ranking' }
   │
   ▼
Backend: buildReportData()
   ├── Load siswa per kelas
   ├── Load nilai per siswa
   ├── Group by kelas
   ├── Hitung per-mapel stats (rata-rata, lulus, tidak lulus)
   └── Sort by ranking / perhatian
         │
         ▼
   Inertia::render('admin/reports/preview', $data)
         │
         ▼
   React: Tabel multi-kelas + tombol export

2. Admin klik "Export PDF"
   │
   ▼
GET /admin/laporan/export/pdf?kelas[]=...
   │
   ▼
Backend: buildReportData() → DomPDF::loadView('rapor-pdf') → download
```

**Pembagian kerja:**
- **Backend**: Query complex (multiple eager loading, grouping), PDF generation (DomPDF), CSV/XLSX export
- **Frontend**: Filter form, preview table, export buttons
- **PDF Template**: Blade view `rapor-pdf` untuk rendering PDF

### Routes

| Method | URI | Nama |
|--------|-----|------|
| GET | `/admin/laporan` | `admin.reports.index` |
| GET | `/admin/laporan/preview` | `admin.reports.preview` |
| GET/POST | `/admin/laporan/export/pdf` | `admin.reports.export.pdf` |
| GET/POST | `/admin/laporan/export/html` | `admin.reports.export.html` |
| GET/POST | `/admin/laporan/export/csv` | `admin.reports.export.csv` |
| GET/POST | `/admin/laporan/export/xlsx` | `admin.reports.export.xlsx` |

### Views

| Halaman | File | Props | Fitur UI |
|---------|------|-------|----------|
| Index | `admin/reports/index.tsx` | `daftar_kelas`, `daftar_mapel` | Pilih kelas, filter, tombol generate |
| Preview | `admin/reports/preview.tsx` | `kelas_list`, `sections`, `stats`, `tanggal_cetak` | Tabel multi-kelas, tombol export PDF/CSV/HTML/XLSX |

### Tests (`AcceptanceLaporanTest.php`)

| Test | Verifikasi |
|------|------------|
| Preview | Tampilkan semua siswa dengan nilai |
| PDF export | Valid PDF dengan header benar |
| HTML export | Berisi nama siswa |
| Multi-kelas | Gabung sections dengan benar |
| Multi-mapel | Filter tampilkan mapel tertentu |
| CSV export | Header + data benar |
| XLSX export | Valid Office Open XML |
| Validasi | Tolak kelas kosong / tidak dikenal |

---

## 10. Modul Admin Manajemen Nilai

### Model: `NilaiUnlockLog`

| Aspek | Detail |
|-------|--------|
| **File** | `app/Models/NilaiUnlockLog.php` |
| **Tabel** | `nilai_unlock_log` (append-only) |
| **Tidak ada `updated_at`** | `UPDATED_AT = null` |
| **Relasi** | `admin()` → User, `guru()` → Guru, `kelas()`, `mataPelajaran()` |

### Controller: `Admin\NilaiController`

| Metode | HTTP | Fungsi |
|--------|------|--------|
| `index()` | GET | Tabel Final combos (grouped by guru+kelas+mapel) + 10 log terbaru + filter |
| `unlock()` | POST | Validasi → dalam transaksi: Final → Draft + tulis audit log; idempotent |

### Routes

| Method | URI | Nama |
|--------|-----|------|
| GET | `/admin/nilai` | `admin.nilai.index` |
| POST | `/admin/nilai/unlock` | `admin.nilai.unlock` |

### View: `admin/nilai/index.tsx`

| Aspek | Detail |
|-------|--------|
| **Props** | `combos` (paginated Final combos), `logs` (recent logs), `kelas_options`, `filters` |
| **Fitur UI** | Tabel Final combos, modal unlock dengan alasan, riwayat audit log |

### Cara Kerja Modul Admin Manajemen Nilai

**Yang membuat modul ini kritis:**
- Admin bisa "membuka kunci" nilai yang sudah Final
- Setiap unlock dicatat di audit log (append-only)
- Unlock mengembalikan status ke Draft, sehingga guru bisa edit lagi

**Alur Unlock Nilai:**

```
Admin klik "Unlock" pada combo tertentu
         │
         ▼
Modal muncul: "Masukkan alasan unlock (min:10 karakter)"
         │
         ▼
POST /admin/nilai/unlock → { combo_id, reason: "Koreksi kesalahan input" }
         │
         ▼
Backend: DB::transaction
  1. Validasi: reason min:10
  2. Find Nilai WHERE kelas + mapel + guru + status = Final
  3. UPDATE status_validasi = 'Draft'
  4. NilaiUnlockLog::create({ admin_id, guru_id, kelas_id, mapel_id, reason })
         │
         ▼
Redirect + toast "Berhasil unlock"
         │
         ▼
Guru sekarang bisa edit nilai lagi
```

**Pembagian kerja:**
- **Backend**: Validasi alasan, transaction handling, audit logging (append-only)
- **Frontend**: Modal konfirmasi, form alasan, riwayat log
- **Audit**: `nilai_unlock_log` table mencatat semua unlock action

### Tests (`AcceptanceAdminNilaiUnlockTest.php`)

| Test | Verifikasi |
|------|------------|
| Halaman | Render combos + logs |
| Hanya Final | Draft tidak ditampilkan |
| Search/filter | Bekerja |
| POST unlock | Final → Draft + audit log |
| Reason min:10 | Validasi |
| Scope | Hanya combo yang dipilih |
| Idempotent | Unlock kedua: affected=0, log tetap |
| Post-unlock | Guru bisa edit nilai |
| Otorisasi | Guru/siswa 403 |

---

## 11. Modul Guru Dashboard

### Controller: `Guru\DashboardController`

| Aspek | Detail |
|-------|--------|
| **File** | `app/Http/Controllers/Guru/DashboardController.php` |
| **Metode** | `index()` — Personalized dashboard: stats aggregate + per-combo breakdown |

**Metode Private:**
- `buildPerComboStats()` — Untuk setiap combo mengajar: jumlah siswa, jumlah input, jumlah final, jumlah draft

### Route

| Method | URI | Nama | Middleware |
|--------|-----|------|-----------|
| GET | `/guru/dashboard` | `guru.dashboard` | `auth`, `role:guru` |

### Cara Kerja Modul Guru Dashboard

**Yang membuat modul ini personal:**
- Setiap guru hanya melihat data kombinasi kelas + mapel yang diajar
- Dashboard menampilkan status input nilai (Draft/Final) per combo

**Alur Kerja:**

```
Guru login → /guru/dashboard
         │
         ▼
DashboardController::index()
  ├── Load guru berdasarkan auth()->id()
  ├── Build stats aggregate (total siswa, nilai, draft, final)
  └── Build per-combo stats:
       ├── Combo: X RPL 1 + Matematika
       │   ├── jumlah_siswa: 30
       │   ├── jumlah_input: 25 (belum input 5)
       │   ├── jumlah_final: 20 (belum final 5)
       │   └── jumlah_draft: 5
       └── Combo: XI TKJ 2 + Fisika
           └── ...
         │
         ▼
Inertia::render('guru/dashboard', $data)
         │
         ▼
React: Header selamat datang + stat cards + tabel status per combo
```

**Pembagian kerja:**
- **Backend**: Filter data hanya untuk combo yang diajar guru, hitung statistik per combo
- **Frontend**: Personalized greeting, stat cards, badge status (Draft/Final), link ke input nilai

### View: `guru/dashboard.tsx`

| Aspek | Detail |
|-------|--------|
| **Props** | `{ guru, stats, per_combo_stats }` |
| **Fitur UI** | Header selamat datang, stat cards (siswa, nilai, draft, final), ringkasan kelulusan, tabel status per combo dengan badge dan link ke input-nilai |

### Tests (`AcceptanceGuruDashboardTest.php`)

| Test | Verifikasi |
|------|------------|
| Stats | total_siswa, total_nilai, draft, final, lulus, tidak_lulus, rata_rata benar |
| Per-combo | jumlah_siswa, jumlah_input, jumlah_final, jumlah_draft benar |

---

## 12. Modul Guru Input Nilai

### Controller: `Guru\NilaiController`

| Metode | HTTP | Fungsi |
|--------|------|--------|
| `index()` | GET | Form input terbatas pada combo mengajar guru; load siswa + nilai existing |
| `save()` | POST | Bulk save/update nilai; validasi otorisasi; hitung nilai_akhir + status_lulus |
| `validateFinal()` | POST | Lock semua non-null nilai: status_validasi = Final |
| `destroy()` | DELETE | Hapus 1 baris nilai; tolak jika sudah Final; 403 jika bukan milik guru |

### Routes

| Method | URI | Nama |
|--------|-----|------|
| GET | `/guru/input-nilai` | `guru.nilai.index` |
| POST | `/guru/input-nilai/save` | `guru.nilai.save` |
| POST | `/guru/input-nilai/validate-final` | `guru.nilai.validate-final` |
| DELETE | `/guru/input-nilai/{nilai}` | `guru.nilai.destroy` |

### View: `guru/nilai/index.tsx`

| Aspek | Detail |
|-------|--------|
| **Props** | `{ guru, daftar_kelas, mapel_by_kelas, kelas, kelas_id, mata_pelajaran, mata_pelajaran_id, siswa, nilai_map, status_validasi_global, has_mengajar }` |
| **Fitur UI** | Dropdown dependent (kelas → mapel), tabel bulk input tugas/UTS/UAS, tombol Simpan (Draft), tombol Validasi Final (lock), delete per baris |

### Alur Input Nilai

```
Guru memilih Kelas → Mapel (auto-filtered dari mengajar)
     │
     ▼
Tabel siswa tampil dengan input fields
     │
     ▼
Input Tugas/UTS/UAS → Nilai Akhir dihitung real-time
     │
     ├─► [Simpan sebagai Draft] → status_validasi = Draft
     │
     └─► [Validasi Final] → status_validasi = Final (locked)
```

### Cara Kerja Modul Guru Input Nilai

**Yang membuat modul ini kompleks:**
- Guru hanya bisa input nilai untuk combo kelas + mapel yang diajar
- Nilai dihitung real-time (0.3×tugas + 0.3×uts + 0.4×uas = nilai_akhir)
- Status Final mengunci nilai (tidak bisa di-edit kecuali admin unlock)
- Bulk save: semua siswa disimpan sekaligus

**Alur Lengkap Input Nilai:**

```
1. Guru pilih kelas + mapel di dropdown
   │
   ▼
GET /guru/input-nilai?kelas=X+RPL+1&mata_pelajaran=Matematika
   │
   ▼
Backend: Load siswa + nilai existing + status validasi
         │
         ▼
React: Tabel 30 siswa, setiap baris ada input Tugas/UTS/UAS
         │
         ▼
Guru input nilai → Nilai Akhir = 0.3×T + 0.3×U + 0.4×A
         │
         ├─► Klik "Simpan Draft"
         │   POST /guru/input-nilai/save → { data: [{nis, tugas, uts, uas}, ...] }
         │   Backend: bulk insert/update → status_validasi = Draft
         │
         └─► Klik "Validasi Final"
             POST /guru/input-nilai/validate-final → { kelas, mapel }
             Backend: UPDATE status_validasi = 'Final' WHERE Draft
             → Nilai terkunci, guru tidak bisa edit lagi
```

**Pembagian kerja:**
- **Backend**: Otorisasi (cek mengajarDiKelasMapel), bulk save, kalkulasi otomatis, validasi final
- **Frontend**: Real-time calculation, bulk input UI, status badge, dropdown dependent
- **Otorisasi**: Guru tidak bisa input di combo yang tidak diajar → 403

### Tests (`AcceptanceNilaiTest.php`)

| Test | Verifikasi |
|------|------------|
| Validasi 0-100 | Score > 100 atau < 0 ditolak |
| Kalkulasi bobot | 0.3×tugas + 0.3×uts + 0.4×uas = nilai_akhir |
| Status lulus | >= 70 = "Lulus", < 70 = "Tidak Lulus" |
| Validate final | status_validasi = Final |
| Otorisasi 403 | Guru tidak bisa input di kelas+mapel yang tidak diajar |
| Guru tanpa mengajar | has_mengajar = false |

---

## 13. Modul Guru Rekap

### Controller: `Guru\NilaiController::rekap()` (sama dengan input nilai)

| Metode | HTTP | Fungsi |
|--------|------|--------|
| `rekap()` | GET | Halaman read-only: rekap per siswa untuk combo (kelas, mapel) + statistik |

### Route

| Method | URI | Nama |
|--------|-----|------|
| GET | `/guru/rekap` | `guru.rekap.index` |

### Cara Kerja Modul Guru Rekap

**Yang membuat modul ini berbeda dari Input Nilai:**
- **Read-only**: Tidak ada input/edit, hanya melihat
- Per siswa ditampilkan ringkasan: tugas, UTS, UAS, nilai akhir, status
- Statistik: jumlah lulus, tidak lulus, belum input

**Alur Kerja:**

```
Guru pilih kelas + mapel
         │
         ▼
GET /guru/rekap?kelas=X+RPL+1&mata_pelajaran=Matematika
         │
         ▼
Backend: Load siswa + nilai → group per siswa
         │
         ▼
React: Tabel rekap per siswa + statistik summary
  ├── Nama | Tugas | UTS | UAS | Akhir | Status
  ├── Budi | 80    | 75  | 85  | 80    | Lulus ✓
  ├── Ani  | 60    | 55  | 65  | 60    | Tidak Lulus ✗
  └── ...
         │
         ▼
Statistik: 25 lulus, 3 tidak lulus, 2 belum input
```

**Pembagian kerja:**
- **Backend**: Query nilai + format data untuk rekap
- **Frontend**: Tabel read-only, badge status, statistik cards

### View: `guru/rekap/index.tsx`

| Aspek | Detail |
|-------|--------|
| **Props** | `{ guru, kelas, kelas_id, mata_pelajaran, mata_pelajaran_id, daftar_kelas, mapel_by_kelas, rows, stats, has_mengajar }` |
| **Fitur UI** | Dropdown dependent, tabel rekap per siswa dengan badge status, ringkasan statistik (lulus/tidak_lulus/belum) |

### Tests (`AcceptanceTier2Test.php`)

| Test | Verifikasi |
|------|------------|
| Otorisasi | Guru hanya lihat combo yang diajar |
| Tanpa mengajar | has_mengajar = false |
| Stats | lulus/tidak_lulus/belum count benar |

---

## 14. Modul Siswa Dashboard

### Controller: `Siswa\DashboardController`

| Aspek | Detail |
|-------|--------|
| **File** | `app/Http/Controllers/Siswa/DashboardController.php` |
| **Metode** | `index()` — Load siswa berdasarkan `auth()->id()`, cek apakah ada Final nilai (`has_nilai`) |

### Route

| Method | URI | Nama | Middleware |
|--------|-----|------|-----------|
| GET | `/siswa/dashboard` | `siswa.dashboard` | `auth`, `role:siswa` |

### Cara Kerja Modul Siswa Dashboard

**Yang membuat modul ini sederhana:**
- Siswa hanya melihat data sendiri (data isolation)
- Dashboard menampilkan profil dan akses cepat ke nilai/rapor

**Alur Kerja:**

```
Siswa login → /siswa/dashboard
         │
         ▼
DashboardController::index()
  ├── Load siswa berdasarkan auth()->id()
  └── Cek has_nilai: ada Final nilai? → true/false
         │
         ▼
Inertia::render('siswa/dashboard', ['siswa' => $siswa, 'has_nilai' => $hasNilai])
         │
         ▼
React:
  ├── Profile cards: NIS, Kelas, Username
  ├── Tombol "Lihat Nilai" → /siswa/nilai
  └── Tombol "Cetak Rapor" (hanya jika has_nilai=true) → /siswa/rapor/pdf
```

**Pembagian kerja:**
- **Backend**: Load data siswa, cek status nilai (Final/Draft)
- **Frontend**: Profile cards, action buttons (conditional render berdasarkan has_nilai)

### View: `siswa/dashboard.tsx`

| Aspek | Detail |
|-------|--------|
| **Props** | `{ siswa, has_nilai }` |
| **Fitur UI** | Profile stat cards (NIS, Kelas, Username), tombol "Lihat Nilai", tombol "Cetak Rapor" (hanya jika `has_nilai=true`) |

### Tests (`AcceptanceSiswaRaporTest.php`)

| Test | Verifikasi |
|------|------------|
| has_nilai | true jika ada Final nilai, false jika tidak ada / hanya Draft |
| Transisi | false → true saat nilai menjadi Final |

---

## 15. Modul Siswa Nilai

### Controller: `Siswa\NilaiController`

| Aspek | Detail |
|-------|--------|
| **File** | `app/Http/Controllers/Siswa/NilaiController.php` |
| **Metode** | `index()` — Load hanya Final nilai; group by (kelas, mapel); build guru_map + mapel_list |

### Routes

| Method | URI | Nama |
|--------|-----|------|
| GET | `/siswa/nilai` | `siswa.nilai.index` |
| GET | `/siswa/statistik` | `siswa.statistik.index` |

### Cara Kerja Modul Siswa Nilai

**Yang membuat modul ini berbeda dari input guru:**
- **Read-only**: Siswa tidak bisa edit nilai
- Hanya menampilkan nilai yang sudah **Final** (Draft tidak terlihat)
- Menampilkan rata-rata keseluruhan dan per mapel

**Alur Kerja:**

```
Siswa klik "Lihat Nilai"
         │
         ▼
GET /siswa/nilai
         │
         ▼
Backend: Load nilai WHERE status_validasi = 'Final' + auth siswa
  ├── Group by (kelas, mapel)
  ├── Build guru_map: { mapel_id: nama_guru }
  └── Build mapel_list: [{ id, nama, tugas, uts, uas, akhir, status }]
         │
         ▼
React:
  ├── Dashboard cards: rata-rata keseluruhan, ringkasan akademik
  ├── Tabel nilai per mapel dengan bar chart
  └── Tombol "Cetak Rapor"
```

**Pembagian kerja:**
- **Backend**: Filter Final only, data isolation (hanya nilai sendiri), format data untuk UI
- **Frontend**: Visualisasi nilai (bar chart), status badge (Lulus/Tidak Lulus), rapor link

### View: `siswa/nilai/index.tsx`

| Aspek | Detail |
|-------|--------|
| **Props** | `{ nilai, mapel_list, guru_map }` |
| **Fitur UI** | Dashboard cards (rata-rata keseluruhan, ringkasan akademik, komponen perlu perhatian), tabel nilai per mapel dengan bar chart, tombol Cetak Rapor |

### Tests (`AcceptanceSiswaAksesTest.php`)

| Test | Verifikasi |
|------|------------|
| Data isolation | Siswa hanya lihat nilai sendiri |
| 403 akses guru/admin | Ditolak |
| Akun nonaktif | Tidak bisa login |
| guru_map | Nama guru ditampilkan dengan benar |
| Hanya Final | Draft tidak ditampilkan |

---

## 16. Modul Siswa Statistik

### Controller: `Siswa\NilaiController::statistik()` (sama dengan nilai)

| Metode | HTTP | Fungsi |
|--------|------|--------|
| `statistik()` | GET | Build `chart_data`: per-mapel (tugas, uts, uas, akhir, status, kkm) + aggregate stats |

### Route

| Method | URI | Nama |
|--------|-----|------|
| GET | `/siswa/statistik` | `siswa.statistik.index` |

### Cara Kerja Modul Siswa Statistik

**Yang membuat modul ini menarik:**
- Visualisasi data dalam bentuk **bar chart interaktif**
- Filter interaktif: status kelulusan, komponen nilai, mata pelajaran
- Menampilkan KKM (Kriteria Ketuntasan Minimal) sebagai garis referensi

**Alur Kerja:**

```
Siswa klik "Statistik"
         │
         ▼
GET /siswa/statistik
         │
         ▼
Backend: Build chart_data per mapel:
  {
    Matematika: { tugas: 80, uts: 75, uas: 85, akhir: 80, status: 'Lulus', kkm: 70 },
    Fisika: { tugas: 60, uts: 55, uas: 65, akhir: 60, status: 'Tidak Lulus', kkm: 70 },
    ...
  }
         │
         ▼
React: Bar chart SVG per mapel
  ├── Setiap mapel = 4 bar (tugas, uts, uas, akhir)
  ├── Garis KKM horizontal (merah)
  ├── Filter: status (Lulus/Tidak/Semua), komponen (Tugas/UTS/UAS)
  └── Statistik: jumlah mapel lulus, tidak lulus, rata-rata
```

**Pembagian kerja:**
- **Backend**: Format data untuk chart, hitung statistik
- **Frontend**: SVG bar chart, filter interaktif, KKM line, responsive design

### View: `siswa/statistik/index.tsx`

| Aspek | Detail |
|-------|--------|
| **Props** | `{ mapel_list, chart_data }` |
| **Fitur UI** | Filter interaktif (status kelulusan, komponen nilai, mata pelajaran), bar chart SVG per mapel dengan KKM line, ringkasan statistik |

### Tests (`AcceptanceSiswaRaporTest.php`)

| Test | Verifikasi |
|------|------------|
| chart_data | per_mapel, stats, kkm benar |
| Empty state | Untuk siswa tanpa nilai |

---

## 17. Modul Siswa Rapor

### Controller: `Siswa\RaporController`

| Aspek | Detail |
|-------|--------|
| **File** | `app/Http/Controllers/Siswa/RaporController.php` |
| **Metode** | `pdf()` — Generate rapor PDF via DomPDF (A4 portrait); hanya Final nilai; include metadata sekolah |

**Helper Private:**
- `guessTahunAjaran()` — Tahun ajaran berdasarkan bulan (>= Juli = tahun ini/depan)
- `guessSemester()` — "Ganjil" (Jul-Jun) atau "Genap" (Feb-Jun)
- `getLogoBase64()` — Baca logo rapor untuk inline embedding
- `guessNextKelas()` — Hitung kelas berikutnya (X→XI, XII→LULUS)

### Route

| Method | URI | Nama | Middleware |
|--------|-----|------|-----------|
| GET | `/siswa/rapor/pdf` | `siswa.rapor.pdf` | `auth`, `role:siswa` |

### Cara Kerja Modul Siswa Rapor

**Yang membuat modul ini unik:**
- Generate PDF resmi dengan format standar rapor
- Hanya nilai **Final** yang ditampilkan
- Otomatis menentukan tahun ajaran dan semester berdasarkan tanggal saat ini
- Data isolation: siswa hanya bisa download rapor sendiri

**Alur Kerja:**

```
Siswa klik "Cetak Rapor"
         │
         ▼
GET /siswa/rapor/pdf
         │
         ▼
Backend: RaporController::pdf()
  1. Load siswa berdasarkan auth()->id()
  2. Load nilai Final
  3. guessTahunAjaran() → "2025/2026"
  4. guessSemester() → "Ganjil" atau "Genap"
  5. guessNextKelas() → "XI RPL 1" atau "LULUS"
  6. getLogoBase64() → embed logo sekolah
  7. DomPDF::loadView('rapor-pdf', $data) → render PDF
         │
         ▼
Download: Rapor_Budi_00001.pdf
```

**Pembagian kerja:**
- **Backend**: Query nilai Final, format PDF (DomPDF), auto-detect tahun ajaran/semester, data isolation
- **Frontend**: Tombol trigger download (bisa via `<a href>` atau `window.location`)
- **PDF Template**: Blade view `rapor-pdf` dengan layout resmi rapor

### Tests (`AcceptanceSiswaRaporTest.php`)

| Test | Verifikasi |
|------|------------|
| PDF download | Content-Type: application/pdf |
| Content | Berisi nama siswa, NIS, kelas, mapel |
| Data isolation | PDF siswa A tidak expose siswa B |
| Filename | `Rapor>Nama_NIS.pdf` |
| Otorisasi | Guru/admin 403 |
| Hanya Final | Draft tidak di-include |

---

## 18. Diagram Relasi Model

```
User (1) ──── hasOne ────▶ Siswa (N) ──── belongsTo ────▶ Kelas (1)
User (1) ──── hasOne ────▶ Guru (1) ──── hasMany ────▶ GuruMengajar (N)
                                                            │
                                                            ▼
                                                    Kelas (1) ◀── belongsTo ── GuruMengajar
                                                    MataPelajaran (1) ◀── belongsTo ── GuruMengajar

Kelas ◀──── belongsToMany ────▶ MataPelajaran
              (pivot: kelas_mata_pelajaran)

Siswa (1) ──── hasMany ────▶ Nilai (N) ◀──── belongsTo ──── Guru
Nilai ◀──── belongsTo ──── Kelas
Nilai ◀──── belongsTo ──── MataPelajaran

NilaiUnlockLog ──── belongsTo ──── User (as admin)
NilaiUnlockLog ──── belongsTo ──── Guru
NilaiUnlockLog ──── belongsTo ──── Kelas
NilaiUnlockLog ──── belongsTo ──── MataPelajaran
```

---

## 19. Ringkasan Otorisasi

| Aksi | Siapa | Mekanisme |
|------|-------|-----------|
| Login | Guest | Fortify, rate-limited (5/menit) |
| Admin dashboard & `/admin/*` | Admin | Middleware `role:admin` |
| Guru dashboard & `/guru/*` | Guru | Middleware `role:guru` |
| Siswa dashboard & `/siswa/*` | Siswa | Middleware `role:siswa` |
| Login akun nonaktif | - | `EnsureUserHasRole` paksa logout + 403 |
| Guru input nilai | Guru | Cek `mengajarDiKelasMapelId()` |
| Admin self-deactivation | Admin | `AccountController::toggleActive()` tolak |
| Admin nilai unlock | Admin | Reason required (min:10), audit logged |
| Data isolation siswa | Siswa | Query filter by `auth()->user()->siswa->nis`; Draft nilai hidden |

---

## Ringkasan Jumlah File

| Kategori | Jumlah |
|----------|--------|
| Model | 9 |
| Controller | 14 (1 base + 6 admin + 2 guru + 3 siswa + 2 reuse) |
| Form Request | 5 |
| Frontend Pages (tsx) | 27 |
| Feature Tests (php) | 15 |
| Test Cases | ~95 |
| Routes | 34 (termasuk 7 export variants) |
