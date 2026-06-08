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
_Dokumen ini dapat terus diperbarui jika terdapat modul baru dalam aplikasi._
