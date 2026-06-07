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

test('Siswa nilai page HANYA menampilkan nilai yang berstatus Final (Draft tersembunyi)', function () {
    $userSiswa = User::factory()->siswa()->create();
    $userGuru = User::factory()->guru()->create();
    $siswa = Siswa::create(['nis' => '00001', 'user_id' => $userSiswa->id, 'nama_siswa' => 'Ahmad', 'kelas_id' => $this->kelasId('X-A')]);
    $guru = Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')]);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Bahasa Indonesia')]);

    Nilai::create([
        'nis' => $siswa->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika'),
        'nilai_tugas' => 80, 'nilai_uts' => 70, 'nilai_uas' => 90, 'nilai_akhir' => 81, 'status_lulus' => 'Lulus',
        'status_validasi' => Nilai::STATUS_FINAL,
    ]);
    Nilai::create([
        'nis' => $siswa->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Bahasa Indonesia'),
        'nilai_tugas' => 60, 'nilai_uts' => 50, 'nilai_uas' => 55, 'nilai_akhir' => 55, 'status_lulus' => 'Tidak Lulus',
        'status_validasi' => Nilai::STATUS_DRAFT,
    ]);

    $response = $this->actingAs($userSiswa)->get('/siswa/nilai');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('siswa/nilai/index')
        ->has('nilai', 1)
        ->has('nilai.'.$this->kelasId('X-A').'|'.$this->mapelId('Matematika'), 1)
        ->missing('nilai.'.$this->kelasId('X-A').'|'.$this->mapelId('Bahasa Indonesia'))
    );
});

test('Siswa nilai page menampilkan chart_data hanya untuk nilai Final', function () {
    $userSiswa = User::factory()->siswa()->create();
    $userGuru = User::factory()->guru()->create();
    $siswa = Siswa::create(['nis' => '00001', 'user_id' => $userSiswa->id, 'nama_siswa' => 'Ahmad', 'kelas_id' => $this->kelasId('X-A')]);
    $guru = Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')]);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Bahasa Indonesia')]);

    Nilai::create([
        'nis' => $siswa->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika'),
        'nilai_tugas' => 80, 'nilai_uts' => 70, 'nilai_uas' => 90, 'nilai_akhir' => 81, 'status_lulus' => 'Lulus',
        'status_validasi' => Nilai::STATUS_FINAL,
    ]);
    Nilai::create([
        'nis' => $siswa->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Bahasa Indonesia'),
        'nilai_tugas' => 60, 'nilai_uts' => 50, 'nilai_uas' => 55, 'nilai_akhir' => 55, 'status_lulus' => 'Tidak Lulus',
        'status_validasi' => Nilai::STATUS_DRAFT,
    ]);

    $response = $this->actingAs($userSiswa)->get('/siswa/nilai');

    $response->assertInertia(fn ($page) => $page
        ->where('chart_data.overall.count', 1)
        ->where('chart_data.stats.total_mapel', 1)
        ->where('chart_data.stats.lulus', 1)
        ->where('chart_data.stats.tidak_lulus', 0)
        ->has('chart_data.per_mapel', 1)
        ->where('chart_data.per_mapel.0.mapel', 'Matematika')
    );
});

test('Siswa rapor PDF HANYA memuat nilai Final, bukan Draft', function () {
    $userSiswa = User::factory()->siswa()->create();
    $userGuru = User::factory()->guru()->create();
    $siswa = Siswa::create(['nis' => '00001', 'user_id' => $userSiswa->id, 'nama_siswa' => 'Ahmad', 'kelas_id' => $this->kelasId('X-A')]);
    $guru = Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')]);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Bahasa Indonesia')]);

    Nilai::create([
        'nis' => $siswa->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika'),
        'nilai_tugas' => 80, 'nilai_uts' => 70, 'nilai_uas' => 90, 'nilai_akhir' => 81, 'status_lulus' => 'Lulus',
        'status_validasi' => Nilai::STATUS_FINAL,
    ]);
    Nilai::create([
        'nis' => $siswa->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Bahasa Indonesia'),
        'nilai_tugas' => 60, 'nilai_uts' => 50, 'nilai_uas' => 55, 'nilai_akhir' => 55, 'status_lulus' => 'Tidak Lulus',
        'status_validasi' => Nilai::STATUS_DRAFT,
    ]);

    $response = $this->actingAs($userSiswa)->get('/siswa/rapor/pdf');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
});

test('Siswa dashboard has_nilai=true HANYA jika ada nilai Final', function () {
    $userSiswa = User::factory()->siswa()->create();
    $userGuru = User::factory()->guru()->create();
    $siswa = Siswa::create(['nis' => '00001', 'user_id' => $userSiswa->id, 'nama_siswa' => 'Ahmad', 'kelas_id' => $this->kelasId('X-A')]);
    $guru = Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')]);

    Nilai::create([
        'nis' => $siswa->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika'),
        'nilai_tugas' => 80, 'nilai_uts' => 70, 'nilai_uas' => 90, 'nilai_akhir' => 81, 'status_lulus' => 'Lulus',
        'status_validasi' => Nilai::STATUS_DRAFT,
    ]);

    $this->actingAs($userSiswa)->get('/siswa/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('has_nilai', false)
        );

    $first = Nilai::where('nis', $siswa->nis)->first();
    $first->update(['status_validasi' => Nilai::STATUS_FINAL]);

    $this->actingAs($userSiswa)->get('/siswa/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('has_nilai', true)
        );
});

test('Siswa hanya melihat nilai Final miliknya sendiri, bukan Draft siswa lain', function () {
    $userSiswaA = User::factory()->siswa()->create();
    $userSiswaB = User::factory()->siswa()->create();
    $userGuru = User::factory()->guru()->create();
    $siswaA = Siswa::create(['nis' => '00001', 'user_id' => $userSiswaA->id, 'nama_siswa' => 'Siswa A', 'kelas_id' => $this->kelasId('X-A')]);
    $siswaB = Siswa::create(['nis' => '00002', 'user_id' => $userSiswaB->id, 'nama_siswa' => 'Siswa B', 'kelas_id' => $this->kelasId('X-A')]);
    $guru = Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')]);

    Nilai::create([
        'nis' => $siswaA->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika'),
        'nilai_tugas' => 80, 'nilai_uts' => 70, 'nilai_uas' => 90, 'nilai_akhir' => 81, 'status_lulus' => 'Lulus',
        'status_validasi' => Nilai::STATUS_FINAL,
    ]);
    Nilai::create([
        'nis' => $siswaB->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika'),
        'nilai_tugas' => 50, 'nilai_uts' => 60, 'nilai_uas' => 65, 'nilai_akhir' => 59, 'status_lulus' => 'Tidak Lulus',
        'status_validasi' => Nilai::STATUS_DRAFT,
    ]);

    $response = $this->actingAs($userSiswaA)->get('/siswa/nilai');

    $response->assertInertia(fn ($page) => $page
        ->where('siswa.nis', '00001')
        ->has('nilai', 1)
        ->where('nilai.'.$this->kelasId('X-A').'|'.$this->mapelId('Matematika').'.0.nilai_akhir', '81.00')
    );
});

test('Siswa melihat semua nilai Final (mixed mapel Final + Draft) sesuai yang divalidasi', function () {
    $userSiswa = User::factory()->siswa()->create();
    $userGuru = User::factory()->guru()->create();
    $siswa = Siswa::create(['nis' => '00001', 'user_id' => $userSiswa->id, 'nama_siswa' => 'Ahmad', 'kelas_id' => $this->kelasId('X-A')]);
    $guru = Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Sari']);

    $mapels = ['Matematika', 'Bahasa Indonesia', 'IPA', 'IPS'];
    foreach ($mapels as $i => $m) {
        GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId($m)]);
        Nilai::create([
            'nis' => $siswa->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId($m),
            'nilai_tugas' => 80, 'nilai_uts' => 70, 'nilai_uas' => 90, 'nilai_akhir' => 81, 'status_lulus' => 'Lulus',
            'status_validasi' => $i % 2 === 0 ? Nilai::STATUS_FINAL : Nilai::STATUS_DRAFT,
        ]);
    }

    $this->actingAs($userSiswa)->get('/siswa/nilai')
        ->assertInertia(fn ($page) => $page
            ->has('nilai', 2)
            ->has('nilai.'.$this->kelasId('X-A').'|'.$this->mapelId('Matematika'), 1)
            ->has('nilai.'.$this->kelasId('X-A').'|'.$this->mapelId('IPA'), 1)
            ->missing('nilai.'.$this->kelasId('X-A').'|'.$this->mapelId('Bahasa Indonesia'))
            ->missing('nilai.'.$this->kelasId('X-A').'|'.$this->mapelId('IPS'))
        );
});
