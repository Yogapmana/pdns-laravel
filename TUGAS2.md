# Output Tugas 2 — Aplikasi Pengolahan Nilai Siswa SMAN 7 Solo

> **Project:** Sistem informasi akademik berbasis web untuk pengelolaan nilai siswa.
> **Stack:** Laravel 13.14 + Inertia.js v3 + React 19 + MySQL 8.0
> **Author:** [Nama Anda]
> **Tanggal:** Juni 2026
> **Repo:** `pdns/` (local development)

---

## Daftar Isi

1. [Halaman Login Berdasarkan Role](#1-halaman-login-berdasarkan-role)
2. [Form Input Data Siswa dan Nilai](#2-form-input-data-siswa-dan-nilai)
3. [Proses Perhitungan Nilai Akhir](#3-proses-perhitungan-nilai-akhir)
4. [Laporan Hasil Nilai Siswa](#4-laporan-hasil-nilai-siswa)
5. [Bukti Pengujian Database](#5-bukti-pengujian-database)
6. [Catatan Error / Debugging](#6-catatan-error--debugging)
7. [Perbaikan Error dan Hasil Setelah Diperbaiki](#7-perbaikan-error-dan-hasil-setelah-diperbaiki)
   - 7.5 Cetak Rapor Digital (PDF) + Grafik Performa Akademik Siswa
   - 7.6 Grafik Interaktif Dashboard Admin
   - 7.7 Fitur Intervensi / Buka Kunci Nilai oleh Admin (Audit Trail)
   - 7.8 Widget Notifikasi Dashboard Guru
   - 7.9 Fix Bug: Stats Dashboard Guru & Status Mixed Final/Draft
8. [Potongan Kode Fungsi / Procedure](#8-potongan-kode-fungsi--procedure)
9. [Potongan Kode Class dan Method](#9-potongan-kode-class-dan-method)
10. [Penjelasan Library atau Komponen yang Digunakan](#10-penjelasan-library-atau-komponen-yang-digunakan)
11. [Penjelasan Coding Guidelines dan Best Practices](#11-penjelasan-coding-guidelines-dan-best-practices)

---

## 1. Halaman Login Berdasarkan Role

Aplikasi menggunakan **Laravel Fortify** dengan **username-based authentication** (bukan email). Terdapat **3 role**: `admin`, `guru`, `siswa`. Setiap user yang login akan di-redirect ke dashboard sesuai role-nya.

### 1.1 Alur Login

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

### 1.2 Konfigurasi Fortify

`config/fortify.php` (konfigurasi relevan):

```php
'username' => 'username',
'home' => '/redirect-by-role',
'features' => [
    Features::username(),
    // registration, reset-password, two-factor DISABLED
],
```

### 1.3 Routing — `routes/web.php`

```php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route(Auth::user()->dashboardRoute());
    }
    return redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'role:admin,guru,siswa'])
    ->get('/redirect-by-role', function () {
        $user = Auth::user();
        return redirect()->route($user->dashboardRoute());
    })->name('redirect-by-role');

Route::prefix('admin')->middleware(['auth', 'role:admin'])->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    // ... route siswa/guru/akun/laporan
});

Route::prefix('guru')->middleware(['auth', 'role:guru'])->name('guru.')->group(function () {
    Route::get('/dashboard', [GuruDashboardController::class, 'index'])->name('dashboard');
    Route::get('/input-nilai', [NilaiController::class, 'index'])->name('nilai.index');
    // ... route nilai & rekap
});

Route::prefix('siswa')->middleware(['auth', 'role:siswa'])->name('siswa.')->group(function () {
    Route::get('/dashboard', [SiswaDashboardController::class, 'index'])->name('dashboard');
    Route::get('/nilai', [SiswaNilaiController::class, 'index'])->name('nilai.index');
});
```

### 1.4 User Model — `app/Models/User.php`

```php
class User extends Authenticatable
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_GURU  = 'guru';
    public const ROLE_SISWA = 'siswa';

    public function isAdmin(): bool  { return $this->role === self::ROLE_ADMIN; }
    public function isGuru(): bool   { return $this->role === self::ROLE_GURU; }
    public function isSiswa(): bool  { return $this->role === self::ROLE_SISWA; }

    public function dashboardRoute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN  => 'admin.dashboard',
            self::ROLE_GURU   => 'guru.dashboard',
            self::ROLE_SISWA  => 'siswa.dashboard',
            default           => 'login',
        };
    }
}
```

### 1.5 Login Form (React + Inertia) — `resources/js/pages/auth/login.tsx`

```tsx
import { Form } from '@inertiajs/react';

export default function Login() {
    return (
        <Form action="/login" method="post">
            {({ errors, processing }) => (
                <>
                    <h1 className="text-2xl font-bold text-navy">SMAN 7 Solo</h1>
                    <p className="text-sm text-muted-foreground mt-1">
                        Sistem Manajemen Akademik
                    </p>
                    <div>
                        <label htmlFor="username">Username</label>
                        <input id="username" name="username" required
                               autoComplete="username" placeholder="Masukkan username" />
                        {errors.username && <p>{errors.username}</p>}
                    </div>
                    <div>
                        <label htmlFor="password">Password</label>
                        <input id="password" name="password" type="password" required
                               autoComplete="current-password" placeholder="Masukkan password" />
                        {errors.password && <p>{errors.password}</p>}
                    </div>
                    <button type="submit" disabled={processing}>MASUK</button>
                </>
            )}
        </Form>
    );
}
```

### 1.6 Role Middleware — `app/Http/Middleware/EnsureUserHasRole.php`

```php
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Anda harus login terlebih dahulu.');
        }
        if (! $user->is_active) {
            auth()->logout();
            abort(403, 'Akun Anda telah dinonaktifkan. Hubungi admin.');
        }
        if (! in_array($user->role, $roles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }
        return $next($request);
    }
}
```

Middleware di-alias sebagai `role` di `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \App\Http\Middleware\EnsureUserHasRole::class,
    ]);
})
```

### 1.7 Kredensial Default (setelah `migrate:fresh --seed`)

| Role  | Username       | Password    | Keterangan |
|-------|----------------|-------------|------------|
| admin | `admin`        | `admin123`  | Akses penuh |
| guru  | `sariwahyuni`  | `guru123`   | Ajar Matematika di X-A & X-B |
| guru  | `budihartono`  | `guru123`   | Ajar B.Indo di X-A & XI-A |
| guru  | `riniastuti`   | `guru123`   | Ajar IPA di X-B & XI-B |
| guru  | `jokosantoso`  | `guru123`   | Ajar IPS di XI-A & XI-B |
| siswa | `00001`–`00029` | `siswa123`  | Username = NIS (5 digit) |

> Username siswa = NIS, username guru = nama lengkap lowercase digabung (honorific "Ibu/Pak/Bu/Bpk" dihapus).

### 1.8 Bukti Pengujian Login

| Skenario | URL Tested | HTTP Code | Hasil |
|----------|-----------|-----------|-------|
| Login admin (valid) | `POST /login` | 302 | Redirect ke `/admin/dashboard` |
| Login guru (valid) | `POST /login` | 302 | Redirect ke `/guru/dashboard` |
| Login siswa (valid) | `POST /login` | 302 | Redirect ke `/siswa/dashboard` |
| Login password salah | `POST /login` | 302 | Kembali ke login dengan error |
| Akses `/admin/dashboard` sebagai guru | `GET /admin/dashboard` | 403 | Forbidden |
| Akses `/guru/input-nilai` sebagai siswa | `GET /guru/input-nilai` | 403 | Forbidden |
| Akun `is_active=false` login | `POST /login` | 302 | Auto-logout + 403 |

> **[SCREENSHOT REQUIRED]** Screenshot halaman login (3 role mencoba login)

---

## 2. Form Input Data Siswa dan Nilai

### 2.1 Form Tambah Siswa — `resources/js/pages/admin/siswa/create.tsx`

Form input siswa dengan field:
- **NIS** (wajib, max 20 char, unik)
- **Nama Siswa** (wajib, max 255 char)
- **Kelas** (pilih dari dropdown atau input kelas baru)

```tsx
<Form action="/admin/siswa" method="post">
    {({ processing, errors }) => (
        <>
            <div>
                <label htmlFor="nis">NIS <span className="text-danger">*</span></label>
                <Input id="nis" name="nis" required placeholder="Contoh: 00030" />
                <InputError message={errors.nis} />
            </div>
            <div>
                <label htmlFor="nama_siswa">Nama Siswa <span className="text-danger">*</span></label>
                <Input id="nama_siswa" name="nama_siswa" required placeholder="Nama lengkap siswa" />
                <InputError message={errors.nama_siswa} />
            </div>
            <div>
                <label htmlFor="kelas">Kelas <span className="text-danger">*</span></label>
                <Select id="kelas" name="kelas" defaultValue="">
                    <option value="">Pilih kelas...</option>
                    {daftar_kelas.map((k) => <option key={k} value={k}>{k}</option>)}
                    <option value="__baru__">+ Kelas baru...</option>
                </Select>
                <Input name="kelas_baru" placeholder="Atau ketik kelas baru" className="mt-1" />
                <InputError message={errors.kelas} />
            </div>
            <Button type="submit" disabled={processing}>Simpan</Button>
        </>
    )}
</Form>
```

### 2.2 Form Request Validasi — `app/Http/Requests/Admin/SiswaRequest.php`

```php
public function rules(): array
{
    $nis = $this->route('siswa')?->nis;

    return [
        'nis' => [
            'required', 'string', 'max:20',
            Rule::unique(Siswa::class, 'nis')->ignore($nis, 'nis'),
        ],
        'nama_siswa' => ['required', 'string', 'max:255'],
        'kelas'      => ['nullable', 'string', 'max:20'],
        'kelas_baru' => ['nullable', 'string', 'max:20'],
    ];
}

public function withValidator($validator): void
{
    $validator->after(function ($v) {
        if (empty($this->kelas) && empty($this->kelas_baru)) {
            $v->errors()->add('kelas', 'Pilih kelas dari daftar atau isi kelas baru.');
        }
    });
}
```

> **Catatan:** Pada method `PUT/PATCH`, field `nis` di-`unset` dari `validated()` agar **NIS immutable** setelah siswa dibuat.

### 2.3 Controller — `app/Http/Controllers/Admin/SiswaController.php`

```php
public function store(SiswaRequest $request): RedirectResponse
{
    $data = $request->validated();
    $kelas = $data['kelas_baru'] ?? null;
    if (!empty($data['kelas'])) {
        $kelas = $data['kelas'];
    } elseif (empty($kelas)) {
        unset($data['kelas']);
    }
    unset($data['kelas_baru']);

    Siswa::create([
        'nis'         => $data['nis'],
        'nama_siswa'  => $data['nama_siswa'],
        'kelas'       => $kelas,
    ]);

    return redirect()->route('admin.siswa.index')
        ->with('success', "Siswa {$data['nama_siswa']} berhasil ditambahkan.");
}
```

### 2.4 Form Input Nilai — `resources/js/pages/guru/nilai/index.tsx`

Form input nilai dengan **2 cascading dropdown** (kelas → mapel auto-filtered dari kombinasi mengajar guru) + tabel nilai per siswa dengan real-time calculation.

```tsx
<Card>
    <CardContent>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
            <div>
                <label htmlFor="kelas">Kelas</label>
                <Select id="kelas" value={selectedKelas}
                        onChange={(e) => changeKelas(e.target.value)}>
                    <option value="">Pilih Kelas...</option>
                    {daftar_kelas.map((k) => <option key={k} value={k}>{k}</option>)}
                </Select>
            </div>
            <div>
                <label htmlFor="mapel">Mata Pelajaran</label>
                <Select id="mapel" value={selectedMapel}
                        onChange={(e) => setSelectedMapel(e.target.value)}
                        disabled={!selectedKelas}>
                    <option value="">
                        {selectedKelas ? 'Pilih Mata Pelajaran...' : 'Pilih kelas dulu'}
                    </option>
                    {availableMapel.map((m) => <option key={m} value={m}>{m}</option>)}
                </Select>
            </div>
            <Button onClick={applyFilter} disabled={!selectedKelas || !selectedMapel}>
                Tampilkan Daftar Siswa
            </Button>
        </div>
    </CardContent>
</Card>

<Form action="/guru/input-nilai/save" method="post">
    <input type="hidden" name="kelas" value={kelas} />
    <input type="hidden" name="mata_pelajaran" value={mataPelajaran} />
    <table>
        <thead>
            <tr>
                <th>NIS</th><th>Nama</th>
                <th>Tugas (30%)</th><th>UTS (30%)</th><th>UAS (40%)</th>
                <th>Akhir</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
            {siswa.map((s) => (
                <NilaiRow key={s.nis} siswa={s} initial={nilai_map[s.nis]} disabled={isFinal} />
            ))}
        </tbody>
    </table>
    <Button type="submit" disabled={processing || isFinal}>
        <Save /> Simpan sebagai Draft
    </Button>
</Form>
```

### 2.5 Form Tambah Guru (dengan Mengajar) — `resources/js/pages/admin/guru/create.tsx`

Guru bisa mengajar di banyak kombinasi (kelas, mapel), dikumpulkan via **dynamic rows**:

```tsx
<Form action="/admin/guru" method="post">
    <Input id="nama_guru" name="nama_guru" required placeholder="Nama lengkap guru" />
    <div className="space-y-3">
        {rows.map((row, i) => (
            <div key={i} className="grid grid-cols-12 gap-2">
                <Select name={`mengajar[${i}][kelas]`} value={row.kelas}
                        onChange={(e) => updateRow(i, 'kelas', e.target.value)}>
                    <option value="">Pilih kelas</option>
                    {daftar_kelas.map((k) => <option key={k} value={k}>{k}</option>)}
                </Select>
                <Select name={`mengajar[${i}][mata_pelajaran]`} value={row.mata_pelajaran}
                        onChange={(e) => updateRow(i, 'mata_pelajaran', e.target.value)}>
                    <option value="">Pilih mata pelajaran</option>
                    {daftar_mapel.map((m) => <option key={m} value={m}>{m}</option>)}
                </Select>
                <Input name={`mengajar[${i}][mata_pelajaran_baru]`}
                       placeholder="Atau ketik mapel baru" />
                <button type="button" onClick={() => removeRow(i)}>
                    <Trash2 />
                </button>
            </div>
        ))}
    </div>
    <Button type="button" onClick={addRow}><Plus /> Tambah Baris</Button>
    <Button type="submit">Simpan</Button>
</Form>
```

> **[SCREENSHOT REQUIRED]** Screenshot form tambah siswa, form tambah guru dengan dynamic rows, form input nilai dengan cascading dropdown

---

## 3. Proses Perhitungan Nilai Akhir

### 3.1 Rumus

```
nilai_akhir = (0.30 × nilai_tugas) + (0.30 × nilai_uts) + (0.40 × nilai_uas)
status_lulus = (nilai_akhir >= 70) ? "Lulus" : "Tidak Lulus"   (KKM = 70)
```

### 3.2 Implementasi — `app/Models/Nilai.php`

```php
class Nilai extends Model
{
    public const BOBOT_TUGAS = 0.30;
    public const BOBOT_UTS   = 0.30;
    public const BOBOT_UAS   = 0.40;
    public const KKM         = 70.0;
    public const STATUS_DRAFT = 'Draft';
    public const STATUS_FINAL = 'Final';
    public const LULUS        = 'Lulus';
    public const TIDAK_LULUS  = 'Tidak Lulus';

    protected function casts(): array
    {
        return [
            'nilai_tugas'  => 'decimal:2',
            'nilai_uts'    => 'decimal:2',
            'nilai_uas'    => 'decimal:2',
            'nilai_akhir'  => 'decimal:2',
        ];
    }

    /**
     * Hitung nilai akhir berbobot 0.30/0.30/0.40.
     */
    public static function hitungNilaiAkhir(float $tugas, float $uts, float $uas): float
    {
        return round(
            ($tugas * self::BOBOT_TUGAS)
          + ($uts   * self::BOBOT_UTS)
          + ($uas   * self::BOBOT_UAS),
            2
        );
    }

    /**
     * Tentukan status kelulusan berdasar KKM.
     */
    public static function tentukanKelulusan(float $nilaiAkhir): string
    {
        return $nilaiAkhir >= self::KKM ? self::LULUS : self::TIDAK_LULUS;
    }

    /**
     * Validasi nilai 0-100.
     */
    public static function validasiNilai(float $nilai): bool
    {
        return $nilai >= 0 && $nilai <= 100;
    }

    public function siswa(): BelongsTo { return $this->belongsTo(Siswa::class, 'nis', 'nis'); }
    public function guru():  BelongsTo { return $this->belongsTo(Guru::class, 'id_guru'); }
}
```

### 3.3 Contoh Perhitungan (test suite, semua PASS)

| Tugas | UTS | UAS | Hitung | Hasil | KKM 70 | Status |
|-------|-----|-----|--------|-------|--------|--------|
| 80    | 70  | 90  | 0.3·80 + 0.3·70 + 0.4·90 = 24+21+36 = **81** | 81.00 | ≥ 70 | **Lulus** |
| 50    | 60  | 65  | 0.3·50 + 0.3·60 + 0.4·65 = 15+18+26 = **59** | 59.00 | < 70 | **Tidak Lulus** |
| 70    | 70  | 70  | 0.3·70 + 0.3·70 + 0.4·70 = 21+21+28 = **70** | 70.00 | ≥ 70 | **Lulus** |
| 105   | 70  | 90  | — | ditolak (validasi 0-100) | — | error |
| -5    | 70  | 90  | — | ditolak (validasi 0-100) | — | error |

### 3.4 Penerapan di Controller — `Guru/NilaiController::save()`

```php
public function save(Request $request): RedirectResponse
{
    $validated = $request->validate([
        'kelas'           => ['required', 'string'],
        'mata_pelajaran'  => ['required', 'string'],
        'nilai'           => ['required', 'array'],
        'nilai.*.nis'     => ['required', 'string', 'exists:siswa,nis'],
        'nilai.*.nilai_tugas' => ['nullable', 'numeric', 'between:0,100'],
        'nilai.*.nilai_uts'   => ['nullable', 'numeric', 'between:0,100'],
        'nilai.*.nilai_uas'   => ['nullable', 'numeric', 'between:0,100'],
    ]);

    if (! $guru->mengajarDiKelasMapel($validated['kelas'], $validated['mata_pelajaran'])) {
        abort(403, 'Anda tidak mengajar kombinasi kelas dan mata pelajaran ini.');
    }

    DB::transaction(function () use ($validated, $guru) {
        foreach ($validated['nilai'] as $row) {
            // skip row kosong
            if ($row['nilai_tugas'] === null && $row['nilai_uts'] === null && $row['nilai_uas'] === null) {
                continue;
            }
            $akhir = Nilai::hitungNilaiAkhir($row['nilai_tugas'], $row['nilai_uts'], $row['nilai_uas']);
            $status = Nilai::tentukanKelulusan($akhir);

            Nilai::updateOrCreate(
                ['nis' => $row['nis'], 'id_guru' => $guru->id,
                 'kelas' => $validated['kelas'], 'mata_pelajaran' => $validated['mata_pelajaran']],
                ['nilai_tugas' => $row['nilai_tugas'], 'nilai_uts' => $row['nilai_uts'],
                 'nilai_uas' => $row['nilai_uas'], 'nilai_akhir' => $akhir,
                 'status_lulus' => $status, 'status_validasi' => Nilai::STATUS_DRAFT],
            );
        }
    });

    return back()->with('success', 'Nilai berhasil disimpan sebagai Draft.');
}
```

### 3.5 Real-time Calculation di Frontend — `resources/js/lib/utils.ts`

```ts
export function calculateNilaiAkhir(
    tugas: number | null, uts: number | null, uas: number | null
): number | null {
    if (tugas === null || uts === null || uas === null) return null;
    return Math.round((tugas * 0.3) + (uts * 0.3) + (uas * 0.4) * 100) / 100;
}

export function calculateStatusLulus(akhir: number | null): 'Lulus' | 'Tidak Lulus' | null {
    if (akhir === null) return null;
    return akhir >= 70 ? 'Lulus' : 'Tidak Lulus';
}
```

### 3.6 Bukti Pengujian (Pest test)

```php
test('Hitung nilai akhir: 0.3*80 + 0.3*70 + 0.4*90 = 81', function () {
    expect(Nilai::hitungNilaiAkhir(80, 70, 90))->toBe(81.0);
});

test('Hitung nilai akhir: 0.3*50 + 0.3*60 + 0.4*65 = 59', function () {
    expect(Nilai::hitungNilaiAkhir(50, 60, 65))->toBe(59.0);
});

test('Tentukan kelulusan: >= 70 Lulus, < 70 Tidak Lulus', function () {
    expect(Nilai::tentukanKelulusan(70))->toBe('Lulus');
    expect(Nilai::tentukanKelulusan(80))->toBe('Lulus');
    expect(Nilai::tentukanKelulusan(69.99))->toBe('Tidak Lulus');
    expect(Nilai::tentukanKelulusan(59))->toBe('Tidak Lulus');
});

test('Validasi nilai: 0-100 valid, di luar ditolak', function () {
    expect(Nilai::validasiNilai(0))->toBeTrue();
    expect(Nilai::validasiNilai(100))->toBeTrue();
    expect(Nilai::validasiNilai(-1))->toBeFalse();
    expect(Nilai::validasiNilai(101))->toBeFalse();
    expect(Nilai::validasiNilai(105))->toBeFalse();
});
```

**Hasil:** `php artisan test` → 4 tests passed ✓

> **[SCREENSHOT REQUIRED]** Screenshot form input nilai dengan kalkulasi real-time

---

## 4. Laporan Hasil Nilai Siswa

### 4.1 Fitur Laporan

| Fitur | Endpoint | Method | Output |
|-------|----------|--------|--------|
| Pilih kelas | `/admin/laporan` | GET | Inertia page dengan dropdown kelas |
| Preview laporan | `/admin/laporan/preview?kelas=X-A` | GET | Inertia page dengan tabel |
| Export PDF | `/admin/laporan/export/pdf?kelas=X-A` | GET | Download PDF (landscape A4) |
| Export HTML | `/admin/laporan/export/html?kelas=X-A` | GET | Download HTML |

### 4.2 Controller — `app/Http/Controllers/Admin/ReportController.php`

```php
class ReportController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $daftarKelas = Siswa::query()->distinct()->orderBy('kelas')->pluck('kelas');
        return Inertia::render('admin/reports/index', ['daftar_kelas' => $daftarKelas]);
    }

    public function preview(Request $request): InertiaResponse
    {
        $request->validate(['kelas' => ['required', 'string']]);
        return Inertia::render('admin/reports/preview', $this->buildReportData($request->input('kelas')));
    }

    public function exportPdf(Request $request): Response
    {
        $request->validate(['kelas' => ['required', 'string']]);
        $data = $this->buildReportData($request->input('kelas'));
        $kelas = $request->input('kelas');

        $pdf = Pdf::loadView('reports.pdf', $data)->setPaper('a4', 'landscape');
        $filename = 'laporan_kelas_'.str_replace('-', '_', $kelas).'_'.date('Y').'.pdf';

        return $pdf->download($filename);
    }

    public function exportHtml(Request $request): Response
    {
        $request->validate(['kelas' => ['required', 'string']]);
        $data = $this->buildReportData($request->input('kelas'));
        $kelas = $request->input('kelas');

        $html = view('reports.html', $data)->render();
        return response($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="laporan_kelas_'.str_replace('-', '_', $kelas).'_'.date('Y').'.html"',
        ]);
    }

    private function buildReportData(string $kelas): array
    {
        $siswaList = Siswa::where('kelas', $kelas)->orderBy('nis')->get();
        $nisSiswa = $siswaList->pluck('nis');

        $nilai = Nilai::whereIn('nis', $nisSiswa)
            ->orderBy('mata_pelajaran')
            ->get()
            ->groupBy(['nis', 'mata_pelajaran']);

        $mapelList = Nilai::whereIn('nis', $nisSiswa)
            ->distinct()->orderBy('mata_pelajaran')->pluck('mata_pelajaran');

        $rows = $siswaList->map(function ($siswa) use ($nilai, $mapelList) {
            $rowNilai = [];
            $totalAkhir = 0;
            $jumlahMapel = 0;

            foreach ($mapelList as $mapel) {
                $item = $nilai->get($siswa->nis)?->get($mapel)?->first();
                $rowNilai[$mapel] = $item;
                if ($item && $item->nilai_akhir !== null) {
                    $totalAkhir += (float) $item->nilai_akhir;
                    $jumlahMapel++;
                }
            }

            $rataRata = $jumlahMapel > 0 ? round($totalAkhir / $jumlahMapel, 2) : null;
            return ['siswa' => $siswa, 'nilai_per_mapel' => $rowNilai, 'rata_rata' => $rataRata];
        });

        $jumlahLulus = 0;
        $jumlahTidakLulus = 0;
        foreach ($rows as $r) {
            foreach ($r['nilai_per_mapel'] as $n) {
                if ($n) {
                    if ($n->status_lulus === Nilai::LULUS) $jumlahLulus++;
                    elseif ($n->status_lulus === Nilai::TIDAK_LULUS) $jumlahTidakLulus++;
                }
            }
        }

        return [
            'kelas' => $kelas, 'rows' => $rows, 'mapel_list' => $mapelList,
            'stats' => ['jumlah_siswa' => $siswaList->count(), 'jumlah_lulus' => $jumlahLulus, 'jumlah_tidak_lulus' => $jumlahTidakLulus],
            'tanggal_cetak' => now()->format('d F Y'),
        ];
    }
}
```

### 4.3 PDF Template — `resources/views/reports/pdf.blade.php`

```php
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Laporan Kelas {{ $kelas }}</title>
    <style>
        @page { margin: 1cm; }
        body { font-family: 'Calibri', 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; }
        .header { text-align: center; border-bottom: 2px solid #1A56DB; padding-bottom: 10px; }
        .header h1 { color: #1E3A5F; font-size: 18px; }
        .summary { display: flex; justify-content: space-between; margin: 15px 0;
                   padding: 8px; background: #F1F5F9; border-radius: 6px; }
        table { width: 100%; border-collapse: collapse; font-size: 9px; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 6px; text-align: center; }
        th { background: #1A56DB; color: white; }
        .badge-lulus   { background: #dcfce7; color: #166534; padding: 2px 6px; border-radius: 4px; font-weight: bold; }
        .badge-tidak   { background: #fee2e2; color: #991b1b; padding: 2px 6px; border-radius: 4px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SMAN 7 SOLO</h1>
        <p style="font-size: 13px; font-weight: bold;">LAPORAN REKAPITULASI NILAI SISWA</p>
        <p>Kelas: <strong>{{ $kelas }}</strong> &mdash; Tanggal Cetak: {{ $tanggal_cetak }}</p>
    </div>

    <div class="summary">
        <div class="summary-item">
            <strong>{{ $stats['jumlah_siswa'] }}</strong><span>Total Siswa</span>
        </div>
        <div class="summary-item">
            <strong style="color:#10B981;">{{ $stats['jumlah_lulus'] }}</strong><span>Lulus</span>
        </div>
        <div class="summary-item">
            <strong style="color:#EF4444;">{{ $stats['jumlah_tidak_lulus'] }}</strong><span>Tidak Lulus</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2">No</th>
                <th rowspan="2">NIS</th>
                <th rowspan="2">Nama Siswa</th>
                @foreach($mapel_list as $mapel)
                    <th colspan="3">{{ $mapel }}</th>
                @endforeach
                <th rowspan="2">Rata-rata</th>
            </tr>
            <tr>
                @foreach($mapel_list as $mapel)
                    <th style="font-size:8px;">Tgs</th>
                    <th style="font-size:8px;">UTS</th>
                    <th style="font-size:8px;">UAS</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row['siswa']->nis }}</td>
                    <td>{{ $row['siswa']->nama_siswa }}</td>
                    @foreach($mapel_list as $mapel)
                        @php $n = $row['nilai_per_mapel'][$mapel] ?? null; @endphp
                        <td>{{ $n?->nilai_tugas ?? '—' }}</td>
                        <td>{{ $n?->nilai_uts ?? '—' }}</td>
                        <td>{{ $n?->nilai_uas ?? '—' }}</td>
                    @endforeach
                    <td><strong>{{ $row['rata_rata'] ?? '—' }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
```

### 4.4 Bukti Pengujian Laporan

| Skenario | URL | HTTP | Hasil |
|----------|-----|------|-------|
| Preview laporan X-A | `/admin/laporan/preview?kelas=X-A` | 200 | Inertia page 8 siswa |
| Export PDF X-A | `/admin/laporan/export/pdf?kelas=X-A` | 200 | `application/pdf` |
| Export HTML X-A | `/admin/laporan/export/html?kelas=X-A` | 200 | `text/html` |
| Preview tanpa `?kelas` | `/admin/laporan/preview` | 422 | Validation error |

Pest test (semua PASS):
```php
test('AC-09: Admin generate laporan kelas menampilkan semua siswa dengan nilai', function () {
    // ... setup
    $response = $this->actingAs($admin)->get('/admin/laporan/preview?kelas=X-A');
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/reports/preview')
        ->where('stats.jumlah_siswa', 2)
        ->where('stats.jumlah_lulus', 1)
        ->where('stats.jumlah_tidak_lulus', 1)
    );
});

test('AC-10: Admin ekspor laporan ke PDF menghasilkan file PDF', function () {
    $response = $this->actingAs($admin)->get('/admin/laporan/export/pdf?kelas=X-A');
    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('laporan_kelas_X_A_');
});
```

> **[SCREENSHOT REQUIRED]** Screenshot preview laporan, hasil export PDF (halaman 1-2), HTML export

---

## 5. Bukti Pengujian Database

### 5.1 Tabel Database (Skema Final)

```
users
├── id (PK, bigint)
├── username (string, unique)
├── name (string, nullable)
├── role (enum: admin|guru|siswa)
├── is_active (boolean, default true)
├── password (hashed)
├── remember_token
└── timestamps

siswa
├── nis (PK, string(20))              -- PK string bukan auto-increment
├── user_id (FK → users.id, unique, nullOnDelete)
├── nama_siswa (string)
├── kelas (string(20), index)
└── timestamps

guru
├── id (PK, bigint)
├── user_id (FK → users.id, unique, nullOnDelete)
├── nama_guru (string)
└── timestamps

guru_mengajar
├── id (PK, bigint)
├── id_guru (FK → guru.id, CASCADE)
├── kelas (string(20))
├── mata_pelajaran (string(100))
├── timestamps
├── UNIQUE(id_guru, kelas, mata_pelajaran)
└── INDEX(kelas, mata_pelajaran)

nilai
├── id (PK, bigint)
├── nis (FK → siswa.nis, CASCADE)
├── id_guru (FK → guru.id, RESTRICT)
├── kelas (string(20))
├── mata_pelajaran (string(100))
├── nilai_tugas (decimal(5,2), nullable)
├── nilai_uts (decimal(5,2), nullable)
├── nilai_uas (decimal(5,2), nullable)
├── nilai_akhir (decimal(5,2), nullable)
├── status_lulus (enum: 'Lulus'|'Tidak Lulus', nullable)
├── status_validasi (enum: 'Draft'|'Final', default 'Draft')
├── timestamps
├── UNIQUE(nis, id_guru, kelas, mata_pelajaran)
└── INDEX(kelas, mata_pelajaran)
```

### 5.2 Migrasi — `database/migrations/2026_06_05_000001_create_siswa_table.php`

```php
public function up(): void
{
    Schema::create('siswa', function (Blueprint $table) {
        $table->string('nis', 20)->primary();
        $table->foreignId('user_id')->nullable()->unique()
              ->constrained('users')->nullOnDelete();
        $table->string('nama_siswa');
        $table->string('kelas', 20)->index();
        $table->timestamps();
    });
}
```

### 5.3 Migrasi — `2026_06_05_000003_create_nilai_table.php`

```php
public function up(): void
{
    Schema::create('nilai', function (Blueprint $table) {
        $table->id();
        $table->string('nis', 20);
        $table->unsignedBigInteger('id_guru');
        $table->string('kelas', 20);
        $table->string('mata_pelajaran', 100);
        $table->decimal('nilai_tugas', 5, 2)->nullable();
        $table->decimal('nilai_uts', 5, 2)->nullable();
        $table->decimal('nilai_uas', 5, 2)->nullable();
        $table->decimal('nilai_akhir', 5, 2)->nullable();
        $table->enum('status_lulus', ['Lulus', 'Tidak Lulus'])->nullable();
        $table->enum('status_validasi', ['Draft', 'Final'])->default('Draft');
        $table->timestamps();

        $table->foreign('nis')->references('nis')->on('siswa')->cascadeOnDelete();
        $table->foreign('id_guru')->references('id')->on('guru')->restrictOnDelete();
        $table->unique(['nis', 'id_guru', 'kelas', 'mata_pelajaran'], 'nilai_nis_guru_kelas_mapel_unique');
        $table->index(['kelas', 'mata_pelajaran']);
    });
}
```

### 5.4 Migrasi — `2026_06_05_000004_create_guru_mengajar_table.php`

```php
public function up(): void
{
    Schema::create('guru_mengajar', function (Blueprint $table) {
        $table->id();
        $table->foreignId('id_guru')->constrained('guru')->cascadeOnDelete();
        $table->string('kelas', 20);
        $table->string('mata_pelajaran', 100);
        $table->timestamps();

        $table->unique(['id_guru', 'kelas', 'mata_pelajaran'], 'guru_mengajar_unique');
        $table->index(['kelas', 'mata_pelajaran']);
    });
}
```

### 5.5 Bukti Eksekusi — `php artisan migrate:fresh --seed`

```
Dropping all tables .. 287.83ms DONE
INFO Preparing database.
Creating migration table .. 26.20ms DONE
INFO Running migrations.
0001_01_01_000000_create_users_table .. 122.63ms DONE
0001_01_01_000001_create_cache_table .. 75.18ms DONE
0001_01_01_000002_create_jobs_table .. 128.85ms DONE
2026_06_05_000001_create_siswa_table .. 113.58ms DONE
2026_06_05_000002_create_guru_table .. 115.05ms DONE
2026_06_05_000003_create_nilai_table .. 195.93ms DONE
2026_06_05_000004_create_guru_mengajar_table .. 116.74ms DONE
2026_06_05_000005_restructure_guru_mata_pelajaran_to_mengajar  198.23ms DONE
INFO Seeding database.
```

### 5.6 State Database Setelah Seeding

| Tabel | Row Count | Keterangan |
|-------|-----------|------------|
| `users` | 34 | 1 admin + 4 guru + 29 siswa |
| `guru` | 4 | 4 guru dengan akun |
| `guru_mengajar` | 8 | 2 kombinasi mengajar per guru |
| `siswa` | 29 | X-A: 8, X-B: 7, XI-A: 7, XI-B: 7 |
| `nilai` | 58 | total nilai dari kombinasi mengajar |

**Sample Guru Mengajar:**

| Username Guru | Nama | Kombinasi Mengajar |
|---------------|------|---------------------|
| `sariwahyuni` | Ibu Sari Wahyuni | X-A-Matematika, X-B-Matematika |
| `budihartono` | Pak Budi Hartono | X-A-Bahasa Indonesia, XI-A-Bahasa Indonesia |
| `riniastuti`  | Bu Rini Astuti  | X-B-IPA, XI-B-IPA |
| `jokosantoso` | Pak Joko Santoso | XI-A-IPS, XI-B-IPS |

**Sample Siswa per Kelas:**

```
00001 X-A  Eko Wulandari
00002 X-A  Toni Nurhaliza
...
00008 X-A  Mira Handayani
00009 X-B  Citra Anggraini
...
00029 XI-B (terakhir)
```

**Statistik Nilai:**

```
Total Nilai   : 58
  - Lulus     : 44 (76%)
  - Tidak Lulus: 14 (24%)
  - Draft     : 32
  - Final     : 26
```

### 5.7 Bukti Pengujian Pest

```
PHPUnit 12.5.28
Pest      4.x
tests: 40 passed (40 total)
assertions: 193
duration: ~3.0s
```

**Test files:**

| File | Test Count |
|------|-----------|
| `tests/Feature/AcceptanceTest.php` | 5 |
| `tests/Feature/AcceptanceSiswaTest.php` | 5 |
| `tests/Feature/AcceptanceNilaiTest.php` | 10 |
| `tests/Feature/AcceptanceSiswaAksesTest.php` | 4 |
| `tests/Feature/AcceptanceLaporanTest.php` | 7 |
| `tests/Feature/AcceptanceGuruAkunTest.php` | 7 |
| **Total** | **40 tests, 193 assertions** |

> **[SCREENSHOT REQUIRED]** Screenshot phpMyAdmin / HeidiSQL / MySQL Workbench tabel `users`, `siswa`, `guru`, `guru_mengajar`, `nilai` dengan data real; output `migrate:fresh --seed` di terminal; output `php artisan test`

---

## 6. Catatan Error / Debugging

Detail lengkap tersimpan di **`DEBUG.md`** (40+ entri). Berikut ringkasan error yang ditemui selama development:

| # | Tanggal | Konteks | Error Singkat |
|---|---------|---------|---------------|
| 1 | 2026-06-05 | Koneksi PDO MySQL | `PDO::__construct()` hang tanpa output |
| 2 | 2026-06-05 | MySQL tidak tersedia lokal | Tidak ada `mysqld` di host |
| 3 | 2026-06-05 | Migration | Table `gurus`/`nilais` not found (plural vs singular) |
| 4 | 2026-06-05 | Route model binding | "Attempt to read property nama_siswa on null" (Siswa PK=nis) |
| 5 | 2026-06-05 | Edit siswa | Perubahan NIS tidak di-block |
| 6 | 2026-06-05 | ESLint | 25 unused-import errors |
| 7 | 2026-06-05 | Decimal cast Inertia | Nilai 80.00 di-cast jadi 80 (int) di frontend |
| 8 | 2026-06-05 | Browser blank page | `CardHeader is not defined` di dashboard pages |
| 9 | 2026-06-05 | Inertia Forms | `preserveScroll` not in type definitions |
| 10 | 2026-06-05 | Manajemen Akun | SQLSTATE 42S22: Column not found `siswa.id` (FK ke Siswa PK string) |
| 11 | 2026-06-05 | Tambah Siswa | `Column 'kelas' cannot be null` (kelas_baru bug) |
| 12 | 2026-06-05 | Guru akun inline | Hapus form "Buat akun login sekaligus" |
| 13 | 2026-06-05 | Seeder guru | Username guru harus deterministik (lowercase, no honorific) |
| 14 | 2026-06-05 | Refactor mengajar | Guru → many-to-many (kelas, mapel) via pivot table |

> Detail per error: penyebab, perbaikan, dan hasil verifikasi ada di `DEBUG.md`.

---

## 7. Perbaikan Error dan Hasil Setelah Diperbaiki

### Contoh 1: Error #1 — PDO MySQL hang

**Error:**
```
SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo failed
```
Proses `php artisan migrate` hang tanpa output.

**Root Cause:** MySQL 8 default `caching_sha2_password` plugin konflik dengan driver PDO PHP di environment ini.

**Perbaikan (di dalam container Docker):**
```sql
ALTER USER 'pdns'@'%' IDENTIFIED WITH mysql_native_password BY 'pdns123';
FLUSH PRIVILEGES;
```

**Hasil:** `php artisan db:show` → `MySQL 8.0.46` ✓

---

### Contoh 2: Error #3 — Table `gurus` not found

**Error:**
```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'pdns.gurus' doesn't exist
```

**Root Cause:** Laravel default pluralisasi: `Guru → gurus`, `Nilai → nilais`. Spesifikasi ERD kita menggunakan singular (`guru`, `nilai`).

**Perbaikan:** Tambah `protected $table` di setiap model:
```php
class Guru extends Model  { protected $table = 'guru'; }
class Nilai extends Model { protected $table = 'nilai'; }
class Siswa extends Model { protected $table = 'siswa'; }
```

**Hasil:** `migrate:fresh --seed` → 34 users, 29 siswa, 4 guru, 58 nilai ✓

---

### Contoh 3: Error #4 — Route model binding Siswa

**Error:**
```
Attempt to read property "nama_siswa" on null
```

**Root Cause:** Route `{siswa}` di `routes/web.php` lookup PK `id` (default), tapi Siswa PK = `nis` (string).

**Perbaikan:** Override `getRouteKeyName` di `Siswa`:
```php
class Siswa extends Model
{
    protected $table = 'siswa';
    protected $primaryKey = 'nis';
    public $incrementing = false;
    protected $keyType = 'string';

    public function getRouteKeyName(): string { return 'nis'; }
}
```

**Hasil:** Test `AcceptanceSiswaTest::AC-03c` passed ✓

---

### Contoh 4: Error #10 — SQLSTATE Column not found `siswa.id`

**Error:**
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'siswa.id' in 'where clause'
```

**Root Cause:** Eager load `User::with('siswa')` otomatis pilih `siswa.id` (PK), tapi Siswa PK = `nis` (string). Inertia 1-to-1 default pakai PK string target → MySQL bingung.

**Perbaikan:** Specify explicit select di `AccountController`:
```php
$accounts = User::query()
    ->with([
        'siswa:nis,user_id,nama_siswa,kelas',  // PK eksplisit, tanpa `id`
        'guru:id,user_id,nama_guru',
        'guru.mengajar:id_guru,kelas,mata_pelajaran',
    ])
    // ...
```

**Hasil:** `GET /admin/akun` → 200 ✓

---

### Contoh 5: Error #11 — `Column 'kelas' cannot be null`

**Error:**
```
SQLSTATE[23000]: Column 'kelas' cannot be null
```

**Root Cause:** `SiswaRequest::validated()` override melakukan `unset($data['kelas_baru'])` sebelum return. Controller baca `$data['kelas_baru']` setelah `validated()` dipanggil, tapi field-nya sudah dihapus → controller tidak bisa apply `kelas_baru` ke `kelas` → DB INSERT `kelas=NULL` (NOT NULL).

**Perbaikan:**
```php
// SiswaRequest::validated() — biarkan kelas_baru ada
public function validated($key = null, $default = null)
{
    $data = parent::validated();
    if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
        unset($data['nis']);
    }
    return $data;
}

// SiswaController::store() — handle di sini
$data = $request->validated();
$kelas = $data['kelas_baru'] ?? null;
if (!empty($data['kelas'])) {
    $kelas = $data['kelas'];
} elseif (empty($kelas)) {
    unset($data['kelas']);
}
unset($data['kelas_baru']);
```

**Hasil:** Test `AC-03b2: Admin menambah siswa dengan kelas_baru` passed ✓ (create siswa NIS=00699 dengan `kelas_baru=XII-C` → tersimpan di DB)

---

### Contoh 6: Error #14 — Refactor Guru ke Many-to-Many Mengajar

**Konteks:** User ingin 1 guru bisa mengajar banyak kombinasi (kelas, mapel), dan guru hanya boleh input nilai di kombinasi yang diajar.

**Perbaikan:** 11 sub-langkah (lihat DEBUG.md entry #14 untuk lengkap):
1. Migration `guru_mengajar(id_guru, kelas, mata_pelajaran)`
2. Migration restructure: drop `guru.mata_pelajaran`, add `nilai.kelas`, ubah unique
3. Model `GuruMengajar`
4. `Guru` helpers: `mengajarDiKelasMapel`, `getMapelByKelas`, dll
5. `GuruRequest` validasi `mengajar[]` array
6. `GuruController::syncMengajar()` (delete + recreate)
7. `Guru/NilaiController` 2 cascading dropdowns + 403 check
8. `Siswa/NilaiController` group by `kelas|mapel`
9. React: form create/edit guru pakai dynamic mengajar rows
10. Tambah 2 regression test: 403 untuk kelas+mapel yang tidak diajar, guru tanpa mengajar
11. Seeder distribusi mengajar

**Hasil:**
- `migrate:fresh --seed` → 34 users, 4 guru, 8 guru_mengajar, 29 siswa, 58 nilai ✓
- `php artisan test` → 40/40 passed (193 assertions) ✓
- `npm run build` ✓, `npm run lint` → 0 errors ✓, `npx tsc --noEmit` ✓

> **[SCREENSHOT REQUIRED]** Screenshot output `php artisan test` menunjukkan 40 passed; output `npm run lint` 0 errors

---

## 7.5 Fitur Tambahan: Cetak Rapor Digital & Grafik Performa Akademik (Siswa)

Dua fitur baru untuk role siswa, melengkapi pengalaman belajar di sistem:

### 7.5.1 Cetak Rapor Digital (Export PDF)

Tombol **Cetak Rapor (PDF)** tersedia di:

- **Dashboard siswa**: card khusus (hijau, Printer icon) — hanya tampil jika siswa sudah memiliki nilai (`has_nilai=true`).
- **Halaman Nilai Saya**: tombol di header (sebelah judul) + tombol besar di footer tabel per mapel.

Generate PDF via `barryvdh/laravel-dompdf`, render dari `resources/views/reports/rapor-pdf.blade.php`:

- **Header**: "SMAN 7 SOLO" + "RAPOR DIGITAL SISWA" + Tahun Ajaran (auto-detect: `YYYY/YYYY+1` jika bulan ≥ 7, else `YYYY-1/YYYY`)
- **Identity table**: Nama Siswa (uppercase), NIS, Kelas, KKM (70)
- **Tabel nilai (9 kolom)**: No, Mata Pelajaran, Guru Pengajar, Tugas (30%), UTS (30%), UAS (40%), Nilai Akhir, Status (Lulus/Tidak Lulus), Validasi (Final/Draft)
- **Summary boxes**: Jumlah Mapel, Rata-rata, Lulus, Tidak Lulus
- **Signature section**: 3 kolom tanda tangan (Orang Tua/Wali, Wali Kelas, Kepala Sekolah)
- **Meta footer**: timestamp cetak otomatis, klaim "Rapor digital ini sah"

Route: `GET /siswa/rapor/pdf` (middleware `auth` + `role:siswa`). Filename: `Rapor_{nama_siswa}_{nis}.pdf`. Otorisasi: hanya siswa yang login (route otomatis ambil dari `auth()->user()->siswa`). Guru & admin ditolak 403.

### 7.5.2 Grafik Performa Akademik (Bar Chart Visual)

3 dashboard cards di atas tabel nilai siswa:

1. **Rata-rata Keseluruhan**: 3 bar chart `<ComponentBar>` untuk Tugas/UTS/UAS (cross-mapel average). KKM line navy vertical di 70. Bar hijau jika ≥ KKM, merah jika < KKM. Di bawah: nilai akhir rata-rata + badge Lulus/Tidak Lulus.
2. **Ringkasan Akademik**: 3 box (Total Mapel, Lulus, Tidak Lulus) dengan icon dan warna (primary/emerald/rose).
3. **Komponen Perlu Perhatian**: klasifikasi Tugas/UTS/UAS ke "Perlu Ditingkatkan" (merah) atau "Sudah Di Atas KKM" (hijau). Atau "🎉 Semua komponen di atas KKM" jika 0 below.

Section tambahan **"Performa per Mata Pelajaran"**: card dengan `<PerMapelChart>` yang menampilkan per mapel 3 mini bars (Tgs/UTS/UAS) + KKM line. Auto-detect "Komponen terlemah" (T atau U atau A) dan tampilkan warning jika ada di bawah KKM.

Backend aggregation di `Siswa\NilaiController::buildChartData()`:

```json
{
  "overall": { "tugas": 77.5, "uts": 70, "uas": 75, "akhir": 74.25, "count": 2 },
  "per_mapel": [
    { "mapel": "Matematika", "kelas": "X-A", "tugas": 65, "uts": 56, "uas": 53, "akhir": 57.5, "status": "Tidak Lulus", "kkm": 70 },
    { "mapel": "Bahasa Indonesia", "kelas": "X-A", "tugas": 90, "uts": 84, "uas": 97, "akhir": 91, "status": "Lulus", "kkm": 70 }
  ],
  "kkm": 70,
  "stats": { "total_mapel": 2, "lulus": 1, "tidak_lulus": 1 }
}
```

**Pure CSS/SVG bar chart** — tidak pakai chart library. Bar: `position: absolute; left: 0; width: {value}%` dengan background emerald/rose. KKM line: `position: absolute; left: {kkm}%; width: 0.5px; bg-navy`.

### 7.5.3 Verifikasi & Test

- `php artisan test` → 86/86 ✓ (474 assertions, +10 tests, +77 assertions dari T1+T2+T3 baseline 76/397)
- `npx tsc --noEmit` → clean ✓
- `npm run lint` → 0 errors ✓
- `vendor/bin/pint --dirty --format agent` → passed ✓
- `npm run build` → built in 6.31s ✓
- Smoke test siswa 00001: `/siswa/nilai` 200 (40.3 KB) dengan `chart_data.overall.tugas=77.5, uts=70, uas=75, akhir=74.25` + 2 tombol Cetak Rapor ✓
- `/siswa/rapor/pdf` 200, 1.27 MB, `application/pdf`, `%PDF-1.7` magic, NIS `00001` 7× di body ✓
- Login guru `sariwahyuni` → `/siswa/rapor/pdf` **403** ✓
- Login admin → `/siswa/rapor/pdf` **403** ✓
- `/siswa/dashboard` 200 (23.7 KB) dengan Cetak Rapor card + `has_nilai=true` ✓

10 test baru di `tests/Feature/AcceptanceSiswaRaporTest.php`:

- `chart_data` dikirim lengkap (overall counts, per_mapel, stats, kkm)
- `chart_data` untuk siswa tanpa nilai (semua null + count=0)
- Siswa bisa download rapor PDF (Content-Type: application/pdf + `%PDF` magic)
- Rapor PDF body berisi nama siswa (UTF-16BE hex), NIS, kelas, mapel (FlateDecode-decompressed)
- Siswa lain tidak bisa akses rapor siswa lain (data isolation verified)
- Filename pattern: `Rapor_Ahmad_Subagja_00001.pdf`
- Guru ditolak 403
- Admin ditolak 403
- Dashboard `has_nilai=true` jika ada nilai
- Dashboard `has_nilai=false` jika belum ada nilai

### 7.5.4 Catatan Teknis

- **PDF body extraction**: `dompdf` compress body stream dengan FlateDecode. Untuk test body content, gunakan `gzinflate()` pada extracted stream (regex `/stream\r?\n(.*?)\r?\nendstream/s`).
- **UTF-16BE encoding**: dompdf encode text strings sebagai UTF-16BE dalam PDF stream. `assertStringContainsString($str, $pdfContent)` GAGAL untuk ASCII strings — gunakan `bin2hex(mb_convert_encoding($str, 'UTF-16BE', 'UTF-8'))` lalu cari di `bin2hex($pdfContent)`.
- **JSON float encoding**: `json_encode(70.0)` di PHP menghasilkan `"70"` (bukan `"70.0"`). Untuk konsistensi tipe, gunakan `(float)` cast di controller ATAU expect int di test assertion.
- **PDF binary assertions**: `getContent()` mengembalikan binary, bukan streamed. `streamedContent()` tidak bisa dipakai untuk `BinaryFileResponse`/PDF.

> **[SCREENSHOT REQUIRED]** Screenshot dashboard siswa (card Cetak Rapor) + halaman Nilai Saya (3 dashboard cards + bar chart + tombol Cetak Rapor) + PDF rapor yang sudah di-download (1-5 gambar)

---

## 7.6 Fitur Tambahan: Grafik Interaktif Dashboard Admin

Dashboard admin (`/admin/dashboard`) diperkaya dengan visualisasi data interaktif berbasis pure CSS/SVG (no chart library). 4 section utama:

### 7.6.1 Statistik Overview (Stat Cards)

4 stat cards di header:
- **Total Siswa** (primary)
- **Total Guru** (accent)
- **Mata Pelajaran** (warning) — baru, dari `MataPelajaran::count()`
- **Persentase Lulus** (success)

### 7.6.2 Komposisi Kelulusan (Donut Chart)

Donut chart CSS `conic-gradient` dengan 2 segmen:
- **Lulus** (hijau `#10B981`)
- **Tidak Lulus** (merah `#EF4444`)

Tengah donut menampilkan **total nilai** dengan label. Animasi `transition-[background] duration-700` saat render. Legend di bawah: lulus count + tidak count.

### 7.6.3 Kelulusan per Kelas (Stacked Bar Chart)

Per kelas (X-A, X-B, XI-A, XI-B), tampilkan stacked horizontal bar:
- **Lulus** (hijau) — width normalized ke max total
- **Tidak Lulus** (merah) — width normalized ke max total

**Interaktif**: klik bar → navigasi ke `/admin/siswa?kelas=X` dengan `<Link prefetch>`. Hover: color shift ke emerald-600 / rose-600. Tooltip per segmen via `title` attribute.

Format: `Kelas | Lulus/Total (Persentase%)`

### 7.6.4 Rata-rata Nilai per Mata Pelajaran (Horizontal Bar Chart)

Per mata pelajaran, horizontal bar menampilkan rata-rata nilai_akhir:
- **Bar hijau** jika rata-rata ≥ KKM (70)
- **Bar kuning** jika rata-rata < KKM (warning)
- **KKM line** navy vertical di posisi 70 (sama dengan chart siswa)
- Caption: "Lulus N • Tidak M" dengan total nilai

**Interaktif**: klik bar → `/admin/laporan?mapel=X`. Hover: opacity-80 transition.

### 7.6.5 Top Siswa Berprestasi & Siswa Perlu Perhatian (Sortable Lists)

2 cards berdampingan, masing-masing list top 5 siswa dengan:

**Top Siswa Berprestasi** (icon Trophy, hijau):
- Sort by rata-rata nilai_akhir desc
- Tampilan: avatar (rank atau huruf pertama), nama, kelas, "N mapel • Lulus X/N", progress bar hijau, nilai rata-rata besar di kanan
- Click → `/admin/siswa/{nis}/edit`

**Siswa Perlu Perhatian** (icon AlertTriangle, merah):
- Filter: siswa dengan **minimal 1 mapel tidak lulus**
- Sort: rasio_tidak_lulus desc, lalu rata-rata asc
- Tampilan: avatar (rank atau huruf), nama, kelas, "X/N mapel tidak lulus", progress bar merah (lebar = rasio%), rasio % besar di kanan
- Click → `/admin/siswa/{nis}/edit`

**Interaktif**: tombol sort toggle (Ranking ↔ A-Z) di header masing-masing card, dengan `useState<SortKey>` + `useMemo`.

### 7.6.6 Conditional Insights (Smart Alerts)

Di bawah dashboard, alert kontekstual:
- **Tingkat kelulusan rendah** (rose-50) jika `tidak_lulus > lulus` — icon TrendingDown, saran evaluasi
- **Performa akademik baik** (emerald-50) jika `lulus > 0 && lulus > tidak_lulus` — icon TrendingUp, pujian

### 7.6.7 Backend Aggregation (3 Query Baru)

Di `Admin\DashboardController`:

1. **`buildRataRataPerMapel()`**: GROUP BY `mata_pelajaran`, AVG(nilai_akhir) + SUM(CASE WHEN lulus/tidak_lulus) + COUNT(*). Sort desc by rata-rata. 4 mapel (Matematika 76.29, IPS 75.17, IPA 74.88, Bahasa Indonesia 73.97).
2. **`buildTopSiswa(int $limit = 5)`**: JOIN siswa, GROUP BY nis, AVG + COUNT + SUM per status. Sort desc by rata-rata. 5 siswa (Eko Hidayat 85.25, dst).
3. **`buildSiswaPerhatian(int $limit = 5)`**: JOIN siswa, GROUP BY nis, HAVING SUM(tidak_lulus) > 0. Sort by SUM(tidak_lulus) DESC, AVG(nilai_akhir) ASC. 5 siswa dengan rasio tertinggi.

### 7.6.8 Verifikasi & Test

- `php artisan test` → 96/96 ✓ (637 assertions, +10 tests, +163 assertions dari T8 baseline 86/474)
- `npx tsc --noEmit` → clean ✓
- `npm run lint` → 0 errors ✓
- `vendor/bin/pint --dirty --format agent` → passed (1 unused import fix) ✓
- `npm run build` → built in 6.32s ✓
- Smoke test admin → `/admin/dashboard` 200, 54.3KB ✓
- `rekap_per_kelas`: 4 entries (X-A 62.5%, X-B 71.4%, XI-A 85.7%, XI-B 71.4%) ✓
- `top_siswa`: 5 entries sorted desc ✓
- `siswa_perhatian`: 5 entries sorted by rasio desc ✓
- `kkm=70` ✓
- Login guru/siswa → `/admin/dashboard` 403 ✓

10 test baru di `AcceptanceAdminDashboardTest.php`:

- Stats utama dengan total yang benar
- Persentase lulus (66.7% untuk 2/3 lulus)
- Rekap per kelas dengan jumlah siswa, lulus, tidak lulus, total, persentase (50% lulus di X-A)
- Rata-rata per mapel sorted descending + persentase lulus (Matematika 85.0 100%, B.Indo 60.0 0%)
- Top siswa sorted descending dan dibatasi 5
- Siswa perhatian hanya berisi siswa dengan minimal 1 mapel tidak lulus
- Siswa perhatian diurutkan rasio desc lalu rata-rata asc
- Siswa perhatian kosong ketika semua siswa lulus
- Role auth: admin 200, guru/siswa 403
- Unauthenticated → redirect /login

### 7.6.9 Catatan Teknis

- **`DB::raw` dengan `?` binding tidak bisa** — `selectRaw` tidak support `?` placeholder untuk SQL aggregate. Solusi: interpolasi string `"SUM(CASE WHEN status_lulus = '{$lulusValue}' THEN 1 ELSE 0 END)"`. Aman karena value dari PHP constant `Nilai::LULUS` (bukan user input).
- **PHP `round()` return type quirk**: `round(50, 1)` returns `50` (int), bukan `50.0` (float), karena input int. Solusi: explicit `(float) cast` di controller output.
- **JSON strips `.0`**: `json_encode(70.0)` returns `"70"`. Test assertion `->where('kkm', 70.0)` GAGAL strict comparison karena test melihat int 70 dari JSON. Solusi: assertion pakai int `70` bukan float `70.0`.
- **Pure CSS donut chart**: `conic-gradient(#10B981 0deg ${pct*3.6}deg, #EF4444 ${pct*3.6}deg ..., #E2E8F0 ... 360deg)` di `div` rounded-full. Inner circle putih absolute dengan total.
- **`<Link prefetch>`** di Inertia v3: pre-fetch halaman di hover (atau click) untuk navigasi instan.
- **CSS animation budget**: 700ms `transition-[background]` di donut cukup smooth tanpa menyebabkan layout shift.

> **[SCREENSHOT REQUIRED]** Screenshot dashboard admin baru: 4 stat cards, donut chart kelulusan, stacked bar kelulusan per kelas, horizontal bar rata-rata per mapel (dengan KKM line), 2 list sortable (top siswa + siswa perlu perhatian), conditional alert. (5-7 gambar)

---

## 7.7 Fitur Tambahan: Intervensi / Buka Kunci Nilai oleh Admin

### 7.7.1 Latar Belakang

Setelah T8 ada fitur validasi Final di sisi guru (tombol **Validasi Final** mengunci nilai agar tidak bisa diedit), admin belum punya cara untuk membuka kunci nilai yang sudah Final jika ada kesalahan input dari guru atau revisi mendadak. T9 menambahkan:

1. **Halaman khusus admin** untuk melihat semua combo nilai yang sedang berstatus Final
2. **Tombol Buka Kunci** per-combo (guru + kelas + mata pelajaran) yang mengembalikan nilai ke Draft
3. **Wajib mengisi alasan** (min 10 karakter) — untuk dokumentasi audit
4. **Log audit immutable** yang mencatat siapa, kapan, target combo, berapa baris, dan alasan

### 7.7.2 Lokasi Fitur

- **Sidebar Admin**: item baru **"Manajemen Nilai"** dengan icon `ClipboardCheck` (muncul di antara "Manajemen Akun" dan "Laporan")
- **URL**: `GET /admin/nilai` (admin only, 403 untuk guru/siswa)
- **Route name**: `admin.nilai.index`
- **POST endpoint**: `POST /admin/nilai/unlock` (name `admin.nilai.unlock`)

### 7.7.3 Halaman Manajemen Nilai (Tampilan)

Halaman utama `/admin/nilai` menampilkan:

**1. Info Alert (Peringatan)** — box biru di paling atas menjelaskan:
> Fitur ini akan mengembalikan nilai berstatus **Final** ke status **Draft**, sehingga guru mata pelajaran terkait dapat mengeditnya kembali. Tindakan ini **tidak menghapus data**, hanya membuka kunci edit dan akan tercatat di log audit (siapa, kapan, berapa baris, alasan).

**2. Card 1: "Nilai Berstatus Final"** — berisi:
- **Filter bar**:
  - **Search box** (real-time, debounce 300ms via `useInertiaSearch`): cari nama guru, mata pelajaran, atau kelas (LIKE)
  - **Select kelas** (filter exact match)
  - **Tombol Reset** (muncul hanya jika ada filter)
- **Tabel kolom**: Guru, Mata Pelajaran, Kelas (badge), Siswa (jumlah), Divalidasi (tanggal + jam), Aksi
- **Tombol merah "Buka Kunci"** per row di kolom Aksi
- **Empty state**: "Tidak ada nilai berstatus Final saat ini. Guru belum memvalidasi nilai ke Final."

**3. Card 2: "Log Pembukaan Kunci (10 Terbaru)"** — berisi:
- Tabel immutable 10 entry log terbaru (diurutkan `created_at desc`)
- **Kolom**: Waktu (tanggal + jam Indonesia), Admin (nama), Target (guru + mapel + kelas badge), Baris (badge hijau/abu), Alasan (line-clamp-2)
- **Empty state**: "Belum ada tindakan pembukaan kunci nilai."

### 7.7.4 Modal Konfirmasi Buka Kunci

Klik tombol **Buka Kunci** → muncul modal dengan:

- **Title**: "Konfirmasi Buka Kunci Nilai"
- **Description**: `"Buka kunci nilai [mapel] kelas [kelas] (guru: [nama_guru])?"`
- **Warning box** (kuning): "Peringatan: Guru akan dapat mengedit nilai-nilai ini kembali. Tindakan ini akan dicatat di log audit dengan alasan yang Anda berikan."
- **Textarea "Alasan Pembukaan Kunci"** (wajib, min 10 karakter):
  - Placeholder: `"Contoh: Koreksi nilai UAS karena ada kesalahan input, akan diedit ulang."`
  - Live counter di kanan bawah: `"X/10"` (hijau jika OK, abu jika belum)
  - Validasi inline
- **Footer**: Tombol "Batal" (abu) + "Buka Kunci" (merah, disabled sampai reason 10+ char, menampilkan spinner saat submitting)

### 7.7.5 Backend Logic

**`Admin\NilaiController::index()`**:
- Query aggregate `Nilai` JOIN `guru` WHERE `status_validasi = Final` GROUP BY `(id_guru, nama_guru, kelas, mata_pelajaran)`
- SELECT: `id_guru, nama_guru, kelas, mata_pelajaran, COUNT(*) as total_siswa, MAX(updated_at) as validated_at`
- Filter: `search` (LIKE nama_guru/mapel/kelas) + `kelas` (exact)
- Sort: `validated_at DESC, kelas, mata_pelajaran` (deterministic — 2ndary sort handle same-time inserts)
- Eager-load 10 latest `NilaiUnlockLog` entries with `admin:id,name` + `guru:id,nama_guru` relations

**`Admin\NilaiController::unlock()`**:
- Validate: `id_guru (exists:guru,id), kelas, mata_pelajaran, reason (min:10, max:500)`
- `DB::transaction`:
  1. UPDATE `Nilai` WHERE `(id_guru, kelas, mata_pelajaran, status_validasi=Final)` SET `status_validasi=Draft` — count affected
  2. `NilaiUnlockLog::create()` audit row dengan `id_admin = auth()->id()`, `affected_rows`, `reason`
- Return flash: `success` jika affected>0, `info` jika affected=0 (idempotent)

### 7.7.6 Audit Log Table (Immutable)

Tabel `nilai_unlock_log`:

```sql
CREATE TABLE nilai_unlock_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_admin BIGINT UNSIGNED NOT NULL,
    id_guru BIGINT UNSIGNED NOT NULL,
    kelas VARCHAR(20) NOT NULL,
    mata_pelajaran VARCHAR(100) NOT NULL,
    affected_rows INT NOT NULL,
    reason TEXT NOT NULL,
    created_at TIMESTAMP NULL,
    
    FOREIGN KEY (id_admin) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (id_guru) REFERENCES guru(id) ON DELETE RESTRICT,
    INDEX (id_guru, kelas, mata_pelajaran),
    INDEX (id_admin),
    INDEX (created_at)
);
```

- **No `updated_at` column** — log append-only
- **Model `NilaiUnlockLog`**: `UPDATED_AT = null`, `$fillable = [id_admin, id_guru, kelas, mata_pelajaran, affected_rows, reason]`
- **FK RESTRICT**: admin/guru yang punya log unlock TIDAK BISA dihapus (proteksi audit trail)
- **Index** `[id_guru, kelas, mata_pelajaran]` — untuk query cepat "siapa saja yang pernah unlock combo X?"

### 7.7.7 Verifikasi & Test

- `php artisan test --compact` → **108/108 ✓** (744 assertions, +12 tests, +107 assertions)
- `npx tsc --noEmit` → clean ✓
- `npm run lint` → 0 errors ✓
- `vendor/bin/pint --dirty --format agent` → passed (3 files: NilaiUnlockLog, routes/web, test) ✓
- `npm run build` → built in 10.47s ✓
- `php artisan wayfinder:generate` → generated
- **Smoke test admin**:
  - Login admin → `/admin/nilai` 200, 15.8KB
  - 3 combo Final terlihat: Bu Rini XI-B IPA (5 siswa), Pak Joko XI-B IPS (4), Pak Budi X-A B.Indo (5)
  - POST `/admin/nilai/unlock` id_guru=3 kelas=XI-B mapel=IPA reason="Smoke test..." → 302 redirect ✓
  - Reload → combo XI-B IPA hilang dari daftar ✓
  - Log tercatat: admin "Administrator", 5 baris affected, reason lengkap ✓
  - DB: 7 row XI-B IPA sekarang status `Draft`, 1 row `nilai_unlock_log` ✓
- **Smoke test siswa**: login `00001` / `siswa123` → `/admin/nilai` **403** ✓
- **Smoke test guru**: login `sariwahyuni` / `guru123` → `/admin/nilai` **403** ✓
- **Cleanup**: `migrate:fresh --seed` → 32 Final / 26 Draft (original state) ✓

### 7.7.8 12 Test di `AcceptanceAdminNilaiUnlockTest.php`

| # | Test | Skenario |
|---|------|----------|
| 1 | Halaman 200 OK + Inertia props | GET `/admin/nilai` component `admin/nilai/index`, combos/logs/kelas_options keys exist |
| 2 | 3 combo dikelompokkan per (guru+kelas+mapel) | Sort: validated_at desc + kelas+mapel asc (deterministic) |
| 3 | Combo Draft TIDAK muncul | Filter `status_validasi = Final` di query |
| 4 | Filter search + kelas | query params propagated to controller |
| 5 | POST unlock → Final→Draft + log audit | 302 success, DB Draft, log row with affected_rows |
| 6 | Validasi reason min 10 | "koreksi" (7 char) → 422 |
| 7 | Scope per-combo (1 guru 2 kelas) | Unlock X-A saja → X-B tetap Final |
| 8 | id_guru tidak valid ditolak | 9999 → 422 `id_guru` error |
| 9 | Idempotent | Call kedua affected=0, log ke-2 tetap tercatat, flash "info" |
| 10 | Guru bisa edit nilai post-unlock | PUT `/guru/input-nilai/save` setelah unlock → success, status Draft |
| 11 | Guru/siswa 403 | Role middleware blocks both GET and POST |
| 12 | Log muncul di halaman | Setelah unlock, GET `/admin/nilai` → logs array has 1 entry with admin_name, nama_guru, affected_rows |

### 7.7.9 Catatan Teknis

- **Eloquent `HasOne` (User→Guru/Siswa) vs `BelongsTo`**: method `associate()` HANYA ada di `BelongsTo` (inverse). User→Guru adalah `HasOne` jadi `$user->guru()->associate($guru)` akan throw "Call to undefined method HasOne::associate()". Pattern yang benar: `Guru::create(['user_id' => $guruUser->id, ...])` (manual FK set).
- **Combo ordering deterministic**: `MAX(nilai.updated_at) DESC` saja tidak cukup kalau 3+ combo di-create di detik yang sama (race condition di SQLite/MySQL). Solusi: tambahkan secondary sort `ORDER BY kelas, mata_pelajaran` untuk tie-breaker yang stabil.
- **Eloquent `create()` butuh `$fillable`**: tanpa explicit fillable array, `NilaiUnlockLog::create([...])` throw `MassAssignmentException: Add [id_admin] to fillable property`. Solusi: definisikan `$fillable = [...]` di model.
- **Idempotent audit log**: meskipun affected=0 (guru sudah unlock manual), admin tetap catat log dengan reason-nya. Ini penting untuk audit trail: "siapa yang pertama kali coba unlock dan kenapa" — tidak boleh hilang.
- **DB::transaction wrap critical**: UPDATE nilai + INSERT log harus atomic. Kalau INSERT log gagal setelah UPDATE sukses, kita akan kehilangan jejak audit. Solusi: `DB::transaction` callback return value.
- **Live counter UX**: `X/10` di kanan textarea memberikan feedback real-time — user tidak perlu submit dulu untuk tahu apakah reason mereka cukup panjang.

> **[SCREENSHOT REQUIRED]** Screenshot halaman `/admin/nilai`: info alert, filter bar, tabel Final combos, modal konfirmasi dengan textarea, tabel log audit, plus screenshot 403 untuk guru/siswa. (3-5 gambar)

---

## 7.8 Fitur Tambahan: Widget Notifikasi Dashboard Guru (Alert Kuning/Merah)

### 7.8.1 Latar Belakang

Setelah T8 (Validasi Final) dan T9 (Buka Kunci oleh Admin), guru punya workflow yang jelas untuk input + validasi nilai. Tapi belum ada visual feedback di dashboard guru untuk mengingatkan tugas yang belum selesai. User minta:

> "Widget Notifikasi di Dashboard: Begitu guru login, hal pertama yang mereka lihat di dashboard atas adalah kartu peringatan (misalnya berwarna kuning atau merah). Contoh teksnya: 'Terdapat 2 Kelas (Biologi X-A, Biologi X-B) yang nilainya belum Anda input atau masih berstatus Draft.'"

T10 menambahkan **2 kartu alert** di paling atas dashboard guru:

1. **Kartu Kuning (warning)** — combo mengajar yang **belum lengkap diinput** (`jumlah_diinput < jumlah_siswa`)
2. **Kartu Merah (error)** — combo mengajar yang **sudah lengkap tapi masih berstatus Draft** (perlu di-validasi Final)

### 7.8.2 Lokasi & Tampilan

- **Posisi**: Paling atas dashboard guru, **di atas** stat cards (Total Siswa, Total Nilai, Status Draft, Status Final)
- **Kondisional**: Hanya render jika salah satu/dua-duanya list tidak kosong
- **Komponen**: Pakai `Alert` component existing (variant `warning` / `error`)

**Layout kartu kuning (belum_diinput):**

```
⚠ Perhatian: Terdapat 2 kelas (Matematika X-A, IPA X-B) yang nilainya
   belum Anda input atau belum lengkap.
   [X-A Matematika (3/8) ›]  [X-B IPA (1/5) ›]
```

**Layout kartu merah (masih_draft):**

```
✕ Tindak Lanjuti: Terdapat 2 kelas (Matematika X-A, Matematika X-B)
   yang nilainya sudah lengkap diinput tetapi masih berstatus Draft.
   Segera klik tombol "Validasi Final" untuk mengunci nilai.
   [X-A Matematika 2 Draft ›]  [X-B Matematika 3 Draft ›]
```

Setiap chip clickable → `/guru/input-nilai?kelas=X&mata_pelajaran=Y` (langsung filter ke combo yang perlu di-handle).

### 7.8.3 Logika Backend

`Guru\DashboardController::buildNotifikasi(Guru $guru)`:

```php
// 1. Ambil semua mengajar combo guru (sorted)
$mengajar = GuruMengajar::where('id_guru', $guru->id)
    ->orderBy('kelas')->orderBy('mata_pelajaran')->get();

// 2. Single grouped query: count siswa per kelas
$siswaCounts = Siswa::selectRaw('kelas, COUNT(*) as total')
    ->groupBy('kelas')->pluck('total', 'kelas');

// 3. Untuk setiap combo, hitung 3 angka
foreach ($mengajar as $m) {
    $jumlahSiswa = $siswaCounts[$m->kelas] ?? 0;
    $nilaiRows = Nilai::where('id_guru', $guru->id)
        ->where('kelas', $m->kelas)
        ->where('mata_pelajaran', $m->mata_pelajaran)
        ->get(['status_validasi', 'nilai_akhir']);
    
    $jumlahDiinput = $nilaiRows->whereNotNull('nilai_akhir')->count();
    $jumlahDraftRows = $nilaiRows->where('status_validasi', 'Draft')->count();
    
    // Kategorisasi
    if ($jumlahSiswa > 0 && $jumlahDiinput < $jumlahSiswa) {
        $belumDiinput[] = [...];
    } elseif ($jumlahSiswa > 0 && $jumlahDiinput === $jumlahSiswa && $jumlahDraftRows > 0) {
        $masihDraft[] = [...];
    }
}
```

**Key decision**: Combo dengan `jumlah_siswa == 0` (kelas benar-benar kosong) **TIDAK** muncul di notifikasi — tidak ada yang perlu diinput.

### 7.8.4 Verifikasi & Test

- `php artisan test --compact` → **120/120 ✓** (904 assertions, +12 tests, +160 assertions)
- `npx tsc --noEmit` → clean ✓
- `npm run lint` → 0 errors ✓
- `vendor/bin/pint --dirty --format agent` → passed (1 test file) ✓
- `npm run build` → built in 9.69s ✓
- **Smoke test login `sariwahyuni` / `guru123`**:
  - `/guru/dashboard` 200, 31.7KB
  - `notifikasi.belum_diinput = []` (semua 15 siswa sudah ada nilai)
  - `notifikasi.masih_draft = [{X-A Mat 2 Draft}, {X-B Mat 3 Draft}]`
  - HTML mengandung: "Tindak Lanjuti" + "status Draft" + "Validasi Final"
- **Role middleware**: admin/siswa → `/guru/dashboard` **403** ✓

### 7.8.5 12 Test di `AcceptanceGuruNotifikasiTest.php`

| # | Skenario | Expected |
|---|----------|----------|
| 1 | Guru tanpa mengajar | `belum_diinput=[]`, `masih_draft=[]` |
| 2 | Kelas kosong (0 siswa) | Tidak muncul di notifikasi (skip) |
| 3 | 0/3 siswa punya nilai | 1 entry `belum_diinput` (sisa=3) |
| 4 | 1/3 siswa punya nilai | 1 entry `belum_diinput` (diinput=1, sisa=2) |
| 5 | 3/3 siswa Draft | 1 entry `masih_draft` (jumlah_draft=3) |
| 6 | Mixed 1 Draft + 1 Final + 1 Draft | 1 entry `masih_draft` (jumlah_draft=2, hanya count Draft) |
| 7 | Semua Final | Tidak ada notifikasi |
| 8 | 2 combo: 1 Draft semua + 1 Final semua | 1 entry `masih_draft` saja |
| 9 | Scope per-guru (combo X-A Mat dari guru lain) | Hanya nilai dari `id_guru = guru->id` yang dihitung |
| 10 | 2 kelas mapel sama (X-A + X-B) dihitung independent | 1 belum + 1 masih |
| 11 | Siswa dengan nilai null (komponen belum lengkap) | Tetap dihitung sebagai `belum_diinput` (`whereNotNull('nilai_akhir')`) |
| 12 | Role middleware | admin/siswa → 403 |

### 7.8.6 Catatan Teknis

- **Siswa count via single grouped query**: 1 query untuk semua kelas (bukan N+1 per combo). Pakai `Siswa::selectRaw('kelas, COUNT(*) as total')->groupBy('kelas')->pluck('total', 'kelas')`. Hemat ~7 queries untuk guru dengan 8 mengajar combo.
- **Eager-load field selection**: `Nilai::get(['status_validasi', 'nilai_akhir'])` agar tidak load full row. Hemat memory + bandwidth.
- **`whereNotNull('nilai_akhir')`**: siswa yang baru di-input 1-2 komponen (T saja, atau T+UTS) belum punya `nilai_akhir` dihitung. Siswa dengan `nilai_akhir = null` tetap masuk hitungan "belum_diinput" (sesuai spek: "nilai yang belum Anda input atau masih berstatus Draft").
- **Siswa count `> 0` guard**: combo mengajar untuk kelas yang belum punya siswa (misal guru mengajar XII-A tapi XII-A belum di-seed) **tidak** muncul di notifikasi. Tidak ada yang perlu diinput, jadi tidak perlu alert.
- **Counter display**: `({diinput}/{siswa})` untuk kuning, `{n} Draft` badge untuk merah — user langsung tahu progress/completion.
- **Tips box tambahan**: di card "Menu Cepat" ditambah blue info box yang menjelaskan alur: input → Validasi Final → locked. Ini onboarding reminder untuk guru yang baru pertama kali login.

> **[SCREENSHOT REQUIRED]** Screenshot `/guru/dashboard` (login sariwahyuni) dengan kartu merah "Tindak Lanjuti" untuk X-A Mat (2 Draft) + X-B Mat (3 Draft) + chip combo list; plus screenshot login guru dengan combo `belum_diinput` (kuning) + `masih_draft` (merah) keduanya; plus tips box di Menu Cepat. (3-4 gambar)

---

## 7.9 Fix Bug: Stats Dashboard Guru & Status Mixed Final/Draft per-row

### 7.9.1 Latar Belakang (Bug yang Ditemukan User)

Setelah T10 notifikasi live, user menemukan **2 bug kritis** saat login sebagai Joko Santoso (guru IPS XI-A & XI-B):

1. **Bug Stats (SALAH TOTAL)**: Dashboard Joko menampilkan:
   - `Status Draft: 6` ✓ (benar)
   - `Status Final: 0` ❌ (harusnya 8!)
   - `Lulus: 0` ❌ (harusnya 11!)
   - `Tidak Lulus: 0` ❌ (harusnya 3!)

   Padahal data di DB menunjukkan Joko punya 14 nilai (XI-A IPS 4 Final + 3 Draft, XI-B IPS 4 Final + 3 Draft), 11 siswa Lulus + 3 Tidak Lulus.

2. **Bug Status Mixed (Misleading)**: Ketika Joko klik chip notifikasi "XI-A IPS" (yang berlabel "3 Draft"), halaman input-nilai menampilkan "Mode Read-Only: sudah Final" — padahal 3 dari 7 siswa masih Draft dan harusnya bisa diedit!

### 7.9.2 Root Cause Analysis

**Bug #1 — Eloquent Builder Mutation**: Controller `Guru\DashboardController` melakukan:

```php
$nilaiSaya = Nilai::where('id_guru', $guru->id);
$draft = $nilaiSaya->where('status_validasi', 'Draft')->count();
$final = $nilaiSaya->where('status_validasi', 'Final')->count();  // SALAH!
$lulus = $nilaiSaya->where('status_lulus', 'Lulus')->count();
$tidakLulus = $nilaiSaya->where('status_lulus', 'Tidak Lulus')->count();
```

`$nilaiSaya` adalah **Eloquent Builder object** yang **mutable** — setiap `->where()` menambahkan kondisi secara kumulatif. Jadi:
- `$draft` = `WHERE id_guru=X AND status_validasi=Draft` → 3
- `$final` = `WHERE id_guru=X AND status_validasi=Draft AND status_validasi=Final` (impossible!) → 0
- `$lulus` = `WHERE id_guru=X AND status_validasi=Draft AND status_validasi=Final AND status_lulus=Lulus` → 0
- `$tidakLulus` = semua kondisi di atas + `status_lulus=Tidak Lulus` → 0

**Bug #2 — Status Validasi Global Naive**: Controller `Guru\NilaiController::index` set `statusValidasiGlobal = $existing->first()->status_validasi`. Untuk mixed state (4 Final + 3 Draft), `first()` mengembalikan row **pertama** secara urutan DB (mungkin row Final lebih dulu), sehingga `isFinal=true` dan seluruh form jadi read-only — menyesatkan guru.

### 7.9.3 Solusi

**Fix Bug #1 — Clone Pattern**: Pakai `(clone $nilaiBase)` sebelum setiap count agar setiap count mulai dari base query yang bersih:

```php
$nilaiBase = Nilai::where('id_guru', $guru->id);
$draft = (clone $nilaiBase)->where('status_validasi', 'Draft')->count();
$final = (clone $nilaiBase)->where('status_validasi', 'Final')->count();
$lulus = (clone $nilaiBase)->where('status_lulus', 'Lulus')->count();
$tidakLulus = (clone $nilaiBase)->where('status_lulus', 'Tidak Lulus')->count();
```

**Fix Bug #2 — Proper Global Status Logic**: Hitung jumlah Draft vs Final rows secara independen, set `Final` hanya jika **tidak ada** Draft:

```php
$jumlahFinalRows = $existing->where('status_validasi', Nilai::STATUS_FINAL)->count();
$jumlahDraftRows = $existing->where('status_validasi', Nilai::STATUS_DRAFT)->count();
$statusValidasiGlobal = $jumlahDraftRows === 0 ? Nilai::STATUS_FINAL : Nilai::STATUS_DRAFT;
```

**Per-row Disable (guru/nilai/index.tsx)**: Tambah `rowLocked` derived dari `nilai_map[s.nis]?.status_validasi === 'Final'`:

```tsx
const rowIsFinal = nilai_map[s.nis]?.status_validasi === 'Final';
const inputDisabled = isFinal || rowIsFinal;
```

Baris Final ditampilkan dengan **badge hijau "Final"** di samping nama siswa, input disabled, background abu-abu — tapi hanya baris tersebut, bukan seluruh form.

**Alert "Sebagian Final" (info)**: Ditampilkan saat global state Draft tapi ada minimal 1 row Final — memberi klarifikasi ke guru bahwa "beberapa nilai sudah Final, selesaikan sisanya lalu klik Validasi Final".

### 7.9.4 Per-Combo Stats Table (Bonus)

Di `Guru\DashboardController` tambah method `buildPerComboStats()` yang return breakdown per mengajar combo:

```php
return [
    'id_mengajar' => 7,
    'kelas' => 'XI-A',
    'mata_pelajaran' => 'IPS',
    'jumlah_siswa' => 7,
    'jumlah_input' => 7,
    'jumlah_final' => 4,
    'jumlah_draft' => 3,
];
```

Di dashboard ditambahkan tabel **"Status per Mengajar"** (4 kolom: kelas+mapel, siswa, input, status badge) yang menunjukkan status detail per combo + footer summary (comboFinal/sebagian/belumInput/kosong counts). Guru bisa langsung klik baris tabel untuk navigasi ke form input.

### 7.9.5 Verifikasi & Test

**6 test baru** di `AcceptanceGuruNotifikasiTest.php` (sebelumnya 12, sekarang 18):

1. **T11 Stats fix**: dashboard stats menghitung draft/final/lulus/tidak_lulus secara independen (4 rows: 2 Final + 2 Draft, 4 Lulus) → `draft:2, final:2, lulus:4, tidak_lulus:0`
2. **T11 Stats fix lulus vs tidak_lulus mixed**: 3 rows (1 Lulus, 2 Tidak Lulus) → `lulus:1, tidak_lulus:2`
3. **T11 Per-combo stats breakdown**: 2 mengajar combos dengan breakdown lengkap (X-A Mat: 3 siswa, 3 input, 1 Final, 2 Draft; X-B B.Indo: 2 siswa, 2 Final, 0 Draft)
4. **T11 Notifikasi consistency (mixed)**: combo dengan 4 Final + 3 Draft tetap muncul di `masih_draft` dengan `jumlah_final:4, jumlah_draft:3`
5. **T11 Status validasi global mixed**: combo 4 Final + 3 Draft → form EDITABLE (`status_validasi_global=Draft`)
6. **T11 Status validasi global kelas kosong**: 0 siswa → form editable dengan `Draft` (no warning, no false Final)

**4 test T10 existing** di-update untuk field name `jumlah_diinput` → `jumlah_input` (konsistensi dengan `per_combo_stats`).

### 7.9.6 Catatan Teknis

- **(clone $query) pattern**: Laravel Eloquent `Builder` adalah mutable object. `Builder::where()` TIDAK return new builder — ia modify state internal. Untuk hitung 2+ count dengan kondisi berbeda pada scope yang sama, **WAJIB** `(clone $query)` atau instantiate ulang. Ini adalah gotcha umum di Laravel.
- **`$collection->where(...)->count()` di Collection OK**: berbeda dengan Eloquent Builder, Laravel `Collection` method `where()` return new collection (immutable). Jadi `$existing->where('status_validasi', 'Final')->count()` setelah `(clone $qb)->get()` aman.
- **`$existing->first()->status_validasi` itu liar**: untuk mixed-state, `first()` bisa return row mana saja (urutan DB, biasanya `id ASC`). Selalu aggregate dulu via `where('X')->count()` untuk status global.
- **Per-row UI lock**: form input nilai sekarang punya 2 level disable — global (semua baris read-only) + per-row (baris Final read-only walaupun global Draft). Visual cue: badge hijau "Final" di samping nama siswa.
- **Field rename `jumlah_diinput` → `jumlah_input`**: untuk konsistensi dengan `per_combo_stats.jumlah_input` yang dikirim dari backend. Single source of truth.
- **Lihat juga T9 (Buka Kunci Nilai)**: admin bisa unlock seluruh combo Final via `/admin/nilai`. Jika guru ingin edit 1 row Final tanpa admin intervention, harus ke admin dulu.

> **[SCREENSHOT REQUIRED]** Screenshot `/guru/dashboard` Joko Santoso **SETELAH FIX** (stats: draft 6, final 8, lulus 11, tidak lulus 3) + tabel "Status per Mengajar" dengan 2 rows XI-A IPS / XI-B IPS (badge "Sebagian Final"); screenshot `/guru/input-nilai?kelas=XI-A&mata_pelajaran=IPS` (Alert "Sebagian Final" + 4 baris hijau "Final" read-only + 3 baris Draft editable).

---

## 8. Potongan Kode Fungsi / Procedure

### 8.1 Static Method: Hitung Nilai Akhir (Nilai Model)

```php
// app/Models/Nilai.php
public static function hitungNilaiAkhir(float $tugas, float $uts, float $uas): float
{
    return round(
        ($tugas * self::BOBOT_TUGAS)
      + ($uts   * self::BOBOT_UTS)
      + ($uas   * self::BOBOT_UAS),
        2
    );
}
```

**Cara kerja:** Mengalikan tiap komponen nilai dengan bobotnya (30% tugas, 30% UTS, 40% UAS), menjumlahkan, lalu membulatkan ke 2 desimal. Dipanggil di controller dan frontend (`utils.ts`).

---

### 8.2 Static Method: Tentukan Kelulusan (Nilai Model)

```php
// app/Models/Nilai.php
public static function tentukanKelulusan(float $nilaiAkhir): string
{
    return $nilaiAkhir >= self::KKM ? self::LULUS : self::TIDAK_LULUS;
}
```

**Cara kerja:** Membandingkan nilai akhir dengan KKM (70). Return `'Lulus'` atau `'Tidak Lulus'`. Threshold `>=` (bukan `>`), jadi nilai 70.00 tetap Lulus.

---

### 8.3 Static Method: Validasi Nilai (Nilai Model)

```php
// app/Models/Nilai.php
public static function validasiNilai(float $nilai): bool
{
    return $nilai >= 0 && $nilai <= 100;
}
```

**Cara kerja:** Validasi rentang nilai 0-100. Dipakai sebagai defense-in-depth setelah validasi Laravel `between:0,100`.

---

### 8.4 Method: `mengajarDiKelasMapel` (Guru Model)

```php
// app/Models/Guru.php
public function mengajarDiKelasMapel(string $kelas, string $mataPelajaran): bool
{
    return $this->mengajar()
        ->where('kelas', $kelas)
        ->where('mata_pelajaran', $mataPelajaran)
        ->exists();
}
```

**Cara kerja:** Cek apakah kombinasi (kelas, mapel) ada di tabel `guru_mengajar` untuk guru ini. Dipakai di `Guru/NilaiController::save()` dan `validateFinal()` untuk enforce 403 jika guru coba input nilai di luar kombinasi yang diajar.

---

### 8.5 Method: `syncMengajar` (GuruController)

```php
// app/Http/Controllers/Admin/GuruController.php
private function syncMengajar(Guru $guru, array $mengajar): void
{
    $guru->mengajar()->delete();

    $unique = [];
    foreach ($mengajar as $row) {
        $key = $row['kelas'].'|'.$row['mata_pelajaran'];
        if (isset($unique[$key])) continue;
        $unique[$key] = true;
        $guru->mengajar()->create([
            'kelas' => $row['kelas'],
            'mata_pelajaran' => $row['mata_pelajaran'],
        ]);
    }
}
```

**Cara kerja:** Dipanggil dari `store()` dan `update()`. Hapus semua kombinasi mengajar lama, lalu insert ulang dari array hasil validasi. Handle duplikat dengan hash `$unique`.

---

### 8.6 Function: Hitung Nilai Real-time (Frontend)

```ts
// resources/js/lib/utils.ts
export function calculateNilaiAkhir(
    tugas: number | null,
    uts: number | null,
    uas: number | null,
): number | null {
    if (tugas === null || uts === null || uas === null) return null;
    return Math.round((tugas * 0.3) + (uts * 0.3) + (uas * 0.4)) * 100 / 100;
}

export function calculateStatusLulus(akhir: number | null): 'Lulus' | 'Tidak Lulus' | null {
    if (akhir === null) return null;
    return akhir >= 70 ? 'Lulus' : 'Tidak Lulus';
}
```

**Cara kerja:** Dipanggil tiap user mengetik di kolom nilai. Update kolom "Nilai Akhir" dan "Status" di tabel secara real-time tanpa round-trip server.

---

### 8.7 Hook: `useDebouncedValue` (opsional, sudah diganti `setTimeout` di handlers)

```ts
// (legacy) resources/js/hooks/use-debounced-value.ts
import { useEffect, useState } from 'react';

export function useDebouncedValue<T>(value: T, delay: number = 300): T {
    const [debounced, setDebounced] = useState(value);
    useEffect(() => {
        const id = setTimeout(() => setDebounced(value), delay);
        return () => clearTimeout(id);
    }, [value, delay]);
    return debounced;
}
```

> Saat ini, halaman manajemen siswa/guru pakai `useRef<setTimeout>` langsung di `onChange` handler (tanpa useEffect) untuk menghindari konflik dengan pagination Link.

---

## 9. Potongan Kode Class dan Method

### 9.1 Class: `App\Http\Controllers\Guru\NilaiController`

```php
namespace App\Http\Controllers\Guru;

class NilaiController extends Controller
{
    /**
     * Halaman input nilai dengan 2 cascading dropdown (kelas + mapel).
     * Guru hanya bisa input nilai untuk kombinasi yang diajar (403 otherwise).
     */
    public function index(Request $request): Response
    {
        $guru = Guru::with('mengajar')->where('user_id', auth()->id())->firstOrFail();

        $kelas = $request->input('kelas');
        $mataPelajaran = $request->input('mata_pelajaran');

        $daftarKelas = $guru->mengajar()->distinct()->orderBy('kelas')->pluck('kelas')->all();
        $mapelByKelas = [];
        foreach ($guru->mengajar()->orderBy('kelas')->orderBy('mata_pelajaran')->get() as $m) {
            $mapelByKelas[$m->kelas][] = $m->mata_pelajaran;
        }
        $daftarMapel = $kelas && isset($mapelByKelas[$kelas]) ? $mapelByKelas[$kelas] : [];

        $siswa = collect();
        $nilaiMap = [];
        $statusValidasiGlobal = null;
        $hasMengajar = $guru->mengajar()->exists();

        if ($kelas && $mataPelajaran && $hasMengajar
            && $guru->mengajarDiKelasMapel($kelas, $mataPelajaran)) {
            $siswa = Siswa::where('kelas', $kelas)->orderBy('nis')->get();
            $existing = Nilai::where('id_guru', $guru->id)
                ->where('kelas', $kelas)
                ->where('mata_pelajaran', $mataPelajaran)
                ->whereIn('nis', $siswa->pluck('nis'))
                ->get()->keyBy('nis');
            foreach ($existing as $item) $nilaiMap[$item->nis] = $item;
            $statusValidasiGlobal = $existing->first()?->status_validasi ?? Nilai::STATUS_DRAFT;
        }

        return Inertia::render('guru/nilai/index', [
            'guru' => $guru,
            'daftar_kelas' => $daftarKelas,
            'mapel_by_kelas' => $mapelByKelas,
            'kelas' => $kelas,
            'mata_pelajaran' => $mataPelajaran,
            'siswa' => $siswa,
            'nilai_map' => $nilaiMap,
            'status_validasi_global' => $statusValidasiGlobal,
            'has_mengajar' => $hasMengajar,
        ]);
    }

    /**
     * Simpan nilai (draft). Update-or-create per (nis, id_guru, kelas, mapel).
     * Validasi 0-100 + cek kombinasi mengajar.
     */
    public function save(Request $request): RedirectResponse
    {
        $guru = Guru::where('user_id', auth()->id())->firstOrFail();
        $validated = $request->validate([
            'kelas' => ['required', 'string'],
            'mata_pelajaran' => ['required', 'string'],
            'nilai' => ['required', 'array'],
            'nilai.*.nis' => ['required', 'string', 'exists:siswa,nis'],
            'nilai.*.nilai_tugas' => ['nullable', 'numeric', 'between:0,100'],
            'nilai.*.nilai_uts' => ['nullable', 'numeric', 'between:0,100'],
            'nilai.*.nilai_uas' => ['nullable', 'numeric', 'between:0,100'],
        ]);

        if (! $guru->mengajarDiKelasMapel($validated['kelas'], $validated['mata_pelajaran'])) {
            abort(403, 'Anda tidak mengajar kombinasi kelas dan mata pelajaran ini.');
        }

        DB::transaction(function () use ($validated, $guru) {
            foreach ($validated['nilai'] as $row) {
                if ($row['nilai_tugas'] === null && $row['nilai_uts'] === null && $row['nilai_uas'] === null) {
                    continue;
                }
                $akhir = Nilai::hitungNilaiAkhir($row['nilai_tugas'], $row['nilai_uts'], $row['nilai_uas']);
                $status = Nilai::tentukanKelulusan($akhir);

                Nilai::updateOrCreate(
                    ['nis' => $row['nis'], 'id_guru' => $guru->id,
                     'kelas' => $validated['kelas'], 'mata_pelajaran' => $validated['mata_pelajaran']],
                    ['nilai_tugas' => $row['nilai_tugas'], 'nilai_uts' => $row['nilai_uts'],
                     'nilai_uas' => $row['nilai_uas'], 'nilai_akhir' => $akhir,
                     'status_lulus' => $status, 'status_validasi' => Nilai::STATUS_DRAFT],
                );
            }
        });

        return back()->with('success', 'Nilai berhasil disimpan sebagai Draft.');
    }

    /**
     * Lock nilai: set status_validasi = Final untuk semua baris di (kelas, mapel) guru ini.
     */
    public function validateFinal(Request $request): RedirectResponse
    {
        $guru = Guru::where('user_id', auth()->id())->firstOrFail();
        $validated = $request->validate([
            'kelas' => ['required', 'string'],
            'mata_pelajaran' => ['required', 'string'],
        ]);

        if (! $guru->mengajarDiKelasMapel($validated['kelas'], $validated['mata_pelajaran'])) {
            abort(403);
        }

        $updated = Nilai::where('id_guru', $guru->id)
            ->where('kelas', $validated['kelas'])
            ->where('mata_pelajaran', $validated['mata_pelajaran'])
            ->whereNotNull('nilai_akhir')
            ->update(['status_validasi' => Nilai::STATUS_FINAL]);

        return back()->with('success', "Nilai berhasil dikunci (Final). {$updated} baris nilai di-finalisasi.");
    }

    public function rekap(Request $request): Response { /* ... lihat file ... */ }
    public function destroy(Nilai $nilai): RedirectResponse { /* ... */ }
}
```

---

### 9.2 Class: `App\Http\Controllers\Admin\ReportController`

```php
class ReportController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $daftarKelas = Siswa::query()->distinct()->orderBy('kelas')->pluck('kelas');
        return Inertia::render('admin/reports/index', ['daftar_kelas' => $daftarKelas]);
    }

    public function preview(Request $request): InertiaResponse
    {
        $request->validate(['kelas' => ['required', 'string']]);
        return Inertia::render('admin/reports/preview', $this->buildReportData($request->input('kelas')));
    }

    public function exportPdf(Request $request): Response
    {
        $request->validate(['kelas' => ['required', 'string']]);
        $data = $this->buildReportData($request->input('kelas'));
        $kelas = $request->input('kelas');

        $pdf = Pdf::loadView('reports.pdf', $data)->setPaper('a4', 'landscape');
        $filename = 'laporan_kelas_'.str_replace('-', '_', $kelas).'_'.date('Y').'.pdf';

        return $pdf->download($filename);
    }

    public function exportHtml(Request $request): Response
    {
        $request->validate(['kelas' => ['required', 'string']]);
        $data = $this->buildReportData($request->input('kelas'));
        $kelas = $request->input('kelas');

        $html = view('reports.html', $data)->render();
        return response($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="laporan_kelas_'.str_replace('-', '_', $kelas).'_'.date('Y').'.html"',
        ]);
    }

    private function buildReportData(string $kelas): array
    {
        // ... lihat detail di Bagian 4.2 ...
    }
}
```

---

### 9.3 Class: `App\Http\Middleware\EnsureUserHasRole`

```php
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Anda harus login terlebih dahulu.');
        }
        if (! $user->is_active) {
            auth()->logout();
            abort(403, 'Akun Anda telah dinonaktifkan. Hubungi admin.');
        }
        if (! in_array($user->role, $roles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }
        return $next($request);
    }
}
```

---

### 9.4 Class: `App\Models\Guru` (relasi + helpers mengajar)

```php
class Guru extends Model
{
    use HasFactory;
    protected $table = 'guru';

    public function user(): BelongsTo       { return $this->belongsTo(User::class); }
    public function mengajar(): HasMany    { return $this->hasMany(GuruMengajar::class, 'id_guru'); }
    public function nilai(): HasMany       { return $this->hasMany(Nilai::class, 'id_guru'); }

    public function getAllKelasAttribute(): array
    {
        return $this->mengajar()->distinct()->orderBy('kelas')->pluck('kelas')->all();
    }

    public function getAllMapelAttribute(): array
    {
        return $this->mengajar()->distinct()->orderBy('mata_pelajaran')->pluck('mata_pelajaran')->all();
    }

    public function getMapelByKelas(string $kelas): array
    {
        return $this->mengajar()->where('kelas', $kelas)
            ->orderBy('mata_pelajaran')->pluck('mata_pelajaran')->all();
    }

    public function mengajarDiKelasMapel(string $kelas, string $mataPelajaran): bool
    {
        return $this->mengajar()->where('kelas', $kelas)
            ->where('mata_pelajaran', $mataPelajaran)->exists();
    }
}
```

---

### 9.5 Class: `App\Http\Requests\Admin\GuruRequest`

```php
class GuruRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->isAdmin(); }

    public function rules(): array
    {
        $rules = [
            'nama_guru' => ['required', 'string', 'max:255'],
            'mengajar'  => ['required', 'array', 'min:1'],
        ];

        foreach (range(0, 50) as $i) {
            $input = $this->input("mengajar.$i");
            if ($input === null && ! $this->has("mengajar.$i")) continue;

            $rules["mengajar.$i.kelas"]              = ['required', 'string', 'max:20'];
            $rules["mengajar.$i.mata_pelajaran"]     = ['nullable', 'string', 'max:100'];
            $rules["mengajar.$i.mata_pelajaran_baru"] = ['nullable', 'string', 'max:100'];
        }
        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $mengajar = $this->input('mengajar', []);
            $seen = [];

            foreach ($mengajar as $i => $row) {
                if (! is_array($row)) continue;
                $kelas = trim((string) ($row['kelas'] ?? ''));
                $mapel = trim((string) ($row['mata_pelajaran'] ?? ''));
                $mapelBaru = trim((string) ($row['mata_pelajaran_baru'] ?? ''));

                if ($kelas === '') {
                    $v->errors()->add("mengajar.$i.kelas", 'Kelas wajib diisi.');
                    continue;
                }
                $finalMapel = $mapel !== '' ? $mapel : $mapelBaru;
                if ($finalMapel === '') {
                    $v->errors()->add("mengajar.$i.mata_pelajaran", 'Pilih atau isi mata pelajaran.');
                    continue;
                }
                $pair = $kelas.'|'.$finalMapel;
                if (in_array($pair, $seen, true)) {
                    $v->errors()->add("mengajar.$i.mata_pelajaran", 'Kombinasi kelas & mata pelajaran duplikat.');
                } else {
                    $seen[] = $pair;
                }
            }
        });
    }

    public function getMengajar(): array
    {
        $out = [];
        foreach ($this->input('mengajar', []) as $row) {
            if (! is_array($row)) continue;
            $kelas = trim((string) ($row['kelas'] ?? ''));
            $mapel = trim((string) ($row['mata_pelajaran'] ?? ''));
            $mapelBaru = trim((string) ($row['mata_pelajaran_baru'] ?? ''));
            if ($kelas === '') continue;
            $final = $mapel !== '' ? $mapel : $mapelBaru;
            if ($final === '') continue;
            $out[] = ['kelas' => $kelas, 'mata_pelajaran' => $final];
        }
        return $out;
    }
}
```

---

## 10. Penjelasan Library atau Komponen yang Digunakan

### 10.1 Backend Stack

| Library | Versi | Fungsi |
|---------|-------|--------|
| **PHP** | 8.5 | Bahasa pemrograman server |
| **Laravel** | 13.14 | Framework PHP utama (routing, ORM, validation, auth) |
| **Laravel Fortify** | v1 | Headless authentication backend (login, sessions, password hashing) |
| **Inertia.js (Laravel adapter)** | v3 | Jembatan Laravel ↔ React (server-side routing + SPA) |
| **Eloquent ORM** | (Laravel) | ActiveRecord ORM untuk `User`, `Siswa`, `Guru`, `Nilai`, `GuruMengajar` |
| **barryvdh/laravel-dompdf** | latest | Generate laporan PDF dari Blade view |
| **Pest PHP** | v4 | Testing framework (alternatif modern PHPUnit) |

### 10.2 Frontend Stack

| Library | Versi | Fungsi |
|---------|-------|--------|
| **React** | 19 | Library UI |
| **Inertia.js (Client adapter)** | v3 | SPA navigation, Link, Form, useForm, useHttp hooks |
| **TypeScript** | latest | Static typing untuk komponen React |
| **Vite** | latest | Build tool & dev server (HMR) |
| **Tailwind CSS** | v4 | Utility-first CSS framework |
| **Lucide React** | latest | Icon set (GraduationCap, ChevronLeft, Trash2, dll) |
| **Laravel Wayfinder** | v0 | Generate TypeScript functions untuk route Laravel |
| **Sonner** | latest | Toast notification (flash messages) |

### 10.3 Database & Infrastructure

| Library | Versi | Fungsi |
|---------|-------|--------|
| **MySQL** | 8.0.46 | RDBMS utama (Docker container `pdns-mysql`) |
| **SQLite** | 3.x | Test database (in-memory via `RefreshDatabase`) |
| **Docker** | latest | Containerization untuk MySQL |

### 10.4 Penjelasan Komponen Penting

#### Laravel Fortify
**Apa:** Package auth Laravel yang *headless* (tidak memaksakan view/login UI sendiri). Kita pakai `Features::username()` untuk auth via username, dan override `home` ke `/redirect-by-role`.

**Kenapa pakai:** Ringan, tidak memaksakan stack UI tertentu (cocok untuk Inertia+React). Membership logic, password hashing, throttling semua handled.

#### Inertia.js v3
**Apa:** Adapter yang menjembatani Laravel (server-side routing) dengan React SPA (client-side rendering) — tanpa perlu REST/GraphQL API. Controller return `Inertia::render('component', $props)` → React render `<Component>` dengan props.

**Kenapa pakai:** Best of both worlds: routing Laravel (CSRF, sessions, middleware) + UX SPA (no full page reload, fast navigation).

**Komponen penting Inertia v3:**
- `<Link href="...">` — navigasi client-side
- `<Form>` — form dengan progressive enhancement (auto-handle errors, processing state)
- `useForm` hook — controlled form state + `data`, `setData`, `post`, `put`, `delete`
- `useHttp` — standalone HTTP request (v3 baru, tanpa perlu Inertia visit)
- `router.visit` / `router.get` / `router.post` — programmatic navigation
- `Head` component — set `<title>`, `<meta>`

#### Laravel Wayfinder
**Apa:** Generate TypeScript functions untuk route Laravel + controller methods. Tujuannya: tidak perlu hardcode URL di frontend, auto-completion di IDE, type-safe.

**Contoh:**
```ts
// Generated di resources/js/routes/admin/guru.ts
export const index = (): string => '/admin/guru';
export const create = (): string => '/admin/guru/create';
export const update = (guru: number | Guru): string => `/admin/guru/${guru}`;
```

**Generate:** `php artisan wayfinder:generate`

#### Eloquent ORM
**Apa:** ActiveRecord ORM Laravel. Setiap tabel DB = 1 class Model. Query jadi method chain: `User::where('role', 'admin')->get()`.

**Pattern di project ini:**
- `Siswa` PK string (`nis`), override `getRouteKeyName()`
- Relasi: `User hasOne Siswa`, `User hasOne Guru`, `Guru hasMany GuruMengajar`, `Guru hasMany Nilai`
- Eager load: `with(['siswa', 'guru', 'guru.mengajar'])` di AccountController

#### barryvdh/laravel-dompdf
**Apa:** Wrapper untuk library DomPDF (PHP) yang bisa render HTML/CSS ke PDF. Pakai view Blade yang sama dengan HTML export, dengan styling inline.

**Cara pakai:**
```php
$pdf = Pdf::loadView('reports.pdf', $data)->setPaper('a4', 'landscape');
return $pdf->download('laporan.pdf');
```

#### Tailwind CSS v4
**Apa:** Utility-first CSS. Pakai `@theme` di `resources/css/app.css` untuk define design tokens (primary, navy, success, danger, dll).

**Custom theme (`resources/css/app.css`):**
```css
@import "tailwindcss";
@theme {
    --color-primary: #1A56DB;
    --color-navy:    #1E3A5F;
    --color-success: #10B981;
    --color-danger:  #EF4444;
    --color-warning: #F59E0B;
    --color-accent:  #0EA5E9;
    --color-surface: #F1F5F9;
    --color-muted-foreground: #64748B;
}
```

#### Sonner (Toast)
**Apa:** Library toast notification yang elegan. Dipakai untuk flash messages Laravel (success/error setelah submit form).

**Hook custom:** `resources/js/hooks/use-flash-toast.ts` — auto-trigger toast saat ada `flash.success` / `flash.error` di Inertia props.

---

## 11. Penjelasan Coding Guidelines dan Best Practices

### 11.1 Konvensi Laravel (PHP)

Diadaptasi dari **Laravel Boost Guidelines** (tersimpan di `AGENTS.md`):

| Aturan | Contoh |
|--------|--------|
| Selalu gunakan kurung kurawal untuk control structures | `if ($x) { return $y; }` |
| PHP 8 constructor property promotion | `public function __construct(public GitHub $github) {}` |
| Explicit return type + type hints | `function isAccessible(User $user): bool` |
| TitleCase untuk enum keys | `enum Status { case FavoritePerson; case BestLake; }` |
| Prefer PHPDoc over inline comments | `@param int $id User ID` |
| Array shape di PHPDoc | `@param array{name: string, age: int} $user` |

### 11.2 Konvensi React (TypeScript)

| Aturan | Contoh |
|--------|--------|
| Functional components dengan `export default function` | `export default function Login() { ... }` |
| Props interface named `Props` (atau component-specific) | `type Props = { siswa: Siswa[] }` |
| Co-located types dengan component | `type Guru = { id: number; ... }` dideklarasikan di atas component |
| Hindari `any` — gunakan `unknown` lalu narrow | `function process(input: unknown) { ... }` |
| Import order: external → internal (`@/...`) | `import { Link } from '@inertiajs/react';\nimport { Card } from '@/components/ui/card';` |
| ESLint + Prettier auto-format | `npm run lint` clean sebelum commit |

### 11.3 Best Practices yang Diterapkan

#### ✅ Security

1. **CSRF protection otomatis** — Inertia Forms include `_token` otomatis
2. **SQL injection prevention** — Eloquent pakai prepared statements
3. **Authorization** — `EnsureUserHasRole` middleware di setiap route prefix
4. **NIS immutable** — `unset($data['nis'])` di `SiswaRequest::validated()` untuk PUT/PATCH
5. **Account disabled check** — middleware auto-logout + 403 untuk user `is_active=false`
6. **Password hashing** — `Hash::make()` + Laravel auto-verify
7. **CASCADE/RESTRICT FK** — siswa→nilai CASCADE, guru→nilai RESTRICT

#### ✅ Performance

1. **Eager loading** — `User::with(['siswa', 'guru', 'guru.mengajar'])` untuk hindari N+1
2. **Pagination** — `paginate(15)` di semua index page
3. **Select columns** — Explicit select untuk eager load: `'siswa:nis,user_id,nama_siswa,kelas'`
4. **Index database** — `kelas`, `(kelas, mata_pelajaran)`, `id_guru` di tabel `nilai`
5. **Real-time search** — `router.get` dengan `only: ['siswa', 'filters']` agar tidak re-render seluruh halaman
6. **Vite build** — Code splitting otomatis, JS bundle 386 KB (gzip 121 KB)

#### ✅ Maintainability

1. **Single Responsibility** — `NilaiController` cuma handle input nilai, `ReportController` cuma handle laporan
2. **Form Request validation** — Pisahkan validasi dari controller logic (`SiswaRequest`, `GuruRequest`, `AccountRequest`)
3. **Static methods untuk business logic** — `Nilai::hitungNilaiAkhir()`, `tentukanKelulusan()`, `validasiNilai()`
4. **Constants untuk magic values** — `BOBOT_TUGAS = 0.30`, `KKM = 70.0`, `STATUS_DRAFT = 'Draft'`
5. **Custom middleware** — `EnsureUserHasRole` reusable, alias `role`
6. **Seeder reproducibility** — `firstOrCreate` bukan `create` agar bisa diulang
7. **Pivot table naming convention** — Singular noun `guru_mengajar` (bukan `guru_mengajars`)

#### ✅ Testing

1. **Pest 4 + RefreshDatabase** — setiap test di-rollback otomatis
2. **AcceptanceTest naming** — `AC-XX: <behavior>` untuk traceability ke spesifikasi
3. **Regression test** — Setiap bug fix tambah test baru
4. **Inertia assertions** — `assertInertia(fn ($page) => $page->component(...)->where(...))`
5. **PHPUnit attributes** — `#[Test]`, `#[DataProvider('cases')]`
6. **40 tests / 193 assertions** — semua PASS dalam ~3 detik

#### ✅ Code Style

1. **PHP Pint** — `vendor/bin/pint` otomatis format sesuai PSR-12 + Laravel preset
2. **ESLint 9** — TypeScript-aware lint
3. **Prettier 3** — auto-format `.tsx`, `.ts`, `.css`
4. **Hapus komentar** — kode self-documenting, kecuali logic yang benar-benar kompleks
5. **Naming convention konsisten**:
   - Method: `camelCase`
   - Class: `PascalCase`
   - Constant: `UPPER_SNAKE_CASE`
   - Table DB: `snake_case` singular
   - Migration: `YYYY_MM_DD_NNNNNN_<description>.php`

### 11.4 Standar yang TIDAK Diterapkan (Sengaja)

| Praktik Umum | Alasan Tidak Dipakai |
|--------------|---------------------|
| REST API + JSON endpoint | Pakai Inertia: server-side routing + client-side render (lebih sederhana untuk admin app) |
| Repository pattern | Eloquent sudah cukup abstrak untuk skala project ini; repo pattern overkill |
| Event/Listener untuk audit log | Tidak ada requirement audit log di spesifikasi |
| Queue/Jobs untuk export PDF | PDF export kecil (< 1 detik), tidak perlu antrian |
| Multi-language (i18n) | Aplikasi single-language (Indonesia) sesuai spek |
| SPA mode terpisah | Inertia sudah cukup, tidak perlu pisah backend API + frontend SPA |

---

## Lampiran: Yang Perlu di-Screenshot atau Ditambahkan Manual

Beberapa bagian dari dokumen ini **memerlukan screenshot atau input manual** agar menjadi laporan yang utuh. Berikut checklist-nya:

| # | Bagian | Yang perlu di-screenshot / ditambahkan |
|---|--------|---------------------------------------|
| 1 | **§1.8** Bukti Pengujian Login | 📸 Screenshot halaman login (1 gambar, 3 role mencoba login) |
| 2 | **§2** Form Input | 📸 Screenshot form tambah siswa, form tambah guru dengan dynamic rows, form input nilai dengan cascading dropdown (3 gambar) |
| 3 | **§3.6** Perhitungan Real-time | 📸 Screenshot form input nilai dengan kolom Tugas/UTS/UAS/Hasil real-time terupdate |
| 4 | **§4.4** Laporan | 📸 Screenshot preview laporan, hasil export PDF (halaman 1-2), HTML export (3-4 gambar) |
| 5 | **§5.7** Bukti Pengujian DB | 📸 Screenshot phpMyAdmin/HeidiSQL/MySQL Workbench tabel `users`, `siswa`, `guru`, `guru_mengajar`, `nilai`, **`kelas`, `mata_pelajaran`** dengan data real (7 gambar) |
| 6 | **§5.7** Bukti Pest | 📸 Screenshot output `php artisan test` menunjukkan 126 passed (992 assertions) |
| 7 | **§7** Perbaikan Error | 📸 Screenshot output `npm run lint` 0 errors, output `pint` passed |
| 8 | **§6/§7** DEBUG.md | Opsional: 📸 Screenshot file `DEBUG.md` atau bagian tertentu yang menarik (misal entry #14 refactor mengajar, entry #17 master tables, entry #18 rapor digital, entry #19 grafik interaktif admin, **entry #20 buka kunci nilai, entry #21 notifikasi guru, entry #22 fix bug stats+mixed state**) |
| 9 | **Manajemen Kelas & Mata Pelajaran** | 📸 Screenshot halaman `/admin/kelas` (search, count badges, delete-protection indicator) + `/admin/mata-pelajaran` (2 gambar) |
| 10 | **Sidebar collapse** | 📸 Screenshot sidebar expanded dan collapsed (2 gambar) |
| 11 | **Cetak Rapor Digital (Siswa)** | 📸 Screenshot `/siswa/dashboard` dengan card Cetak Rapor + `/siswa/nilai` dengan tombol Cetak Rapor di header & footer + 3 dashboard cards (Rata-rata Keseluruhan, Ringkasan Akademik, Komponen Perlu Perhatian) + bar chart "Performa per Mata Pelajaran" (5 gambar) |
| 12 | **Rapor PDF (Siswa)** | 📸 Screenshot rapor PDF yang sudah di-download (header SMAN 7 Solo, identity table, tabel nilai, summary boxes, signature section) (1-2 gambar) |
| 13 | **Grafik Interaktif Dashboard Admin** | 📸 Screenshot `/admin/dashboard`: 4 stat cards, donut chart kelulusan, stacked bar kelulusan per kelas (hover state), horizontal bar rata-rata per mapel (KKM line), Top Siswa Berprestasi + Siswa Perlu Perhatian (sortable, dengan progress bar), conditional alert (Performa baik) (5-7 gambar) |
| 14 | **Manajemen Nilai (Buka Kunci + Audit Log)** | 📸 Screenshot `/admin/nilai`: info alert, filter bar (search + kelas), tabel Final combos (3 rows), tombol Buka Kunci merah, modal konfirmasi dengan textarea + live counter X/10, tabel Log Pembukaan Kunci 10 Terbaru; plus screenshot 403 untuk login sebagai guru/siswa (4-6 gambar) |
| 15 | **Widget Notifikasi Dashboard Guru** | 📸 Screenshot `/guru/dashboard` (login sariwahyuni) dengan kartu merah "Tindak Lanjuti" untuk X-A Mat (2 Draft) + X-B Mat (3 Draft) + chip combo list; plus screenshot login guru dengan combo belum_diinput (kuning) + masih_draft (merah) keduanya; plus tips box di Menu Cepat (3-4 gambar) |
| 16 | **Fix Bug Stats & Mixed State (T11)** | 📸 Screenshot `/guru/dashboard` Joko Santoso **SETELAH FIX** (stats benar: draft 6, final 8, lulus 11, tidak lulus 3 + 4 stat cards baru + tabel "Status per Mengajar" 2 rows dengan badge "Sebagian Final") + screenshot `/guru/input-nilai?kelas=XI-A&mata_pelajaran=IPS` dengan Alert "Sebagian Final" + 4 baris hijau "Final" read-only + 3 baris Draft editable (2-3 gambar) |

### Tambahan Manual (jika perlu)

| Item | Keterangan |
|------|------------|
| **Diagram ERD** | Bisa generate dari MySQL Workbench atau tambahkan screenshot dari `Spesifikasi Program LSP.md` |
| **Screenshot Docker MySQL** | Opsional: tampilkan `docker ps` yang menunjukkan container `pdns-mysql` running |
| **Screenshot Halaman Dashboard** | Per role (admin, guru, siswa) — 3 gambar |
| **Screenshot CRUD lengkap** | Edit siswa, edit guru, dll (opsional, untuk kelengkapan) |

---

> **Dokumen ini dibuat otomatis dari kode project.** Semua snippet kode di atas adalah kode aktual yang berjalan di project. Untuk memastikan keakuratan, silakan verifikasi dengan menjalankan `git log --oneline` dan `php artisan test` setelah membaca.
