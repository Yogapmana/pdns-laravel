<?php

declare(strict_types=1);

use App\Models\Guru;
use App\Models\GuruMengajar;
use App\Models\Kelas;
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
 * Helper: create a (guru, kelas, mapel) teaching assignment + N siswa
 * with Final nilai, so we have a real locked combo to test against.
 *
 * @return array{guru: Guru, siswa: Collection}
 */
function makeFinalCombo(string $kelas = 'X-A', string $mapel = 'Matematika', int $jumlah = 3, string $status = Nilai::LULUS): array
{
    $guru = Guru::factory()->create();
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => $kelas, 'mata_pelajaran' => $mapel]);

    $siswa = collect();
    for ($i = 0; $i < $jumlah; $i++) {
        $s = Siswa::factory()->create(['kelas' => $kelas, 'nama_siswa' => "Siswa {$i}"]);
        Nilai::create([
            'nis' => $s->nis,
            'id_guru' => $guru->id,
            'kelas' => $kelas,
            'mata_pelajaran' => $mapel,
            'nilai_tugas' => 80, 'nilai_uts' => 80, 'nilai_uas' => 80,
            'nilai_akhir' => 80,
            'status_lulus' => $status,
            'status_validasi' => Nilai::STATUS_FINAL,
        ]);
        $siswa->push($s);
    }

    return ['guru' => $guru, 'siswa' => $siswa];
}

test('Admin: halaman manajemen nilai 200 OK dan render page yang benar', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/nilai')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/nilai/index')
            ->has('combos')
            ->has('logs')
            ->has('kelas_options')
            ->where('filters.search', null)
            ->where('filters.kelas', null)
        );
});

test('Admin: halaman menampilkan combo Final yang dikelompokkan per guru+kelas+mapel', function () {
    $guru1 = Guru::factory()->create(['nama_guru' => 'Pak Anton']);
    $guru2 = Guru::factory()->create(['nama_guru' => 'Bu Sari']);

    GuruMengajar::create(['id_guru' => $guru1->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    GuruMengajar::create(['id_guru' => $guru1->id, 'kelas' => 'X-B', 'mata_pelajaran' => 'Matematika']);
    GuruMengajar::create(['id_guru' => $guru2->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Bahasa Indonesia']);

    foreach (['X-A', 'X-B'] as $kelas) {
        $s = Siswa::factory()->create(['kelas' => $kelas]);
        Nilai::create([
            'nis' => $s->nis, 'id_guru' => $guru1->id, 'kelas' => $kelas, 'mata_pelajaran' => 'Matematika',
            'nilai_tugas' => 80, 'nilai_uts' => 80, 'nilai_uas' => 80, 'nilai_akhir' => 80,
            'status_lulus' => Nilai::LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
        ]);
    }

    $s3 = Siswa::factory()->create(['kelas' => 'X-A']);
    Nilai::create([
        'nis' => $s3->nis, 'id_guru' => $guru2->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Bahasa Indonesia',
        'nilai_tugas' => 80, 'nilai_uts' => 80, 'nilai_uas' => 80, 'nilai_akhir' => 80,
        'status_lulus' => Nilai::LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/nilai')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('combos', 3)
            ->where('combos.0.mata_pelajaran', 'Bahasa Indonesia')
            ->where('combos.0.kelas', 'X-A')
            ->where('combos.0.nama_guru', 'Bu Sari')
            ->where('combos.0.total_siswa', 1)
        );
});

test('Admin: halaman tidak menampilkan combo berstatus Draft', function () {
    ['guru' => $guru] = makeFinalCombo('X-A', 'Matematika', 2, Nilai::LULUS);

    $s = Siswa::factory()->create(['kelas' => 'X-A']);
    Nilai::create([
        'nis' => $s->nis, 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Bahasa Indonesia',
        'nilai_tugas' => 80, 'nilai_uts' => 80, 'nilai_uas' => 80, 'nilai_akhir' => 80,
        'status_lulus' => Nilai::LULUS, 'status_validasi' => Nilai::STATUS_DRAFT,
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/nilai')
        ->assertInertia(fn ($page) => $page->has('combos', 1)->where('combos.0.mata_pelajaran', 'Matematika'));
});

test('Admin: halaman support filter search dan kelas', function () {
    $guru = Guru::factory()->create(['nama_guru' => 'Pak Anton']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-B', 'mata_pelajaran' => 'Bahasa Indonesia']);

    foreach (['X-A', 'X-B'] as $kelas) {
        $mapel = $kelas === 'X-A' ? 'Matematika' : 'Bahasa Indonesia';
        $s = Siswa::factory()->create(['kelas' => $kelas]);
        Nilai::create([
            'nis' => $s->nis, 'id_guru' => $guru->id, 'kelas' => $kelas, 'mata_pelajaran' => $mapel,
            'nilai_tugas' => 80, 'nilai_uts' => 80, 'nilai_uas' => 80, 'nilai_akhir' => 80,
            'status_lulus' => Nilai::LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
        ]);
    }

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/nilai?search=Matematika')
        ->assertInertia(fn ($page) => $page
            ->has('combos', 1)
            ->where('combos.0.mata_pelajaran', 'Matematika')
            ->where('filters.search', 'Matematika')
        );

    $this->actingAs($admin)
        ->get('/admin/nilai?kelas=X-A')
        ->assertInertia(fn ($page) => $page
            ->has('combos', 1)
            ->where('combos.0.kelas', 'X-A')
            ->where('filters.kelas', 'X-A')
        );
});

test('Admin: POST unlock mengembalikan Final ke Draft dan menulis log audit', function () {
    ['guru' => $guru] = makeFinalCombo('X-A', 'Matematika', 3, Nilai::LULUS);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/admin/nilai/unlock', [
            'id_guru' => $guru->id,
            'kelas' => 'X-A',
            'mata_pelajaran' => 'Matematika',
            'reason' => 'Koreksi nilai UAS yang salah input, akan diedit ulang.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('nilai', [
        'id_guru' => $guru->id,
        'kelas' => 'X-A',
        'mata_pelajaran' => 'Matematika',
        'status_validasi' => Nilai::STATUS_DRAFT,
    ]);

    $this->assertDatabaseHas('nilai_unlock_log', [
        'id_admin' => $admin->id,
        'id_guru' => $guru->id,
        'kelas' => 'X-A',
        'mata_pelajaran' => 'Matematika',
        'affected_rows' => 3,
        'reason' => 'Koreksi nilai UAS yang salah input, akan diedit ulang.',
    ]);
});

test('Admin: POST unlock validasi reason min 10 karakter', function () {
    ['guru' => $guru] = makeFinalCombo('X-A', 'Matematika', 1, Nilai::LULUS);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/admin/nilai/unlock', [
            'id_guru' => $guru->id,
            'kelas' => 'X-A',
            'mata_pelajaran' => 'Matematika',
            'reason' => 'koreksi',
        ])
        ->assertSessionHasErrors('reason');

    $this->assertDatabaseCount('nilai_unlock_log', 0);
    $this->assertDatabaseHas('nilai', [
        'id_guru' => $guru->id,
        'kelas' => 'X-A',
        'mata_pelajaran' => 'Matematika',
        'status_validasi' => Nilai::STATUS_FINAL,
    ]);
});

test('Admin: POST unlock hanya membuka combo spesifik, bukan combo lain dari guru yang sama', function () {
    $guru = Guru::factory()->create();
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-B', 'mata_pelajaran' => 'Matematika']);

    foreach (['X-A', 'X-B'] as $kelas) {
        $s = Siswa::factory()->create(['kelas' => $kelas]);
        Nilai::create([
            'nis' => $s->nis, 'id_guru' => $guru->id, 'kelas' => $kelas, 'mata_pelajaran' => 'Matematika',
            'nilai_tugas' => 80, 'nilai_uts' => 80, 'nilai_uas' => 80, 'nilai_akhir' => 80,
            'status_lulus' => Nilai::LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
        ]);
    }

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/admin/nilai/unlock', [
            'id_guru' => $guru->id,
            'kelas' => 'X-A',
            'mata_pelajaran' => 'Matematika',
            'reason' => 'Buka kunci hanya X-A Matematika, X-B tetap Final.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('nilai', [
        'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika',
        'status_validasi' => Nilai::STATUS_DRAFT,
    ]);
    $this->assertDatabaseHas('nilai', [
        'id_guru' => $guru->id, 'kelas' => 'X-B', 'mata_pelajaran' => 'Matematika',
        'status_validasi' => Nilai::STATUS_FINAL,
    ]);
});

test('Admin: POST unlock dengan id_guru tidak valid ditolak', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/admin/nilai/unlock', [
            'id_guru' => 9999,
            'kelas' => 'X-A',
            'mata_pelajaran' => 'Matematika',
            'reason' => 'Alasan yang valid minimal 10 karakter.',
        ])
        ->assertSessionHasErrors('id_guru');
});

test('Admin: POST unlock idempotent — call kedua affected_rows=0, log tetap tercatat', function () {
    ['guru' => $guru] = makeFinalCombo('X-A', 'Matematika', 2, Nilai::LULUS);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/admin/nilai/unlock', [
            'id_guru' => $guru->id,
            'kelas' => 'X-A',
            'mata_pelajaran' => 'Matematika',
            'reason' => 'Unlock pertama untuk audit log.',
        ])
        ->assertSessionHas('success');

    $this->actingAs($admin)
        ->post('/admin/nilai/unlock', [
            'id_guru' => $guru->id,
            'kelas' => 'X-A',
            'mata_pelajaran' => 'Matematika',
            'reason' => 'Unlock kedua (idempotent test) tetap dicatat di log.',
        ])
        ->assertSessionHas('info');

    $this->assertDatabaseCount('nilai_unlock_log', 2);
    $this->assertDatabaseHas('nilai_unlock_log', [
        'id_guru' => $guru->id,
        'affected_rows' => 2,
    ]);
    $this->assertDatabaseHas('nilai_unlock_log', [
        'id_guru' => $guru->id,
        'affected_rows' => 0,
    ]);
});

test('Admin: setelah unlock, guru dapat mengedit nilai yang sebelumnya Final', function () {
    ['guru' => $guru] = makeFinalCombo('X-A', 'Matematika', 1, Nilai::LULUS);
    $admin = User::factory()->admin()->create();

    $guruUser = User::factory()->guru()->create();
    $guru->update(['user_id' => $guruUser->id]);

    $nilai = Nilai::where('id_guru', $guru->id)->where('kelas', 'X-A')->first();
    expect($nilai->status_validasi)->toBe(Nilai::STATUS_FINAL);

    $this->actingAs($admin)
        ->post('/admin/nilai/unlock', [
            'id_guru' => $guru->id,
            'kelas' => 'X-A',
            'mata_pelajaran' => 'Matematika',
            'reason' => 'Koreksi nilai akan diedit ulang oleh guru.',
        ])
        ->assertSessionHas('success');

    $this->actingAs($guruUser)
        ->post('/guru/input-nilai/save', [
            'kelas' => 'X-A',
            'mata_pelajaran' => 'Matematika',
            'nilai' => [
                ['nis' => $nilai->nis, 'nilai_tugas' => 85, 'nilai_uts' => 85, 'nilai_uas' => 85],
            ],
        ])
        ->assertSessionDoesntHaveErrors();

    $nilai->refresh();
    expect($nilai->status_validasi)->toBe(Nilai::STATUS_DRAFT);
    expect((float) $nilai->nilai_akhir)->toBe(85.0);
});

test('Guru dan Siswa tidak dapat akses /admin/nilai (role middleware)', function () {
    $guruUser = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $guruUser->id, 'nama_guru' => 'Test Guru']);

    $siswaUser = User::factory()->siswa()->create();
    $siswa = Siswa::create(['nis' => '99999', 'user_id' => $siswaUser->id, 'nama_siswa' => 'Test Siswa', 'kelas' => 'X-A']);

    $this->actingAs($guruUser)->get('/admin/nilai')->assertStatus(403);
    $this->actingAs($siswaUser)->get('/admin/nilai')->assertStatus(403);
    $this->actingAs($guruUser)->post('/admin/nilai/unlock', [
        'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika',
        'reason' => 'Alasan valid minimal 10 karakter.',
    ])->assertStatus(403);
});

test('Admin: halaman menampilkan log buka-kunci terbaru (desc by created_at)', function () {
    $guru = Guru::factory()->create();
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    $s = Siswa::factory()->create(['kelas' => 'X-A']);
    Nilai::create([
        'nis' => $s->nis, 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika',
        'nilai_tugas' => 80, 'nilai_uts' => 80, 'nilai_uas' => 80, 'nilai_akhir' => 80,
        'status_lulus' => Nilai::LULUS, 'status_validasi' => Nilai::STATUS_FINAL,
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/admin/nilai/unlock', [
            'id_guru' => $guru->id,
            'kelas' => 'X-A',
            'mata_pelajaran' => 'Matematika',
            'reason' => 'Log entry pertama untuk testing.',
        ])
        ->assertRedirect();

    $this->actingAs($admin)
        ->get('/admin/nilai')
        ->assertInertia(fn ($page) => $page
            ->has('logs', 1)
            ->where('logs.0.admin_name', $admin->name)
            ->where('logs.0.nama_guru', $guru->nama_guru)
            ->where('logs.0.affected_rows', 1)
        );
});
