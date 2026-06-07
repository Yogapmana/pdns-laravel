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
    Siswa::create(['nis' => '00001', 'nama_siswa' => 'Siswa 1', 'kelas_id' => $this->kelasId('X-A')]);
    Siswa::create(['nis' => '00002', 'nama_siswa' => 'Siswa 2', 'kelas_id' => $this->kelasId('X-A')]);
    Siswa::create(['nis' => '00003', 'nama_siswa' => 'Siswa 3', 'kelas_id' => $this->kelasId('X-A')]);
    Siswa::create(['nis' => '00004', 'nama_siswa' => 'Siswa 4', 'kelas_id' => $this->kelasId('X-B')]);
    Siswa::create(['nis' => '00005', 'nama_siswa' => 'Siswa 5', 'kelas_id' => $this->kelasId('X-B')]);
    Guru::create(['nama_guru' => 'Guru 1']);
    Guru::create(['nama_guru' => 'Guru 2']);

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
    $guru = Guru::create(['nama_guru' => 'Ibu Guru']);
    $siswaA = Siswa::create(['nis' => '00001', 'nama_siswa' => 'Alpha', 'kelas_id' => $this->kelasId('X-A')]);
    $siswaB = Siswa::create(['nis' => '00002', 'nama_siswa' => 'Beta', 'kelas_id' => $this->kelasId('X-A')]);
    $siswaC = Siswa::create(['nis' => '00003', 'nama_siswa' => 'Gamma', 'kelas_id' => $this->kelasId('X-B')]);

    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')]);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-B'), 'mata_pelajaran_id' => $this->mapelId('Matematika')]);

    Nilai::create([
        'nis' => $siswaA->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika'),
        'nilai_tugas' => 80, 'nilai_uts' => 80, 'nilai_uas' => 80,
        'nilai_akhir' => 80, 'status_lulus' => Nilai::LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);
    Nilai::create([
        'nis' => $siswaB->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika'),
        'nilai_tugas' => 60, 'nilai_uts' => 60, 'nilai_uas' => 60,
        'nilai_akhir' => 60, 'status_lulus' => Nilai::TIDAK_LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);
    Nilai::create([
        'nis' => $siswaC->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-B'), 'mata_pelajaran_id' => $this->mapelId('Matematika'),
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
    $guru = Guru::create(['nama_guru' => 'Ibu Guru']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')]);

    $siswa1 = Siswa::create(['nis' => '00001', 'nama_siswa' => 'A1', 'kelas_id' => $this->kelasId('X-A')]);
    $siswa2 = Siswa::create(['nis' => '00002', 'nama_siswa' => 'A2', 'kelas_id' => $this->kelasId('X-A')]);
    $siswa3 = Siswa::create(['nis' => '00003', 'nama_siswa' => 'B1', 'kelas_id' => $this->kelasId('X-B')]);

    Nilai::create([
        'nis' => $siswa1->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika'),
        'nilai_tugas' => 80, 'nilai_uts' => 80, 'nilai_uas' => 80,
        'nilai_akhir' => 80, 'status_lulus' => Nilai::LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);
    Nilai::create([
        'nis' => $siswa2->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika'),
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
    $guru = Guru::create(['nama_guru' => 'Ibu Guru']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')]);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Bahasa Indonesia')]);

    $s1 = Siswa::create(['nis' => '00001', 'nama_siswa' => 'S1', 'kelas_id' => $this->kelasId('X-A')]);
    $s2 = Siswa::create(['nis' => '00002', 'nama_siswa' => 'S2', 'kelas_id' => $this->kelasId('X-A')]);
    $s3 = Siswa::create(['nis' => '00003', 'nama_siswa' => 'S3', 'kelas_id' => $this->kelasId('X-A')]);

    Nilai::create([
        'nis' => $s1->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika'),
        'nilai_tugas' => 90, 'nilai_uts' => 90, 'nilai_uas' => 90,
        'nilai_akhir' => 90, 'status_lulus' => Nilai::LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);
    Nilai::create([
        'nis' => $s2->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika'),
        'nilai_tugas' => 80, 'nilai_uts' => 80, 'nilai_uas' => 80,
        'nilai_akhir' => 80, 'status_lulus' => Nilai::LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);
    Nilai::create([
        'nis' => $s3->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Bahasa Indonesia'),
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
    $guru = Guru::create(['nama_guru' => 'Ibu Guru']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')]);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Bahasa Indonesia')]);

    for ($i = 1; $i <= 6; $i++) {
        $siswa = Siswa::create(['nis' => str_pad((string) $i, 5, '0', STR_PAD_LEFT), 'nama_siswa' => 'Siswa '.$i, 'kelas_id' => $this->kelasId('X-A')]);
        $avg = 70 + $i;

        Nilai::create([
            'nis' => $siswa->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika'),
            'nilai_tugas' => $avg, 'nilai_uts' => $avg, 'nilai_uas' => $avg,
            'nilai_akhir' => (float) $avg, 'status_lulus' => $avg >= 70 ? Nilai::LULUS : Nilai::TIDAK_LULUS,
            'status_validasi' => Nilai::STATUS_FINAL,
        ]);
        Nilai::create([
            'nis' => $siswa->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Bahasa Indonesia'),
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
    $guru = Guru::create(['nama_guru' => 'Ibu Guru']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')]);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Bahasa Indonesia')]);

    $berprestasi = Siswa::create(['nis' => '00001', 'nama_siswa' => 'Top Student', 'kelas_id' => $this->kelasId('X-A')]);
    Nilai::create([
        'nis' => $berprestasi->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika'),
        'nilai_tugas' => 90, 'nilai_uts' => 90, 'nilai_uas' => 90,
        'nilai_akhir' => 90, 'status_lulus' => Nilai::LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);
    Nilai::create([
        'nis' => $berprestasi->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Bahasa Indonesia'),
        'nilai_tugas' => 95, 'nilai_uts' => 95, 'nilai_uas' => 95,
        'nilai_akhir' => 95, 'status_lulus' => Nilai::LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);

    $perluPerhatian = Siswa::create(['nis' => '00002', 'nama_siswa' => 'Struggling Student', 'kelas_id' => $this->kelasId('X-A')]);
    Nilai::create([
        'nis' => $perluPerhatian->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika'),
        'nilai_tugas' => 50, 'nilai_uts' => 50, 'nilai_uas' => 50,
        'nilai_akhir' => 50, 'status_lulus' => Nilai::TIDAK_LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);
    Nilai::create([
        'nis' => $perluPerhatian->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Bahasa Indonesia'),
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
    $guru = Guru::create(['nama_guru' => 'Ibu Guru']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')]);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Bahasa Indonesia')]);

    $worst = Siswa::create(['nis' => '00001', 'nama_siswa' => 'Worst', 'kelas_id' => $this->kelasId('X-A')]);
    Nilai::create([
        'nis' => $worst->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika'),
        'nilai_tugas' => 40, 'nilai_uts' => 40, 'nilai_uas' => 40,
        'nilai_akhir' => 40, 'status_lulus' => Nilai::TIDAK_LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);
    Nilai::create([
        'nis' => $worst->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Bahasa Indonesia'),
        'nilai_tugas' => 40, 'nilai_uts' => 40, 'nilai_uas' => 40,
        'nilai_akhir' => 40, 'status_lulus' => Nilai::TIDAK_LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);

    $half = Siswa::create(['nis' => '00002', 'nama_siswa' => 'Half', 'kelas_id' => $this->kelasId('X-A')]);
    Nilai::create([
        'nis' => $half->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika'),
        'nilai_tugas' => 80, 'nilai_uts' => 80, 'nilai_uas' => 80,
        'nilai_akhir' => 80, 'status_lulus' => Nilai::LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);
    Nilai::create([
        'nis' => $half->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Bahasa Indonesia'),
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
    $guru = Guru::create(['nama_guru' => 'Ibu Guru']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')]);

    $siswa = Siswa::create(['nis' => '00001', 'nama_siswa' => 'Siswa', 'kelas_id' => $this->kelasId('X-A')]);
    Nilai::create([
        'nis' => $siswa->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika'),
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
