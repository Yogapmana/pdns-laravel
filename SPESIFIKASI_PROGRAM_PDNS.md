# SPESIFIKASI PROGRAM
## Sistem Informasi Penilaian Digital Sekolah (PDNS)

**Nama Proyek:** `pdns-laravel`
**Framework:** Laravel 13 · Inertia.js v3 · React 19 · TypeScript · TailwindCSS v4
**Database:** MySQL (db: `pdns`)
**PHP:** 8.3+
**Tanggal Dokumen:** 6 Juni 2026
**Versi:** 1.0

---

## DAFTAR ISI
1. [Deskripsi Sistem](#1-deskripsi-sistem)
2. [Tujuan Sistem](#2-tujuan-sistem)
3. [Analisis Kebutuhan Pengguna](#3-analisis-kebutuhan-pengguna)
4. [Fungsi Utama Sistem](#4-fungsi-utama-sistem)
5. [Spesifikasi Fungsional & Nonfungsional](#5-spesifikasi-fungsional--nonfungsional)
6. [Alur Kerja Sistem (Flowchart)](#6-alur-kerja-sistem-flowchart)
7. [UML Sistem](#7-uml-sistem)
8. [Rancangan Database](#8-rancangan-database)
9. [Fungsi/Prosedur (Pemrograman Terstruktur)](#9-fungsi-prosedur-pemrograman-terstruktur)
10. [Class & Method (OOP)](#10-class--method-oop)
11. [Batasan Sistem](#11-batasan-sistem)

---

## 1. DESKRIPSI SISTEM

### 1.1 Gambaran Umum
**PDNS (Penilaian Digital Nasional Sekolah)** adalah aplikasi berbasis web yang digunakan untuk melakukan **pengelolaan nilai akademik siswa** secara digital pada satuan pendidikan (sekolah). Sistem ini dibangun menggunakan framework **Laravel 13** sebagai *backend* (RESTful + Inertia.js) dan **React 19 + TypeScript + TailwindCSS v4** sebagai *frontend* SPA (*Single-Page Application*) yang dirender melalui Inertia.js v3.

Sistem mendukung tiga peran pengguna utama: **Admin**, **Guru**, dan **Siswa**, masing-masing dengan hak akses, dashboard, dan modul kerja yang berbeda. Sistem mengelola data master (kelas, mata pelajaran), data pengguna (admin, guru, siswa), data pengajaran (guru mengajar kombinasi kelas–mapel), serta transaksi nilai akademik siswa yang dihitung menggunakan bobot **Tugas 30%, UTS 30%, UAS 40%** dan KKM **70.0**.

### 1.2 Karakteristik Sistem
| Aspek | Keterangan |
|---|---|
| **Arsitektur** | Monolith Laravel + SPA Inertia (server-driven SPA) |
| **Autentikasi** | Laravel Fortify (login, logout, 2FA-ready, password reset) |
| **Otentikasi Role** | Custom middleware `EnsureUserHasRole` (alias `role:admin,guru,siswa`) |
| **Frontend** | React 19, TypeScript, TailwindCSS v4, Vite, Inertia.js v3, Laravel Wayfinder |
| **ORM** | Eloquent ORM (Laravel) |
| **Database** | MySQL 8.x |
| **Session/Cache/Queue** | Database driver |
| **Export** | PDF (barryvdh/laravel-dompdf), HTML, CSV (UTF-8 BOM), XLSX (openspout/openspout) |
| **Notifikasi** | In-app notification (bell icon) + polling 60 dtk |
| **Audit Log** | `nilai_unlock_log` (append-only) untuk pembukaan kunci nilai |
| **Observers** | `GradeObserver` otomatis mengirim notifikasi saat transisi nilai |

### 1.3 Modul-Modul Sistem
1. **Autentikasi & Manajemen Akun** – login multi-role, kelola akun admin/guru/siswa, reset password, aktivasi/nonaktif.
2. **Master Data** – CRUD kelas, mata pelajaran, pivot `kelas_mata_pelajaran`.
3. **Manajemen Guru** – CRUD guru + penetapan kombinasi mengajar `(kelas, mata_pelajaran)`.
4. **Manajemen Siswa** – CRUD siswa + auto-create akun siswa (username = NIS).
5. **Input Nilai** – entry nilai Tugas/UTS/UAS per siswa, hitung Nilai Akhir & status Lulus otomatis, kunci Final.
6. **Rekap Nilai Guru** – rekapitulasi per kelas-mapel (Lulus / Tidak Lulus / Belum).
7. **Buka Kunci Nilai (Admin)** – reopen nilai Final ke Draft dengan alasan (audit log).
8. **Dashboard Statistik** – total siswa/guru/mapel, rekap per kelas, rata-rata per mapel, top-5 siswa, siswa perhatian.
9. **Laporan** – cetak/preview laporan nilai (filter multi-kelas & multi-mapel) → PDF/HTML/CSV/XLSX.
10. **Rapor Siswa** – cetak rapor per siswa berbasis PDF (hanya nilai Final).
11. **Notifikasi In-App** – lonceng notifikasi dengan auto-fire saat transisi nilai & perubahan akun.
12. **Halaman Nilai & Statistik Siswa** – siswa melihat nilai Final per mapel + grafik statistik.

---

## 2. TUJUAN SISTEM

### 2.1 Tujuan Umum
Membangun sistem informasi penilaian digital yang **terpusat, terstruktur, dan akuntabel** untuk menggantikan proses penilaian manual (buku rapor/Excel terpisah) sehingga tercapai **efisiensi, transparansi, dan konsistensi** data nilai di lingkungan sekolah.

### 2.2 Tujuan Khusus
1. **Memusatkan data nilai** – seluruh nilai siswa tersimpan dalam satu basis data relasional MySQL.
2. **Otomasi perhitungan** – Nilai Akhir dihitung otomatis dengan bobot tetap (Tugas 30% + UTS 30% + UAS 40%) dan kelulusan ditentukan otomatis berdasarkan KKM ≥ 70.
3. **Multi-role workflow** – Admin mengelola master data & akun, Guru menginput & mengunci nilai, Siswa melihat nilai & rapor.
4. **Filter real-time** – pencarian akun (admin/guru/siswa), siswa, guru, dan nilai dapat difilter langsung per kolom/kelas/mapel (sesuai kebutuhan `todo.md`).
5. **Laporan multi-format** – mendukung cetak laporan dalam format **PDF, HTML, CSV, XLSX** dengan filter banyak kelas dan banyak mata pelajaran (sesuai `todo.md`).
6. **Audit & keamanan** – pembukaan kunci nilai Final wajib disertai alasan dan tercatat di log; akun nonaktif otomatis logout.
7. **Notifikasi proaktif** – siswa & guru menerima notifikasi otomatis saat nilai berubah status (Draft → Final) atau saat akun diubah.
8. **Rapor digital** – siswa dapat mengunduh rapor PDF resmi yang hanya memuat nilai Final.

### 2.3 Sasaran Pengguna
- **Admin sekolah** (staf tata usaha / operator) – mengelola seluruh data master dan akun.
- **Guru mata pelajaran** – memasukkan nilai akademik siswa.
- **Siswa** – memantau hasil belajar dan mengunduh rapor.

---

## 3. ANALISIS KEBUTUHAN PENGGUNA

### 3.1 Identifikasi Aktor (Pengguna)
| No | Aktor | Peran | Kebutuhan Utama |
|----|------|-------|-----------------|
| 1 | **Admin** | Administrator sistem | Kelola akun, master data, laporan, buka kunci nilai |
| 2 | **Guru** | Pengajar | Input nilai per (kelas, mapel), validasi Final, lihat rekap |
| 3 | **Siswa** | Peserta didik | Lihat nilai, lihat statistik, unduh rapor |
| 4 | **Sistem** | Background | Auto-kirim notifikasi, auto-cleanup, auto-audit log |

### 3.2 Kebutuhan Fungsional per Aktor

#### 3.2.1 Kebutuhan Admin
| Kode | Kebutuhan |
|------|-----------|
| ADM-F01 | Login & logout dengan kredensial admin |
| ADM-F02 | Melihat dashboard statistik agregat (total siswa, guru, mapel, nilai, % kelulusan) |
| ADM-F03 | Mengelola data Siswa (CRUD) + filter real-time (search NIS/nama, filter kelas) |
| ADM-F04 | Mengelola data Guru (CRUD) + filter (search nama, filter kelas, filter mapel) + penetapan kombinasi mengajar |
| ADM-F05 | Mengelola data Kelas (CRUD) + mata pelajaran yang diizinkan per kelas |
| ADM-F06 | Mengelola data Mata Pelajaran (CRUD) |
| ADM-F07 | Mengelola Akun Pengguna (list + filter role, toggle aktif/nonaktif, reset password) |
| ADM-F08 | Membuat akun admin baru (form create-admin) |
| ADM-F09 | Membuka kunci (unlock) nilai Final → Draft dengan alasan (min 10 karakter, max 500), tercatat di audit log |
| ADM-F10 | Melihat laporan nilai dengan filter multi-kelas & multi-mapel, preview & ekspor ke PDF/HTML/CSV/XLSX |
| ADM-F11 | Menerima notifikasi bell di header |

#### 3.2.2 Kebutuhan Guru
| Kode | Kebutuhan |
|------|-----------|
| GR-F01 | Login & logout dengan akun guru |
| GR-F02 | Melihat dashboard guru (info mengajar) |
| GR-F03 | Memilih (kelas, mata_pelajaran) yang dia ampu (dibatasi oleh `guru_mengajar`) |
| GR-F04 | Input nilai Tugas, UTS, UAS per siswa (range 0–100), sistem menghitung Nilai Akhir & status Lulus otomatis |
| GR-F05 | Menyimpan nilai berstatus Draft |
| GR-F06 | Mengunci (Validasi Final) seluruh nilai pada satu kombinasi (kelas, mapel) |
| GR-F07 | Menghapus nilai Draft (nilai Final tidak dapat dihapus) |
| GR-F08 | Melihat Rekap nilai (Lulus / Tidak Lulus / Belum) per kombinasi |
| GR-F09 | Menerima notifikasi bell (auto) saat nilai masih Draft, dll. |

#### 3.2.3 Kebutuhan Siswa
| Kode | Kebutuhan |
|------|-----------|
| SW-F01 | Login & logout dengan akun siswa (username = NIS) |
| SW-F02 | Melihat dashboard siswa (ringkasan nilai & status kelulusan) |
| SW-F03 | Melihat daftar nilai per mata pelajaran (hanya nilai Final yang ditampilkan) |
| SW-F04 | Melihat halaman statistik nilai (grafik/distribusi) |
| SW-F05 | Mengunduh rapor PDF pribadi (hanya memuat nilai Final, dengan KKM, tahun ajaran, dll.) |
| SW-F06 | Menerima notifikasi bell (auto) saat nilai mapel sudah Final |

### 3.3 Kebutuhan Nonfungsional
| Kode | Kategori | Kebutuhan |
|------|----------|-----------|
| NF-01 | Keamanan | Password di-hash (bcrypt rounds=12), role-based access, CSRF, prepared statement via Eloquent |
| NF-02 | Performa | Query agregat menggunakan `groupBy` & `withCount` untuk menghindari N+1; paginasi 15/20 per halaman |
| NF-03 | Ketersediaan | Session driver database, queue database |
| NF-04 | Usability | SPA modern dengan Inertia.js, Tailwind responsive, filter real-time via query string |
| NF-05 | Maintainability | Kode PSR-12, type-hint eksplisit, `declare(strict_types=1)`, Laravel Pint formatter |
| NF-06 | Audit | Tabel `nilai_unlock_log` append-only (tanpa `updated_at`); kolom `reason` wajib diisi |
| NF-07 | Internasionalisasi | Tanggal cetak menggunakan `translatedFormat('d F Y')` (Bahasa Indonesia) |
| NF-08 | Portabilitas | Dependensi via Composer & npm; build frontend via Vite |

---

## 4. FUNGSI UTAMA SISTEM

| No | Fungsi Utama | Modul Terkait | Aktor |
|----|--------------|---------------|-------|
| **F1** | **Autentikasi Multi-Role** – login dengan username/password, logout, auto-redirect ke dashboard sesuai role | Fortify + middleware `role` | Semua |
| **F2** | **Manajemen Akun** – CRUD implisit via tambah guru/siswa + toggle aktif/nonaktif + reset password | `AccountController`, `User` | Admin |
| **F3** | **Manajemen Siswa** – CRUD + auto-create akun (username = NIS) + filter real-time | `SiswaController` | Admin |
| **F4** | **Manajemen Guru** – CRUD + penetapan mengajar (kelas, mapel) + filter real-time + auto-generate username unik | `GuruController` | Admin |
| **F5** | **Manajemen Kelas** – CRUD + pivot `kelas_mata_pelajaran` (mapel yang diizinkan per kelas) | `KelasController` | Admin |
| **F6** | **Manajemen Mata Pelajaran** – CRUD sederhana | `MataPelajaranController` | Admin |
| **F7** | **Input Nilai** – entry Tugas/UTS/UAS per siswa, hitung Nilai Akhir (bobot tetap), set status Lulus otomatis, simpan sebagai Draft, lalu kunci Final per kombinasi | `Guru/NilaiController` | Guru |
| **F8** | **Rekap Nilai** – agregasi Lulus/Tidak Lulus/Belum per kombinasi (kelas, mapel) | `Guru/NilaiController::rekap` | Guru |
| **F9** | **Buka Kunci Nilai** – admin me-reopen nilai Final → Draft dengan alasan; tercatat di `nilai_unlock_log` | `Admin/NilaiController` | Admin |
| **F10** | **Dashboard Statistik** – total agregat, rekap per kelas, rata-rata per mapel, top-5 siswa, siswa perhatian (rank by failed subjects) | `Admin/DashboardController` | Admin |
| **F11** | **Laporan Multi-Format** – filter multi-kelas + multi-mapel, preview HTML, ekspor ke PDF/HTML/CSV/XLSX | `Admin/ReportController` + `XlsxWriter` | Admin |
| **F12** | **Rapor Siswa** – generate rapor PDF per siswa (hanya nilai Final, dengan tahun ajaran, KKM, nama guru pengampu) | `Siswa/RaporController` + DomPDF | Siswa |
| **F13** | **Notifikasi In-App** – bell icon, polling unread-count 60 dtk, mark-read per item, mark-all-read, auto-fire via Observer saat transisi nilai, `notifications:cleanup` harian | `NotificationController`, `GradeObserver`, `NotificationDispatcher` | Semua |
| **F14** | **Halaman Nilai & Statistik Siswa** – siswa melihat nilai per mapel (hanya Final) + halaman statistik visual | `Siswa/NilaiController` | Siswa |

---

## 5. SPESIFIKASI FUNGSIONAL & NONFUNGSIONAL

### 5.1 Spesifikasi Fungsional

#### SF-01 : Login Multi-Role
- **Input:** `username` (string), `password` (string)
- **Proses:** Cek kredensial via Fortify → cek `is_active` → cek `role` → redirect ke dashboard sesuai role (`admin.dashboard` / `guru.dashboard` / `siswa.dashboard`).
- **Output:** Session auth + redirect; jika gagal → error 401.
- **Aturan:** Akun nonaktif otomatis logout (middleware `EnsureUserHasRole`).

#### SF-02 : Hitung Nilai Akhir
- **Input:** `nilai_tugas`, `nilai_uts`, `nilai_uas` (float 0–100)
- **Proses:** `Nilai::hitungNilaiAkhir(t,u,v) = (t·0.30) + (u·0.30) + (v·0.40)` dibulatkan 2 desimal.
- **Output:** `nilai_akhir` (float) + `status_lulus` (Lulus / Tidak Lulus) bila `nilai_akhir ≥ 70`.

#### SF-03 : Validasi Final per Kombinasi
- **Input:** `kelas`, `mata_pelajaran`
- **Proses:** Set `status_validasi = 'Final'` untuk semua baris nilai pada kombinasi (guru, kelas, mapel) yang `nilai_akhir NOT NULL`.
- **Output:** Redirect + flash success; trigger `GradeObserver::updated` → kirim notifikasi ke semua siswa di kelas tersebut.
- **Aturan:** Hanya guru yang mengajar kombinasi tersebut (cek `mengajarDiKelasMapel()`), selain itu → 403.

#### SF-04 : Buka Kunci Nilai (Admin)
- **Input:** `id_guru`, `kelas`, `mata_pelajaran`, `reason` (min 10, max 500)
- **Proses:** Set `status_validasi = 'Draft'` untuk baris Final; tulis baris audit ke `nilai_unlock_log`.
- **Output:** Flash success; log tercatat selamanya (append-only).
- **Aturan:** `reason` wajib diisi minimal 10 karakter.

#### SF-05 : Laporan dengan Filter
- **Input:** `kelas` (array, min 1, semua nama harus valid di tabel `kelas`), `mata_pelajaran` (array, opsional, semua nama harus valid)
- **Proses:** Ambil siswa sesuai `kelas`, ambil nilai siswa tersebut, agregasi per-kelas dan global, hitung rata-rata, hitung kelulusan.
- **Output:** Preview HTML atau unduhan PDF/HTML/CSV/XLSX.
- **Aturan:** Penamaan file: `laporan_<kelas>_<YYYY>.csv/.xlsx/.pdf/.html`, multi-kelas → `laporan_multi_<n>kelas_<YYYY>...`.

#### SF-06 : Generate Rapor Siswa
- **Input:** `auth()->user()` (siswa login)
- **Proses:** Ambil siswa → ambil semua nilai `status_validasi = Final` → kelompokkan per mapel → hitung rata-rata & jumlah Lulus/Tidak Lulus → render ke view `reports.rapor-pdf` via DomPDF.
- **Output:** PDF ter-unduh `Rapor_<nama>_<nis>.pdf`.
- **Aturan:** Tahun ajaran ditebak otomatis (`<YYYY>/<YYYY+1>` jika bulan ≥ 7, else `<YYYY-1>/<YYYY>`).

#### SF-07 : Notifikasi Auto-Fire
- **Trigger 1 (Draft→Final):** Setiap baris nilai bertransisi ke Final → kirim notifikasi ke semua siswa di kelas tersebut.
- **Trigger 2 (Saved dengan Draft tersisa):** Setelah simpan nilai, jika masih ada baris Draft → kirim reminder ke guru pengampu.
- **Trigger 3 (Akun diubah):** Toggle aktif/nonaktif & reset password → kirim notifikasi ke user yang bersangkutan.

#### SF-08 : Rapor – Filter Real-Time
- **Input:** `search` (string), `kelas` (string), `mapel` (string)
- **Proses:** `when()` clause pada Eloquent query; preserve query string pada pagination.
- **Output:** Tabel dengan filter aktif, paginasi 15/20 item.

### 5.2 Spesifikasi Nonfungsional

| Kategori | Spesifikasi | Implementasi |
|----------|-------------|--------------|
| **Keamanan** | Hashing bcrypt, CSRF token, session encryption optional, prepared statement, role middleware | Laravel Fortify + `EnsureUserHasRole` |
| **Performa** | Hindari N+1, gunakan `with`/`withCount`, query agregat tunggal untuk dashboard | Eager loading di semua controller list |
| **Skalabilitas** | Session DB, queue DB, cache DB; siap dipisah ke Redis | `.env` configurable |
| **Ketersediaan** | Tidak ada single point of failure; queue & session berbasis DB | Laravel default |
| **Maintainability** | PSR-12, strict types, type-hint eksplisit, Pint formatter | `declare(strict_types=1)` di semua file |
| **Usability** | SPA Inertia, Tailwind responsive, flash message, validasi form | Laravel validation + Inertia |
| **Kompatibilitas** | PHP 8.3+, MySQL 8+, Node 20+, browser modern | `composer.json` requirement |
| **Audit** | Tabel `nilai_unlock_log` append-only (tanpa `updated_at`) | Migrasi `2026_06_05_000007` |
| **i18n** | Tanggal Bahasa Indonesia | `translatedFormat('d F Y')` |
| **Testing** | Pest 4 + PHPUnit 12, Coverage via `php artisan test` | `pestphp/pest ^4.7` |

---

## 6. ALUR KERJA SISTEM (FLOWCHART)

### 6.1 Flowchart Umum – Login & Redirect
```
┌──────────┐
│  START   │
└────┬─────┘
     ▼
┌──────────────┐
│ Buka /login  │
└────┬─────────┘
     ▼
┌──────────────────────────┐    Tidak
│  Sudah login? ───────────┼────────►┐
└────┬─────────────────────┘         ▼
     │ Ya                      ┌─────────────┐
     ▼                         │ Tampil Form │
┌──────────────────────────┐   │   Login     │
│ Panggil dashboardRoute() │   └──────┬──────┘
└────┬─────────────────────┘          │
     ▼                                ▼
┌──────────────┐               ┌──────────────┐
│ Redirect ke  │               │ Submit kred. │
│ dashboard    │               └──────┬───────┘
│ sesuai role  │                      │
└──────────────┘                      ▼
                              ┌──────────────────┐
                              │ Fortify validate │
                              └──────┬───────────┘
                                     ▼
                              ┌──────────────────┐    Gagal
                              │ Cek is_active ───┼─────► 401/403
                              └──────┬───────────┘
                                     ▼ Sukses
                              ┌──────────────┐
                              │ Redirect by  │
                              │    role      │
                              └──────────────┘
```

### 6.2 Flowchart – Manajemen Siswa (Admin)
```
┌──────────────────────┐
│ GET /admin/siswa     │
└────┬─────────────────┘
     ▼
┌──────────────────────┐    Tidak
│ Ada filter? ─────────┼──┐
└────┬─────────────────┘  │
     │ Ya                 ▼
     │             ┌─────────────────┐
     ▼             │ Query siswa +   │
┌──────────────────┤ eager load user │
│ Terapkan where   │ & paginate 15   │
│ like nama/nis +  └────────┬────────┘
│ where kelas               │
└────┬─────────────────────┘
     ▼
┌──────────────────────────┐
│ Render inertia page +    │
│ preserve query string    │
└────┬─────────────────────┘
     ▼
┌──────────────────────────┐
│ Admin klik "Tambah"      │
└────┬─────────────────────┘
     ▼
┌──────────────────────────┐
│ POST /admin/siswa        │
│ (SiswaRequest validated) │
└────┬─────────────────────┘
     ▼
┌──────────────────────────┐
│ DB::transaction          │
│  ├─ User::create(username=NIS, role=siswa, pwd hashed)│
│  └─ Siswa::create(nis, user_id, nama, kelas)        │
└────┬─────────────────────┘
     ▼
┌──────────────────────────┐
│ Redirect + flash success │
│ "Akun login otomatis…"   │
└──────────────────────────┘
```

### 6.3 Flowchart – Input Nilai (Guru)
```
┌──────────────────────────────┐
│ GET /guru/input-nilai        │
│ ?kelas=X&mata_pelajaran=Y    │
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐    Tidak
│ Guru mengajarDiKelasMapel()─┼─────► empty page (no access)
└────┬─────────────────────────┘
     │ Ya
     ▼
┌──────────────────────────────┐
│ Ambil siswa di kelas tsb    │
│ (order by nis)              │
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐
│ Ambil Nilai existing        │
│ keyBy(nis)                  │
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐
│ Render form Tugas/UTS/UAS   │
│ per siswa (pre-populated)   │
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐
│ Guru submit form            │
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐
│ Validasi 0–100 per nilai    │
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐
│ DB::transaction             │
│  foreach baris:             │
│    hitungNilaiAkhir()       │
│    tentukanKelulusan()      │
│    updateOrCreate Draft     │
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐
│ GradeObserver::saved fires  │
│ → jika masih ada Draft,     │
│   kirim notif ke guru       │
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐
│ Redirect + flash success    │
└──────────────────────────────┘
```

### 6.4 Flowchart – Validasi Final
```
┌──────────────────────────────┐
│ POST /guru/input-nilai/      │
│      validate-final          │
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐    Tidak
│ mengajarDiKelasMapel()?─────┼─────► 403
└────┬─────────────────────────┘
     │ Ya
     ▼
┌──────────────────────────────┐
│ Ambil semua nilai NOT NULL  │
│ pada (guru,kelas,mapel)      │
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐
│ UPDATE status_validasi=Final│
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐
│ Ambil siswa di kelas tsb    │
│ yg punya user_id            │
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐
│ NotificationDispatcher::    │
│ sendMany() → TYPE_NILAI_    │
│ SUDAH_FINAL ke semua siswa  │
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐
│ GradeObserver::updated juga │
│ fire → safety-net notif     │
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐
│ Redirect + flash success    │
│ "<n> baris difinalisasi"    │
└──────────────────────────────┘
```

### 6.5 Flowchart – Buka Kunci Nilai (Admin)
```
┌──────────────────────────────┐
│ GET /admin/nilai            │
│ Tampilkan tabel Final combo │
│ + 10 log terakhir           │
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐
│ Admin pilih baris + isi     │
│ alasan (min 10 char)         │
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐
│ POST /admin/nilai/unlock    │
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐
│ Validasi reason 10-500 char │
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐
│ DB::transaction             │
│  1. UPDATE nilai set Draft  │
│  2. INSERT nilai_unlock_log │
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐
│ Redirect + flash "Nilai     │
│ dibuka, <n> baris → Draft,  │
│ alasan tercatat di log"     │
└──────────────────────────────┘
```

### 6.6 Flowchart – Generate Laporan
```
┌──────────────────────────────┐
│ GET /admin/laporan          │
│ Tampilkan form filter       │
│ (multi-select kelas, mapel) │
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐
│ Admin pilih kelas (wajib)   │
│ & mapel (opsional)          │
└────┬─────────────────────────┘
     ▼
     ├──────────► Preview HTML (GET /laporan/preview)
     │
     ▼
     ├──────────► Export PDF  (loadView → DomPDF → download)
     ├──────────► Export HTML (view render → download)
     ├──────────► Export CSV  (StreamedResponse + UTF-8 BOM)
     └──────────► Export XLSX (XlsxWriter → download)
                  │
                  ▼
            ┌──────────────────────────────┐
            │ validateFilter()             │
            │  - kelas required, valid     │
            │  - mapel optional, valid     │
            │  - dedup + sort              │
            └────┬─────────────────────────┘
                 ▼
            ┌──────────────────────────────┐
            │ buildReportData()            │
            │  - load siswa whereIn kelas  │
            │  - load nilai whereIn mapel  │
            │  - group by [nis][mapel]     │
            │  - aggregate per kelas+global│
            │  - hitung rata-rata          │
            └────┬─────────────────────────┘
                 ▼
            ┌──────────────────────────────┐
            │ flattenRowsForExport()       │
            │  - header + rows 2D          │
            └────┬─────────────────────────┘
                 ▼
            ┌──────────────────────────────┐
            │ Stream file ke browser       │
            └──────────────────────────────┘
```

### 6.7 Flowchart – Rapor Siswa
```
┌──────────────────────────────┐
│ GET /siswa/rapor/pdf        │
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐
│ Cari Siswa by user_id login │
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐
│ Ambil semua Nilai Final     │
│ (with guru) orderBy mapel   │
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐
│ Group by mata_pelajaran     │
│ → first item per mapel      │
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐
│ Hitung agregat:             │
│  - jumlah_mapel             │
│  - lulus / tidak_lulus      │
│  - rata_rata (AVG akhir)    │
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐
│ Guess tahun ajaran:         │
│  >= Juli → Y/YYYY+1         │
│  < Juli  → Y-1/YYYY         │
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐
│ Pdf::loadView('reports.     │
│   rapor-pdf', $data)        │
│   ->setPaper('a4','portrait')│
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐
│ NotificationDispatcher::    │
│ send TYPE_RAPOR_TERSEDIA    │
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐
│ $pdf->download(             │
│  "Rapor_<nama>_<nis>.pdf")  │
└──────────────────────────────┘
```

### 6.8 Flowchart – Sistem Notifikasi
```
┌──────────────────────────────┐
│ Trigger sumber:             │
│  • Nilai Draft → Final      │
│  • Nilai saved, masih Draft │
│  • Akun toggle aktif        │
│  • Akun reset password      │
│  • Rapor dicetak            │
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐
│ NotificationDispatcher      │
│  send(user, type, title,    │
│       body, link)           │
│  sendMany(users, ...)       │
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐
│ Dedup by (user_id, type,    │
│ link) + insert ke tabel     │
│ notifications               │
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐
│ Frontend poll               │
│ GET /notifications/         │
│   unread-count (60 dtk)     │
└────┬─────────────────────────┘
     ▼
┌──────────────────────────────┐
│ User buka bell → GET /      │
│ notifications (paginated 20)│
│ + mark-read / mark-all-read │
└──────────────────────────────┘
```

---

## 7. UML SISTEM

### 7.1 Use Case Diagram
```
                     ┌────────────────────────────────────────────────────┐
                     │          SISTEM INFORMASI PDNS                     │
                     └────────────────────────────────────────────────────┘
                                              │
        ┌─────────────────────────────────────┼──────────────────────────────────────┐
        │                                     │                                      │
        ▼                                     ▼                                      ▼
┌──────────────┐                     ┌──────────────┐                       ┌──────────────┐
│   <<admin>>  │                     │   <<guru>>   │                       │  <<siswa>>   │
│    Admin     │                     │     Guru     │                       │    Siswa     │
└──────┬───────┘                     └──────┬───────┘                       └──────┬───────┘
       │                                    │                                      │
       │ UC-01 Login                        │ UC-10 Login                          │ UC-19 Login
       │ UC-02 Logout                       │ UC-11 Logout                         │ UC-20 Logout
       │ UC-03 Dashboard Statistik          │ UC-12 Dashboard Guru                 │ UC-21 Dashboard Siswa
       │ UC-04 Kelola Siswa (CRUD)          │ UC-13 Input Nilai (Tgs/UTS/UAS)      │ UC-22 Lihat Nilai
       │ UC-05 Kelola Guru (CRUD+mengajar)  │ UC-14 Validasi Final                 │ UC-23 Lihat Statistik
       │ UC-06 Kelola Kelas (CRUD+mapel)    │ UC-15 Hapus Nilai Draft              │ UC-24 Unduh Rapor PDF
       │ UC-07 Kelola Mata Pelajaran (CRUD) │ UC-16 Lihat Rekap Nilai              │
       │ UC-08 Kelola Akun + buat admin     │ UC-17 Menerima Notifikasi            │ UC-25 Menerima Notifikasi
       │ UC-09 Buka Kunci Nilai + Audit     │                                      │
       │ UC-18 Laporan (preview+export)     │                                      │
       │ UC-26 Menerima Notifikasi          │                                      │
       │                                    │                                      │
       └──────────────┬────────────────────┴──────────────────────────────────────┘
                      │
                      ▼
            ┌────────────────────┐
            │   <<sistem>>       │
            │  Auto-Notification │
            │  + Audit Log       │
            │  + Cleanup Harian  │
            └────────────────────┘

Cross-cutting:
  - UC-27 Auto-fire notifikasi (Draft→Final, saved-Draft, akun-diubah, rapor)
  - UC-28 Audit log pembukaan kunci nilai
  - UC-29 Polling unread-count notifikasi
```

#### Tabel Ringkasan Use Case
| UC | Aktor | Nama Use Case | Deskripsi Singkat |
|----|-------|---------------|-------------------|
| UC-01 | Semua | Login | Masuk sistem dengan kredensial |
| UC-02 | Semua | Logout | Keluar sistem |
| UC-03 | Admin | Dashboard Statistik | Lihat ringkasan agregat |
| UC-04 | Admin | Kelola Siswa | CRUD siswa + filter |
| UC-05 | Admin | Kelola Guru | CRUD guru + mengajar + filter |
| UC-06 | Admin | Kelola Kelas | CRUD kelas + pivot mapel |
| UC-07 | Admin | Kelola Mata Pelajaran | CRUD mapel |
| UC-08 | Admin | Kelola Akun | Toggle aktif, reset pwd, buat admin |
| UC-09 | Admin | Buka Kunci Nilai | Reopen Final → Draft + log |
| UC-10 | Guru | Login (sebagai guru) | – |
| UC-11 | Guru | Logout | – |
| UC-12 | Guru | Dashboard | Info mengajar |
| UC-13 | Guru | Input Nilai | Entry Tugas/UTS/UAS |
| UC-14 | Guru | Validasi Final | Kunci Draft → Final |
| UC-15 | Guru | Hapus Nilai Draft | Hapus nilai sebelum Final |
| UC-16 | Guru | Rekap Nilai | Lulus/Tidak Lulus/Belum |
| UC-17 | Guru | Terima Notifikasi | Bell icon |
| UC-18 | Admin | Laporan Multi-Format | Preview + export |
| UC-19 | Siswa | Login (sebagai siswa) | – |
| UC-20 | Siswa | Logout | – |
| UC-21 | Siswa | Dashboard | Ringkasan nilai |
| UC-22 | Siswa | Lihat Nilai | Per mapel (Final only) |
| UC-23 | Siswa | Lihat Statistik | Grafik nilai |
| UC-24 | Siswa | Unduh Rapor PDF | Cetak rapor |
| UC-25 | Siswa | Terima Notifikasi | Bell icon |
| UC-26 | Admin | Terima Notifikasi | Bell icon |
| UC-27 | Sistem | Auto-Notifikasi | Observer-triggered |
| UC-28 | Sistem | Audit Log | Append-only ke `nilai_unlock_log` |
| UC-29 | Sistem | Polling Notifikasi | Frontend poll 60 detik |

### 7.2 Class Diagram
```
┌─────────────────────────────────────────────────┐
│ <<abstract>> Authenticatable (Laravel)         │
└─────────────────────────────────────────────────┘
                       △
                       │ extends
                       │
┌──────────────────────────────────────────────────┐
│  User                                            │
│  ────────────────────────────────────────────    │
│  - id: int                                       │
│  - username: string (unique)                     │
│  - name: string?                                 │
│  - role: enum(admin|guru|siswa)                  │
│  - is_active: bool                               │
│  - password: string (hashed)                     │
│  - remember_token: string?                       │
│  ────────────────────────────────────────────    │
│  + siswa(): HasOne<Siswa>                        │
│  + guru(): HasOne<Guru>                          │
│  + notifications(): HasMany<Notification>        │
│  + hasRole(...$roles): bool                      │
│  + isAdmin() / isGuru() / isSiswa(): bool        │
│  + dashboardRoute(): string                      │
└──────┬───────────────┬──────────────────┬────────┘
       │ 1:1           │ 1:1              │ 1:N
       ▼               ▼                  ▼
┌──────────────┐ ┌──────────────┐ ┌──────────────────┐
│   Siswa      │ │    Guru      │ │   Notification   │
│ ──────────── │ │ ──────────── │ │ ──────────────── │
│ - nis: str PK│ │ - id: int PK │ │ - id, user_id FK │
│ - user_id FK │ │ - user_id FK │ │ - type, title    │
│ - nama_siswa │ │ - nama_guru  │ │ - body, link     │
│ - kelas: str │ │ ──────────── │ │ - read_at, crt_at│
│ ──────────── │ │ + mengajar() │ │ ──────────────── │
│ + user()     │ │ + nilai()    │ │ + user()         │
│ + nilai()    │ │ + allKelas() │ │ + isRead(): bool │
└──────┬───────┘ │ + allMapel() │ └──────────────────┘
       │ 1:N     │ + getMapel.. │
       ▼         │ + mengajar.. │
┌──────────────┐ └──────┬───────┘
│   Nilai      │        │ 1:N
│ ──────────── │        ▼
│ - id: int PK │  ┌────────────────────┐
│ - nis: FK    │  │  GuruMengajar      │
│ - id_guru FK │  │ ────────────────── │
│ - kelas: str │  │ - id, id_guru FK   │
│ - mapel: str │  │ - kelas, mapel     │
│ - n_tugas    │  └────────────────────┘
│ - n_uts      │         │ N:1
│ - n_uas      │         ▼
│ - n_akhir    │  ┌────────────────────┐
│ - status_ls  │  │  Guru (sudah ada)  │
│ - status_vl  │  └────────────────────┘
│ ──────────── │
│ + hitungAkhir│
│ + kelulusan  │
│ + validasi   │
│ + siswa()    │
│ + guru()     │
└──────────────┘
       ▲
       │ 1:N
       │
┌──────────────┐
│  Notification│  (sudah ada di User)
└──────────────┘

┌──────────────────────────────────────────────────┐
│  Kelas                                           │
│  - id: int PK                                    │
│  - nama: string (unique)                         │
│  + siswa(), guruMengajar(), nilai(): HasMany     │
│  + mataPelajaran(): BelongsToMany                │
│  + scopeSearch(term): Builder                    │
│  + pluckNamaOrdered(): Collection                │
└──────┬─────────────────┬──────────────────────────┘
       │ 1:N             │ N:M (pivot)
       ▼                 ▼
┌─────────────────┐ ┌──────────────────────────────┐
│  Siswa (ada)    │ │  KelasMataPelajaran (pivot)  │
└─────────────────┘ │  - kelas, mata_pelajaran     │
                    └──────────────┬───────────────┘
                                   │ N:M
                                   ▼
                    ┌──────────────────────────────┐
                    │  MataPelajaran               │
                    │  - id, nama (unique)         │
                    │  + guruMengajar(), nilai()   │
                    │  + kelas(): BelongsToMany    │
                    │  + scopeSearch(term)         │
                    └──────────────────────────────┘

┌──────────────────────────────────────────────────┐
│  NilaiUnlockLog (append-only)                    │
│  - id, id_admin FK, id_guru FK                  │
│  - kelas, mata_pelajaran                        │
│  - affected_rows, reason (text)                 │
│  - created_at (no updated_at)                   │
│  + admin(), guru(): BelongsTo                   │
└──────────────────────────────────────────────────┘
```

#### Hubungan Antar Class
| Class A | Relasi | Class B | Keterangan |
|---------|--------|---------|------------|
| User | 1:1 | Siswa | Optional profile |
| User | 1:1 | Guru | Optional profile |
| User | 1:N | Notification | In-app bell |
| Siswa | 1:N | Nilai | Transaksi nilai |
| Guru | 1:N | GuruMengajar | Kombinasi mengajar |
| Guru | 1:N | Nilai | Sebagai penginput |
| Kelas | 1:N | Siswa | siswa.kelas = kelas.nama |
| Kelas | 1:N | GuruMengajar | mengajar.kelas = kelas.nama |
| Kelas | N:M | MataPelajaran | via `kelas_mata_pelajaran` |
| MataPelajaran | N:M | Kelas | via `kelas_mata_pelajaran` |
| Nilai | N:1 | Siswa | FK nis |
| Nilai | N:1 | Guru | FK id_guru |
| NilaiUnlockLog | N:1 | User (admin) | FK id_admin |
| NilaiUnlockLog | N:1 | Guru | FK id_guru |

### 7.3 Entity Relationship Diagram (ERD)
```
┌────────────┐         ┌──────────────┐         ┌──────────────┐
│   users    │         │    siswa     │         │    guru      │
├────────────┤         ├──────────────┤         ├──────────────┤
│ id (PK)    │1───────1│ user_id (FK) │         │ user_id (FK) │1
│ username   │         │ nis (PK)     │1────┐   │ id (PK)      │
│ name       │         │ nama_siswa   │     │   │ nama_guru    │
│ role       │         │ kelas        │     │   └──────┬───────┘
│ is_active  │         │ created_at   │     │          │1
│ password   │         │ updated_at   │     │          │
│ remember_t │         └──────────────┘     │          │
│ created_at │                              │          ▼ N
│ updated_at │         ┌──────────────┐     │   ┌──────────────────┐
└─────┬──────┘         │    nilai     │     │   │ guru_mengajar    │
      │ 1              ├──────────────┤     │   ├──────────────────┤
      │                │ id (PK)      │     │   │ id (PK)          │
      │                │ nis (FK)     │─────┘   │ id_guru (FK)     │
      │                │ id_guru (FK) │─────────┤ kelas            │
      │                │ kelas        │     │   │ mata_pelajaran   │
      │ N              │ mata_pelajaran│    │   │ created_at       │
      ▼                │ n_tugas      │     │   │ updated_at       │
┌────────────────┐     │ n_uts        │     │   └────────┬─────────┘
│ notifications  │     │ n_uas        │     │            │
├────────────────┤     │ n_akhir      │     │            │
│ id (PK)        │     │ status_lulus │     │            │
│ user_id (FK)   │     │ status_val   │     │            │
│ type           │     │ created_at   │     │            │
│ title          │     │ updated_at   │     │            │
│ body           │     └──────────────┘     │            │
│ link           │                          │            │
│ read_at        │                          │            │
│ created_at     │                          │            │
└────────────────┘                          │            │
                                            │            │
┌──────────────┐         ┌──────────────┐   │            │
│   kelas      │         │mata_pelajaran│   │            │
├──────────────┤         ├──────────────┤   │            │
│ id (PK)      │1       N│ id (PK)      │   │            │
│ nama (unique)│─────────┤ nama (unique)│   │            │
│ created_at   │         │ created_at   │   │            │
│ updated_at   │         │ updated_at   │   │            │
└──────┬───────┘         └──────┬───────┘   │            │
       │ 1                      │ 1         │            │
       │                        │           │            │
       │ N              ┌───────┴────────┐  │            │
       └────────────────┤kelas_mata_     ├──┴────────────┘
                        │  pelajaran     │
                        ├────────────────┤
                        │ id (PK)        │
                        │ kelas          │
                        │ mata_pelajaran │
                        │ created_at     │
                        │ updated_at     │
                        └────────────────┘

┌──────────────────────┐
│  nilai_unlock_log    │ (append-only)
├──────────────────────┤
│ id (PK)              │
│ id_admin (FK) ────► users.id
│ id_guru (FK)  ────► guru.id
│ kelas                │
│ mata_pelajaran       │
│ affected_rows        │
│ reason (text)        │
│ created_at (useCurrent)
└──────────────────────┘
```

**Catatan ERD:**
- `kelas_mata_pelajaran` adalah **pivot table** many-to-many antara `kelas` dan `mata_pelajaran`.
- `nilai.mata_pelajaran` dan `nilai.kelas` adalah **string denormalized** (merefer ke `kelas.nama` dan `mata_pelajaran.nama`), bukan FK numerik — hal ini konsisten dengan model Eloquent.
- `nilai_unlock_log` tidak punya `updated_at` (append-only).

---

## 8. RANCANGAN DATABASE

### 8.1 Tabel `users` (Autentikasi)
| Field | Tipe | Null | Default | Keterangan |
|-------|------|------|---------|------------|
| `id` | BIGINT UNSIGNED (PK) | NO | AUTO_INCREMENT | Primary key |
| `username` | VARCHAR(255) | NO | – | Unique, dipakai login |
| `name` | VARCHAR(255) | YES | NULL | Nama tampilan |
| `role` | ENUM('admin','guru','siswa') | NO | – | Indexed |
| `is_active` | BOOLEAN | NO | true | Indexed, untuk middleware |
| `password` | VARCHAR(255) | NO | – | Bcrypt-hashed |
| `remember_token` | VARCHAR(100) | YES | NULL | "Remember me" |
| `created_at` | TIMESTAMP | YES | NULL | |
| `updated_at` | TIMESTAMP | YES | NULL | |
| **Index** | `username` UNIQUE, `role` INDEX, `is_active` INDEX | | | |

### 8.2 Tabel `siswa`
| Field | Tipe | Null | Default | Keterangan |
|-------|------|------|---------|------------|
| `nis` | VARCHAR(20) (PK) | NO | – | Nomor Induk Siswa |
| `user_id` | BIGINT UNSIGNED (FK→users.id) | YES | NULL | Unique, ON DELETE SET NULL |
| `nama_siswa` | VARCHAR(255) | NO | – | |
| `kelas` | VARCHAR(20) | NO | – | Indexed, match `kelas.nama` |
| `created_at` | TIMESTAMP | YES | NULL | |
| `updated_at` | TIMESTAMP | YES | NULL | |
| **Index** | `kelas` INDEX, `user_id` UNIQUE | | | |

### 8.3 Tabel `guru`
| Field | Tipe | Null | Default | Keterangan |
|-------|------|------|---------|------------|
| `id` | BIGINT UNSIGNED (PK) | NO | AUTO_INCREMENT | |
| `user_id` | BIGINT UNSIGNED (FK→users.id) | YES | NULL | Unique, ON DELETE SET NULL |
| `nama_guru` | VARCHAR(255) | NO | – | |
| `created_at` | TIMESTAMP | YES | NULL | |
| `updated_at` | TIMESTAMP | YES | NULL | |

### 8.4 Tabel `kelas`
| Field | Tipe | Null | Default | Keterangan |
|-------|------|------|---------|------------|
| `id` | BIGINT UNSIGNED (PK) | NO | AUTO_INCREMENT | |
| `nama` | VARCHAR(20) | NO | – | Unique (mis. "X-A") |
| `created_at` | TIMESTAMP | YES | NULL | |
| `updated_at` | TIMESTAMP | YES | NULL | |
| **Index** | `nama` UNIQUE | | | |

### 8.5 Tabel `mata_pelajaran`
| Field | Tipe | Null | Default | Keterangan |
|-------|------|------|---------|------------|
| `id` | BIGINT UNSIGNED (PK) | NO | AUTO_INCREMENT | |
| `nama` | VARCHAR(100) | NO | – | Unique |
| `created_at` | TIMESTAMP | YES | NULL | |
| `updated_at` | TIMESTAMP | YES | NULL | |
| **Index** | `nama` UNIQUE | | | |

### 8.6 Tabel `kelas_mata_pelajaran` (Pivot)
| Field | Tipe | Null | Default | Keterangan |
|-------|------|------|---------|------------|
| `id` | BIGINT UNSIGNED (PK) | NO | AUTO_INCREMENT | |
| `kelas` | VARCHAR(20) | NO | – | Match `kelas.nama` |
| `mata_pelajaran` | VARCHAR(100) | NO | – | Match `mata_pelajaran.nama` |
| `created_at` | TIMESTAMP | YES | NULL | |
| `updated_at` | TIMESTAMP | YES | NULL | |
| **Index** | UNIQUE(kelas, mata_pelajaran), INDEX(kelas), INDEX(mata_pelajaran) | | | |

### 8.7 Tabel `guru_mengajar`
| Field | Tipe | Null | Default | Keterangan |
|-------|------|------|---------|------------|
| `id` | BIGINT UNSIGNED (PK) | NO | AUTO_INCREMENT | |
| `id_guru` | BIGINT UNSIGNED (FK→guru.id) | NO | – | ON DELETE CASCADE |
| `kelas` | VARCHAR(20) | NO | – | Match `kelas.nama` |
| `mata_pelajaran` | VARCHAR(100) | NO | – | Match `mata_pelajaran.nama` |
| `created_at` | TIMESTAMP | YES | NULL | |
| `updated_at` | TIMESTAMP | YES | NULL | |
| **Index** | UNIQUE(id_guru, kelas, mata_pelajaran), INDEX(kelas, mata_pelajaran) | | | |

### 8.8 Tabel `nilai`
| Field | Tipe | Null | Default | Keterangan |
|-------|------|------|---------|------------|
| `id` | BIGINT UNSIGNED (PK) | NO | AUTO_INCREMENT | |
| `nis` | VARCHAR(20) (FK→siswa.nis) | NO | – | ON DELETE CASCADE |
| `id_guru` | BIGINT UNSIGNED (FK→guru.id) | NO | – | ON DELETE RESTRICT |
| `kelas` | VARCHAR(20) | NO | – | Match `kelas.nama` |
| `mata_pelajaran` | VARCHAR(100) | NO | – | Match `mata_pelajaran.nama` |
| `nilai_tugas` | DECIMAL(5,2) | YES | NULL | 0–100 |
| `nilai_uts` | DECIMAL(5,2) | YES | NULL | 0–100 |
| `nilai_uas` | DECIMAL(5,2) | YES | NULL | 0–100 |
| `nilai_akhir` | DECIMAL(5,2) | YES | NULL | Hasil hitung |
| `status_lulus` | ENUM('Lulus','Tidak Lulus') | YES | NULL | Turunan dari KKM |
| `status_validasi` | ENUM('Draft','Final') | NO | 'Draft' | Workflow |
| `created_at` | TIMESTAMP | YES | NULL | |
| `updated_at` | TIMESTAMP | YES | NULL | |
| **Index** | UNIQUE(nis, id_guru, kelas, mata_pelajaran), INDEX(id_guru, mata_pelajaran), INDEX(kelas, mata_pelajaran), INDEX(nis FK) | | | |

### 8.9 Tabel `nilai_unlock_log` (Audit, append-only)
| Field | Tipe | Null | Default | Keterangan |
|-------|------|------|---------|------------|
| `id` | BIGINT UNSIGNED (PK) | NO | AUTO_INCREMENT | |
| `id_admin` | BIGINT UNSIGNED (FK→users.id) | NO | – | ON DELETE RESTRICT |
| `id_guru` | BIGINT UNSIGNED (FK→guru.id) | NO | – | ON DELETE RESTRICT |
| `kelas` | VARCHAR(20) | NO | – | |
| `mata_pelajaran` | VARCHAR(100) | NO | – | |
| `affected_rows` | INT UNSIGNED | NO | 0 | Baris yg di-revert |
| `reason` | TEXT | NO | – | Alasan unlock (min 10 char) |
| `created_at` | TIMESTAMP | NO | CURRENT_TIMESTAMP | **No updated_at** (append-only) |
| **Index** | INDEX(id_guru, kelas, mata_pelajaran), INDEX(id_admin), INDEX(created_at) | | | |

### 8.10 Tabel `notifications`
| Field | Tipe | Null | Default | Keterangan |
|-------|------|------|---------|------------|
| `id` | BIGINT UNSIGNED (PK) | NO | AUTO_INCREMENT | |
| `user_id` | BIGINT UNSIGNED (FK→users.id) | NO | – | ON DELETE CASCADE |
| `type` | VARCHAR(60) | NO | – | Tipe notif (enum di model) |
| `title` | VARCHAR(255) | NO | – | Judul |
| `body` | TEXT | NO | – | Isi pesan |
| `link` | VARCHAR(255) | YES | NULL | Tujuan redirect |
| `read_at` | TIMESTAMP | YES | NULL | Soft-flag "sudah dibaca" |
| `created_at` | TIMESTAMP | NO | CURRENT_TIMESTAMP | **No updated_at** |
| **Index** | INDEX(user_id, read_at, created_at), INDEX(user_id, created_at) | | | |

### 8.11 Tabel Pendukung Laravel (otomatis)
- `sessions` – driver session DB.
- `cache`, `cache_locks` – driver cache DB.
- `jobs`, `job_batches`, `failed_jobs` – driver queue DB.

### 8.12 Konstanta Domain
| Konstanta | Nilai | Sumber |
|-----------|-------|--------|
| `KKM` | 70.0 | `Nilai::KKM` |
| `BOBOT_TUGAS` | 0.30 | `Nilai::BOBOT_TUGAS` |
| `BOBOT_UTS` | 0.30 | `Nilai::BOBOT_UTS` |
| `BOBOT_UAS` | 0.40 | `Nilai::BOBOT_UAS` |
| `STATUS_DRAFT` | 'Draft' | `Nilai::STATUS_DRAFT` |
| `STATUS_FINAL` | 'Final' | `Nilai::STATUS_FINAL` |
| `LULUS` | 'Lulus' | `Nilai::LULUS` |
| `TIDAK_LULUS` | 'Tidak Lulus' | `Nilai::TIDAK_LULUS` |
| `BCRYPT_ROUNDS` | 12 | `.env` |
| Honorifics username guru | ['ibu','pak','bu','bpk','bapak','ibu.'] | `GuruController::generateUniqueUsername` |

---

## 9. FUNGSI/PROSEDUR (PEMROGRAMAN TERSTRUKTUR)

Bagian ini memetakan prosedur/fungsi prosedural yang muncul di controller & helper, sebagai padanan *structured programming*. Setiap signature, parameter, dan efeknya didokumentasikan secara ringkas.

### 9.1 Prosedur Autentikasi & Akun

| ID | Nama Fungsi/Prosedur | Lokasi | Signature | Deskripsi |
|----|----------------------|--------|-----------|-----------|
| F-A01 | `dashboardRoute()` | `User::dashboardRoute()` | `(): string` | Mengembalikan nama route dashboard sesuai role user. |
| F-A02 | `hasRole(...$roles)` | `User::hasRole()` | `(string ...$roles): bool` | Cek apakah user punya salah satu role. |
| F-A03 | `isAdmin/isGuru/isSiswa()` | `User` | `(): bool` | Cek role spesifik. |
| F-A04 | `ensureUserHasRole` (middleware) | `EnsureUserHasRole::handle()` | `(Request, Closure, string ...$roles): Response` | Middleware: cek auth + is_active + role → abort 401/403 bila gagal. |
| F-A05 | `accounts.index` | `Admin/AccountController::index` | `(Request): Response` | Tampilkan list akun + filter search/role. |
| F-A06 | `accounts.create-admin` | `Admin/AccountController::showCreateAdmin` | `(): Response` | Render form buat admin. |
| F-A07 | `accounts.create-admin.store` | `Admin/AccountController::createAdmin` | `(Request): RedirectResponse` | Validasi + simpan user admin baru (username, name, password). |
| F-A08 | `accounts.toggleActive` | `Admin/AccountController::toggleActive` | `(User, NotificationDispatcher): RedirectResponse` | Toggle `is_active`; tolak jika user = admin yang login. |
| F-A09 | `accounts.resetPassword` | `Admin/AccountController::resetPassword` | `(Request, User, NotificationDispatcher): RedirectResponse` | Hash password baru + simpan. |

### 9.2 Prosedur Manajemen Siswa (Admin)

| ID | Nama Fungsi/Prosedur | Lokasi | Signature | Deskripsi |
|----|----------------------|--------|-----------|-----------|
| F-S01 | `admin.siswa.index` | `Admin/SiswaController::index` | `(Request): Response` | List siswa + filter (search NIS/nama, filter kelas) + eager-load `user`. |
| F-S02 | `admin.siswa.create` | `Admin/SiswaController::create` | `(): Response` | Render form tambah siswa. |
| F-S03 | `admin.siswa.store` | `Admin/SiswaController::store` | `(SiswaRequest): RedirectResponse` | DB::transaction: User::create (username=NIS) + Siswa::create. |
| F-S04 | `admin.siswa.edit` | `Admin/SiswaController::edit` | `(Siswa): Response` | Render form edit (route binding via `nis`). |
| F-S05 | `admin.siswa.update` | `Admin/SiswaController::update` | `(SiswaRequest, Siswa): RedirectResponse` | Update siswa; jika `password` tidak kosong, reset password user. |
| F-S06 | `admin.siswa.destroy` | `Admin/SiswaController::destroy` | `(Siswa): RedirectResponse` | DB::transaction: hapus user (jika ada) + siswa (cascade nilai). |

### 9.3 Prosedur Manajemen Guru (Admin)

| ID | Nama Fungsi/Prosedur | Lokasi | Signature | Deskripsi |
|----|----------------------|--------|-----------|-----------|
| F-G01 | `admin.guru.index` | `Admin/GuruController::index` | `(Request): Response` | List guru + filter (search, kelas, mapel) + eager-load `user` & `mengajar` + `withCount('nilai')`. |
| F-G02 | `admin.guru.create` | `Admin/GuruController::create` | `(): Response` | Render form tambah guru + mapel_by_kelas. |
| F-G03 | `admin.guru.store` | `Admin/GuruController::store` | `(GuruRequest): RedirectResponse` | DB::transaction: User (auto-username) + Guru + sync mengajar. |
| F-G04 | `admin.guru.edit` | `Admin/GuruController::edit` | `(Guru): Response` | Render form edit + load mengajar. |
| F-G05 | `admin.guru.update` | `Admin/GuruController::update` | `(GuruRequest, Guru): RedirectResponse` | DB::transaction: update nama + sync mengajar. |
| F-G06 | `admin.guru.destroy` | `Admin/GuruController::destroy` | `(Guru): RedirectResponse` | Tolak jika ada nilai; else cascade hapus user + mengajar + guru. |
| F-G07 | `syncMengajar` | `Admin/GuruController::syncMengajar` | `(Guru, array): void` | Replace mengajar rows (dedup). |
| F-G08 | `generateUniqueUsername` | `Admin/GuruController::generateUniqueUsername` | `(string): string` | Hasilkan username unik: lowercase, drop honorifics, suffix numerik bila bentrok. |
| F-G09 | `buildMapelByKelas` | `Admin/GuruController::buildMapelByKelas` | `(): array` | Bentuk `[kelas => [mapel...]]` dari pivot `kelas_mata_pelajaran`. |

### 9.4 Prosedur Manajemen Kelas & Mata Pelajaran (Admin)

| ID | Nama Fungsi/Prosedur | Lokasi | Signature | Deskripsi |
|----|----------------------|--------|-----------|-----------|
| F-K01 | `admin.kelas.index` | `Admin/KelasController::index` | `(Request): Response` | List kelas + search + withCount (siswa, guru_mengajar, mapel). |
| F-K02 | `admin.kelas.create` | `Admin/KelasController::create` | `(): Response` | Render form buat kelas + semua_mapel. |
| F-K03 | `admin.kelas.store` | `Admin/KelasController::store` | `(KelasRequest): RedirectResponse` | Buat kelas + sync mapel yang diizinkan. |
| F-K04 | `admin.kelas.edit` | `Admin/KelasController::edit` | `(Kelas $kela): Response` | Render form edit + selected_mapel. |
| F-K05 | `admin.kelas.update` | `Admin/KelasController::update` | `(KelasRequest, Kelas $kela): RedirectResponse` | Update nama + sync mapel. |
| F-K06 | `admin.kelas.destroy` | `Admin/KelasController::destroy` | `(Kelas $kela): RedirectResponse` | Tolak bila dipakai siswa/guru; else detach mapel + hapus. |
| F-K07 | `syncAvailableMapel` | `Admin/KelasController::syncAvailableMapel` | `(Kelas, array): void` | Detach lalu attach mapel yang dipilih (firstOrCreate). |
| F-MP01 | `admin.mata-pelajaran` (resource) | `Admin/MataPelajaranController` | CRUD standar | List, create, store, edit, update, destroy. |
| F-MP02 | `pluckNamaOrdered` | `Kelas` & `MataPelajaran` | `(): Collection` | Ambil semua `nama` terurut ascending (untuk dropdown). |
| F-MP03 | `scopeSearch` | `Kelas`, `MataPelajaran` | `(Builder, string): Builder` | Filter LIKE pada `nama`; abaikan jika string kosong. |

### 9.5 Prosedur Input Nilai (Guru)

| ID | Nama Fungsi/Prosedur | Lokasi | Signature | Deskripsi |
|----|----------------------|--------|-----------|-----------|
| F-N01 | `guru.nilai.index` | `Guru/NilaiController::index` | `(Request): Response` | Tampilkan form nilai per (kelas, mapel), pre-populate dari existing. |
| F-N02 | `guru.nilai.save` | `Guru/NilaiController::save` | `(Request): RedirectResponse` | Validasi 0-100, hitung akhir & kelulusan, updateOrCreate Draft per siswa. |
| F-N03 | `guru.nilai.validate-final` | `Guru/NilaiController::validateFinal` | `(Request, NotificationDispatcher): RedirectResponse` | Set Final + kirim notif ke siswa via dispatcher. |
| F-N04 | `guru.nilai.destroy` | `Guru/NilaiController::destroy` | `(Nilai): RedirectResponse` | Hapus nilai (tolak bila Final). |
| F-N05 | `guru.rekap.index` | `Guru/NilaiController::rekap` | `(Request): Response` | Tampilkan rekap Lulus/Tidak Lulus/Belum. |
| F-N06 | `hitungNilaiAkhir` | `Nilai::hitungNilaiAkhir` | `(float $t, float $u, float $v): float` | Hitung (0.30·t + 0.30·u + 0.40·v) round 2 desimal. |
| F-N07 | `tentukanKelulusan` | `Nilai::tentukanKelulusan` | `(float $akhir): string` | Kembalikan "Lulus" / "Tidak Lulus" berdasarkan KKM 70. |
| F-N08 | `validasiNilai` | `Nilai::validasiNilai` | `(float): bool` | Validasi 0 ≤ n ≤ 100. |
| F-N09 | `mengajarDiKelasMapel` | `Guru::mengajarDiKelasMapel` | `(string $kelas, string $mapel): bool` | Otorisasi guru mengajar kombinasi tsb. |
| F-N10 | `getMapelByKelas` | `Guru::getMapelByKelas` | `(string $kelas): array` | Daftar mapel yang diajar guru tsb di kelas tsb. |

### 9.6 Prosedur Buka Kunci Nilai (Admin)

| ID | Nama Fungsi/Prosedur | Lokasi | Signature | Deskripsi |
|----|----------------------|--------|-----------|-----------|
| F-U01 | `admin.nilai.index` | `Admin/NilaiController::index` | `(Request): Response` | List Final combo + 10 log terakhir + filter search/kelas. |
| F-U02 | `admin.nilai.unlock` | `Admin/NilaiController::unlock` | `(Request): RedirectResponse` | DB::transaction: UPDATE ke Draft + INSERT audit log. |

### 9.7 Prosedur Laporan (Admin)

| ID | Nama Fungsi/Prosedur | Lokasi | Signature | Deskripsi |
|----|----------------------|--------|-----------|-----------|
| F-L01 | `admin.reports.index` | `Admin/ReportController::index` | `(Request): Response` | Tampilkan form filter. |
| F-L02 | `admin.reports.preview` | `Admin/ReportController::preview` | `(Request): Response` | Render preview HTML. |
| F-L03 | `admin.reports.export.pdf` | `Admin/ReportController::exportPdf` | `(Request): Response` | Generate PDF via DomPDF, A4 landscape. |
| F-L04 | `admin.reports.export.html` | `Admin/ReportController::exportHtml` | `(Request): Response` | Render view + download attachment. |
| F-L05 | `admin.reports.export.csv` | `Admin/ReportController::exportCsv` | `(Request): StreamedResponse` | Stream CSV dengan UTF-8 BOM. |
| F-L06 | `admin.reports.export.xlsx` | `Admin/ReportController::exportXlsx` | `(Request): Response` | Generate XLSX via `XlsxWriter` (openspout). |
| F-L07 | `validateFilter` | `Admin/ReportController::validateFilter` | `(Request): array` | Validasi & normalisasi multi-kelas & multi-mapel. |
| F-L08 | `buildReportData` | `Admin/ReportController::buildReportData` | `(array): array` | Agregasi siswa × nilai × stats per kelas & global. |
| F-L09 | `flattenRowsForExport` | `Admin/ReportController::flattenRowsForExport` | `(array): array` | Flatten ke 2-D array (header + rows). |
| F-L10 | `filenameFor` | `Admin/ReportController::filenameFor` | `(array, string): string` | Generate nama file `laporan_<kelas>_<YYYY>`. |

### 9.8 Prosedur Dashboard & Statistik

| ID | Nama Fungsi/Prosedur | Lokasi | Signature | Deskripsi |
|----|----------------------|--------|-----------|-----------|
| F-D01 | `admin.dashboard` | `Admin/DashboardController::index` | `(Request): Response` | Tampilkan stats agregat + rekap per kelas + per mapel + top siswa + siswa perhatian. |
| F-D02 | `buildRekapPerKelas` | `Admin/DashboardController::buildRekapPerKelas` | `(): array` | Hitung jumlah siswa, lulus, tidak_lulus, persentase per kelas. |
| F-D03 | `buildRataRataPerMapel` | `Admin/DashboardController::buildRataRataPerMapel` | `(): array` | Hitung rata-rata nilai & pass-rate per mapel. |
| F-D04 | `buildTopSiswa` | `Admin/DashboardController::buildTopSiswa` | `(int $limit=5): array` | Leaderboard top-N siswa by AVG nilai_akhir. |
| F-D05 | `buildSiswaPerhatian` | `Admin/DashboardController::buildSiswaPerhatian` | `(int $limit=5): array` | Siswa by abs(tidak_lulus) desc, AVG asc. |
| F-D06 | `siswa.nilai.index` | `Siswa/NilaiController::index` | `(Request): Response` | Tampilkan nilai siswa per mapel (Final only). |
| F-D07 | `siswa.statistik.index` | `Siswa/NilaiController::statistik` | `(Request): Response` | Tampilkan statistik visual siswa. |
| F-D08 | `siswa.dashboard` | `Siswa/DashboardController::index` | `(Request): Response` | Dashboard ringkasan nilai siswa. |
| F-D09 | `guru.dashboard` | `Guru/DashboardController::index` | `(Request): Response` | Dashboard info mengajar guru. |

### 9.9 Prosedur Rapor (Siswa)

| ID | Nama Fungsi/Prosedur | Lokasi | Signature | Deskripsi |
|----|----------------------|--------|-----------|-----------|
| F-R01 | `siswa.rapor.pdf` | `Siswa/RaporController::pdf` | `(NotificationDispatcher): Response` | Generate rapor PDF (Final only) + kirim notif. |
| F-R02 | `guessTahunAjaran` | `Siswa/RaporController::guessTahunAjaran` | `(): string` | Tentukan "YYYY/YYYY" berdasarkan bulan (>=7 = Y/Y+1). |

### 9.10 Prosedur Notifikasi

| ID | Nama Fungsi/Prosedur | Lokasi | Signature | Deskripsi |
|----|----------------------|--------|-----------|-----------|
| F-NT01 | `notifications.index` | `NotificationController::index` | `(Request): JsonResponse` | List 20 notif terbaru user (paginated). |
| F-NT02 | `notifications.unread-count` | `NotificationController::unreadCount` | `(): JsonResponse` | Hitung unread untuk polling 60 dtk. |
| F-NT03 | `notifications.mark-read` | `NotificationController::markRead` | `(Notification): JsonResponse` | Tandai 1 notif sudah dibaca. |
| F-NT04 | `notifications.mark-all-read` | `NotificationController::markAllRead` | `(): JsonResponse` | Tandai semua unread jadi read. |
| F-NT05 | `NotificationDispatcher::send` | `App\Notifications\NotificationDispatcher` | `(User, string $type, string $title, string $body, ?string $link): void` | Kirim 1 notif (dedup). |
| F-NT06 | `NotificationDispatcher::sendMany` | `App\Notifications\NotificationDispatcher` | `(Collection<User>, string, string, string, ?string): void` | Kirim ke banyak user. |
| F-NT07 | `GradeObserver::updated` | `App\Observers\GradeObserver` | `(Nilai): void` | Deteksi transisi Draft→Final → notif siswa. |
| F-NT08 | `GradeObserver::saved` | `App\Observers\GradeObserver` | `(Nilai): void` | Setelah simpan: jika masih ada Draft → notif guru. |
| F-NT09 | `isRead` | `Notification::isRead` | `(): bool` | True bila `read_at` tidak null. |

### 9.11 Prosedur Utilitas

| ID | Nama Fungsi/Prosedur | Lokasi | Signature | Deskripsi |
|----|----------------------|--------|-----------|-----------|
| F-U11 | `XlsxWriter::toString` | `App\Support\XlsxWriter` | `(): string` | Render XLSX binary dari header+rows. |
| F-U12 | `XlsxWriter::setTitle` | `App\Support\XlsxWriter` | `(string): self` | Set metadata judul workbook. |
| F-U13 | `XlsxWriter::addRows` | `App\Support\XlsxWriter` | `(array): self` | Tambah baris ke workbook. |
| F-U14 | `HandleInertiaRequests` | middleware | – | Inject shared props ke Inertia (auth, flash, dll.). |
| F-U15 | `HandleAppearance` | middleware | – | Sinkronisasi preferensi tampilan (light/dark) via cookie. |
| F-U16 | `notifications:cleanup` (artisan) | `app/Console/Commands` | `(): void` | Hapus notif read lebih dari 30 hari. |

---

## 10. CLASS BESERTA METHOD (OOP)

Berikut adalah kelas-kelas utama (Model, Controller, Support, Observer, Middleware, Form Request) yang merepresentasikan implementasi OOP. Disajikan ringkas: **atribut/properti penting** + **method utama**.

### 10.1 Model Eloquent (Eloquent\Model)

#### 10.1.1 `App\Models\User` (extends `Authenticatable`)
- **Properties:** `$table='users'`, `$fillable=['username','name','role','is_active','password']`, `$hidden=['password','remember_token']`, role const `ROLE_ADMIN/GURU/SISWA`.
- **Methods:**
  - `casts(): array` → `password` hashed, `is_active` boolean.
  - `siswa(): HasOne<Siswa>`
  - `guru(): HasOne<Guru>`
  - `notifications(): HasMany<Notification>`
  - `hasRole(string ...$roles): bool`
  - `isAdmin(): bool`, `isGuru(): bool`, `isSiswa(): bool`
  - `dashboardRoute(): string`

#### 10.1.2 `App\Models\Siswa`
- **Properties:** PK `nis` (string, non-incrementing), `$fillable=['nis','user_id','nama_siswa','kelas']`.
- **Methods:**
  - `getRouteKeyName(): string` → `'nis'`
  - `user(): BelongsTo<User>`
  - `nilai(): HasMany<Nilai>`

#### 10.1.3 `App\Models\Guru`
- **Properties:** PK `id` (int), `$fillable=['user_id','nama_guru']`.
- **Methods:**
  - `user(): BelongsTo<User>`
  - `mengajar(): HasMany<GuruMengajar>`
  - `nilai(): HasMany<Nilai>`
  - `getAllKelasAttribute(): array` (accessor)
  - `getAllMapelAttribute(): array` (accessor)
  - `getMapelByKelas(string): array`
  - `mengajarDiKelasMapel(string $kelas, string $mapel): bool`

#### 10.1.4 `App\Models\Nilai`
- **Properties:** const `STATUS_DRAFT='Draft'`, `STATUS_FINAL='Final'`, `LULUS='Lulus'`, `TIDAK_LULUS='Tidak Lulus'`, `BOBOT_TUGAS=0.30`, `BOBOT_UTS=0.30`, `BOBOT_UAS=0.40`, `KKM=70.0`.
- **Methods (static helpers):**
  - `hitungNilaiAkhir(float $t, float $u, float $v): float`
  - `tentukanKelulusan(float $akhir): string`
  - `validasiNilai(float $n): bool`
- **Methods (relasi):**
  - `siswa(): BelongsTo<Siswa>`
  - `guru(): BelongsTo<Guru>`

#### 10.1.5 `App\Models\Kelas`
- **Properties:** `$fillable=['nama']`, `$table='kelas'`.
- **Methods:**
  - `siswa(): HasMany<Siswa>`
  - `guruMengajar(): HasMany<GuruMengajar>`
  - `nilai(): HasMany<Nilai>`
  - `mataPelajaran(): BelongsToMany<MataPelajaran>` (via pivot `kelas_mata_pelajaran`)
  - `getJumlahSiswaAttribute(): int` (accessor)
  - `getJumlahGuruMengajarAttribute(): int`
  - `getJumlahMapelAttribute(): int`
  - `pluckNamaOrdered(): Collection` (static)
  - `scopeSearch(Builder, string $term): Builder`

#### 10.1.6 `App\Models\MataPelajaran`
- **Properties:** `$fillable=['nama']`, `$table='mata_pelajaran'`.
- **Methods:**
  - `guruMengajar(): HasMany<GuruMengajar>`
  - `nilai(): HasMany<Nilai>`
  - `kelas(): BelongsToMany<Kelas>` (via pivot)
  - `getJumlahGuruMengajarAttribute(): int`
  - `getJumlahNilaiAttribute(): int`
  - `getJumlahKelasAttribute(): int`
  - `pluckNamaOrdered(): Collection` (static)
  - `scopeSearch(Builder, string $term): Builder`

#### 10.1.7 `App\Models\GuruMengajar`
- **Properties:** `$fillable=['id_guru','kelas','mata_pelajaran']`, `$table='guru_mengajar'`.
- **Methods:**
  - `guru(): BelongsTo<Guru>`

#### 10.1.8 `App\Models\KelasMataPelajaran`
- **Properties:** `$fillable=['kelas','mata_pelajaran']`, `$table='kelas_mata_pelajaran'`.
- (Model tipis untuk pivot.)

#### 10.1.9 `App\Models\NilaiUnlockLog` (append-only)
- **Properties:** `UPDATED_AT = null`, `$fillable=[id_admin,id_guru,kelas,mata_pelajaran,affected_rows,reason]`.
- **Methods:**
  - `admin(): BelongsTo<User>`
  - `guru(): BelongsTo<Guru>`

#### 10.1.10 `App\Models\Notification`
- **Properties:** const `TYPE_NILAI_BELUM_DIINPUT`, `TYPE_NILAI_MASIH_DRAFT`, `TYPE_NILAI_SUDAH_FINAL`, `TYPE_RAPOR_TERSEDIA`, `TYPE_AKUN_DIUBAH`, `TYPE_INFO`. `UPDATED_AT = null`.
- **Methods:**
  - `user(): BelongsTo<User>`
  - `isRead(): bool`

### 10.2 Controller Classes

#### 10.2.1 `App\Http\Controllers\Controller` (base)
- Base class Laravel dengan helper standar.

#### 10.2.2 `App\Http\Controllers\Admin\DashboardController`
- `index(Request): Response` — render dashboard + panggil helper private.
- Private: `buildRekapPerKelas()`, `buildRataRataPerMapel()`, `buildTopSiswa(int $limit=5)`, `buildSiswaPerhatian(int $limit=5)`.

#### 10.2.3 `App\Http\Controllers\Admin\SiswaController`
- `index(Request): Response`
- `create(): Response`
- `store(SiswaRequest): RedirectResponse`
- `edit(Siswa): Response`
- `update(SiswaRequest, Siswa): RedirectResponse`
- `destroy(Siswa): RedirectResponse`

#### 10.2.4 `App\Http\Controllers\Admin\GuruController`
- `index(Request): Response`
- `create(): Response`
- `store(GuruRequest): RedirectResponse`
- `edit(Guru): Response`
- `update(GuruRequest, Guru): RedirectResponse`
- `destroy(Guru): RedirectResponse`
- Private: `syncMengajar(Guru, array): void`, `generateUniqueUsername(string): string`, `buildMapelByKelas(): array`.

#### 10.2.5 `App\Http\Controllers\Admin\KelasController`
- `index(Request): Response`
- `create(): Response`
- `store(KelasRequest): RedirectResponse`
- `edit(Kelas $kela): Response`
- `update(KelasRequest, Kelas $kela): RedirectResponse`
- `destroy(Kelas $kela): RedirectResponse`
- Private: `syncAvailableMapel(Kelas, array): void`, `buildSyncMessage(string, string, array, ?array): string`.

#### 10.2.6 `App\Http\Controllers\Admin\MataPelajaranController`
- Resource controller (CRUD `index/create/store/edit/update/destroy`).

#### 10.2.7 `App\Http\Controllers\Admin\AccountController`
- `index(Request): Response`
- `showCreateAdmin(): Response`
- `createAdmin(Request): RedirectResponse`
- `toggleActive(User, NotificationDispatcher): RedirectResponse`
- `resetPassword(Request, User, NotificationDispatcher): RedirectResponse`

#### 10.2.8 `App\Http\Controllers\Admin\NilaiController`
- `index(Request): Response` — list Final combo + 10 log.
- `unlock(Request): RedirectResponse` — DB::transaction reopen + audit log.

#### 10.2.9 `App\Http\Controllers\Admin\ReportController`
- `index(Request): InertiaResponse`
- `preview(Request): InertiaResponse`
- `exportPdf(Request): Response`
- `exportHtml(Request): Response`
- `exportCsv(Request): StreamedResponse`
- `exportXlsx(Request): Response`
- Private: `validateFilter(Request): array`, `filenameFor(array, string): string`, `buildReportData(array): array`, `flattenRowsForExport(array): array`.

#### 10.2.10 `App\Http\Controllers\Guru\DashboardController`
- `index(Request): Response` — ringkasan mengajar guru.

#### 10.2.11 `App\Http\Controllers\Guru\NilaiController`
- `index(Request): Response`
- `save(Request): RedirectResponse`
- `validateFinal(Request, NotificationDispatcher): RedirectResponse`
- `destroy(Nilai): RedirectResponse`
- `rekap(Request): Response`

#### 10.2.12 `App\Http\Controllers\Siswa\DashboardController`
- `index(Request): Response` — ringkasan nilai siswa.

#### 10.2.13 `App\Http\Controllers\Siswa\NilaiController`
- `index(Request): Response` — daftar nilai Final.
- `statistik(Request): Response` — visualisasi.

#### 10.2.14 `App\Http\Controllers\Siswa\RaporController`
- `pdf(NotificationDispatcher): Response`
- Private: `guessTahunAjaran(): string`.

#### 10.2.15 `App\Http\Controllers\NotificationController`
- `index(Request): JsonResponse`
- `unreadCount(): JsonResponse`
- `markRead(Notification): JsonResponse`
- `markAllRead(): JsonResponse`
- Private: `respondWithPaginator(LengthAwarePaginator): JsonResponse`, `authorizeOwnership(Notification): void`.

### 10.3 Form Request Classes

#### 10.3.1 `App\Http\Requests\Admin\SiswaRequest`
- `authorize(): bool`, `rules(): array`
- `getMengajar()` / `getMataPelajaran()` (custom helper, bila ada).

#### 10.3.2 `App\Http\Requests\Admin\GuruRequest`
- `authorize(): bool`, `rules(): array`
- `getMengajar(): array` (mem-parsing array mengajar dari form).

#### 10.3.3 `App\Http\Requests\Admin\KelasRequest`
- `authorize(): bool`, `rules(): array`
- `getMataPelajaran(): array` (mem-parsing array mata pelajaran dari form).

### 10.4 Middleware Classes

#### 10.4.1 `App\Http\Middleware\EnsureUserHasRole`
- `handle(Request, Closure, string ...$roles): Response`
- Logika: cek auth → cek `is_active` (logout + 403) → cek role (403).

#### 10.4.2 `App\Http\Middleware\HandleInertiaRequests`
- `share(Request): array` — inject shared props (auth.user, flash, dll.) ke Inertia.

#### 10.4.3 `App\Http\Middleware\HandleAppearance`
- `handle(Request, Closure): Response` — sinkronisasi cookie appearance (light/dark).

### 10.5 Observer Classes

#### 10.5.1 `App\Observers\GradeObserver`
- Constructor: inject `NotificationDispatcher`.
- `updated(Nilai): void` — deteksi Draft→Final, notif siswa.
- `saved(Nilai): void` — notif guru bila masih ada Draft.
- Private: `isDraftToFinalTransition(Nilai): bool`, `notifyStudentsFinal(Nilai): void`, `notifyGuruDraft(Nilai): void`, `comboKey(Nilai): string`.

### 10.6 Support Class

#### 10.6.1 `App\Support\XlsxWriter`
- Fluent API untuk generate XLSX tanpa library besar.
- `setTitle(string): self`
- `addRows(array<int, array>): self`
- `toString(): string` — kembalikan binary string workbook.

### 10.7 Notifications

#### 10.7.1 `App\Notifications\NotificationDispatcher`
- `send(User, string $type, string $title, string $body, ?string $link): void`
- `sendMany(Collection<User>, string, string, string, ?string): void`
- Logika dedup: `(user_id, type, link)` unique untuk mencegah spam.

### 10.8 Fortify Actions (`App\Actions\Fortify\*`)
- Standar Fortify: `CreateNewUser`, `UpdateUserProfileInformation`, `ResetUserPassword`, `AttemptToAuthenticate`, dll.

### 10.9 Console Command (artisan)
- `App\Console\Commands\NotificationsCleanup` (atau setara):
  - `handle(): int` — hapus notifikasi dengan `read_at IS NOT NULL` dan `created_at < now()-30 days`.

### 10.10 Ringkasan Class Diagram Teks
```
User ──hasOne──> Siswa ──hasMany──> Nilai ──belongsTo──> Guru
  │                                  ▲                     │
  │                                  │                     │ hasMany
  ├──hasOne──> Guru ──hasMany──> GuruMengajar              ▼
  │                                                       Nilai
  ├──hasMany──> Notification
  └──dashboardRoute() → match role → string

Kelas ──hasMany──> Siswa (kelas.nama == siswa.kelas)
   ├──hasMany──> GuruMengajar
   ├──hasMany──> Nilai
   └──belongsToMany──> MataPelajaran (via kelas_mata_pelajaran)

MataPelajaran ──belongsToMany──> Kelas (sama)
NilaiUnlockLog ──belongsTo──> User (admin), Guru
```

---

## 11. BATASAN SISTEM

### 11.1 Batasan Fungsional
1. **Tidak ada modul akademik lain** – Sistem hanya mengelola **nilai akademik** (Tugas, UTS, UAS). Modul-modul lain seperti **jadwal pelajaran, absensi, rapor semester multi-periode, kurikulum, dan KRS** berada di luar cakupan.
2. **Bobot nilai tetap** – Bobot Tugas/UTS/UAS dikode *hard-coded* pada `Nilai::BOBOT_*` (30/30/40) dan tidak dapat diubah per-mapel atau per-kelas melalui UI.
3. **KKM tunggal** – Hanya satu nilai KKM (70.0) yang berlaku untuk **seluruh** mata pelajaran; tidak ada konfigurasi KKM per-mapel.
4. **Tahun ajaran sederhana** – Tahun ajaran ditebak otomatis dari bulan (`>= 7` → semester baru) tanpa tabel konfigurasi.
5. **Tidak ada e-learning / materi** – Sistem tidak menyediakan upload materi, tugas online, atau kuis interaktif.
6. **Tidak ada multi-bahasa** – Antarmuka hanya dalam **Bahasa Indonesia** (tanggal & pesan), tidak ada i18n frontend.
7. **Rapor per siswa sekali generate** – Tidak ada konsep "periode rapor" (UTS/UAS/PTS/PAS) — semua nilai Final pada tahun ajaran aktif digabung ke satu rapor.

### 11.2 Batasan Teknis
1. **PHP 8.3+** – Memerlukan PHP minimal 8.3 (Laravel 13).
2. **MySQL/MariaDB** – Driver DB default; migrasi tidak portabel ke SQLite tanpa penyesuaian (penggunaan `ENUM` dan `INDEX`).
3. **Session/Cache/Queue = Database** – Tidak ada Redis; untuk produksi skala besar perlu migrasi ke Redis.
4. **Storage lokal** – `FILESYSTEM_DISK=local`; tidak ada S3 / cloud storage secara default.
5. **Export XLSX** – Menggunakan `openspout/openspout` (composer), sehingga membutuhkan ekstensi ZIP & XML PHP.
6. **PDF** – `barryvdh/laravel-dompdf` mendukung HTML+CSS sederhana; styling kompleks CSS Grid/Flex dapat terbatas.
7. **Inertia v3** – Membutuhkan Node 20+, Vite, dan frontend SPA yang sudah di-`npm run build` sebelum deploy.
8. **Notifikasi polling** – Polling 60 detik pada `GET /notifications/unread-count`; bukan WebSocket/SSE. Skala besar mungkin perlu optimasi.
9. **Audit log append-only** – `nilai_unlock_log` tidak punya `updated_at`; tidak ada mekanisme purge (bertahan selamanya).
10. **Tidak ada API publik** – Sistem adalah aplikasi web server-rendered SPA; tidak ada endpoint REST publik untuk integrasi pihak ketiga.

### 11.3 Batasan Keamanan
1. **Role-based** – Hanya tiga role: admin, guru, siswa. Tidak ada role hierarkis (mis. "kepala sekolah", "wali kelas").
2. **Tidak ada 2FA** – Fortify sudah mendukung 2FA, namun tidak diaktifkan di template ini secara eksplisit.
3. **Akun self-disable ditolak** – Admin tidak bisa menonaktifkan akunnya sendiri; tetapi bisa reset password sendiri.
4. **Akses notifikasi scoped** – `authorizeOwnership()` mengembalikan 404 (bukan 403) agar tidak bocor info row notifikasi user lain.
5. **Buka kunci nilai** – `reason` wajib diisi (10–500 char), tercatat di log yang tidak bisa di-edit.

### 11.4 Batasan Operasional
1. **Single-tenant** – Sistem dirancang untuk **satu sekolah** per instalasi. Tidak ada fitur multi-sekolah (tenant) / multi-cabang.
2. **Concurrent editing** – Tidak ada lock optimistic; dua guru mengajar mapel berbeda tidak akan konflik, namun bila dua admin mengedit data master bersamaan, hasil "last write wins".
3. **Paginasi** – 15 item/halaman untuk list akun/siswa/guru, 20 untuk kelas — tidak ada infinite scroll.
4. **Bahasa dokumentasi** – Kode, komentar PHPDoc, dan pesan flash menggunakan **Bahasa Indonesia** dan **Inggris** (campuran).
5. **Backup** – Tidak ada modul backup bawaan; backup DB sepenuhnya menjadi tanggung jawab operator.
6. **Testing** – Disiapkan Pest 4 dan PHPUnit 12, namun cakupan test yang ada bergantung pada isi direktori `tests/`.

### 11.5 Asumsi
- Sekolah memiliki admin/operator yang mampu mengelola data master.
- Guru memiliki akses ke komputer/laptop dengan browser modern.
- Siswa memiliki kredensial (username = NIS, password dari admin).
- Koneksi internet stabil untuk aplikasi berbasis web.

---

## LAMPIRAN

### A. Daftar Route Singkat
| Method | URI | Name | Aktor |
|--------|-----|------|-------|
| GET | `/` | home | semua |
| GET | `/login` | login | guest |
| GET | `/admin/dashboard` | admin.dashboard | admin |
| GET | `/admin/siswa` | admin.siswa.index | admin |
| GET/POST/PUT/DELETE | `/admin/siswa[/...]` | admin.siswa.* | admin |
| GET/POST/PUT/DELETE | `/admin/guru[/...]` | admin.guru.* | admin |
| GET/POST/PUT/DELETE | `/admin/kelas[/...]` | admin.kelas.* | admin |
| GET/POST/PUT/DELETE | `/admin/mata-pelajaran[/...]` | admin.mata-pelajaran.* | admin |
| GET | `/admin/akun` | admin.accounts.index | admin |
| POST | `/admin/akun/create-admin` | admin.accounts.create-admin.store | admin |
| PATCH | `/admin/akun/{user}/toggle-active` | admin.accounts.toggle-active | admin |
| POST | `/admin/akun/{user}/reset-password` | admin.accounts.reset-password | admin |
| GET | `/admin/laporan` | admin.reports.index | admin |
| GET | `/admin/laporan/preview` | admin.reports.preview | admin |
| GET/POST | `/admin/laporan/export/{pdf\|html\|csv\|xlsx}` | admin.reports.export.* | admin |
| GET | `/admin/nilai` | admin.nilai.index | admin |
| POST | `/admin/nilai/unlock` | admin.nilai.unlock | admin |
| GET | `/guru/dashboard` | guru.dashboard | guru |
| GET | `/guru/input-nilai` | guru.nilai.index | guru |
| POST | `/guru/input-nilai/save` | guru.nilai.save | guru |
| POST | `/guru/input-nilai/validate-final` | guru.nilai.validate-final | guru |
| DELETE | `/guru/input-nilai/{nilai}` | guru.nilai.destroy | guru |
| GET | `/guru/rekap` | guru.rekap.index | guru |
| GET | `/siswa/dashboard` | siswa.dashboard | siswa |
| GET | `/siswa/nilai` | siswa.nilai.index | siswa |
| GET | `/siswa/statistik` | siswa.statistik.index | siswa |
| GET | `/siswa/rapor/pdf` | siswa.rapor.pdf | siswa |
| GET | `/notifications` | notifications.index | semua |
| GET | `/notifications/unread-count` | notifications.unread-count | semua |
| POST | `/notifications/{notification}/read` | notifications.mark-read | semua |
| POST | `/notifications/read-all` | notifications.mark-all-read | semua |

### B. Library/Package Penting
- `barryvdh/laravel-dompdf ^3.1` — PDF (rapor, laporan).
- `openspout/openspout ^5.7` — XLSX writer (laporan).
- `inertiajs/inertia-laravel ^3.0` — SPA adapter.
- `laravel/wayfinder ^0.1.14` — TypeScript route generation.
- `laravel/fortify ^1.37.2` — Autentikasi.
- `laravel/framework ^13.7` — Core framework.
- `pestphp/pest ^4.7` — Testing.

### C. Konvensi Kode
- PSR-12, `declare(strict_types=1)` di setiap file.
- Type-hint eksplisit pada parameter & return.
- PHPDoc dengan `@property` & `@return` generic.
- Pint formatter (Laravel Pint).
- Bahasa Inggris untuk identifier kode; Bahasa Indonesia untuk pesan UI & flash message.

---

**Akhir Dokumen**
