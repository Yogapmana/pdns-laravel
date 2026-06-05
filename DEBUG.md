# DEBUG.md — Catatan Error & Perbaikan

> Aplikasi Pengolahan Nilai Siswa
> Format: Tabel terstruktur per insiden. Setiap entri: Waktu | Konteks | Error/Root Cause | Perbaikan | Hasil Verifikasi

---

## Status Implementasi

| Komponen | Status | Catatan |
|----------|--------|---------|
| Setup project (Inertia+React) | ✅ Selesai | Laravel 13 + Inertia v3 + React 19 |
| Database MySQL | ✅ Selesai | Docker `pdns-mysql` (MySQL 8.0.46) di port 3306 |
| Laravel Fortify | ✅ Selesai | Login pakai username; registration/reset disabled; home = `/redirect-by-role` |
| Migrations | ✅ Selesai | 5 migrasi (users+sessions, siswa, guru, nilai, guru_mengajar, restructure); `siswa.kelas` index di original migration |
| Models + Relasi | ✅ Selesai | User, Siswa (PK=nis), Guru, GuruMengajar, Nilai + static helper hitungNilaiAkhir/tentukanKelulusan/validasiNilai |
| Role Middleware | ✅ Selesai | `App\Http\Middleware\EnsureUserHasRole` alias `role`; cek `is_active` logout paksa |
| Seeder lengkap | ✅ Selesai | 1 admin, 4 guru (8 kombinasi mengajar), 29 siswa (X-A/B, XI-A/B), 58 nilai (29 siswa × rata-rata 2 mapel per kelas) |
| Halaman Login | ✅ Selesai | React+Inertia+Fortify, show/hide password |
| Dashboard Admin | ✅ Selesai | Stat cards + rekap per kelas; N+1 dioptimasi (8 query total) |
| Manajemen Siswa (CRUD) | ✅ Selesai | Real-time search, filter kelas, pagination, edit (NIS immutable) |
| Manajemen Guru (CRUD) | ✅ Selesai | Real-time search, filter kelas+mapel, pagination, toggle-active, delete (RESTRICT), sync mengajar dalam transaction |
| Manajemen Akun | ✅ Selesai | List akun, create, toggle-active (tidak bisa self-disable), reset password |
| Dashboard Guru + Input Nilai | ✅ Selesai | Real-time calc, 0-100 validation, status Draft/Final per-(kelas+mapel), 2 cascading dropdowns (kelas→mapel auto-filtered dari kombinasi mengajar guru), extract `<NilaiInput>` component |
| Rekap Nilai Guru | ✅ Selesai | Stats lulus/tidak/belum per kelas+mapel |
| Laporan (PDF/HTML) | ✅ Selesai | Per-kelas, rekap + summary, export via barryvdh/laravel-dompdf |
| Dashboard Siswa + Nilai Pribadi | ✅ Selesai | Read-only tampil nilai + status lulus + status validasi; group by kelas\|mapel; guru_map flat keyBy |
| Shared frontend hooks | ✅ Selesai | `useInertiaSearch` (real-time search + debounce), `useFlashToast` |
| Pest Test | ✅ Selesai | 51 test, 275 assertions — SEMUA PASS |
| Lint (Pint + ESLint + TSC) | ✅ Selesai | Pint passed, ESLint 0 errors, TSC 0 errors |

---

## Permissions Matrix (Role × Action)

> Route prefix: `admin.*` = `role:admin`, `guru.*` = `role:guru`, `siswa.*` = `role:siswa`
> Middleware: `auth` + `role:{role}` (alias di `bootstrap/app.php`)
> `EnsureUserHasRole` juga cek `is_active` — nonaktif → 403 + logout paksa

| Action / Endpoint | Method | Admin | Guru | Siswa | Catatan |
|---|---|---|---|---|---|
| `/` (root redirect) | GET | ✓ (→ `admin.dashboard`) | ✓ (→ `guru.dashboard`) | ✓ (→ `siswa.dashboard`) | Guest → `/login` |
| `/login` | GET/POST | ✓ | ✓ | ✓ | Fortify; throttled 5/menit per username+IP |
| `/logout` | POST | ✓ | ✓ | ✓ | Fortify |
| `/admin/dashboard` | GET | ✓ | ✗ (403) | ✗ (403) | Stats + rekap per kelas |
| `/admin/siswa` (index) | GET | ✓ (search by NIS/nama + filter kelas) | ✗ | ✗ | Pagination 15 |
| `/admin/siswa/create` | GET | ✓ | ✗ | ✗ | |
| `POST /admin/siswa` | POST | ✓ | ✗ | ✗ | kelas_baru opsional |
| `/admin/siswa/{nis}/edit` | GET | ✓ | ✗ | ✗ | Route model binding via `nis` |
| `PUT /admin/siswa/{nis}` | PUT | ✓ (NIS immutable) | ✗ | ✗ | validated() unsets nis |
| `DELETE /admin/siswa/{nis}` | DELETE | ✓ (cascade nilai) | ✗ | ✗ | FK CASCADE di migration |
| `/admin/guru` (index) | GET | ✓ (search/filter kelas/mapel) | ✗ | ✗ | Pagination 15 |
| `/admin/guru/create` | GET | ✓ | ✗ | ✗ | |
| `POST /admin/guru` | POST | ✓ (transaksional, sync mengajar) | ✗ | ✗ | `DB::transaction` |
| `/admin/guru/{id}/edit` | GET | ✓ | ✗ | ✗ | |
| `PUT /admin/guru/{id}` | PUT | ✓ (transaksional) | ✗ | ✗ | |
| `DELETE /admin/guru/{id}` | DELETE | ✓ (RESTRICT jika ada nilai) | ✗ | ✗ | Else `DB::transaction` cascade user+mengajar |
| `PATCH /admin/guru/{id}/toggle-active` | PATCH | ✓ | ✗ | ✗ | Toggle `user.is_active` |
| `/admin/guru/{id}/create-account` | GET/POST | ✓ (404 jika sudah punya akun) | ✗ | ✗ | |
| `/admin/akun` (index) | GET | ✓ (search/filter role) | ✗ | ✗ | |
| `/admin/akun/create` | GET | ✓ | ✗ | ✗ | Dropdown siswa/guru tanpa akun |
| `POST /admin/akun` | POST | ✓ (link ke Siswa/Guru) | ✗ | ✗ | |
| `PATCH /admin/akun/{user}/toggle-active` | PATCH | ✓ (tidak bisa self-disable) | ✗ | ✗ | |
| `POST /admin/akun/{user}/reset-password` | POST | ✓ (min 6 char) | ✗ | ✗ | |
| `/admin/laporan` | GET | ✓ | ✗ | ✗ | Pilih kelas |
| `/admin/laporan/preview` | GET | ✓ | ✗ | ✗ | Rekap + summary |
| `/admin/laporan/export/pdf` | GET | ✓ (download PDF) | ✗ | ✗ | barryvdh/laravel-dompdf |
| `/admin/laporan/export/html` | GET | ✓ (download HTML) | ✗ | ✗ | |
| `/guru/dashboard` | GET | ✗ | ✓ | ✗ | Stats nilai sendiri |
| `/guru/input-nilai` | GET | ✗ | ✓ (filter kelas×mapel mengajar) | ✗ | Cascading dropdowns |
| `POST /guru/input-nilai/save` | POST | ✗ | ✓ (403 jika bukan mengajarannya) | ✗ | updateOrCreate; status=Draft |
| `POST /guru/input-nilai/validate-final` | POST | ✗ | ✓ (kunci nilai) | ✗ | status=Final |
| `DELETE /guru/input-nilai/{id}` | DELETE | ✗ | ✓ (403 jika bukan miliknya; 400 jika Final) | ✗ | |
| `/guru/rekap` | GET | ✗ | ✓ (hanya kelas+mapel diajar) | ✗ | Stats lulus/tidak/belum |
| `/siswa/dashboard` | GET | ✗ | ✗ | ✓ | Info pribadi |
| `/siswa/nilai` | GET | ✗ | ✗ | ✓ (read-only, hanya nilai sendiri) | Group by kelas\|mapel |

### Resource-level guards
- **Siswa**: hanya bisa akses `siswa.nis = auth()->user()->siswa->nis` (filtered di controller)
- **Guru**: hanya bisa input/update/delete nilai dengan `id_guru = auth()->user()->guru->id` (verified di controller + test)
- **Guru mengajar**: hanya bisa nilai di `(kelas, mata_pelajaran)` yang ada di `guru_mengajar` miliknya (`mengajarDiKelasMapel()`)
- **Admin**: super-user di semua resource; `toggle-active` tidak bisa self-disable (prevent lockout)
- **Account nonaktif**: logout paksa di `EnsureUserHasRole::handle()` sebelum role check

---

| # | Waktu | Konteks | Error / Root Cause | Perbaikan | Hasil Verifikasi |
|---|-------|---------|---------------------|-----------|------------------|
| 1 | 2026-06-05 | Koneksi PDO MySQL | `PDO::__construct()` hang tanpa output. Root cause: MySQL 8 default `caching_sha2_password` plugin konflik dengan driver PDO PHP di environment ini. | `ALTER USER 'pdns'@'%' IDENTIFIED WITH mysql_native_password BY 'pdns123';` (di dalam container Docker) | `php artisan db:show` → `MySQL .. 8.0.46` ✓ |
| 2 | 2026-06-05 | MySQL tidak tersedia lokal | Tidak ada `mysqld` di host. MariaDB & MySQL daemon belum terinstall. | Jalankan `docker run -d mysql:8.0` di port 3306 dengan env `MYSQL_DATABASE=pdns` dll. | `docker exec pdns-mysql mysql -updns -ppdns123 -e 'SELECT VERSION()'` → `8.0.46` ✓ |
| 3 | 2026-06-05 | Migration: table 'gurus'/'nilais' not found | Laravel default pluralisasi nama model (Guru → gurus, Nilai → nilais), tapi tabel kita singular (`guru`, `nilai`) sesuai ERD spesifikasi. | Tambah `protected $table = 'guru'/'nilai'/'siswa'` di masing-masing Model. | `php artisan migrate:fresh --seed` → 34 users, 29 siswa, 4 guru, 116 nilai ✓ |
| 4 | 2026-06-05 | Pest test: PUT /admin/siswa/{nis} → "Attempt to read property nama_siswa on null" | Route model binding default cari `id` column, tapi Siswa PK = `nis` (string). | Tambah `public function getRouteKeyName(): string { return 'nis'; }` di `app/Models/Siswa.php`. | `php artisan test tests/Feature/AcceptanceSiswaTest.php` → 5/5 passed ✓ |
| 5 | 2026-06-05 | Pest test: Siswa edit perubahan NIS tidak di-block | SiswaRequest validated() return NIS, controller update dengan NIS baru → student NIS berubah. | Modifikasi `validated()` di `SiswaRequest`: `unset($data['nis'])` pada method PUT/PATCH. NIS jadi immutable. | Test AC-03c: NIS tetap `00001` setelah PUT attempt `99999` ✓ |
| 6 | 2026-06-05 | ESLint: 25 unused-import errors | Import `usePage`, `Edit`, `CardHeader`, `CardContent`, `buildFormData` dll yang tidak dipakai di JSX. | Hapus import unused dari 8 file `.tsx` (`admin/dashboard`, `admin/siswa/index`, `admin/guru/index`, `admin/accounts/index`, `admin/reports/index`, `admin/reports/preview`, `guru/rekap/index`, `siswa/nilai/index`, `guru/nilai/index`). | `npm run lint` → 0 errors ✓ |
| 9 | 2026-06-05 | Browser console: `ReferenceError: CardHeader is not defined` di dashboard pages | Saat cleaning unused imports (entry #6), `CardHeader` ikut terhapus dari import di `admin/dashboard.tsx` dan `siswa/nilai/index.tsx`, tapi JSX-nya masih pakai `<CardHeader>`. Guru dashboard import-nya masih utuh (tidak ada perubahan). | Tambah kembali `CardHeader` di import `admin/dashboard.tsx` dan `siswa/nilai/index.tsx`. | `npm run build` ✓; `npm run lint` → 0 errors ✓; login admin/guru/siswa → 200 di masing-masing dashboard ✓ |
| 11 | 2026-06-05 | User: guru baru dari form "Tambah Guru" tidak punya akun login (status "Tanpa Akun") | Form tambah guru tidak menyediakan input untuk buat akun. Guru tanpa `user_id` tidak bisa login ke sistem. | Tambah 3 perubahan: (1) `GuruRequest` + `create_account`/`username`/`password` fields (required-if-create_account); (2) `GuruController::store` & `update` buat `User` dalam transaction jika `create_account` checked; (3) Halaman baru `/admin/guru/{guru}/create-account` untuk guru yang sudah ada tapi tanpa akun (dengan password confirmation). Hapus guru juga cascade-delete user account. Tambah tombol `UserPlus` (hijau) di index untuk guru tanpa akun, dan section "Buat Akun" di edit page. Tambah 6 test di `AcceptanceGuruAkunTest.php`. | `php artisan test` → 35/35 ✓; `GET /admin/guru` (admin) → 200 dengan tombol Buat Akun ✓; guru baru dengan akun → bisa login dengan username/password ✓ |

---

## Catatan Penting

- **Stack yang dipakai:** Inertia.js v3 + React 19 (override DESIGN.md yang menyebut TALL stack) — diputuskan setelah klarifikasi dengan user
- **Database:** MySQL 8.0 di Docker
- **Auth:** Laravel Fortify + custom `EnsureUserHasRole` middleware
- **PDF:** barryvdh/laravel-dompdf
- **Testing:** Pest v4 + RefreshDatabase (SQLite in-memory untuk test)
- **Lokasi file `users` & relasi:** lihat `database/migrations/`
- **Kredensial default (setelah `migrate:fresh --seed`):**
  - admin: `admin / admin123`
  - guru: username = nama panjang lowercase digabung (honorific "Ibu/Pak/Bu/Bpk" dihapus); password = `guru123`
    - `sariwahyuni` (Ibu Sari Wahyuni / Matematika di X-A & X-B)
    - `budihartono` (Pak Budi Hartono / Bahasa Indonesia di X-A & XI-A)
    - `riniastuti` (Bu Rini Astuti / IPA di X-B & XI-B)
    - `jokosantoso` (Pak Joko Santoso / IPS di XI-A & XI-B)
  - siswa: username = NIS (5 digit, mis. `00001`…`00029`); password = `siswa123`
- **Formula nilai:** `0.3 * tugas + 0.3 * uts + 0.4 * uas` (KKM = 70; ≥ 70 Lulus)
- **FK behavior:** siswa→nilai CASCADE (hapus siswa hapus nilainya), guru→nilai RESTRICT (guru dengan nilai tidak bisa dihapus, toggle-active sebagai gantinya)
- **NIS immutable:** setelah siswa dibuat, NIS tidak bisa diubah via form edit
- **Mengajar pivot:** relasi many-to-many `guru_mengajar(id_guru, kelas, mata_pelajaran)`. Guru hanya bisa input/nilai siswa pada kombinasi (kelas, mapel) yang terdaftar di pivot. Tambah/hapus kombinasi via halaman Tambah/Edit Guru.
- **Status validasi global per-(kelas,mapel):** "Validasi Final" mengunci semua nilai mapel tertentu di kelas tertentu untuk guru tersebut sekaligus
- **Server dev:** `php artisan serve --host=127.0.0.1 --port=8000`
- **Login flow:** `POST /login` (X-Requested-With + X-XSRF-TOKEN) → 302 → `/redirect-by-role` → 302 ke `{admin,guru,siswa}/dashboard`

---

## Hasil Test Akhir

```
PHPUnit 12.5.28 by Sebastian Mann and contributors.
Pest      4.x

tests: 96 passed (96 total)
assertions: 637
duration: ~18s
```

- `tests/Feature/AcceptanceTest.php` (5 test): Login flow per role + password salah + username kosong + root `/` redirect
- `tests/Feature/AcceptanceSiswaTest.php` (6 test): CRUD siswa, NIS duplikat, NIS immutable, CASCADE delete, search, **kelas dari master table**
- `tests/Feature/AcceptanceNilaiTest.php` (10 test): Validasi 0-100, hitung nilai akhir, kelulusan, validasi final, guru tidak bisa nilai di kelas+mapel yang tidak diajar (403), guru tanpa mengajar
- `tests/Feature/AcceptanceSiswaAksesTest.php` (5 test): Siswa hanya lihat nilai sendiri, 403 forbidden akses halaman guru/admin, siswa nonaktif ditolak, guru_map flat shape regression
- `tests/Feature/AcceptanceLaporanTest.php` (12 test): Multi-kelas, multi-mapel, generate laporan, export PDF/HTML/CSV/XLSX, validasi filter, RESTRICT guru delete, Manajemen Akun index regression
- `tests/Feature/AcceptanceGuruAkunTest.php` (7 test): Buat guru tanpa akun, multi-mapel, validasi min:1, create-account, duplikat, hapus guru, edit mengajar
- `tests/Feature/AcceptanceTier2Test.php` (10 test): Edit siswa/guru, toggle-active admin, reset password, guru rekap authorization, stats
- `tests/Feature/AcceptanceKelasMapelTest.php` (19 test): CRUD master kelas & mapel, FK protection (tidak bisa delete yang dipakai siswa/mengajar/nilai), Rule::exists validation di siswa/guru form, search real-time
- `tests/Feature/AcceptanceSiswaRaporTest.php` (10 test): chart_data overall/per_mapel/stats, chart untuk siswa tanpa nilai, download rapor PDF (Content-Type + magic), body content UTF-16BE + gzinflate, filename pattern, 403 untuk guru/admin, dashboard has_nilai true/false
- `tests/Feature/AcceptanceAdminDashboardTest.php` (10 test): stats utama, persentase lulus, rekap per kelas dengan persentase, rata-rata per mapel sorted desc + persentase, top 5 siswa sorted desc, siswa perhatian (filter tidak lulus, sort by rasio desc + rata-rata asc), edge case (no siswa perhatian when all lulus), role auth (admin ok, guru/siswa 403, unauthenticated redirect)

---

## Log Error & Perbaikan (Lanjutan)

| # | Waktu | Konteks | Error / Root Cause | Perbaikan | Hasil Verifikasi |
|---|-------|---------|---------------------|-----------|------------------|
| 12 | 2026-06-05 | `POST /admin/siswa` dengan `kelas_baru` → 500 `Column 'kelas' cannot be null` | `SiswaRequest::validated()` override melakukan `unset($data['kelas_baru'])` sebelum return. Controller `SiswaController::store` baca `$data['kelas_baru']` setelah `validated()` dipanggil, tapi field-nya sudah dihapus → controller tidak bisa apply `kelas_baru` ke `kelas` → DB INSERT dengan `kelas=NULL` (NOT NULL constraint). | Hapus `unset($data['kelas_baru'])` dari `SiswaRequest::validated()` (biarkan controller yang unset). Tambah `elseif (empty($data['kelas'])) { unset($data['kelas']); }` di controller supaya tidak kirim NULL ke DB. Tambah regression test "AC-03b2: Admin menambah siswa dengan kelas_baru". | `php artisan test` → 37/37 ✓; create siswa NIS=00699 kelas_baru=XII-C → tersimpan di DB ✓ |
| 13 | 2026-06-05 | Hapus form "Buat akun login sekaligus" dari halaman Tambah/Edit Guru | Permintaan user: input akun dihapus dari Manajemen Guru, karena sudah ada halaman Manajemen Akun & halaman khusus `/admin/guru/{id}/create-account`. | Hapus section "Buat akun login sekaligus" dari `guru/create.tsx` dan `guru/edit.tsx`. Edit page tampilkan tombol/link "Buat Akun Login" untuk guru tanpa akun yang mengarah ke `/admin/guru/{id}/create-account`. Hapus logic account creation dari `GuruController::store` & `update` (bukan tanggung jawab guru CRUD lagi). Hapus rules `create_account/username/name/password` dari `GuruRequest`. Update test di `AcceptanceGuruAkunTest.php`. | `php artisan test` → 36/36 ✓; `npm run build` ✓; `npm run lint` → 0 errors ✓; create guru "Test Guru Baru" (Biologi) → tersimpan dengan `user_id=NULL` ✓ |
| 14 | 2026-06-05 | Refactor: Guru → mengajar (kelas, mapel) many-to-many. Sebelumnya: 1 guru = 1 mapel (field `guru.mata_pelajaran`). User ingin 1 guru bisa mengajar >1 kombinasi (kelas, mapel), dan guru hanya boleh input nilai untuk siswa di kombinasi yang diajar. | (1) Migration baru `guru_mengajar(id, id_guru FK CASCADE, kelas, mata_pelajaran, timestamps, unique(id_guru,kelas,mata_pelajaran), index(kelas,mata_pelajaran))`. (2) Migration `000005_restructure` drop `guru.mata_pelajaran`, add `nilai.kelas`, change unique `(nis,mata_pelajaran)` → `(nis,id_guru,kelas,mata_pelajaran)`, index `(kelas,mata_pelajaran)`. (3) Model `GuruMengajar`. (4) `Guru` model tambah `mengajar()` relation + helpers `getAllKelasAttribute/getAllMapelAttribute/getMapelByKelas/mengajarDiKelasMapel`. (5) `GuruRequest` validasi `mengajar[]` array. (6) `GuruController::syncMengajar` delete+recreate kombinasi. (7) `Guru/NilaiController` pakai 2 cascading dropdowns (kelas dulu, mapel auto-filtered). (8) `Siswa/NilaiController` group by `kelas|mapel`. (9) React: form create/edit guru pakai dynamic mengajar rows, guru index tampilkan mengajar badges, guru dashboard/rekap/input-nilai pakai cascading dropdowns, siswa/nilai group per (kelas,mapel). (10) Tambah 2 regression test: "Guru tidak bisa input nilai untuk kelas+mapel yang tidak diajar (403)" dan "Guru yang tidak mengajar di kelas manapun tidak bisa input nilai". (11) Seeder distribusi 2 kombinasi per guru. | `php artisan migrate:fresh --seed` → 34 users, 4 guru, 8 guru_mengajar, 29 siswa, 58 nilai ✓; `php artisan test` → 40/40 ✓ (193 assertions); `npm run build` ✓; `npm run lint` → 0 errors ✓; `vendor/bin/pint` → passed ✓; smoke test: login sariwahyuni → `/guru/input-nilai?kelas=X-A&mata_pelajaran=Matematika` 200 ✓; admin → `/admin/guru` 200 dengan mengajar badges ✓; siswa → `/siswa/nilai` 200 dengan grouping baru ✓ |
| 15 | 2026-06-05 | Halaman `/siswa/nilai` tampil "Guru: —" (em-dash) untuk semua mapel, padahal DB punya data `id_guru` valid | `Siswa\NilaiController` menggunakan `Guru::whereHas(...)->get()->groupBy('id')`. `groupBy` selalu menghasilkan Collection of Collections — JSON output menjadi `{"2": [Guru...], "4": [Guru...]}`. Frontend TypeScript mengetik `guru_map: Record<string, Guru>`, lalu `guru_map[id]?.nama_guru` baca `.nama_guru` dari sebuah **array** → `undefined` → fallback ke `—`. | Ganti `groupBy('id')` → `keyBy('id')` (flat map: `{"2": Guru, "4": Guru}`). Tambah regression test "Siswa nilai page menampilkan nama guru pengajar (guru_map adalah flat keyBy, bukan groupBy)" yang assert `guru_map.{id}.nama_guru` dan `guru_map.{id}.id`. | `php artisan test` → 41/41 ✓ (195 assertions, +2); `npx tsc --noEmit` → clean ✓; `vendor/bin/pint` → passed ✓; smoke test: login raka (00016) → `/siswa/nilai` tampil "Guru: **Pak Budi Hartono**" untuk Bahasa Indonesia dan "Guru: **Pak Joko Santoso**" untuk IPS ✓ |
| 16 | 2026-06-05 | Code quality pass (T1 + T2) | Hasil audit komprehensif: N+1 di admin dashboard, duplikasi real-time search logic di 2 admin pages (100+ LOC), 3 input nilai identik inline, guru_map masih pakai `whereHas` (1+N), GuruController `syncMengajar` tanpa transaction. | (1) Extract `useInertiaSearch` hook + refactor `admin/siswa/index.tsx` & `admin/guru/index.tsx` (DRY). (2) Optimasi `Admin/DashboardController::buildRekapPerKelas` dari N+1 (12+ query untuk 4 kelas) jadi 1 join+group (8 query total). (3) Wrap `GuruController::store/update` `syncMengajar` dalam `DB::transaction`. (4) `SiswaController::index` `with('user:id,username,is_active')` (selective). (5) Extract `<NilaiInput>` component di `guru/nilai/index.tsx` (3 input identik). (6) Optimasi `Siswa/NilaiController` `guru_map` dari `whereHas` jadi `whereIn(id)` dari nilai yang sudah di-load (1 query, no extra). (7) Tambah `siswa.kelas` index — **ditemukan sudah ada** di original migration `2026_06_05_000001_create_siswa_table.php` (line 15: `$table->string('kelas', 20)->index();`). (8) Tambah 10 regression test di `AcceptanceTier2Test.php`: edit siswa (NIS immutable verified), edit guru, toggle-active admin (self-disable ditolak), reset password, guru rekap authorization + stats. (9) Tambah Permissions Matrix table di DEBUG.md. | `php artisan test` → 51/51 ✓ (275 assertions, +10 tests, +70 assertions); `npx tsc --noEmit` → clean ✓; `npm run lint` → 0 errors ✓; `vendor/bin/pint --dirty --format agent` → passed ✓; `npm run build` → clean ✓; query count admin dashboard: 8 (was 12+); admin/siswa/index.tsx: 226→225 LOC (cleaner); admin/guru/index.tsx: 303→260 LOC (-43 LOC) ✓ |
| 17 | 2026-06-05 | Tambah master tables `kelas` & `mata_pelajaran` agar tidak perlu input manual/hardcode | Sebelumnya `kelas` & `mata_pelajaran` adalah string free-text dengan sumber dari `SELECT DISTINCT pluck` di banyak controller (5 file). User ingin pengelolaan terstruktur via master tables. Existing data di-nuke via `migrate:fresh --seed` dan re-seed. | (1) Migration baru `000006_create_kelas_and_mata_pelajaran_tables`: `kelas(id, nama string(20) UNIQUE, timestamps)` + `mata_pelajaran(id, nama string(100) UNIQUE, timestamps)`. (2) Models `Kelas` & `MataPelajaran` dengan relasi `siswa/guruMengajar/nilai` + static helper `pluckNamaOrdered()` + scope `search()`. (3) `DatabaseSeeder` urutan: `seedKelas()` (6: X-A..XII-B) → `seedMataPelajaran()` (10: Matematika..B.Jawa) → `seedAdmin/Guru/Siswa/Nilai` (FK aman karena firstOrCreate). (4) `SiswaRequest` hapus `kelas_baru` rule, `kelas` → `Rule::exists('kelas','nama')`. (5) `GuruRequest` hapus `mata_pelajaran_baru` rule + logic, `kelas`/`mata_pelajaran` → `Rule::exists`. (6) UI siswa/guru: hapus input `*_baru` + "Tambah Baru" button, ganti link ke halaman master "Belum ada kelas yang sesuai? Tambah kelas baru". (7) Resource controllers `KelasController` & `MataPelajaranController` (index/create/store/edit/update/destroy) dengan FK protection (422 jika dipakai). (8) 4 Inertia pages: `admin/kelas/{index,create,edit}` + `admin/mata-pelajaran/{index,create,edit}` dengan search real-time, count siswa/guru-mengajar/nilai badges, delete disabled jika dipakai. (9) Replace `Siswa::distinct()->pluck('kelas')` → `Kelas::pluckNamaOrdered()` di 5 controller files (SiswaController, GuruController, ReportController, DashboardController). (10) Routes: `Route::resource('kelas', ...)->except('show')` + `Route::resource('mata-pelajaran', ...)->except('show')`. (11) Sidebar nav: tambah 2 item (School icon + Library icon) antara Guru & Akun. (12) Test trait `Tests\Traits\SeedsAkademikMasters` dengan helpers `seedKelas()/seedMataPelajaran()` — dipakai di 6 existing test files via `beforeEach`. (13) 19 test baru di `AcceptanceKelasMapelTest.php`: CRUD master, duplikat ditolak, FK protection (delete kelas dipakai siswa/mengajar, delete mapel dipakai mengajar/nilai), Rule::exists di siswa/guru form, search real-time. | `php artisan migrate:fresh --seed` → 6 kelas + 10 mapel + 34 users + 4 guru + 8 guru_mengajar + 29 siswa + 58 nilai ✓; `php artisan test` → 76/76 ✓ (397 assertions, +25 tests, +122 assertions); `npx tsc --noEmit` → clean (pre-existing Wayfinder warnings only) ✓; `npm run lint` → 0 errors ✓; `vendor/bin/pint --dirty --format agent` → passed ✓; `npm run build` → built in 7.20s ✓; smoke test: login admin → `/admin/kelas` 200, `/admin/mata-pelajaran` 200, `/admin/siswa/create` 200 (no more kelas_baru input), `/admin/guru/create` 200 (no more mata_pelajaran_baru input) ✓; sidebar 7 nav items termasuk "Manajemen Kelas" & "Mata Pelajaran" ✓ |
| 18 | 2026-06-05 | Tambah Cetak Rapor Digital (PDF) + Grafik Performa Akademik untuk role siswa | User minta: (a) tombol cetak/print PDF di halaman nilai pribadi siswa dengan format rapor lengkap; (b) visualisasi bar chart sederhana yang membandingkan progres nilai Tugas, UTS, UAS agar siswa tahu di komponen mana mereka kurang maksimal. | (1) `Siswa\NilaiController::buildChartData` agregasi: `overall.{tugas,uts,uas,akhir,count}` (rata-rata cross-mapel) + `per_mapel[]` (T/U/A per mapel + status + KKM) + `stats.{total_mapel,lulus,tidak_lulus}` + `kkm` dari `Nilai::KKM`. (2) Route baru `GET /siswa/rapor/pdf` di bawah middleware `role:siswa`. (3) `Siswa\RaporController::pdf()`: load siswa + nilai grouped by `kelas|mapel` + eager-load guru, render `resources/views/reports/rapor-pdf.blade.php` (portrait A4), dompdf, filename `Rapor_{nama}_{nis}.pdf`. Rapor PDF berisi: header (SMAN 7 Solo, "RAPOR DIGITAL SISWA", tahun ajaran), identity table (nama uppercase, NIS, kelas, KKM), tabel nilai (9 kolom: No, Mapel, Guru, T/UTS/UAS, Akhir, Status, Validasi), summary boxes (jumlah mapel, rata-rata, lulus, tidak lulus), signature section (ortu/wali kelas/kepsek), meta footer. (4) `Siswa\DashboardController` tambah `has_nilai` boolean (Nilai::where('nis',...)->exists()). (5) `siswa/dashboard.tsx` tambah Cetak Rapor quick action card (Printer icon, hijau) conditional on `has_nilai`. (6) `siswa/nilai/index.tsx` rewrite: tombol Cetak Rapor (Printer icon) di header + tombol besar di footer; 3 dashboard cards di top (Rata-rata Keseluruhan with `<ComponentBar>` x3, Ringkasan Akademik stats, Komponen Perlu Perhatian dengan weak/above classification); section "Performa per Mata Pelajaran" dengan `<PerMapelChart>` yang punya `<MiniBar>` per mapel (Tgs/UTS/UAS bars + KKM line) + deteksi "komponen terlemah" auto-annotation. (7) Semua bar chart pure CSS (no library), KKM line ditandai dengan navy vertical line. (8) 10 test baru di `AcceptanceSiswaRaporTest.php`: chart_data structure (overall counts, per_mapel, stats, kkm), chart_data untuk siswa tanpa nilai, PDF download (Content-Type + magic), PDF body content (UTF-16BE hex decode + gzinflate stream decompression), 403 untuk guru/admin, filename pattern, dashboard has_nilai true/false. (9) Helper global `decompressPdfStreams()` di test file untuk unzip FlateDecode streams dan assert body text. (10) Test fix issues: (a) `round(70, 2)` returns int not float di PHP — use `(float) cast`; (b) `streamedContent()` bukan untuk binary PDF, use `getContent()`; (c) `assertStringContainsString` gagal untuk UTF-16BE encoded strings, use `bin2hex(mb_convert_encoding($str, 'UTF-16BE'))`. | `php artisan test` → 86/86 ✓ (474 assertions, +10 tests, +77 assertions); `npx tsc --noEmit` → clean (pre-existing Wayfinder warnings only) ✓; `npm run lint` → 0 errors ✓; `vendor/bin/pint --dirty --format agent` → passed ✓; `npm run build` → built in 6.31s ✓; smoke test: login siswa 00001 → `/siswa/nilai` 200 (40.3KB) dengan `chart_data.overall.tugas=77.5, uts=70, uas=75, akhir=74.25` + 2 tombol `/siswa/rapor/pdf` ✓; `GET /siswa/rapor/pdf` 200, 1.27MB, `application/pdf`, `%PDF-1.7` magic, NIS `00001` ditemukan 7× di body ✓; `/siswa/dashboard` 200 (23.7KB) dengan Cetak Rapor card + `has_nilai=true` ✓; login guru sariwahyuni → `/siswa/rapor/pdf` **403** ✓; login admin → `/siswa/rapor/pdf` **403** ✓ |
| 19 | 2026-06-05 | Tambah grafik interaktif di halaman beranda Admin (dashboard) | Sebelumnya dashboard admin hanya menampilkan 3 stat card polos (Total Siswa, Total Guru, Persentase Lulus) + tabel rekap kelulusan per kelas. User minta: visualisasi data yang lebih kaya & interaktif (chart) untuk membantu admin memahami data akademik dengan cepat. | (1) `Admin\DashboardController` ditambah 3 query method: `buildRataRataPerMapel` (AVG + count + lulus + tidak_lulus + persentase_lulus per mapel, sorted desc), `buildTopSiswa` (top 5 siswa by AVG nilai_akhir, eager-joined dengan siswa), `buildSiswaPerhatian` (siswa dengan ≥1 mapel tidak lulus, sorted by rasio_tidak_lulus desc + rata_rata asc, top 5). Tambah `total_mapel` di stats. Rekap per kelas tambah `total_nilai` + `persentase_lulus`. (2) `resources/js/pages/admin/dashboard.tsx` rewrite dengan 4 section interaktif: (a) **Donut Chart** kelulusan overall (pure CSS `conic-gradient`, total nilai di tengah, legend di bawah); (b) **Kelulusan per Kelas**: stacked horizontal bar (lulus hijau + tidak lulus merah, max width normalized), klik bar → `/admin/siswa?kelas=X` dengan `prefetch`; (c) **Rata-rata per Mata Pelajaran**: horizontal bar dengan KKM navy line, klik → `/admin/laporan?mapel=X`; (d) **Top Siswa Berprestasi** + **Siswa Perlu Perhatian**: dua list sortable (Ranking vs A-Z toggle) dengan progress bar mini, klik → `/admin/siswa/{nis}/edit`. (3) Interactive features: hover state pada semua bar (color shift + bar grow + tooltip via `title` attribute), CSS animation pada donut (700ms transition), sort toggle (rank ↔ alpha) untuk top 5 lists, prefetched link untuk navigasi cepat. (4) Color semantics: success (lulus), danger (tidak lulus), warning (di bawah KKM), primary (default). (5) Conditional alerts: jika `tidak_lulus > lulus` tampilkan "Tingkat kelulusan rendah" (rose-50), jika `lulus > 0 && lulus > tidak_lulus` tampilkan "Performa akademik baik" (emerald-50). (6) Quick action cards preserved dengan hover border primary/accent/success sesuai konteks. (7) 10 test baru di `AcceptanceAdminDashboardTest.php`: stats, persentase lulus, rekap per kelas dengan persentase, rata-rata per mapel sorted, top 5 siswa, siswa perhatian filter+sort, edge case (no siswa perhatian), role auth (admin/guru/siswa/unauth). (8) Test fix: (a) `selectRaw` dengan `?` binding di DB::raw tidak bisa — pakai interpolasi string `"SUM(CASE WHEN status_lulus = '{$val}' THEN ...)"` (aman karena value dari constant `Nilai::LULUS`); (b) PHP `round(50, 1)` returns int not float — pakai `(float) cast` di controller; (c) JSON drops `.0` decimal untuk whole numbers — test assertion pakai int (`70` bukan `70.0`) untuk match exact comparison `===`. | `php artisan test` → 96/96 ✓ (637 assertions, +10 tests, +163 assertions); `npx tsc --noEmit` → clean (pre-existing Wayfinder warnings only) ✓; `npm run lint` → 0 errors ✓; `vendor/bin/pint --dirty --format agent` → passed (1 unused import fix) ✓; `npm run build` → built in 6.32s ✓; smoke test: login admin → `/admin/dashboard` 200, 54.3KB (vs ~30KB sebelumnya) ✓; `rekap_per_kelas` punya 4 entries (X-A: 62.5%, X-B: 71.4%, XI-A: 85.7%, XI-B: 71.4%) ✓; `rata_rata_per_mapel` punya 4 entries sorted desc (Matematika 76.29, IPS 75.17, IPA 74.88, Bahasa Indonesia 73.97) ✓; `top_siswa` punya 5 entries (Eko Hidayat 85.25, Galih Santoso 84.45, Xena Fauzi 83.45, Dewi Sari 81.15, Zara Maulana 78.35) ✓; `siswa_perhatian` punya 5 entries sorted by rasio (Xena Wulandari 100%, Jihan Wijaya 100%, Indra Sari 50%, Jihan Saputra 50%, Dewi Handayani 50%) ✓; `kkm=70` ✓; login guru/siswa → `/admin/dashboard` **403** ✓ |

---

## Lint

- `vendor/bin/pint --dirty --format agent` → **passed** ✓
- `npm run lint` → **0 errors** ✓
- `npm run build` → **built in 6.32s** ✓
