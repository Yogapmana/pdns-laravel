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

test('AC-04: Guru menginput nilai tugas=105 ditolak dengan validasi 0-100', function () {
    $userGuru = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')]);
    Siswa::create(['nis' => '00001', 'nama_siswa' => 'Ahmad', 'kelas_id' => $this->kelasId('X-A')]);

    $response = $this->actingAs($userGuru)->post('/guru/input-nilai/save', [
        'kelas_id' => $this->kelasId('X-A'),
        'mata_pelajaran_id' => $this->mapelId('Matematika'),
        'nilai' => [
            [
                'nis' => '00001',
                'nilai_tugas' => 105,
                'nilai_uts' => 70,
                'nilai_uas' => 90,
            ],
        ],
    ]);

    $response->assertSessionHasErrors();
    expect(Nilai::where('nis', '00001')->count())->toBe(0);
});

test('AC-04b: Guru menginput nilai negatif ditolak', function () {
    $userGuru = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')]);
    Siswa::create(['nis' => '00001', 'nama_siswa' => 'Ahmad', 'kelas_id' => $this->kelasId('X-A')]);

    $response = $this->actingAs($userGuru)->post('/guru/input-nilai/save', [
        'kelas_id' => $this->kelasId('X-A'),
        'mata_pelajaran_id' => $this->mapelId('Matematika'),
        'nilai' => [
            [
                'nis' => '00001',
                'nilai_tugas' => -5,
                'nilai_uts' => 70,
                'nilai_uas' => 90,
            ],
        ],
    ]);

    $response->assertSessionHasErrors();
    expect(Nilai::where('nis', '00001')->count())->toBe(0);
});

test('AC-05: Guru input tugas=80 UTS=70 UAS=90 → Nilai Akhir=81, Lulus', function () {
    $userGuru = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')]);
    Siswa::create(['nis' => '00001', 'nama_siswa' => 'Ahmad', 'kelas_id' => $this->kelasId('X-A')]);

    $response = $this->actingAs($userGuru)->post('/guru/input-nilai/save', [
        'kelas_id' => $this->kelasId('X-A'),
        'mata_pelajaran_id' => $this->mapelId('Matematika'),
        'nilai' => [
            [
                'nis' => '00001',
                'nilai_tugas' => 80,
                'nilai_uts' => 70,
                'nilai_uas' => 90,
            ],
        ],
    ]);

    $response->assertSessionHas('success');
    $nilai = Nilai::where('nis', '00001')
        ->where('kelas_id', $this->kelasId('X-A'))
        ->where('mata_pelajaran_id', $this->mapelId('Matematika'))
        ->first();
    expect($nilai)->not->toBeNull();
    expect((float) $nilai->nilai_akhir)->toBe(81.0);
    expect($nilai->status_lulus)->toBe('Lulus');
});

test('AC-06: Guru input tugas=50 UTS=60 UAS=65 → Nilai Akhir=59, Tidak Lulus', function () {
    $userGuru = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')]);
    Siswa::create(['nis' => '00001', 'nama_siswa' => 'Ahmad', 'kelas_id' => $this->kelasId('X-A')]);

    $response = $this->actingAs($userGuru)->post('/guru/input-nilai/save', [
        'kelas_id' => $this->kelasId('X-A'),
        'mata_pelajaran_id' => $this->mapelId('Matematika'),
        'nilai' => [
            [
                'nis' => '00001',
                'nilai_tugas' => 50,
                'nilai_uts' => 60,
                'nilai_uas' => 65,
            ],
        ],
    ]);

    $response->assertSessionHas('success');
    $nilai = Nilai::where('nis', '00001')
        ->where('kelas_id', $this->kelasId('X-A'))
        ->where('mata_pelajaran_id', $this->mapelId('Matematika'))
        ->first();
    expect($nilai)->not->toBeNull();
    expect((float) $nilai->nilai_akhir)->toBe(59.0);
    expect($nilai->status_lulus)->toBe('Tidak Lulus');
});

test('Guru bisa validasi final sehingga status menjadi Final', function () {
    $userGuru = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')]);
    Siswa::create(['nis' => '00001', 'nama_siswa' => 'Ahmad', 'kelas_id' => $this->kelasId('X-A')]);
    Nilai::create([
        'nis' => '00001',
        'id_guru' => $guru->id,
        'kelas_id' => $this->kelasId('X-A'),
        'mata_pelajaran_id' => $this->mapelId('Matematika'),
        'nilai_tugas' => 80,
        'nilai_uts' => 70,
        'nilai_uas' => 90,
        'nilai_akhir' => 81,
        'status_lulus' => 'Lulus',
        'status_validasi' => 'Draft',
    ]);

    $this->actingAs($userGuru)->post('/guru/input-nilai/validate-final', [
        'kelas_id' => $this->kelasId('X-A'),
        'mata_pelajaran_id' => $this->mapelId('Matematika'),
    ]);

    $nilai = Nilai::where('nis', '00001')->first();
    expect($nilai->status_validasi)->toBe('Final');
});

test('Guru tidak bisa input nilai untuk kelas+mapel yang tidak diajar (403)', function () {
    $userGuru = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')]);
    Siswa::create(['nis' => '00001', 'nama_siswa' => 'Ahmad', 'kelas_id' => $this->kelasId('X-B')]);

    $this->actingAs($userGuru)->post('/guru/input-nilai/save', [
        'kelas_id' => $this->kelasId('X-B'),
        'mata_pelajaran_id' => $this->mapelId('Matematika'),
        'nilai' => [
            [
                'nis' => '00001',
                'nilai_tugas' => 80,
                'nilai_uts' => 70,
                'nilai_uas' => 90,
            ],
        ],
    ])->assertForbidden();
});

test('Guru yang tidak mengajar di kelas manapun tidak bisa input nilai (guru tanpa mengajar)', function () {
    $userGuru = User::factory()->guru()->create();
    Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Tanpa Mengajar']);
    Siswa::create(['nis' => '00001', 'nama_siswa' => 'Ahmad', 'kelas_id' => $this->kelasId('X-A')]);

    $this->actingAs($userGuru)->get('/guru/input-nilai')
        ->assertInertia(fn ($page) => $page
            ->component('guru/nilai/index')
            ->where('has_mengajar', false)
        );
});
