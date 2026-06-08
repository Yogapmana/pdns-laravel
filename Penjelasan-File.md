# Panduan Struktur Folder dan Berkas Aplikasi

Dokumen ini menjelaskan fungsi dan kegunaan dari setiap folder dan berkas utama yang terdapat di dalam folder `app/` dan `resources/` pada aplikasi PDNS (Penginputan Data & Nilai Siswa) berbasis Laravel + Inertia (React).

---

## 📂 Folder `app/` (Backend Logic)

Folder `app/` berisi logika utama backend aplikasi, mulai dari kontrol alur (Controllers), model database (Models), validasi masukan (Requests), middleware penyaring akses, hingga kelas pendukung otentikasi.

### 1. `app/Actions/`
Menyimpan kelas aksi (*Action Classes*) yang masing-masing memiliki satu tanggung jawab spesifik (*single-responsibility*) untuk menangani alur kerja tertentu.
*   **`Fortify/CreateNewUser.php`**: Menangani logika pendaftaran (registrasi) pengguna baru beserta aturan validasi awalnya.
*   **`Fortify/ResetUserPassword.php`**: Mengatur validasi dan pembaruan kata sandi pengguna saat melakukan proses pemulihan/reset password.

### 2. `app/Concerns/`
Tempat untuk PHP *Traits* yang berisi kumpulan aturan validasi modular yang dapat digunakan kembali (*reusable*) di berbagai kelas (seperti *Form Requests* atau *Actions*).
*   **`PasswordValidationRules.php`**: Menyediakan aturan standar untuk memvalidasi keamanan kata sandi (panjang minimal, kombinasi karakter, dan konfirmasi password).
*   **`ProfileValidationRules.php`**: Menyediakan aturan standardisasi validasi data profil pengguna seperti nama dan keunikan alamat email.

### 3. `app/Http/`
Pusat pengaturan permintaan HTTP, kontrol rute, penyaringan lalu lintas data (middleware), dan validasi masukan form.

#### 🔹 `app/Http/Controllers/`
Menghubungkan antarmuka depan (*frontend*) dengan data backend. Controller memproses permintaan dari pengguna, berinteraksi dengan model, dan mengembalikan respons rendering melalui Inertia.js.
*   **`Controller.php`**: Kelas dasar (*base controller*) induk untuk seluruh controller aplikasi.
*   **📂 `Admin/` (Manajemen & Operasional Administrator)**:
    *   `AccountController.php`: Manajemen akun pengguna (membuat admin baru, mengaktifkan/nonaktifkan akun, serta reset kata sandi pengguna).
    *   `DashboardController.php`: Menyediakan agregasi data statistik global sekolah (total siswa/guru/mapel, persentase kelulusan, grafik rekap, daftar siswa yang membutuhkan perhatian khusus) untuk ditampilkan pada dasbor admin.
    *   `GuruController.php`: Manajemen CRUD data guru serta pemetaan kelas dan mata pelajaran yang diampu oleh masing-masing guru.
    *   `KelasController.php`: Manajemen CRUD kelas beserta pengaturan sinkronisasi mata pelajaran apa saja yang diajarkan pada kelas tersebut.
    *   `MataPelajaranController.php`: Manajemen CRUD daftar mata pelajaran (master data).
    *   `NilaiController.php`: Mengelola permintaan persetujuan pembukaan kunci nilai final (*unlock nilai*) dari status Terkunci (Final) kembali ke Draf agar bisa diedit kembali oleh guru.
    *   `ReportController.php`: Memproses pratinjau dan ekspor laporan nilai secara massal per kelas ke berbagai format seperti PDF, Excel (XLSX), CSV, maupun HTML.
    *   `SiswaController.php`: Manajemen CRUD profil siswa beserta penentuan kelas penugasannya.
*   **📂 `Guru/` (Operasional Guru Pengajar)**:
    *   `DashboardController.php`: Menampilkan ringkasan statistik kelas dan mata pelajaran yang diampu oleh guru yang sedang login.
    *   `NilaiController.php`: Mengatur proses penginputan nilai (tugas, UTS, UAS) siswa per kelas-mapel, penyimpanan draf sementara, finalisasi nilai (terkunci), dan rekapitulasi nilai.
*   **📂 `Siswa/` (Informasi & Rapor Siswa)**:
    *   `DashboardController.php`: Dasbor utama siswa yang menampilkan rangkuman status kelulusan mata pelajaran.
    *   `NilaiController.php`: Menampilkan daftar nilai mata pelajaran siswa yang telah tervalidasi dan dikunci oleh guru pengajar, lengkap dengan grafik perkembangan nilai.
    *   `RaporController.php`: Mengunduh dan menghasilkan berkas cetak rapor PDF resmi siswa bersangkutan.

#### 🔹 `app/Http/Middleware/`
Kelas penengah (*Middleware*) yang menyaring request sebelum masuk ke Controller tujuan.
*   **`EnsureUserHasRole.php`**: Memeriksa peran (*role*) pengguna (apakah admin, guru, atau siswa) untuk memastikan keamanan hak akses halaman.
*   **`HandleAppearance.php`**: Middleware opsional untuk memproses atau mempertahankan preferensi visual (seperti tema gelap/terang).
*   **`HandleInertiaRequests.php`**: Menangani pengiriman data global (*shared props*) dari backend Laravel ke frontend React (Inertia), seperti data pengguna yang sedang login dan notifikasi kilat (*flash messages*).

#### 🔹 `app/Http/Requests/`
Memisahkan validasi data masukan dari controller menggunakan *Form Request Classes*.
*   **📂 `Admin/`**:
    *   `AccountRequest.php`: Validasi data masukan pembuatan akun baru.
    *   `GuruRequest.php`: Validasi data profil guru dan validasi pemetaan kelas/mapel mengajar agar tidak bentrok.
    *   `KelasRequest.php`: Validasi keunikan nama kelas serta daftar mata pelajaran yang diasosiasikan.
    *   `MataPelajaranRequest.php`: Validasi format dan keunikan nama mata pelajaran baru.
    *   `SiswaRequest.php`: Validasi profil data diri siswa baru (NIS, nama, jenis kelamin, kelas).

### 4. `app/Models/`
Representasi skema tabel database berbasis Eloquent ORM. Mengelola manipulasi data dan hubungan (*relations*) antartabel.
*   **`User.php`**: Model utama autentikasi pengguna. Menyimpan username, email, password, role, status aktif, serta relasi *one-to-one* ke model Guru atau Siswa.
*   **`Guru.php`**: Mengelola data personal guru dan relasinya ke penugasan kelas-mapel mengajar (*GuruMengajar*).
*   **`GuruMengajar.php`**: Model pivot yang menghubungkan Guru dengan Kelas dan Mata Pelajaran yang mereka ajar.
*   **`Kelas.php`**: Mengelola tabel kelas, relasi ke daftar Siswa di dalamnya, dan relasi mata pelajaran yang terdaftar di kelas tersebut.
*   **`KelasMataPelajaran.php`**: Model pivot yang menghubungkan Kelas dengan Mata Pelajaran.
*   **`MataPelajaran.php`**: Mengelola master data mata pelajaran.
*   **`Nilai.php`**: Mengelola data penilaian akademik (Tugas, UTS, UAS). Berisi fungsi penghitungan nilai akhir terbobot (30% Tugas + 30% UTS + 40% UAS), penentuan kelulusan berdasarkan KKM, dan status kunci nilai.
*   **`NilaiUnlockLog.php`**: Menyimpan histori log audit saat admin menyetujui pembukaan kunci nilai (*unlock*) yang diajukan oleh guru.
*   **`Siswa.php`**: Mengelola data siswa, Nomor Induk Siswa (NIS), jenis kelamin, serta relasi ke kelas tempat siswa bernaung.

### 5. `app/Providers/`
Tempat untuk mendaftarkan layanan dasar (*bootstrapping*) aplikasi saat pertama kali dijalankan oleh Laravel.
*   **`AppServiceProvider.php`**: Mengatur inisialisasi default aplikasi, format string, dan pembacaan konfigurasi.
*   **`FortifyServiceProvider.php`**: Mengatur konfigurasi khusus backend autentikasi Laravel Fortify (seperti pembatasan laju percobaan login/throttling dan penyesuaian redirect pasca-login).

### 6. `app/Support/`
Berisi kelas utilitas tambahan untuk membantu operasional aplikasi.
*   **`XlsxWriter.php`**: Library pembantu berbasis *OpenSpout* untuk memproses penulisan berkas Excel (.xlsx) dengan kecepatan tinggi dan konsumsi memori server yang sangat minim.

---

## 📂 Folder `resources/` (Frontend & Presentation Layer)

Folder `resources/` berisi kode antarmuka aplikasi, mulai dari file layout, komponen antarmuka React (TSX), styling CSS, hingga file template ekspor.

### 1. `resources/css/`
Mengelola file style visual aplikasi.
*   **`app.css`**: File stylesheet utama aplikasi yang mengimpor utilitas Tailwind CSS v4 beserta kustomisasi warna dan transisi UI aplikasi.

### 2. `resources/js/` (React + Inertia Codebase)
Berisi seluruh logika pemrograman antarmuka depan (*client-side*) yang ditulis menggunakan React dan TypeScript.

*   **📂 `actions/`** & **📂 `routes/`** & **📂 `wayfinder/`**: Berkas-berkas TypeScript yang dibuat secara otomatis oleh *Laravel Wayfinder* untuk memetakan rute (*route helpers*) backend Laravel agar dapat dipanggil secara aman dan dinamis dari sisi React dengan dukungan *static typing*.
*   **`app.tsx`**: Berkas inisialisasi utama (entri poin) untuk aplikasi SPA (Single Page Application) berbasis Inertia.js.
*   **📂 `components/`** (Komponen Reusable):
    *   **📂 `dashboard/`**: Komponen khusus visualisasi data dasbor seperti `action-checklist.tsx` (daftar tindakan penting), `donut-chart.tsx` (diagram lingkaran kelulusan), `kelas-bar-chart.tsx`, `mapel-bar-chart.tsx`, `siswa-list.tsx` (tabel data ringkas), dan `stat-card.tsx` (kartu statistik).
    *   **📂 `ui/`**: Komponen dasar pembangun antarmuka (*UI Primitives*) seperti `button.tsx`, `input.tsx`, `badge.tsx`, `alert.tsx`, `card.tsx`, `drawer.tsx`, `modal.tsx`, `select.tsx`, `pagination.tsx`, `sonner.tsx` (toast notifikasi), dan utilitas input bersama `shared.tsx`.
*   **📂 `hooks/`** (React Custom Hooks):
    *   `use-flash-toast.ts`: Menangkap *flash messages* dari session Laravel dan menampilkannya sebagai pop-up notifikasi toast menggunakan Sonner secara otomatis.
    *   `use-inertia-search.ts`: Membantu pembuatan fungsi pencarian, filter, dan pagination yang responsif dengan menyinkronkan query URL browser menggunakan pemanggilan Inertia secara efisien.
*   **📂 `layouts/`** (Template Halaman):
    *   `app-layout.tsx`: Layout utama halaman beranda setelah login, menyediakan sidebar navigasi dinamis sesuai hak akses, tombol logout, header, dan mode responsif mobile.
    *   `auth-layout.tsx`: Layout minimalis untuk halaman autentikasi (seperti form masuk/login).
*   **📂 `lib/`**:
    *   `utils.ts`: Menyimpan fungsi helper antarmuka seperti penggabung class CSS (*tailwind merge* / `cn`).
*   **📂 `types/`**:
    *   Berkas deklarasi tipe data TypeScript (`auth.ts`, `global.d.ts`, dsb.) untuk memastikan keamanan variabel (*type safety*) di sepanjang kode frontend.
*   **📂 `pages/`** (Halaman Utama Aplikasi):
    *   **📂 `admin/`**: Halaman khusus admin yang mencakup dasbor grafik, CRUD akun admin, data guru, data siswa, data kelas, mapel, pratinjau laporan, dan persetujuan pengajuan *unlock nilai*.
    *   **📂 `guru/`**: Halaman khusus guru mencakup pengisian lembar nilai siswa secara interaktif, grafik kelas yang diajar, serta unduhan ringkasan rekap nilai.
    *   **📂 `siswa/`**: Halaman khusus siswa mencakup tabel transkrip nilai akademik serta statistik performa nilai dalam bentuk grafik garis.
    *   `welcome.tsx`: Halaman awal (landing page) saat aplikasi pertama kali diakses sebelum pengguna melakukan login.
    *   **📂 `errors/`**: Halaman penanganan error halaman, seperti halaman `403.tsx` jika pengguna mencoba mengakses area yang tidak diizinkan.

### 3. `resources/views/` (Blade Templates)
Template server-side Laravel.
*   **`app.blade.php`**: Berkas HTML utama (*master template*) tempat di mana aplikasi React di-mount oleh Inertia. Di dalamnya memuat tag metadata SEO, font Google Fonts, favicon, dan memanggil bundler aset Vite.
*   **📂 `reports/`** (Template Cetak & Ekspor):
    *   `html.blade.php`: Kerangka preview laporan berbasis web.
    *   `pdf.blade.php`: Kerangka laporan PDF yang akan diekspor (seperti laporan rekap kelas). Didesain agar kompatibel dengan mesin cetak *DomPDF*.
    *   `rapor-pdf.blade.php`: Template desain halaman rapor PDF resmi siswa lengkap dengan kop surat sekolah, tabel nilai detail, nilai akhir huruf, predikat kelulusan, dan kolom tanda tangan wali kelas.