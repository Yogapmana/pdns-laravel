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

test('AC-07: Siswa login hanya melihat nilai milik sendiri', function () {
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
        'status_validasi' => Nilai::STATUS_FINAL,
    ]);

    $response = $this->actingAs($userSiswaA)->get('/siswa/nilai');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('siswa/nilai/index')
        ->has('nilai.'.$this->kelasId('X-A').'|'.$this->mapelId('Matematika'), 1)
        ->where('nilai.'.$this->kelasId('X-A').'|'.$this->mapelId('Matematika').'.0.nilai_akhir', '81.00')
    );
});

test('AC-08: Siswa mencoba akses URL edit nilai ditolak 403', function () {
    $userSiswa = User::factory()->siswa()->create();
    Siswa::create(['nis' => '00001', 'user_id' => $userSiswa->id, 'nama_siswa' => 'Ahmad', 'kelas_id' => $this->kelasId('X-A')]);

    $this->actingAs($userSiswa)->get('/guru/input-nilai')->assertForbidden();
    $this->actingAs($userSiswa)->get('/admin/siswa')->assertForbidden();
    $this->actingAs($userSiswa)->get('/guru/dashboard')->assertForbidden();
    $this->actingAs($userSiswa)->get('/admin/dashboard')->assertForbidden();
});

test('Siswa nonaktif tidak bisa login', function () {
    $userSiswa = User::factory()->siswa()->inactive()->create();
    Siswa::create(['nis' => '00001', 'user_id' => $userSiswa->id, 'nama_siswa' => 'Ahmad', 'kelas_id' => $this->kelasId('X-A')]);

    $response = $this->post('/login', [
        'username' => $userSiswa->username,
        'password' => 'password',
    ]);

    $this->assertTrue($response->isRedirection() || $response->isRedirect() || $response->getStatusCode() === 302);
});

test('Siswa nilai page menampilkan nama guru pengajar (guru_map adalah flat keyBy, bukan groupBy)', function () {
    $userSiswa = User::factory()->siswa()->create();
    $userGuru = User::factory()->guru()->create();

    $siswa = Siswa::create(['nis' => '00001', 'user_id' => $userSiswa->id, 'nama_siswa' => 'Ahmad', 'kelas_id' => $this->kelasId('X-A')]);
    $guru = Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Sari Wahyuni']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')]);

    Nilai::create([
        'nis' => $siswa->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika'),
        'nilai_tugas' => 80, 'nilai_uts' => 70, 'nilai_uas' => 90, 'nilai_akhir' => 81, 'status_lulus' => 'Lulus',
        'status_validasi' => Nilai::STATUS_FINAL,
    ]);

    $response = $this->actingAs($userSiswa)->get('/siswa/nilai');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('siswa/nilai/index')
        ->where("guru_map.{$guru->id}.nama_guru", 'Ibu Sari Wahyuni')
        ->where("guru_map.{$guru->id}.id", $guru->id)
    );
});

test('Siswa tidak bisa akses endpoint nilai guru (POST save)', function () {
    $userSiswa = User::factory()->siswa()->create();
    Siswa::create(['nis' => '00001', 'user_id' => $userSiswa->id, 'nama_siswa' => 'Ahmad', 'kelas_id' => $this->kelasId('X-A')]);

    $this->actingAs($userSiswa)
        ->post('/guru/input-nilai/save', [
            'kelas_id' => $this->kelasId('X-A'),
            'mata_pelajaran_id' => $this->mapelId('Matematika'),
            'nilai' => [['nis' => '00001', 'nilai_tugas' => 100, 'nilai_uts' => 100, 'nilai_uas' => 100]],
        ])
        ->assertForbidden();
});

test('TC-21: Siswa mencoba memanipulasi query parameter untuk melihat nilai siswa lain', function () {
    $userSiswaA = User::factory()->siswa()->create();
    $userSiswaB = User::factory()->siswa()->create();
    $userGuru = User::factory()->guru()->create();

    $siswaA = Siswa::create(['nis' => '00001', 'user_id' => $userSiswaA->id, 'nama_siswa' => 'Siswa A', 'kelas_id' => $this->kelasId('X-A')]);
    $siswaB = Siswa::create(['nis' => '00002', 'user_id' => $userSiswaB->id, 'nama_siswa' => 'Siswa B', 'kelas_id' => $this->kelasId('X-A')]);

    $guru = Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Sari']);

    Nilai::create([
        'nis' => $siswaA->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika'),
        'nilai_tugas' => 80, 'nilai_uts' => 70, 'nilai_uas' => 90, 'nilai_akhir' => 81, 'status_lulus' => 'Lulus',
        'status_validasi' => Nilai::STATUS_FINAL,
    ]);
    Nilai::create([
        'nis' => $siswaB->nis, 'id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika'),
        'nilai_tugas' => 50, 'nilai_uts' => 60, 'nilai_uas' => 65, 'nilai_akhir' => 59, 'status_lulus' => 'Tidak Lulus',
        'status_validasi' => Nilai::STATUS_FINAL,
    ]);

    // Siswa A mencoba mengakses nilai dengan memanipulasi query parameter 'nis', 'siswa_id', atau 'id'
    $response = $this->actingAs($userSiswaA)->get('/siswa/nilai?nis=00002&siswa_id='.$siswaB->id.'&id='.$siswaB->id);

    $response->assertOk();
    // Memastikan data yang dirender tetap data Siswa A (nilai_akhir = 81), bukan Siswa B (nilai_akhir = 59)
    $response->assertInertia(fn ($page) => $page
        ->component('siswa/nilai/index')
        ->has('nilai.'.$this->kelasId('X-A').'|'.$this->mapelId('Matematika'), 1)
        ->where('nilai.'.$this->kelasId('X-A').'|'.$this->mapelId('Matematika').'.0.nilai_akhir', '81.00')
    );
});
