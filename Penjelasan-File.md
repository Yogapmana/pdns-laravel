# Panduan Struktur Folder, Berkas, dan Diagram Aplikasi

Dokumen ini menjelaskan fungsi dan kegunaan dari setiap folder dan berkas utama di dalam folder `app/` dan `resources/`, serta menyajikan analisis lengkap terhadap **Diagram Kelas (UML Class Diagram)** dan **Diagram Hubungan Entitas (Entity Relationship Diagram - ERD)** pada sistem PDNS (Penginputan Data & Nilai Siswa) berbasis Laravel + Inertia.

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

---

## 📊 Analisis & Penjelasan Diagram Sistem

Aplikasi ini didesain menggunakan pendekatan Object-Oriented di tingkat kode dan pemodelan relasional di tingkat database. Berikut adalah penjelasan lengkap untuk kedua diagram arsitektur sistem:

### 1. Diagram Kelas (UML Class Diagram)
Diagram Kelas menggambarkan struktur logis kode program dari sisi Object-Oriented Programming (OOP) di Laravel. Setiap kotak merepresentasikan kelas model Laravel yang memetakan data beserta fungsinya (*attributes* & *methods*) serta hubungan multiplisitasnya.

#### A. Penjelasan Kelas dan Tanggung Jawabnya:
*   **`User`**:
    *   **Atribut**: `id`, `username`, `name`, `role`, `is_active`, `password`.
    *   **Metode**:
        *   `hasRole()`: Mengecek kecocokan hak akses pengguna.
        *   `isAdmin()`, `isGuru()`, `isSiswa()`: Fungsi pembantu (helper) instan untuk validasi otorisasi cepat.
        *   `siswa()`, `guru()`: Hubungan ke entitas profil terkait.
*   **`Siswa`**:
    *   **Atribut**: `nis` (Nomor Induk Siswa), `user_id`, `kelas_id`, `nama_siswa`.
    *   **Metode**: `user()`, `kelas()`, `nilai()` mendefinisikan relasi objek navigasi.
*   **`Guru`**:
    *   **Atribut**: `id`, `user_id`, `nama_guru`.
    *   **Metode**: `user()`, `mengajar()`, `nilai()` mendefinisikan relasi guru ke akun, data mengajar, dan nilai yang diinput.
*   **`Kelas`**:
    *   **Atribut**: `id`, `nama`.
    *   **Metode**: Mengembalikan daftar `siswa()`, `mataPelajaran()`, `guruMengajar()` yang ditugaskan, dan data `nilai()`.
*   **`MataPelajaran`**:
    *   **Atribut**: `id`, `nama`.
    *   **Metode**: Mengelola relasi ke tabel `kelas()`, pivot `guruMengajar()`, dan `nilai()`.
*   **`Nilai`**:
    *   **Atribut**: `id`, `nis`, `id_guru`, `kelas_id`, `mapel_id`, `nilai_tugas`, `nilai_uts`, `nilai_uas`, `nilai_akhir`.
    *   **Metode**:
        *   `hitungNilaiAkhir()`: Logika bisnis untuk menghitung nilai akhir dengan bobot persentase khusus (30% Tugas, 30% UTS, 40% UAS).
        *   `tentukanKelulusan()`: Mengevaluasi apakah nilai akhir memenuhi atau melampaui KKM (Kriteria Ketuntasan Minimal).
        *   `validasiNilai()`: Fungsi untuk mengunci nilai agar tidak bisa diubah guru tanpa persetujuan admin.
*   **`GuruMengajar`**: Kelas pivot yang mengaitkan `Guru` dengan kombinasi `Kelas` dan `MataPelajaran` yang diajar.
*   **`NilaiUnlockLog`**: Mencatat riwayat audit ketika kelas nilai dibuka kuncinya oleh admin atas permintaan guru.

#### B. Hubungan Multiplisitas (Kardinalitas Objek):
*   **`User` ke `Siswa`/`Guru` (`1` ke `0..1`)**: Satu akun pengguna (*User*) maksimal hanya terhubung ke satu profil `Siswa` atau satu profil `Guru` (atau tidak sama sekali jika akun tersebut adalah Admin).
*   **`Kelas` ke `Siswa` (`1` ke `*`)**: Satu kelas dapat memiliki banyak siswa (`*`), namun seorang siswa hanya terdaftar di satu kelas (`1`).
*   **`Siswa` ke `Nilai` (`1` ke `*`)**: Seorang siswa memiliki banyak catatan nilai mata pelajaran (`*`).
*   **`Guru` ke `Nilai` (`1` ke `*`)**: Seorang guru dapat menginput banyak data nilai siswa (`*`).
*   **`MataPelajaran` ke `Nilai` (`1` ke `*`)**: Satu mata pelajaran dapat memiliki banyak data nilai siswa (`*`).

---

### 2. Diagram Hubungan Entitas (Entity Relationship Diagram - ERD)
ERD menggambarkan struktur fisik tabel-tabel di database PostgreSQL/MySQL beserta kolom, tipe data, dan konstrain hubungan kunci (*foreign key*) yang menghubungkannya menggunakan notasi *Crow's Foot*.

#### A. Detail Tabel dan Kolom Database:
*   **`USERS`**: Menyimpan kredensial sistem.
    *   `id` (PK, bigint) -> Kunci utama unik.
    *   `role` (enum) -> Membatasi nilai hanya pada opsi role sistem ('admin', 'guru', 'siswa').
    *   `is_active` (boolean) -> Status aktifasi akun untuk pembatasan login.
*   **`SISWA`**: Profil fisik siswa.
    *   `nis` (PK, varchar) -> Menggunakan Nomor Induk Siswa sebagai kunci utama (Primary Key) bertipe string.
    *   `user_id` (FK, bigint) -> Relasi ke tabel `USERS`.
    *   `kelas_id` (FK, bigint) -> Relasi ke tabel `KELAS` (menunjukkan penempatan kelas siswa).
*   **`GURU`**: Profil fisik guru.
    *   `id` (PK, bigint) & `user_id` (FK, bigint).
*   **`KELAS`** & **`MATA_PELAJARAN`**: Tabel master data untuk menyimpan nama kelas dan nama pelajaran.
*   **`KELAS_MATA_PELAJARAN`**: Tabel pivot pembatas.
    *   Menghubungkan `kelas_id` dan `mapel_id` untuk menentukan mata pelajaran apa saja yang aktif/tersedia untuk kelas tertentu.
*   **`GURU_MENAJAR`**: Tabel pivot penugasan guru.
    *   Menghubungkan `id_guru`, `kelas_id`, dan `mapel_id` untuk memetakan guru pengajar spesifik pada kelas dan mata pelajaran tertentu.
*   **`NILAI`**: Tabel transaksi penilaian siswa.
    *   Menghubungkan `nis` (Siswa), `id_guru` (Guru penginput), `kelas_id`, dan `mapel_id`.
    *   Menyimpan data desimal (`decimal`) untuk presisi nilai angka tugas, UTS, UAS, dan nilai akhir.
    *   `status_lulus` (enum) dan `status_validasi` (enum) untuk melacak kelulusan akademik dan status penguncian nilai.
*   **`NILAI_UNLOCK_LOG`**: Tabel log audit.
    *   Menyimpan `id_admin` (FK ke `USERS`), `id_guru`, `kelas_id`, `mapel_id`, jumlah baris yang terpengaruh (`affected_rows`), dan alasan pembukaan kunci (`reason` bertipe text).

#### B. Hubungan Relasional (*Crow's Foot Notation*):
*   **One-to-One (`||` ke `o|`)**: Terjadi antara `USERS` dengan `SISWA` atau `GURU`. Simbol garis tegak ganda mewakili keharusan satu baris di satu sisi, dan lingkaran kecil mewakili opsionalitas di sisi lainnya (seorang pengguna belum tentu memiliki profil siswa/guru jika dia adalah admin).
*   **One-to-Many (`||` ke `|<`)**: Terjadi antara tabel master (seperti `KELAS` atau `SISWA`) ke tabel transaksi `NILAI`. Simbol kaki gagak (`<`) menunjukkan bahwa satu data kelas atau siswa dapat terhubung ke banyak baris transaksi penilaian.
*   **Many-to-Many via Pivot**: Hubungan banyak-ke-banyak dimediasi oleh tabel pivot seperti `KELAS_MATA_PELAJARAN` dan `GURU_MENAJAR` untuk menormalisasi relasi database dan mencegah redundansi data.

---

### 3. Korelasi Antara UML Class Diagram & ERD
*   **Model vs Tabel**: Setiap Class Model di UML (seperti `User`, `Siswa`, `Nilai`) berkorespondensi langsung dengan tabel fisik database di ERD (`USERS`, `SISWA`, `NILAI`).
*   **Relasi Eloquent vs Foreign Key**: Garis hubungan asosiasi di UML diimplementasikan menggunakan konstrain *Foreign Key* (FK) di ERD, yang kemudian dideklarasikan di kelas Model menggunakan fungsi `belongsTo`, `hasMany`, `hasOne`, atau `belongsToMany`.
*   **Logika Bisnis**: Atribut angka di ERD (`nilai_tugas`, `nilai_uts`, `nilai_uas`) diolah secara otomatis oleh metode enkapsulasi logika bisnis di Class diagram (`hitungNilaiAkhir()` dan `tentukanKelulusan()`) sebelum disimpan kembali ke dalam database.