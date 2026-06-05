<?php

use App\Models\Guru;
use App\Models\GuruMengajar;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\User;
use Tests\Traits\SeedsAkademikMasters;

uses(SeedsAkademikMasters::class);

beforeEach(function () {
    $this->seedKelas();
    $this->seedMataPelajaran();
});

test('Admin: index kelas menampilkan daftar master kelas', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/kelas')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/kelas/index')
            ->has('kelas.data', 6)
            ->where('kelas.data.0.nama', 'X-A')
            ->where('kelas.data.5.nama', 'XII-B')
        );
});

test('Admin: create kelas baru dengan nama valid', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/admin/kelas', ['nama' => 'XIII-A'])
        ->assertRedirect('/admin/kelas');

    expect(Kelas::where('nama', 'XIII-A')->exists())->toBeTrue();
});

test('Admin: create kelas dengan nama duplikat ditolak', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/admin/kelas', ['nama' => 'X-A'])
        ->assertSessionHasErrors('nama');
});

test('Admin: create kelas tanpa nama ditolak', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/admin/kelas', ['nama' => ''])
        ->assertSessionHasErrors('nama');
});

test('Admin: edit kelas berhasil', function () {
    $admin = User::factory()->admin()->create();
    $kelas = Kelas::firstOrCreate(['nama' => 'X-A']);

    $this->actingAs($admin)
        ->put("/admin/kelas/{$kelas->id}", ['nama' => 'X-C'])
        ->assertRedirect('/admin/kelas');

    expect($kelas->fresh()->nama)->toBe('X-C');
});

test('Admin: delete kelas yang tidak dipakai berhasil', function () {
    $admin = User::factory()->admin()->create();
    $kelas = Kelas::firstOrCreate(['nama' => 'XII-A']);

    $this->actingAs($admin)
        ->delete("/admin/kelas/{$kelas->id}")
        ->assertRedirect('/admin/kelas');

    expect(Kelas::find($kelas->id))->toBeNull();
});

test('Admin: delete kelas yang dipakai siswa ditolak (FK protection)', function () {
    $admin = User::factory()->admin()->create();
    $kelas = Kelas::firstOrCreate(['nama' => 'X-A']);
    Siswa::create(['nis' => '00001', 'nama_siswa' => 'Ahmad', 'kelas' => 'X-A']);

    $this->actingAs($admin)
        ->delete("/admin/kelas/{$kelas->id}")
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Kelas::find($kelas->id))->not->toBeNull();
});

test('Admin: delete kelas yang dipakai mengajar ditolak (FK protection)', function () {
    $admin = User::factory()->admin()->create();
    $kelas = Kelas::firstOrCreate(['nama' => 'X-A']);
    $guru = Guru::create(['nama_guru' => 'Test']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);

    $this->actingAs($admin)
        ->delete("/admin/kelas/{$kelas->id}")
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Kelas::find($kelas->id))->not->toBeNull();
});

test('Admin: index mata pelajaran menampilkan daftar master mapel', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/mata-pelajaran')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/mata-pelajaran/index')
            ->has('mataPelajaran.data', 10)
        );
});

test('Admin: create mapel baru dengan nama valid', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/admin/mata-pelajaran', ['nama' => 'Fisika'])
        ->assertRedirect('/admin/mata-pelajaran');

    expect(MataPelajaran::where('nama', 'Fisika')->exists())->toBeTrue();
});

test('Admin: create mapel dengan nama duplikat ditolak', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/admin/mata-pelajaran', ['nama' => 'Matematika'])
        ->assertSessionHasErrors('nama');
});

test('Admin: edit mapel berhasil', function () {
    $admin = User::factory()->admin()->create();
    $mapel = MataPelajaran::firstOrCreate(['nama' => 'Matematika']);

    $this->actingAs($admin)
        ->put("/admin/mata-pelajaran/{$mapel->id}", ['nama' => 'Matematika Wajib'])
        ->assertRedirect('/admin/mata-pelajaran');

    expect($mapel->fresh()->nama)->toBe('Matematika Wajib');
});

test('Admin: delete mapel yang tidak dipakai berhasil', function () {
    $admin = User::factory()->admin()->create();
    $mapel = MataPelajaran::firstOrCreate(['nama' => 'Bahasa Jawa']);

    $this->actingAs($admin)
        ->delete("/admin/mata-pelajaran/{$mapel->id}")
        ->assertRedirect('/admin/mata-pelajaran');

    expect(MataPelajaran::find($mapel->id))->toBeNull();
});

test('Admin: delete mapel yang dipakai mengajar ditolak (FK protection)', function () {
    $admin = User::factory()->admin()->create();
    $mapel = MataPelajaran::firstOrCreate(['nama' => 'Matematika']);
    $guru = Guru::create(['nama_guru' => 'Test']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);

    $this->actingAs($admin)
        ->delete("/admin/mata-pelajaran/{$mapel->id}")
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(MataPelajaran::find($mapel->id))->not->toBeNull();
});

test('Siswa form: kelas tidak ada di master ditolak dengan 422', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/admin/siswa', [
            'nis' => '00001',
            'nama_siswa' => 'Ahmad',
            'kelas' => 'KELAS-FAKTA-99',
        ])
        ->assertSessionHasErrors('kelas');
});

test('Guru form mengajar: mata pelajaran tidak ada di master ditolak dengan 422', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/admin/guru', [
            'nama_guru' => 'Pak Test',
            'mengajar' => [
                ['kelas' => 'X-A', 'mata_pelajaran' => 'MAPEL-FAKTA-99'],
            ],
        ])
        ->assertSessionHasErrors('mengajar.0.mata_pelajaran');
});

test('Guru form mengajar: kelas tidak ada di master ditolak dengan 422', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/admin/guru', [
            'nama_guru' => 'Pak Test',
            'mengajar' => [
                ['kelas' => 'KELAS-FAKTA-99', 'mata_pelajaran' => 'Matematika'],
            ],
        ])
        ->assertSessionHasErrors('mengajar.0.kelas');
});

test('Laporan filter: kelas tidak ada di master ditolak (validateFilter via preview)', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/laporan/preview?kelas%5B%5D=KELAS-FAKTA-99')
        ->assertSessionHasErrors('kelas.0');
});

test('Search kelas dengan query string (real-time) memfilter hasil', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/kelas?q=XI-A')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/kelas/index')
            ->where('search', 'XI-A')
            ->has('kelas.data', 1)
        );
});
