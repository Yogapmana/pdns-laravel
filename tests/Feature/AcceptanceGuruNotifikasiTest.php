<?php

declare(strict_types=1);

use App\Models\Guru;
use App\Models\GuruMengajar;
use App\Models\Nilai;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Tests\Traits\SeedsAkademikMasters;

uses(SeedsAkademikMasters::class);

beforeEach(function () {
    $this->seedKelas();
    $this->seedMataPelajaran();
});

/**
 * Create a guru + mengajar combo + N siswa, all with empty (no nilai rows).
 *
 * @return array{guru: Guru, siswa: Collection}
 */
function makeGuruWithSiswa(string $kelas = 'X-A', string $mapel = 'Matematika', int $jumlah = 3): array
{
    $guru = Guru::factory()->create();
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => $kelas, 'mata_pelajaran' => $mapel]);

    $siswa = collect();
    for ($i = 0; $i < $jumlah; $i++) {
        $siswa->push(Siswa::factory()->create(['kelas' => $kelas, 'nama_siswa' => "Siswa {$i}"]));
    }

    return ['guru' => $guru, 'siswa' => $siswa];
}

/**
 * Create one Nilai row for the given siswa, with a known status_validasi.
 */
function seedNilaiForSiswa(Guru $guru, Siswa $siswa, string $kelas, string $mapel, string $status = Nilai::STATUS_DRAFT): Nilai
{
    return Nilai::create([
        'nis' => $siswa->nis,
        'id_guru' => $guru->id,
        'kelas' => $kelas,
        'mata_pelajaran' => $mapel,
        'nilai_tugas' => 80, 'nilai_uts' => 80, 'nilai_uas' => 80,
        'nilai_akhir' => 80,
        'status_lulus' => Nilai::LULUS,
        'status_validasi' => $status,
    ]);
}

function actingAsGuru(Guru $guru): User
{
    $guruUser = User::factory()->guru()->create();
    $guru->update(['user_id' => $guruUser->id]);

    return $guruUser;
}

test('T10 Notifikasi: guru tanpa mengajar tidak punya notifikasi', function () {
    $guru = Guru::factory()->create();
    $guruUser = actingAsGuru($guru);

    $this->actingAs($guruUser)
        ->get('/guru/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guru/dashboard')
            ->where('notifikasi.belum_diinput', [])
            ->where('notifikasi.masih_draft', [])
        );
});

test('T10 Notifikasi: combo dengan 0 siswa (kelas kosong) tidak muncul di notifikasi', function () {
    $guru = Guru::factory()->create();
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    $guruUser = actingAsGuru($guru);

    $this->actingAs($guruUser)
        ->get('/guru/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('notifikasi.belum_diinput', [])
            ->where('notifikasi.masih_draft', [])
        );
});

test('T10 Notifikasi: 1 combo belum_diinput (yellow) saat 0 dari 3 siswa punya nilai', function () {
    ['guru' => $guru, 'siswa' => $siswa] = makeGuruWithSiswa('X-A', 'Matematika', 3);
    $guruUser = actingAsGuru($guru);

    $this->actingAs($guruUser)
        ->get('/guru/dashboard')
        ->assertInertia(fn ($page) => $page
            ->has('notifikasi.belum_diinput', 1)
            ->where('notifikasi.belum_diinput.0.kelas', 'X-A')
            ->where('notifikasi.belum_diinput.0.mata_pelajaran', 'Matematika')
            ->where('notifikasi.belum_diinput.0.jumlah_siswa', 3)
            ->where('notifikasi.belum_diinput.0.jumlah_input', 0)
            ->where('notifikasi.belum_diinput.0.sisa', 3)
            ->where('notifikasi.masih_draft', [])
        );
});

test('T10 Notifikasi: 1 combo belum_diinput (yellow) saat 1 dari 3 siswa punya nilai', function () {
    ['guru' => $guru, 'siswa' => $siswa] = makeGuruWithSiswa('X-A', 'Matematika', 3);
    seedNilaiForSiswa($guru, $siswa[0], 'X-A', 'Matematika', Nilai::STATUS_DRAFT);
    $guruUser = actingAsGuru($guru);

    $this->actingAs($guruUser)
        ->get('/guru/dashboard')
        ->assertInertia(fn ($page) => $page
            ->has('notifikasi.belum_diinput', 1)
            ->where('notifikasi.belum_diinput.0.jumlah_input', 1)
            ->where('notifikasi.belum_diinput.0.sisa', 2)
            ->where('notifikasi.masih_draft', [])
        );
});

test('T10 Notifikasi: 1 combo masih_draft (red) saat semua siswa sudah punya nilai tapi belum divalidasi Final', function () {
    ['guru' => $guru, 'siswa' => $siswa] = makeGuruWithSiswa('X-A', 'Matematika', 3);
    foreach ($siswa as $s) {
        seedNilaiForSiswa($guru, $s, 'X-A', 'Matematika', Nilai::STATUS_DRAFT);
    }
    $guruUser = actingAsGuru($guru);

    $this->actingAs($guruUser)
        ->get('/guru/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('notifikasi.belum_diinput', [])
            ->has('notifikasi.masih_draft', 1)
            ->where('notifikasi.masih_draft.0.kelas', 'X-A')
            ->where('notifikasi.masih_draft.0.mata_pelajaran', 'Matematika')
            ->where('notifikasi.masih_draft.0.jumlah_siswa', 3)
            ->where('notifikasi.masih_draft.0.jumlah_draft', 3)
        );
});

test('T10 Notifikasi: masih_draft red hanya menghitung baris yang Draft, bukan Final', function () {
    ['guru' => $guru, 'siswa' => $siswa] = makeGuruWithSiswa('X-A', 'Matematika', 3);
    seedNilaiForSiswa($guru, $siswa[0], 'X-A', 'Matematika', Nilai::STATUS_DRAFT);
    seedNilaiForSiswa($guru, $siswa[1], 'X-A', 'Matematika', Nilai::STATUS_FINAL);
    seedNilaiForSiswa($guru, $siswa[2], 'X-A', 'Matematika', Nilai::STATUS_DRAFT);
    $guruUser = actingAsGuru($guru);

    $this->actingAs($guruUser)
        ->get('/guru/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('notifikasi.belum_diinput', [])
            ->has('notifikasi.masih_draft', 1)
            ->where('notifikasi.masih_draft.0.jumlah_draft', 2)
        );
});

test('T10 Notifikasi: TIDAK ada notifikasi saat semua nilai sudah Final', function () {
    ['guru' => $guru, 'siswa' => $siswa] = makeGuruWithSiswa('X-A', 'Matematika', 3);
    foreach ($siswa as $s) {
        seedNilaiForSiswa($guru, $s, 'X-A', 'Matematika', Nilai::STATUS_FINAL);
    }
    $guruUser = actingAsGuru($guru);

    $this->actingAs($guruUser)
        ->get('/guru/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('notifikasi.belum_diinput', [])
            ->where('notifikasi.masih_draft', [])
        );
});

test('T10 Notifikasi: 2 combo mengajar — 1 belum_diinput + 1 masih_draft (keduanya muncul)', function () {
    $guru = Guru::factory()->create();
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-B', 'mata_pelajaran' => 'Bahasa Indonesia']);

    $siswaXA = Siswa::factory()->count(2)->create(['kelas' => 'X-A'])->all();
    $siswaXB = Siswa::factory()->count(2)->create(['kelas' => 'X-B'])->all();

    foreach ($siswaXA as $s) {
        seedNilaiForSiswa($guru, $s, 'X-A', 'Matematika', Nilai::STATUS_DRAFT);
    }

    foreach ($siswaXB as $s) {
        seedNilaiForSiswa($guru, $s, 'X-B', 'Bahasa Indonesia', Nilai::STATUS_FINAL);
    }

    $guruUser = actingAsGuru($guru);

    $this->actingAs($guruUser)
        ->get('/guru/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('notifikasi.belum_diinput', [])
            ->has('notifikasi.masih_draft', 1)
            ->where('notifikasi.masih_draft.0.mata_pelajaran', 'Matematika')
            ->where('notifikasi.masih_draft.0.kelas', 'X-A')
            ->where('notifikasi.masih_draft.0.jumlah_draft', 2)
        );
});

test('T10 Notifikasi: scope per-guru — combo X-A Matematika dari guru LAIN tidak ikut notifikasi', function () {
    $guru1 = Guru::factory()->create();
    $guru2 = Guru::factory()->create();

    GuruMengajar::create(['id_guru' => $guru1->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    GuruMengajar::create(['id_guru' => $guru2->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);

    $siswaA = Siswa::factory()->create(['kelas' => 'X-A']);
    $siswaB = Siswa::factory()->create(['kelas' => 'X-A']);
    seedNilaiForSiswa($guru2, $siswaA, 'X-A', 'Matematika', Nilai::STATUS_FINAL);
    seedNilaiForSiswa($guru2, $siswaB, 'X-A', 'Matematika', Nilai::STATUS_FINAL);

    $guru1User = actingAsGuru($guru1);

    $this->actingAs($guru1User)
        ->get('/guru/dashboard')
        ->assertInertia(fn ($page) => $page
            ->has('notifikasi.belum_diinput', 1)
            ->where('notifikasi.belum_diinput.0.jumlah_input', 0)
            ->where('notifikasi.belum_diinput.0.jumlah_siswa', 2)
            ->where('notifikasi.masih_draft', [])
        );
});

test('T10 Notifikasi: kelas berbeda dihitung terpisah (guru mengajar 2 kelas, satu belum satu lengkap Draft)', function () {
    $guru = Guru::factory()->create();
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-B', 'mata_pelajaran' => 'Matematika']);

    $siswaXA = Siswa::factory()->create(['kelas' => 'X-A']);
    $siswaXB = collect();
    for ($i = 0; $i < 2; $i++) {
        $siswaXB->push(Siswa::factory()->create(['kelas' => 'X-B']));
    }

    foreach ($siswaXB as $s) {
        seedNilaiForSiswa($guru, $s, 'X-B', 'Matematika', Nilai::STATUS_DRAFT);
    }

    $guruUser = actingAsGuru($guru);

    $this->actingAs($guruUser)
        ->get('/guru/dashboard')
        ->assertInertia(fn ($page) => $page
            ->has('notifikasi.belum_diinput', 1)
            ->where('notifikasi.belum_diinput.0.kelas', 'X-A')
            ->where('notifikasi.belum_diinput.0.mata_pelajaran', 'Matematika')
            ->where('notifikasi.belum_diinput.0.jumlah_siswa', 1)
            ->has('notifikasi.masih_draft', 1)
            ->where('notifikasi.masih_draft.0.kelas', 'X-B')
            ->where('notifikasi.masih_draft.0.mata_pelajaran', 'Matematika')
        );
});

test('T10 Notifikasi: siswa dengan nilai null (belum lengkap 3 komponen) tetap dihitung sebagai belum_diinput', function () {
    ['guru' => $guru, 'siswa' => $siswa] = makeGuruWithSiswa('X-A', 'Matematika', 3);

    Nilai::create([
        'nis' => $siswa[0]->nis,
        'id_guru' => $guru->id,
        'kelas' => 'X-A',
        'mata_pelajaran' => 'Matematika',
        'nilai_tugas' => 80,
        'nilai_uts' => null,
        'nilai_uas' => null,
        'nilai_akhir' => null,
        'status_lulus' => null,
        'status_validasi' => Nilai::STATUS_DRAFT,
    ]);

    $guruUser = actingAsGuru($guru);

    $this->actingAs($guruUser)
        ->get('/guru/dashboard')
        ->assertInertia(fn ($page) => $page
            ->has('notifikasi.belum_diinput', 1)
            ->where('notifikasi.belum_diinput.0.jumlah_input', 0)
            ->where('notifikasi.belum_diinput.0.jumlah_siswa', 3)
        );
});

test('T10 Notifikasi: role middleware (admin/siswa tidak boleh akses /guru/dashboard)', function () {
    ['guru' => $guru] = makeGuruWithSiswa('X-A', 'Matematika', 2);

    $admin = User::factory()->admin()->create();
    $siswaUser = User::factory()->siswa()->create();
    $siswaProfile = Siswa::create(['nis' => '77777', 'user_id' => $siswaUser->id, 'nama_siswa' => 'Test', 'kelas' => 'X-A']);

    $this->actingAs($admin)->get('/guru/dashboard')->assertStatus(403);
    $this->actingAs($siswaUser)->get('/guru/dashboard')->assertStatus(403);
});

test('T11 Stats fix: dashboard stats menghitung draft/final/lulus/tidak_lulus secara independen (clone builder bug fix)', function () {
    ['guru' => $guru, 'siswa' => $siswa] = makeGuruWithSiswa('X-A', 'Matematika', 4);
    seedNilaiForSiswa($guru, $siswa[0], 'X-A', 'Matematika', Nilai::STATUS_FINAL);
    seedNilaiForSiswa($guru, $siswa[1], 'X-A', 'Matematika', Nilai::STATUS_FINAL);
    seedNilaiForSiswa($guru, $siswa[2], 'X-A', 'Matematika', Nilai::STATUS_DRAFT);
    seedNilaiForSiswa($guru, $siswa[3], 'X-A', 'Matematika', Nilai::STATUS_DRAFT);

    $guruUser = actingAsGuru($guru);

    $this->actingAs($guruUser)
        ->get('/guru/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('stats.total_nilai', 4)
            ->where('stats.draft', 2)
            ->where('stats.final', 2)
            ->where('stats.lulus', 4)
            ->where('stats.tidak_lulus', 0)
        );
});

test('T11 Stats fix: dashboard stats lulus vs tidak_lulus dihitung benar untuk mixed data', function () {
    ['guru' => $guru, 'siswa' => $siswa] = makeGuruWithSiswa('X-A', 'Matematika', 3);
    Nilai::create([
        'nis' => $siswa[0]->nis, 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika',
        'nilai_tugas' => 80, 'nilai_uts' => 80, 'nilai_uas' => 80, 'nilai_akhir' => 80, 'status_lulus' => Nilai::LULUS,
        'status_validasi' => Nilai::STATUS_FINAL,
    ]);
    Nilai::create([
        'nis' => $siswa[1]->nis, 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika',
        'nilai_tugas' => 50, 'nilai_uts' => 50, 'nilai_uas' => 50, 'nilai_akhir' => 50, 'status_lulus' => Nilai::TIDAK_LULUS,
        'status_validasi' => Nilai::STATUS_FINAL,
    ]);
    Nilai::create([
        'nis' => $siswa[2]->nis, 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika',
        'nilai_tugas' => 60, 'nilai_uts' => 60, 'nilai_uas' => 60, 'nilai_akhir' => 60, 'status_lulus' => Nilai::TIDAK_LULUS,
        'status_validasi' => Nilai::STATUS_DRAFT,
    ]);

    $guruUser = actingAsGuru($guru);

    $this->actingAs($guruUser)
        ->get('/guru/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('stats.total_nilai', 3)
            ->where('stats.draft', 1)
            ->where('stats.final', 2)
            ->where('stats.lulus', 1)
            ->where('stats.tidak_lulus', 2)
        );
});

test('T11 Per-combo stats: dashboard menampilkan per-combo breakdown dengan jumlah_siswa/input/final/draft', function () {
    ['guru' => $guru, 'siswa' => $siswa] = makeGuruWithSiswa('X-A', 'Matematika', 3);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-B', 'mata_pelajaran' => 'Bahasa Indonesia']);
    $siswaXB = collect();
    for ($i = 0; $i < 2; $i++) {
        $siswaXB->push(Siswa::factory()->create(['kelas' => 'X-B']));
    }

    seedNilaiForSiswa($guru, $siswa[0], 'X-A', 'Matematika', Nilai::STATUS_FINAL);
    seedNilaiForSiswa($guru, $siswa[1], 'X-A', 'Matematika', Nilai::STATUS_DRAFT);
    seedNilaiForSiswa($guru, $siswa[2], 'X-A', 'Matematika', Nilai::STATUS_DRAFT);
    seedNilaiForSiswa($guru, $siswaXB[0], 'X-B', 'Bahasa Indonesia', Nilai::STATUS_FINAL);
    seedNilaiForSiswa($guru, $siswaXB[1], 'X-B', 'Bahasa Indonesia', Nilai::STATUS_FINAL);

    $guruUser = actingAsGuru($guru);

    $this->actingAs($guruUser)
        ->get('/guru/dashboard')
        ->assertInertia(fn ($page) => $page
            ->has('per_combo_stats', 2)
            ->where('per_combo_stats.0.kelas', 'X-A')
            ->where('per_combo_stats.0.mata_pelajaran', 'Matematika')
            ->where('per_combo_stats.0.jumlah_siswa', 3)
            ->where('per_combo_stats.0.jumlah_input', 3)
            ->where('per_combo_stats.0.jumlah_final', 1)
            ->where('per_combo_stats.0.jumlah_draft', 2)
            ->where('per_combo_stats.1.kelas', 'X-B')
            ->where('per_combo_stats.1.mata_pelajaran', 'Bahasa Indonesia')
            ->where('per_combo_stats.1.jumlah_final', 2)
            ->where('per_combo_stats.1.jumlah_draft', 0)
        );
});

test('T11 Notifikasi consistency: combo dengan 4 Final + 3 Draft tetap muncul di masih_draft (3 Draft rows)', function () {
    ['guru' => $guru, 'siswa' => $siswa] = makeGuruWithSiswa('X-A', 'Matematika', 7);
    for ($i = 0; $i < 4; $i++) {
        seedNilaiForSiswa($guru, $siswa[$i], 'X-A', 'Matematika', Nilai::STATUS_FINAL);
    }
    for ($i = 4; $i < 7; $i++) {
        seedNilaiForSiswa($guru, $siswa[$i], 'X-A', 'Matematika', Nilai::STATUS_DRAFT);
    }

    $guruUser = actingAsGuru($guru);

    $this->actingAs($guruUser)
        ->get('/guru/dashboard')
        ->assertInertia(fn ($page) => $page
            ->has('notifikasi.masih_draft', 1)
            ->where('notifikasi.masih_draft.0.jumlah_final', 4)
            ->where('notifikasi.masih_draft.0.jumlah_draft', 3)
        );
});

test('T11 Status validasi global: combo 4 Final + 3 Draft buka form EDITABLE (sebagian Final)', function () {
    ['guru' => $guru, 'siswa' => $siswa] = makeGuruWithSiswa('X-A', 'Matematika', 7);
    for ($i = 0; $i < 4; $i++) {
        seedNilaiForSiswa($guru, $siswa[$i], 'X-A', 'Matematika', Nilai::STATUS_FINAL);
    }
    for ($i = 4; $i < 7; $i++) {
        seedNilaiForSiswa($guru, $siswa[$i], 'X-A', 'Matematika', Nilai::STATUS_DRAFT);
    }

    $guruUser = actingAsGuru($guru);

    $this->actingAs($guruUser)
        ->get('/guru/input-nilai?kelas=X-A&mata_pelajaran=Matematika')
        ->assertInertia(fn ($page) => $page
            ->where('status_validasi_global', Nilai::STATUS_DRAFT)
        );
});

test('T11 Status validasi global: combo 0 siswa (kelas kosong) → status Draft (editable, no warning)', function () {
    $guru = Guru::factory()->create();
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'XII-C', 'mata_pelajaran' => 'Matematika']);
    $guruUser = actingAsGuru($guru);

    $this->actingAs($guruUser)
        ->get('/guru/input-nilai?kelas=XII-C&mata_pelajaran=Matematika')
        ->assertInertia(fn ($page) => $page
            ->where('status_validasi_global', Nilai::STATUS_DRAFT)
        );
});
