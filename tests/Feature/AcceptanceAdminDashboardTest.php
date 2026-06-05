<?php

declare(strict_types=1);

use App\Models\Guru;
use App\Models\GuruMengajar;
use App\Models\Nilai;
use App\Models\Siswa;
use App\Models\User;
use Tests\Traits\SeedsAkademikMasters;

uses(SeedsAkademikMasters::class);

beforeEach(function () {
    $this->seedKelas();
    $this->seedMataPelajaran();
});

test('Admin: dashboard menampilkan stats utama dengan total yang benar', function () {
    Siswa::factory()->count(3)->create(['kelas' => 'X-A']);
    Siswa::factory()->count(2)->create(['kelas' => 'X-B']);
    Guru::factory()->count(2)->create();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/dashboard')
            ->where('stats.total_siswa', 5)
            ->where('stats.total_guru', 2)
            ->where('stats.total_nilai', 0)
            ->where('stats.total_mapel', 10)
            ->where('stats.lulus', 0)
            ->where('stats.tidak_lulus', 0)
            ->where('stats.persentase_lulus', 0)
            ->where('kkm', 70)
        );
});

test('Admin: dashboard menghitung persentase lulus dari total nilai', function () {
    $guru = Guru::factory()->create();
    $siswaA = Siswa::factory()->create(['kelas' => 'X-A', 'nama_siswa' => 'Alpha']);
    $siswaB = Siswa::factory()->create(['kelas' => 'X-A', 'nama_siswa' => 'Beta']);
    $siswaC = Siswa::factory()->create(['kelas' => 'X-B', 'nama_siswa' => 'Gamma']);

    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-B', 'mata_pelajaran' => 'Matematika']);

    Nilai::create([
        'nis' => $siswaA->nis, 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika',
        'nilai_tugas' => 80, 'nilai_uts' => 80, 'nilai_uas' => 80,
        'nilai_akhir' => 80, 'status_lulus' => Nilai::LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);
    Nilai::create([
        'nis' => $siswaB->nis, 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika',
        'nilai_tugas' => 60, 'nilai_uts' => 60, 'nilai_uas' => 60,
        'nilai_akhir' => 60, 'status_lulus' => Nilai::TIDAK_LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);
    Nilai::create([
        'nis' => $siswaC->nis, 'id_guru' => $guru->id, 'kelas' => 'X-B', 'mata_pelajaran' => 'Matematika',
        'nilai_tugas' => 90, 'nilai_uts' => 90, 'nilai_uas' => 90,
        'nilai_akhir' => 90, 'status_lulus' => Nilai::LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('stats.total_nilai', 3)
            ->where('stats.lulus', 2)
            ->where('stats.tidak_lulus', 1)
            ->where('stats.persentase_lulus', 66.7)
        );
});

test('Admin: rekap per kelas berisi jumlah siswa, lulus, tidak lulus, dan persentase', function () {
    $guru = Guru::factory()->create();
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);

    $siswa1 = Siswa::factory()->create(['kelas' => 'X-A', 'nama_siswa' => 'A1']);
    $siswa2 = Siswa::factory()->create(['kelas' => 'X-A', 'nama_siswa' => 'A2']);
    $siswa3 = Siswa::factory()->create(['kelas' => 'X-B', 'nama_siswa' => 'B1']);

    Nilai::create([
        'nis' => $siswa1->nis, 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika',
        'nilai_tugas' => 80, 'nilai_uts' => 80, 'nilai_uas' => 80,
        'nilai_akhir' => 80, 'status_lulus' => Nilai::LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);
    Nilai::create([
        'nis' => $siswa2->nis, 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika',
        'nilai_tugas' => 50, 'nilai_uts' => 50, 'nilai_uas' => 50,
        'nilai_akhir' => 50, 'status_lulus' => Nilai::TIDAK_LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('rekap_per_kelas', 2)
            ->where('rekap_per_kelas.0.kelas', 'X-A')
            ->where('rekap_per_kelas.0.jumlah_siswa', 2)
            ->where('rekap_per_kelas.0.lulus', 1)
            ->where('rekap_per_kelas.0.tidak_lulus', 1)
            ->where('rekap_per_kelas.0.total_nilai', 2)
            ->where('rekap_per_kelas.0.persentase_lulus', 50)
            ->where('rekap_per_kelas.1.persentase_lulus', 0)
            ->where('rekap_per_kelas.1.kelas', 'X-B')
            ->where('rekap_per_kelas.1.jumlah_siswa', 1)
            ->where('rekap_per_kelas.1.total_nilai', 0)
            ->where('rekap_per_kelas.1.persentase_lulus', 0)
        );
});

test('Admin: rata-rata per mapel diurutkan descending dan menghitung persentase lulus', function () {
    $guru = Guru::factory()->create();
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Bahasa Indonesia']);

    $s1 = Siswa::factory()->create(['kelas' => 'X-A']);
    $s2 = Siswa::factory()->create(['kelas' => 'X-A']);
    $s3 = Siswa::factory()->create(['kelas' => 'X-A']);

    Nilai::create([
        'nis' => $s1->nis, 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika',
        'nilai_tugas' => 90, 'nilai_uts' => 90, 'nilai_uas' => 90,
        'nilai_akhir' => 90, 'status_lulus' => Nilai::LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);
    Nilai::create([
        'nis' => $s2->nis, 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika',
        'nilai_tugas' => 80, 'nilai_uts' => 80, 'nilai_uas' => 80,
        'nilai_akhir' => 80, 'status_lulus' => Nilai::LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);
    Nilai::create([
        'nis' => $s3->nis, 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Bahasa Indonesia',
        'nilai_tugas' => 60, 'nilai_uts' => 60, 'nilai_uas' => 60,
        'nilai_akhir' => 60, 'status_lulus' => Nilai::TIDAK_LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('rata_rata_per_mapel', 2)
            ->where('rata_rata_per_mapel.0.mata_pelajaran', 'Matematika')
            ->where('rata_rata_per_mapel.0.rata_rata', 85)
            ->where('rata_rata_per_mapel.0.total_nilai', 2)
            ->where('rata_rata_per_mapel.0.lulus', 2)
            ->where('rata_rata_per_mapel.0.tidak_lulus', 0)
            ->where('rata_rata_per_mapel.0.persentase_lulus', 100)
            ->where('rata_rata_per_mapel.1.mata_pelajaran', 'Bahasa Indonesia')
            ->where('rata_rata_per_mapel.1.rata_rata', 60)
            ->where('rata_rata_per_mapel.1.persentase_lulus', 0)
        );
});

test('Admin: top siswa diurutkan descending berdasarkan rata-rata dan dibatasi 5', function () {
    $guru = Guru::factory()->create();
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Bahasa Indonesia']);

    for ($i = 1; $i <= 6; $i++) {
        $siswa = Siswa::factory()->create(['kelas' => 'X-A', 'nama_siswa' => 'Siswa '.$i]);
        $avg = 70 + $i;

        Nilai::create([
            'nis' => $siswa->nis, 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika',
            'nilai_tugas' => $avg, 'nilai_uts' => $avg, 'nilai_uas' => $avg,
            'nilai_akhir' => (float) $avg, 'status_lulus' => $avg >= 70 ? Nilai::LULUS : Nilai::TIDAK_LULUS,
            'status_validasi' => Nilai::STATUS_FINAL,
        ]);
        Nilai::create([
            'nis' => $siswa->nis, 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Bahasa Indonesia',
            'nilai_tugas' => $avg, 'nilai_uts' => $avg, 'nilai_uas' => $avg,
            'nilai_akhir' => (float) $avg, 'status_lulus' => $avg >= 70 ? Nilai::LULUS : Nilai::TIDAK_LULUS,
            'status_validasi' => Nilai::STATUS_FINAL,
        ]);
    }

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('top_siswa', 5)
            ->where('top_siswa.0.nama_siswa', 'Siswa 6')
            ->where('top_siswa.0.rata_rata', 76)
            ->where('top_siswa.4.nama_siswa', 'Siswa 2')
            ->where('top_siswa.4.rata_rata', 72)
        );
});

test('Admin: siswa perhatian hanya berisi siswa dengan minimal 1 mapel tidak lulus', function () {
    $guru = Guru::factory()->create();
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Bahasa Indonesia']);

    $berprestasi = Siswa::factory()->create(['kelas' => 'X-A', 'nama_siswa' => 'Top Student']);
    Nilai::create([
        'nis' => $berprestasi->nis, 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika',
        'nilai_tugas' => 90, 'nilai_uts' => 90, 'nilai_uas' => 90,
        'nilai_akhir' => 90, 'status_lulus' => Nilai::LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);
    Nilai::create([
        'nis' => $berprestasi->nis, 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Bahasa Indonesia',
        'nilai_tugas' => 95, 'nilai_uts' => 95, 'nilai_uas' => 95,
        'nilai_akhir' => 95, 'status_lulus' => Nilai::LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);

    $perluPerhatian = Siswa::factory()->create(['kelas' => 'X-A', 'nama_siswa' => 'Struggling Student']);
    Nilai::create([
        'nis' => $perluPerhatian->nis, 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika',
        'nilai_tugas' => 50, 'nilai_uts' => 50, 'nilai_uas' => 50,
        'nilai_akhir' => 50, 'status_lulus' => Nilai::TIDAK_LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);
    Nilai::create([
        'nis' => $perluPerhatian->nis, 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Bahasa Indonesia',
        'nilai_tugas' => 60, 'nilai_uts' => 60, 'nilai_uas' => 60,
        'nilai_akhir' => 60, 'status_lulus' => Nilai::TIDAK_LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('siswa_perhatian', 1)
            ->where('siswa_perhatian.0.nis', $perluPerhatian->nis)
            ->where('siswa_perhatian.0.nama_siswa', 'Struggling Student')
            ->where('siswa_perhatian.0.tidak_lulus', 2)
            ->where('siswa_perhatian.0.total_mapel', 2)
            ->where('siswa_perhatian.0.rasio_tidak_lulus', 100)
        );
});

test('Admin: siswa perhatian diurutkan berdasarkan rasio tidak lulus desc, lalu rata-rata asc', function () {
    $guru = Guru::factory()->create();
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Bahasa Indonesia']);

    $worst = Siswa::factory()->create(['kelas' => 'X-A', 'nama_siswa' => 'Worst']);
    Nilai::create([
        'nis' => $worst->nis, 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika',
        'nilai_tugas' => 40, 'nilai_uts' => 40, 'nilai_uas' => 40,
        'nilai_akhir' => 40, 'status_lulus' => Nilai::TIDAK_LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);
    Nilai::create([
        'nis' => $worst->nis, 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Bahasa Indonesia',
        'nilai_tugas' => 40, 'nilai_uts' => 40, 'nilai_uas' => 40,
        'nilai_akhir' => 40, 'status_lulus' => Nilai::TIDAK_LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);

    $half = Siswa::factory()->create(['kelas' => 'X-A', 'nama_siswa' => 'Half']);
    Nilai::create([
        'nis' => $half->nis, 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika',
        'nilai_tugas' => 80, 'nilai_uts' => 80, 'nilai_uas' => 80,
        'nilai_akhir' => 80, 'status_lulus' => Nilai::LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);
    Nilai::create([
        'nis' => $half->nis, 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Bahasa Indonesia',
        'nilai_tugas' => 40, 'nilai_uts' => 40, 'nilai_uas' => 40,
        'nilai_akhir' => 40, 'status_lulus' => Nilai::TIDAK_LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('siswa_perhatian', 2)
            ->where('siswa_perhatian.0.nama_siswa', 'Worst')
            ->where('siswa_perhatian.0.rasio_tidak_lulus', 100)
            ->where('siswa_perhatian.1.nama_siswa', 'Half')
            ->where('siswa_perhatian.1.rasio_tidak_lulus', 50)
        );
});

test('Admin: siswa perhatian kosong ketika semua siswa lulus', function () {
    $guru = Guru::factory()->create();
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);

    $siswa = Siswa::factory()->create(['kelas' => 'X-A']);
    Nilai::create([
        'nis' => $siswa->nis, 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika',
        'nilai_tugas' => 80, 'nilai_uts' => 80, 'nilai_uas' => 80,
        'nilai_akhir' => 80, 'status_lulus' => Nilai::LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('siswa_perhatian', 0)
        );
});

test('Admin: dashboard dapat diakses oleh admin dan ditolak untuk guru/siswa', function () {
    $guru = User::factory()->guru()->create();
    $siswa = User::factory()->siswa()->create();

    $this->actingAs($guru)
        ->get('/admin/dashboard')
        ->assertForbidden();

    $this->actingAs($siswa)
        ->get('/admin/dashboard')
        ->assertForbidden();
});

test('Admin: dashboard dapat diakses tanpa login akan redirect ke login', function () {
    $this->get('/admin/dashboard')
        ->assertRedirect('/login');
});
