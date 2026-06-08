# Skenario Q&A Teknis Aplikasi (Sistem Penilaian Sekolah)

Dokumen ini berisi daftar pertanyaan yang sangat mungkin ditanyakan oleh penguji, dosen pembimbing, rekan developer, atau _client_ terkait bagaimana aplikasi ini dibangun di sisi kode (_backend_ dan _frontend_).

---

## 1. Arsitektur Umum & Konsep Dasar

**Q: Aplikasi ini menggunakan _stack_ teknologi apa saja? Mengapa memilih teknologi tersebut?**
> **A:** Aplikasi ini menggunakan _stack_ TALL/VIRT yang dimodifikasi. Di sisi _backend_ menggunakan **Laravel 11+ (PHP 8.3+)** karena keamanannya yang solid dan fitur bawaannya (seperti Eloquent ORM, Routing, Fortify). Di sisi _frontend_ menggunakan **React.js** dan **Tailwind CSS** untuk UI yang interaktif dan modern. Untuk menghubungkan keduanya tanpa perlu membuat REST API manual secara terpisah, kita menggunakan **Inertia.js**, yang memungkinkan kita menggunakan _routing_ Laravel secara langsung di React (menjembatani _server-side_ dan _client-side rendering_).

**Q: Apa peran `Laravel Wayfinder` dalam aplikasi ini?**
> **A:** Laravel Wayfinder adalah _plugin_ Vite yang secara otomatis men- _generate_ tipe data TypeScript (serta _helper functions_) dari rute-rute Laravel. Ini menghilangkan kebutuhan untuk menulis URL *hardcoded* (seperti `/guru/input-nilai`) di React, sekaligus memastikan *type safety* pada parameter rute sehingga memperkecil kemungkinan *error* saat navigasi (misalnya menggunakan fungsi `route('guru.nilai.index')` secara langsung di komponen React).

**Q: Bagaimana cara Anda menangani Autentikasi untuk tiga _role_ berbeda (Admin, Guru, Siswa)?**
> **A:** Autentikasi ditangani oleh paket **Laravel Fortify** tanpa UI bawaan, karena UI login dibangun kustom menggunakan React. Setiap entitas (Admin, Guru, Siswa) adalah bagian dari satu tabel `users`. Pemisahan hak akses dilakukan menggunakan kolom `role` (berisi nilai Enum/string `admin`, `guru`, `siswa`). Pengecekan akses dibatasi menggunakan **Middleware** (misal: fitur input nilai dilindungi _middleware_ yang hanya meloloskan `user->role == 'guru'`).

---

## 2. Kelas Model (Database & Eloquent)

**Q: Bagaimana relasi database antara Guru, Mata Pelajaran, dan Kelas?**
> **A:** Relasi ini bersifat _Many-to-Many_ yang direpresentasikan oleh model `GuruMengajar` (tabel pivot `guru_mengajar`). Seorang guru dapat mengajar banyak kelas dan banyak mata pelajaran, sementara satu kelas bisa diajar oleh banyak guru. Model `Guru` memiliki metode _relationship_ `mengajar()` (HasMany ke tabel `guru_mengajar`) untuk mendapatkan daftar kelas dan mapel spesifik yang diampu oleh guru tersebut.

**Q: Pada model `Nilai`, ada metode apa saja yang bertugas menghitung dan memvalidasi nilai?**
> **A:** Di model `Nilai`, terdapat metode statis (prosedur bantuan) yang berisi _business logic_ untuk nilai, antara lain:
> - `Nilai::hitungNilaiAkhir($tugas, $uts, $uas)`: Menghitung persentase berbobot dari 3 komponen (misal Tugas 30%, UTS 30%, UAS 40%).
> - `Nilai::tentukanKelulusan($nilai_akhir)`: Mengembalikan string Enum `'Lulus'` atau `'Tidak Lulus'` berdasarkan kriteria ketuntasan minimal (misalnya nilai >= 75).
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
> 4. Melakukan iterasi (`foreach`) terhadap setiap input murid: menghitung nilai akhir, lalu menggunakan `updateOrCreate()` pada model `Nilai` untuk memperbarui nilai jika data sudah ada, atau menyisipkan data baru dengan status bawaan `'Draft'`.

**Q: Apa perbedaan alur pada `save()` (Simpan Draft) dan `validateFinal()` (Validasi)?**
> **A:** Fungsi `save()` hanya menampung angka yang diinput guru dan menyimpannya secara parsial/seluruhnya, namun masih bisa diedit.
> Sedangkan `validateFinal()` adalah fungsi _lock_ (kunci). Mulanya fungsi ini mengunci semua siswa dalam kelas. Namun sekarang, fungsi ini menerima _array_ NIS (melalui fitur *Checkbox*). Controller menggunakan kueri `whereIn('nis', $array_nis)->update(['status_validasi' => 'Final'])` agar nilai-nilai siswa terpilih tak lagi bisa dimanipulasi melalui *form* input. 

**Q: Bagaimana logika pembuatan urutan _Ranking_ di `ReportController`?**
> **A:** Logika dipecah menjadi beberapa fungsi bantuan:
> 1. Controller mengambil data semua nilai akhir yang memiliki status `Final`.
> 2. Data dikelompokkan (menggunakan koleksi Laravel `groupBy()`) berdasarkan NIS siswa untuk menjumlahkan atau mencari rata-rata nilai seluruh mata pelajaran siswa tersebut.
> 3. Setelah nilai rata-rata tiap siswa ditemukan, Laravel Collection diurutkan (*sorting*) secara descending menggunakan `sortByDesc('rata_rata')`.
> 4. Jika filter yang dipilih adalah "Ranking Kelas", maka pengelompokan dikerjakan pada tiap ID kelas secara terpisah. Jika filter "Ranking Paralel", semua digabung.

**Q: Pada `DashboardController` Admin, bagaimana cara menghitung persentase "Siswa Lulus" dan "Perlu Perhatian"?**
> **A:** Dashboard menggunakan kemampuan Agregasi Database dan Koleksi (*Collection*). Controller memanggil kueri *count* untuk menghitung siswa yang nilai akhirnya melewati ambang batas. Kemudian untuk statistik per mata pelajaran, digunakan *raw query* atau fungsi `pluck` untuk memetakan berapa siswa yang berstatus 'Lulus' berbanding jumlah siswa total yang ada pada mata pelajaran tersebut, untuk mendapatkan representasi persentasenya (%).

---

## 4. Frontend (React, Inertia, Komponen)

**Q: Bagaimana fungsi _state management_ di React menangani nilai *input* yang banyak (misal daftar nilai 40 siswa)?**
> **A:** Nilai awal (_initial props_) dilempar dari Controller dalam bentuk struktur data _Dictionary_ (`nilai_map`), dengan *key* berupa NIS. Di React, setiap baris tabel adalah sub-komponen terpisah (misalnya `<NilaiRowInputs>`) yang memiliki `useState` mandiri. Komponen induk (tabel utama) hanya mengikat data tersebut melalui elemen `<form>` yang jika di-_submit_, secara otomatis merangkai nilai berdasar nama atribut HTML `name="nilai[12345][nilai_uts]"`. Inertia Form kemudian mengubahnya menjadi *payload JSON*. 

**Q: Saat guru mencentang validasi (fitur Checkbox), bagaimana daftar murid yang terpilih ini disimpan dan dikirim?**
> **A:** Frontend menggunakan Hook `useState<string[]>(window.selectedNis)` yang menyimpan _array_ NIS siswa. Setiap kali kotak centang pada murid ditekan, *event onChange* akan menambahkan atau menghapus NIS tersebut dari *array*. Pada saat tombol "Validasi Terpilih" ditekan, *array* ini dikirim via `router.post()` ke _backend_, yang kemudian memprosesnya di fungsi `validateFinal()`.

**Q: Apa fungsi dari `useFlashToast()` *custom hook* di Frontend?**
> **A:** Hook ini dirancang untuk "mendengarkan" properti `flash` dari respon HTTP Inertia (seperti pesan *success* atau *error* yang dilempar dari PHP menggunakan fungsi `back()->with('success', 'Pesan')`). Jika ada perubahan nilai *flash*, hook ini akan memicu *library* Sonner Toast untuk menampilkan notifikasi cantik (kartu pesan yang muncul di pojok layar) tanpa *reload* halaman.

---

## 5. Studi Kasus _Bug_ dan Pemecahan Masalah (Troubleshooting)

**Q: Mengapa *form* konfirmasi dialog terkadang "langsung tertutup, kemudian muncul lagi"? Bagaimana Anda memperbaiki *bug* itu?**
> **A:** Bug itu terjadi karena adanya bentrokan metode *submission*. Jika kita menggunakan komponen `<Form>` bawaan dari Inertia dan menimpa prosedur *onSubmit* tanpa memberikan perintah `e.preventDefault()`, *browser* akan mengambil alih _form_ itu dan melakukan *Native Reload* (seperti halaman web kuno). 
> **Solusi:** Menghapus komponen `<Form>` Inertia yang menumpuk di dalam Modals, mengubahnya menjadi HTML standar `<form>`, lalu memanggil `e.preventDefault(); router.post(...)` agar permintaan benar-benar berjalan secara *background* (SPA/AJAX) sehingga tidak merusak tampilan.

**Q: Apa yang terjadi jika guru menginput nilai "150" atau "-10" melalui alat *developer tool* (mem-bypass UI React)?**
> **A:** Meskipun ada peringatan visual `ERR` di antarmuka React, sistem *backend* `NilaiController` dilengkapi dengan metode pelindung `Nilai::validasiNilai()`. Jika input diluar 0-100, validasi *backend* akan langsung menggagalkan *request* tersebut dan mengembalikan HTTP 422 Unprocessable Entity serta melempar *error message* kembali ke UI. Hal ini menjamin keamanan integritas data database (prinsip _Never trust user input_).

**Q: Jika XAMPP menggunakan PHP 8.2, apa yang akan terjadi?**
> **A:** Saat mencoba menjalankan `composer install` atau `php artisan serve`, sistem akan menolak (*error fatal*), karena Laravel 11 membutuhkan minimal PHP 8.3. Pengguna perlu memperbarui XAMPP-nya agar *service* Apache/PHP menggunakan versi eksekusi biner yang relevan dengan spesifikasi aplikasi ini.

---

## 6. Persiapan Ujian Kompetensi (10 Skenario Modifikasi & Tambahan Fitur)

**Q1: Asesor meminta mengubah bobot perhitungan Nilai Akhir (misal: Tugas 20%, UTS 40%, UAS 40%). Di mana mengubahnya?**
> **A:** Buka file `app/Models/Nilai.php`. Cari fungsi `hitungNilaiAkhir()`, lalu ubah rasio perkalian matematikanya. Sistem ini memusatkan logika perhitungan di dalam Model, sehingga perubahan di satu file ini akan otomatis berdampak ke seluruh halaman aplikasi (Dashboard, Rapor, maupun Laporan).

**Q2: Bagaimana cara menambahkan _field_ / isian baru seperti "No. HP" pada form pendaftaran Siswa?**
> **A:** Ini membutuhkan pendekatan _Full Stack_:
> 1. Buat file migration baru (`php artisan make:migration add_no_hp_to_siswa_table`).
> 2. Buka `app/Models/Siswa.php` dan tambahkan `'no_hp'` ke atribut `$fillable`.
> 3. Buka `app/Http/Requests/Admin/SiswaRequest.php` dan tambah validasi `'no_hp' => 'nullable|numeric'`.
> 4. Buka React View di `resources/js/pages/admin/siswa/create.tsx` dan `edit.tsx`, lalu tambahkan komponen form input `<Input name="no_hp" />`.

**Q3: Penguji meminta untuk mengganti warna _button_ "Simpan" dari biru menjadi hijau. Bagaimana caranya?**
> **A:** Buka _file_ komponen terkait (biasanya di `resources/js/components/ui/button.tsx` atau langsung pada file form seperti `edit.tsx`). Cari atribut `className` dan ubah string _utility class_ bawaan Tailwind dari `bg-blue-600 hover:bg-blue-700` menjadi `bg-green-600 hover:bg-green-700`. Pastikan server Node.js (`npm run dev`) menyala agar CSS langsung di-_compile_.

**Q4: Bagaimana cara menambahkan filter pencarian spesifik (misal: "Hanya Laki-laki") pada tabel Siswa?**
> **A:** Di bagian _Frontend_ (`resources/js/pages/admin/siswa/index.tsx`), buat elemen *dropdown* `<select>`. Gunakan _hook_ kustom aplikasi `useInertiaSearch` untuk menembak query string (contoh: `?gender=L`). Di sisi _Backend_ (`SiswaController@index`), tambahkan logika dinamis di query Eloquent: `$query->when(request('gender'), fn($q, $gender) => $q->where('jenis_kelamin', $gender));`.

**Q5: Bagaimana jika penguji meminta siswa dapat meng-upload foto profil?**
> **A:** 
> 1. Tambah kolom `foto` di database (Migration) dan Model `$fillable`.
> 2. Ubah tipe validasi form menjadi `image|mimes:jpeg,png|max:2048`.
> 3. Di fungsi Controller, gunakan perintah `Storage::disk('public')->putFile(...)` lalu simpan _path_-nya ke database.
> 4. Pastikan form di React menggunakan pengiriman file (`forceFormData: true`).
> 5. Wajib menjalankan `php artisan storage:link` di terminal agar foto bisa diakses oleh HTML `<img src="..." />`.

**Q6: Bagaimana cara menambahkan role pengguna baru, misalnya "Kepala Sekolah"?**
> **A:** Ubah kolom ENUM `role` pada *migration* tabel `users`. Tambahkan *helper function* `isKepalaSekolah()` di model `User.php`. Pada `routes/web.php`, buat rute grup baru yang dilindungi middleware yang sesuai. Kemudian, sesuaikan render *Sidebar* di `app-layout.tsx` agar menyembunyikan tombol edit/tambah untuk role Kepala Sekolah.

**Q7: Penguji menemukan *bug*: Mengapa aplikasi ini tiba-tiba _logout_ sendiri kalau ditinggal agak lama?**
> **A:** Itu bukan _bug_, melainkan batasan keamanan sesi (_Session Lifetime_). Cara memperpanjangnya adalah dengan membuka file `.env` atau `config/session.php`. Ubah nilai variabel `SESSION_LIFETIME=120` (120 menit) menjadi angka yang lebih besar, misalnya `480` (8 jam).

**Q8: Penguji menantang: Bagaimana cara menyembunyikan Menu "Manajemen Pengguna" di sidebar jika yang login bukan Admin?**
> **A:** Buka berkas *layout* utama yaitu `resources/js/layouts/app-layout.tsx`. Cari blok kode navigasi menu. Gunakan *Conditional Rendering* bawaan React untuk membungkus komponen menu tersebut dengan pengecekan properti _auth_: `{auth.user.role === 'admin' && ( <MenuAdminItems /> )}`.

**Q9: Bagaimana membatasi secara ketat agar nilai yang diinput guru tidak boleh lewat dari angka 100?**
> **A:** Validasi ganda harus dilakukan. 
> - **Sisi Frontend:** Buka komponen input form di React (misal `resources/js/pages/guru/nilai/index.tsx`) dan pasang atribut HTML `max="100"` dan `min="0"` pada _tag_ `<input type="number">`.
> - **Sisi Backend:** Meskipun *browser* membatasi, peretas bisa melewatinya. Oleh karenanya, pastikan _logic_ di prosedur pelindung `Nilai::validasiNilai()` selalu memeriksa angka menggunakan kondisi IF (`if ($nilai < 0 || $nilai > 100) throw new Exception()`).

**Q10: Pada saat export Excel, bagaimana cara menambahkan satu kolom baru (misal Kolom NISN)?**
> **A:** Buka `app/Http/Controllers/Admin/ReportController.php`. Cari prosedur yang mengatur *export* file Excel (via *helper class* `XlsxWriter`). Anda tinggal menambahkan teks `'NISN'` pada *array header* baris pertama, lalu pada *looping array data* siswa yang sedang di-_generate_, sertakan parameter `$siswa->nisn` ke dalam _array_ baris baru tersebut.

---
_Dokumen ini dapat terus diperbarui jika terdapat modul baru dalam aplikasi._
