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

test('Guru: dashboard menampilkan stats utama dengan total yang benar', function () {
    $userGuru = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Sari']);

    $kelasId = $this->kelasId('X-A');
    $mapelId = $this->mapelId('Matematika');

    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $kelasId, 'mata_pelajaran_id' => $mapelId]);

    $siswa1 = Siswa::create(['nis' => '00001', 'nama_siswa' => 'Ahmad', 'kelas_id' => $kelasId]);
    $siswa2 = Siswa::create(['nis' => '00002', 'nama_siswa' => 'Budi', 'kelas_id' => $kelasId]);

    Nilai::create([
        'nis' => $siswa1->nis, 'id_guru' => $guru->id, 'kelas_id' => $kelasId, 'mata_pelajaran_id' => $mapelId,
        'nilai_tugas' => 80, 'nilai_uts' => 80, 'nilai_uas' => 80, 'nilai_akhir' => 80,
        'status_lulus' => Nilai::LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);

    Nilai::create([
        'nis' => $siswa2->nis, 'id_guru' => $guru->id, 'kelas_id' => $kelasId, 'mata_pelajaran_id' => $mapelId,
        'nilai_tugas' => 50, 'nilai_uts' => 50, 'nilai_uas' => 50, 'nilai_akhir' => 50,
        'status_lulus' => Nilai::TIDAK_LULUS, 'status_validasi' => Nilai::STATUS_DRAFT,
    ]);

    $this->actingAs($userGuru)
        ->get('/guru/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guru/dashboard')
            ->where('guru.nama_guru', 'Ibu Sari')
            ->where('stats.total_siswa', 2)
            ->where('stats.total_nilai', 2)
            ->where('stats.draft', 1)
            ->where('stats.final', 1)
            ->where('stats.lulus', 1)
            ->where('stats.tidak_lulus', 1)
            ->where('stats.rata_rata', 65)
            ->has('mengajar', 1)
            ->where('mengajar.0.kelas', 'X-A')
            ->where('mengajar.0.mata_pelajaran', 'Matematika')
            ->has('per_combo_stats', 1)
            ->where('per_combo_stats.0.jumlah_siswa', 2)
            ->where('per_combo_stats.0.jumlah_input', 2)
            ->where('per_combo_stats.0.jumlah_final', 1)
            ->where('per_combo_stats.0.jumlah_draft', 1)
        );
});
