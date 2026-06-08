# Skenario Q&A Teknis Aplikasi (Sistem Penilaian Sekolah)

Dokumen ini berisi daftar pertanyaan yang sangat mungkin ditanyakan oleh penguji, dosen pembimbing, rekan developer, atau _client_ terkait bagaimana aplikasi ini dibangun di sisi kode (_backend_ dan _frontend_).

---

## 1. Arsitektur Umum & Konsep Dasar

**Q: Aplikasi ini menggunakan _stack_ teknologi apa saja? Mengapa memilih teknologi tersebut?**
> **A:** Aplikasi ini menggunakan _stack_ TALL/VIRT yang dimodifikasi. Di sisi _backend_ menggunakan **Laravel 11+ (PHP 8.3+)** karena keamanannya yang solid dan fitur bawaannya (seperti Eloquent ORM, Routing, Fortify). Di sisi _frontend_ menggunakan **React.js** dan **Tailwind CSS** untuk UI yang interaktif dan modern. Untuk menghubungkan keduanya tanpa perlu membuat REST API manual secara terpisah, kita menggunakan **Inertia.js**, yang memungkinkan kita menggunakan _routing_ Laravel secara langsung di React (menjembatani _server-side_ dan _client-side rendering_).

**Q: Apa peran `Laravel Wayfinder` dalam aplikasi ini?**
> **A:** Laravel Wayfinder adalah _plugin_ Vite yang secara otomatis men- _generate_ tipe data TypeScript (serta _helper functions_) dari rute-rute Laravel. Ini menghilangkan kebutuhan untuk menulis URL *hardcoded* (seperti `/guru/input-nilai`) di React, sekaligus memastikan *type safety* pada parameter rute sehingga memperkecil kemungkinan *error* saat navigasi.

**Q: Bagaimana cara Anda menangani Autentikasi untuk tiga _role_ berbeda (Admin, Guru, Siswa)?**
> **A:** Autentikasi ditangani oleh paket **Laravel Fortify** tanpa UI bawaan, karena UI login dibangun kustom menggunakan React. Setiap entitas (Admin, Guru, Siswa) adalah bagian dari satu tabel `users`. Pemisahan hak akses dilakukan menggunakan kolom `role` (berisi nilai Enum/string `admin`, `guru`, `siswa`). Pengecekan akses dibatasi menggunakan **Middleware** (misal: fitur input nilai dilindungi _middleware_ yang hanya meloloskan `user->role == 'guru'`).

---

## 2. Kelas Model (Database & Eloquent)

**Q: Bagaimana relasi database antara Guru, Mata Pelajaran, dan Kelas?**
> **A:** Relasi ini bersifat _Many-to-Many_ yang direpresentasikan oleh model `GuruMengajar` (tabel pivot `guru_mengajar`). Seorang guru dapat mengajar banyak kelas dan banyak mata pelajaran, sementara satu kelas bisa diajar oleh banyak guru. Model `Guru` memiliki metode _relationship_ `mengajar()` (HasMany ke tabel `guru_mengajar`) untuk mendapatkan daftar kelas dan mapel spesifik yang diampu oleh guru tersebut.

**Q: Pada model `Nilai`, ada metode apa saja yang bertugas menghitung dan memvalidasi nilai?**
> **A:** Di model `Nilai`, terdapat metode statis (prosedur bantuan) yang berisi _business logic_ untuk nilai, antara lain:
> - `Nilai::hitungNilaiAkhir($tugas, $uts, $uas)`: Menghitung persentase berbobot dari 3 komponen (misal Tugas 30%, UTS 30%, UAS 40%).
> - `Nilai::tentukanKelulusan($nilai_akhir)`: Mengembalikan string Enum `'Lulus'` atau `'Tidak Lulus'` berdasarkan kriteria ketuntasan minimal.
> - `Nilai::validasiNilai($nilai)`: Memastikan nilai tidak minus dan tidak lebih dari 100.

**Q: Bagaimana Anda menghubungkan akun `User` dengan profil `Siswa` atau `Guru`?**
> **A:** Tabel `users` memiliki kolom otentikasi inti (`username`, `password`, `role`). Tabel `siswa` dan `guru` masing-masing memiliki _foreign key_ `user_id` yang merujuk ke tabel `users`. Relasinya adalah _One-to-One_. Model `User` memiliki metode `siswa()` dan `guru()` yang mengembalikan instans relasi `HasOne`.

---

## 3. Controller (Logika Bisnis & Pengolahan Request)

**Q: Tolong jelaskan bagaimana fungsi penginputan nilai `NilaiController@save` bekerja!**
> **A:** Prosedur ini menerima parameter *array* daftar nilai siswa dalam satu kelas.
> 1. Controller memvalidasi input menggunakan `$request->validate()`.
> 2. Memeriksa kembali secara mandiri apakah Guru tersebut benar-benar memiliki wewenang mengajar di `kelas_id` dan `mata_pelajaran_id` tersebut.
> 3. Membuka `DB::transaction()` untuk memastikan *database* tidak berantakan jika terjadi _error_ di tengah proses.
> 4. Melakukan iterasi (`foreach`) terhadap setiap input murid: menghitung nilai akhir, lalu menggunakan `updateOrCreate()` pada model `Nilai` untuk memperbarui atau menyisipkan data baru.

**Q: Apa perbedaan alur pada `save()` (Simpan Draft) dan `validateFinal()` (Validasi)?**
> **A:** Fungsi `save()` hanya menampung angka yang diinput guru dan menyimpannya secara parsial/seluruhnya, namun masih bisa diedit.
> Sedangkan `validateFinal()` adalah fungsi _lock_ (kunci). Fungsi ini menerima _array_ NIS melalui *Checkbox*. Controller menggunakan kueri `whereIn('nis', $array_nis)->update(['status_validasi' => 'Final'])` agar nilai-nilai siswa terpilih tak lagi bisa dimanipulasi melalui *form* input. 

**Q: Bagaimana logika pembuatan urutan _Ranking_ di `ReportController`?**
> **A:** Logika dipecah menjadi beberapa fungsi bantuan:
> 1. Mengambil data semua nilai akhir yang memiliki status `Final`.
> 2. Mengelompokkan berdasarkan NIS siswa untuk menjumlahkan rata-rata nilai.
> 3. Diurutkan secara descending menggunakan `sortByDesc('rata_rata')`.
> 4. Jika filter "Ranking Kelas", pengelompokan dikerjakan pada tiap ID kelas secara terpisah.

**Q: Pada `DashboardController` Admin, bagaimana cara menghitung persentase "Siswa Lulus" dan "Perlu Perhatian"?**
> **A:** Dashboard menggunakan kemampuan Agregasi Database dan Koleksi (*Collection*). Controller memanggil kueri *count* untuk menghitung siswa yang nilai akhirnya melewati ambang batas.

---

## 4. Frontend (React, Inertia, Komponen)

**Q: Bagaimana fungsi _state management_ di React menangani nilai *input* yang banyak?**
> **A:** Nilai awal dilempar dari Controller dalam bentuk struktur data _Dictionary_ (`nilai_map`), dengan *key* berupa NIS. Di React, setiap baris tabel adalah sub-komponen terpisah yang memiliki `useState` mandiri. Komponen induk hanya mengikat data tersebut melalui elemen `<form>` yang jika di-_submit_, secara otomatis merangkai nilai berdasar nama atribut HTML `name="nilai[12345][nilai_uts]"`.

**Q: Apa fungsi dari `useFlashToast()` *custom hook* di Frontend?**
> **A:** Hook ini dirancang untuk "mendengarkan" properti `flash` dari respon HTTP Inertia (seperti pesan *success* atau *error* yang dilempar dari PHP menggunakan fungsi `back()->with('success', 'Pesan')`). Jika ada perubahan, hook ini memicu *library* Sonner Toast untuk menampilkan notifikasi cantik.

---

## 5. Studi Kasus _Bug_ dan Pemecahan Masalah (Troubleshooting)

**Q: Jika XAMPP menggunakan PHP 8.2, apa yang akan terjadi?**
> **A:** Saat mencoba menjalankan `composer install` atau `php artisan serve`, sistem akan menolak (*error fatal*), karena Laravel 11 membutuhkan minimal PHP 8.3. Pengguna perlu memperbarui XAMPP-nya.

**Q: Bagaimana cara kerja `DomPDF` dan `OpenSpout` pada aplikasi ini?**
> **A:** `DomPDF` merender template HTML Blade menjadi dokumen PDF yang utuh (digunakan untuk mencetak Rapor Siswa dan Laporan Kelas). Sedangkan `OpenSpout` dipanggil melalui `XlsxWriter` untuk membuat file Excel (`.xlsx`) secara *streaming* baris per baris, sehingga konsumsi RAM server tetap sangat kecil meskipun meng-export puluhan ribu baris data.

**Q: Kenapa gambar profil atau logo sekolah tidak muncul di aplikasi setelah dideploy/diinstal?**
> **A:** Seringkali hal ini terjadi karena *symlink* direktori publik belum dibuat. Solusinya adalah membuka terminal dan menjalankan perintah `php artisan storage:link`.

---

## 6. Persiapan Ujian Kompetensi (Modifikasi Penambahan Fitur)

Dalam skenario uji kompetensi atau _project defense_, asesor mungkin menyuruh Anda memodifikasi kode secara langsung. Berikut kemungkinan instruksinya dan panduan cara mengeksekusinya:

### A. Mengubah Logika Perhitungan Bobot Nilai Akhir
**Permintaan:** *"Ubah persentase perhitungan nilai. Tugas jadi 20%, UTS 40%, UAS 40%."*
*   **Penyelesaian:** Buka file `app/Models/Nilai.php`, cari prosedur fungsi `hitungNilaiAkhir()`, lalu ubah angka pembobotan desimalnya menjadi `($tugas * 0.2) + ($uts * 0.4) + ($uas * 0.4)`. Seluruh aplikasi dan rapor akan otomatis menyesuaikan hitungannya karena logikanya tersentralisasi di model tersebut.

### B. Menambahkan Kolom / Field Baru pada Master Data
**Permintaan:** *"Tambahkan inputan text field 'Alamat' atau 'No. HP' pada pendataan Siswa baru."*
*   **Penyelesaian Lengkap (Full Stack):**
    1.  **Database:** Buka terminal, ketik `php artisan make:migration add_alamat_to_siswa_table`. Edit file *migration* yang baru dibuat dengan mengisi `$table->text('alamat')->nullable();` di dalam *blueprint*, lalu jalankan `php artisan migrate`.
    2.  **Model:** Buka `app/Models/Siswa.php`, tambahkan string `'alamat'` ke dalam array properti `$fillable`.
    3.  **Request (Validasi):** Buka `app/Http/Requests/Admin/SiswaRequest.php`, tambah aturan `'alamat' => 'required|string'` pada fungsi *rules*.
    4.  **Frontend (UI React):** Buka `resources/js/pages/admin/siswa/create.tsx` dan `edit.tsx`, copy-paste elemen form yang sudah ada, lalu sesuaikan props `<Input name="alamat" />`. Buka juga `index.tsx` jika ingin menampilkannya di tabel daftar siswa.

### C. Menambahkan Filter Pencarian / Pengelompokan Tambahan
**Permintaan:** *"Tambahkan fitur agar data siswa di tabel Dashboard Admin bisa disaring/difilter hanya menampilkan murid laki-laki saja."*
*   **Penyelesaian:** 
    1.  **React Frontend:** Di halaman `resources/js/pages/admin/siswa/index.tsx`, pasang *dropdown* menu `<select>`. Saat nilainya diklik, panggil *hook* kustom aplikasi kita yaitu `useInertiaSearch` untuk mengirimkan kueri URL ke server secara reaktif (misalnya mengirim parameter `?gender=L`).
    2.  **Backend Controller:** Di dalam `SiswaController.php` pada fungsi `index()`, ubah query Eloquent dengan menambahkan _logic_ pengkondisian: 
        ```php
        $query->when(request('gender'), function ($q, $gender) {
            $q->where('jenis_kelamin', $gender);
        });
        ```

### D. Menambahkan Fitur Upload Foto Profil Siswa/Guru
**Permintaan:** *"Tolong tambahkan fitur agar siswa bisa memiliki pas foto visual pada datanya."*
*   **Penyelesaian:** 
    1.  Lakukan penambahan kolom database seperti langkah (B) untuk kolom bernama `foto` dengan tipe `string`. Update `$fillable` Model.
    2.  Ubah aturan validasi form di `SiswaRequest.php` untuk mendeteksi _file_ dengan tipe: `'image|mimes:jpeg,png|max:2048'`.
    3.  Di fungsi `store` / `update` Controller, tangkap _file_ gambar yang dikirim dan simpan ke media penyimpanan lokal menggunakan perintah `Storage::disk('public')->putFile(...)`, lalu simpan jalur string URL lokasinya ke atribut foto.
    4.  Di halaman form React (`create.tsx`), ubah input menjadi tipe file `<input type="file" />` dan ubah cara kirim *Inertia* dengan menambahkan konfigurasi pengunggahan dokumen `forceFormData: true`. 

### E. Memodifikasi Tampilan Warna/Layout Komponen (Tailwind CSS)
**Permintaan:** *"Ganti warna tombol Simpan menjadi warna Hijau, jangan Biru."*
*   **Penyelesaian:** Buka komponen terkait (misalnya `resources/js/components/ui/button.tsx` atau file form `edit.tsx`). Cari atribut properti `className`, lalu ubah *string class* bawaan Tailwind CSS dari `bg-blue-600 hover:bg-blue-700` menjadi `bg-green-600 hover:bg-green-700`. Pastikan proses Node.js (`npm run dev`) sedang berjalan di *background* agar perubahan desain ini dikompilasi secara *live* ke _browser_ asesor.

### F. Menambahkan Hak Akses (Role) Baru
**Permintaan:** *"Tambahkan hak akses 'Kepala Sekolah' yang hanya dapat melihat Dashboard tanpa punya kewenangan merubah."*
*   **Penyelesaian:** Ubah migration ENUM pada kolom `role` di tabel `users` untuk menyertakan nilai `'kepala_sekolah'`. Tambah _helper method_ pengecekan seperti `isKepalaSekolah()` di model `User.php`. Pada `routes/web.php` buat *route group* baru. Selanjutnya, buat file `KepalaSekolahController.php` dan pastikan konfigurasi komponen `app-layout.tsx` menampilkan bilah navigasi (sidebar) khusus yang tidak memuat ikon/menu edit.

---
_Dokumen ini disusun untuk mempermudah pemahaman arsitektur, referensi teknis mendalam, dan panduan cepat kilat bagi tim pengembang dalam menghadapi sidang uji proyek perangkat lunak._
