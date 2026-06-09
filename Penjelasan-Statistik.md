# Penjelasan Cara Kerja Statistik Dashboard Aplikasi

Dokumen ini menguraikan secara teknis logika *Backend* (khususnya *Database Queries* dan *Eloquent ORM*) yang bekerja di balik layar untuk merumuskan angka-angka statistik pada 3 level Dashboard: **Admin, Guru, dan Siswa**.

Aplikasi ini menggunakan berbagai pendekatan mulai dari penghitungan dasar (*Simple Counts*) hingga pemrosesan raw MySQL/PostgreSQL (*Raw Query Aggregation*) untuk mencegah kebocoran memori (N+1 Problem) jika jumlah data sangat masif.

---

## 1. Dashboard Admin (`Admin/DashboardController.php`)

Dashboard admin bertugas menarik metrik dari seluruh penjuru sekolah. Oleh karenanya, pengolahan *query* dirancang secara terkelompok (*grouped*).

### A. Metrik Penghitung Global (Total Siswa, Guru, Mapel, Nilai)
Digunakan fitur Eloquent bawaan yang sangat ringan:
- `Siswa::count()`, `Guru::count()`, dsb.
- Untuk hitungan Lulus/Tidak Lulus menggunakan `Nilai::where('status_lulus', Nilai::LULUS)->count()`.
- Persentase Kelulusan dihitung secara matematis di PHP: `round((Lulus / Total Nilai) * 100, 1)`.

### B. Rekapitulasi Tingkat Kelulusan per Kelas
Untuk menampilkan tabel rekap kelas tanpa menyebabkan *N+1 query* (yang me-_looping_ kueri database pada setiap kelas), digunakan 2 *Query* terpusat:
1. **Query Jumlah Siswa:** `DB::table('siswa')->join('kelas')->groupBy('kelas.nama')->selectRaw('COUNT(*)')`. Menghasilkan sebuah _Dictionary_ (`[ 'Kelas 10-A' => 30, ... ]`).
2. **Query Status Lulus/Tidak Lulus:** `DB::table('nilai')->join('siswa')->join('kelas')->groupBy('kelas.nama', 'status_lulus')->selectRaw('COUNT(*)')`.
Kedua hasil kumpulan (*collection*) tersebut kemudian dikawinkan di level PHP (*in-memory*) membentuk tabel rekap utuh.

### C. Ranking Nilai Rata-rata Mata Pelajaran
Sistem membebankan kerja perhitungannya langsung ke mesin *Database MySQL/PGSQL* (bukan *looping* PHP) melalui *Raw SQL Query*.
- `DB::raw('AVG(nilai.nilai_akhir) as rata_rata')` menghitung rata-rata kelas secara *on-the-fly*.
- `DB::raw("SUM(CASE WHEN nilai.status_lulus = 'Lulus' THEN 1 ELSE 0 END)")` mengkalkulasi jumlah siswa lulus per mapel.
Seluruhnya dikelompokkan dengan `groupBy('mata_pelajaran.id')` dan diurutkan menaik.

### D. Leaderboard Top Siswa (Ranking Paralel)
Menggunakan pendekatan yang serupa dengan Mapel, namun dikelompokkan berdasarkan NIS siswa:
- `groupBy('nilai.nis')` mencari nilai rata-rata dari seluruh nilai yang dimiliki seorang murid dengan menggunakan agregator `AVG(nilai_akhir)`.
- Menggunakan filter khusus: `havingRaw('COUNT(*) > 0')` untuk memastikan siswa yang belum dinilai sama sekali tidak masuk ke perhitungan ranking.
- Hanya 5 siswa teratas (`limit(5)`) yang ditarik (Top 5 Peringkat).

### E. Daftar "Siswa Butuh Perhatian" (Sering Remedial)
Kebalikan dari Top Siswa, ini melacak siswa dengan status "Tidak Lulus" terbanyak.
- `havingRaw("SUM(CASE WHEN status_lulus = 'Tidak Lulus') > 0")`: Filter siswa yang setidaknya memiliki 1 nilai merah.
- Diurutkan dengan format spesifik: `ORDER BY (Jumlah_Tidak_Lulus) DESC, (Rata_Rata_Nilai) ASC`. Ini memastikan siswa dengan nilai merah terbanyak dan nilai terendah berada di peringkat paling atas peringatan.

### F. Fitur Alarm "Tindakan Penting"
Mendeteksi anomali administratif menggunakan fitur *Eloqent Where Has / Doesn't Have*:
- **Siswa Tanpa Nilai:** `Siswa::doesntHave('nilai')->count()` (Mendeteksi anak yang sama sekali belum mendapat nilai).
- **Guru Menganggur:** `Guru::doesntHave('mengajar')->count()` (Mendeteksi guru yang belum diberikan jadwal mapel/kelas).

---

## 2. Dashboard Guru (`Guru/DashboardController.php`)

Dashboard Guru hanya menampilkan metrik terbatas yang relevan dengan tanggung jawab mengajar *User* yang sedang _login_.

### A. Total Siswa & Nilai yang Diampu
- **Total Siswa Diajar:** Sistem melihat tabel *pivot* `guru_mengajar` untuk mendapatkan *array* `kelas_id` dari kelas yang diampu guru tersebut. Kemudian menghitung: `Siswa::whereIn('kelas_id', $array_kelas_id)->count()`.
- **Statistik Draft & Final:** Menggunakan fungsi `clone` (untuk mencegah query menumpuk) pada kueri dasar `Nilai::where('id_guru', auth_guru_id)` lalu ditambah klausa `where('status_validasi', 'Draft' atau 'Final')->count()`.

### B. Progres Pengisian (Per Kelas & Mapel)
Terdapat satu *card* progres yang merinci: "Di kelas X mapel Y, ada berapa siswa, berapa yang sudah dinilai, berapa yang sudah divalidasi?"
- Controller me-_looping_ kombinasi `guru_mengajar`. 
- Menghitung **Jumlah Murid** di dalam kelas tersebut dengan bantuan _Dictionary_ dari agregat tabel siswa.
- Mengambil baris **Nilai** yang spesifik miliki ID Guru + ID Kelas + ID Mapel kombinasi tersebut, untuk mendeteksi berapa anak yang mendapat nilai, dan berapa sisa anak yang nilainya masih kosong (Null).

---

## 3. Dashboard Siswa (`Siswa/DashboardController.php`)

Dashboard Siswa diatur secara *strict* dan disederhanakan keamanannya.

### Flag Ketersediaan Nilai (`$has_nilai`)
- Saat _login_, sistem menggunakan query `Nilai::where('nis', $nis_murid)->where('status_validasi', 'Final')->exists()`.
- Fungsi `exists()` mengembalikan `true` atau `false` dengan amat efisien (karena setara dengan *query* `SELECT 1 LIMIT 1`).
- Jika sistem mendeteksi ada nilai yang masih bersatus **'Draft'** (baru di-save sementara oleh guru, belum dikunci/divalidasi), maka nilai *draft* tersebut **diabaikan** (tidak dihitung). Siswa hanya boleh melihat hasil statistik jika sudah ada nilai sah (Final).
- Jika `$has_nilai` bernilai True, tombol "Lihat Laporan" atau "Print Rapor" pada React UI baru akan aktif.

---
*Catatan: Segala pemrosesan nilai dan persentase tidak disimpan secara permanen (hardcoded) ke dalam kolom tabel. Angka-angka statistik ini dihitung secara instan setiap detiknya saat halaman di-reload. Teknik desentralisasi komputasi ini memastikan Dasbor selalu akurat tanpa anomali data usang.*
