<?php

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Nilai;
use App\Models\Siswa;
use App\Models\User;
use Tests\Traits\SeedsAkademikMasters;

uses(SeedsAkademikMasters::class);

beforeEach(function () {
    $this->seedKelas();
    $this->seedMataPelajaran();
});

test('AC-03: Admin menambahkan siswa dengan NIS duplikat ditolak', function () {
    $admin = User::factory()->admin()->create();
    Siswa::create(['nis' => '00001', 'nama_siswa' => 'Siswa Lama', 'kelas' => 'X-A']);

    $response = $this->actingAs($admin)->post('/admin/siswa', [
        'nis' => '00001',
        'nama_siswa' => 'Siswa Duplikat',
        'kelas' => 'X-A',
    ]);

    $response->assertSessionHasErrors('nis');
    expect(Siswa::where('nama_siswa', 'Siswa Duplikat')->count())->toBe(0);
});

test('AC-03b: Admin berhasil menambah siswa dengan NIS unik', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post('/admin/siswa', [
        'nis' => '00999',
        'nama_siswa' => 'Siswa Baru',
        'kelas' => 'X-A',
    ]);

    $response->assertRedirect('/admin/siswa');
    $response->assertSessionHas('success');
    expect(Siswa::where('nis', '00999')->first())->not->toBeNull();
});

test('AC-03b2: Admin menambah siswa dengan kelas dari master table (sebelumnya kelas_baru inline-add)', function () {
    $admin = User::factory()->admin()->create();
    Kelas::firstOrCreate(['nama' => 'XII-C']);

    $response = $this->actingAs($admin)->post('/admin/siswa', [
        'nis' => '00699',
        'nama_siswa' => 'Mahmud Subagja',
        'kelas' => 'XII-C',
    ]);

    $response->assertRedirect('/admin/siswa');
    $response->assertSessionHas('success');
    $siswa = Siswa::find('00699');
    expect($siswa)->not->toBeNull();
    expect($siswa->kelas)->toBe('XII-C');
});

test('AC-03c: Admin berhasil edit data siswa (kecuali NIS)', function () {
    $admin = User::factory()->admin()->create();
    $siswa = Siswa::create(['nis' => '00001', 'nama_siswa' => 'Ahmad', 'kelas' => 'X-A']);

    $response = $this->actingAs($admin)->put("/admin/siswa/{$siswa->nis}", [
        'nis' => '99999',
        'nama_siswa' => 'Ahmad Updated',
        'kelas' => 'X-B',
    ]);

    $response->assertRedirect('/admin/siswa');
    $updated = Siswa::find('00001');
    expect($updated->nama_siswa)->toBe('Ahmad Updated');
    expect($updated->kelas)->toBe('X-B');
    expect(Siswa::where('nis', '99999')->count())->toBe(0);
});

test('AC-03d: Admin hapus siswa juga menghapus nilai terkait (CASCADE)', function () {
    $admin = User::factory()->admin()->create();
    $siswa = Siswa::create(['nis' => '00001', 'nama_siswa' => 'Ahmad', 'kelas' => 'X-A']);
    $guru = Guru::create(['nama_guru' => 'Ibu Sari']);
    Nilai::create([
        'nis' => $siswa->nis,
        'id_guru' => $guru->id,
        'kelas' => 'X-A',
        'mata_pelajaran' => 'Matematika',
        'nilai_tugas' => 80,
        'nilai_uts' => 70,
        'nilai_uas' => 90,
        'nilai_akhir' => 81,
        'status_lulus' => 'Lulus',
    ]);

    expect(Nilai::where('nis', '00001')->count())->toBe(1);

    $this->actingAs($admin)->delete("/admin/siswa/{$siswa->nis}");

    expect(Siswa::where('nis', '00001')->count())->toBe(0);
    expect(Nilai::where('nis', '00001')->count())->toBe(0);
});

test('AC-03e: Admin bisa search siswa by NIS, nama, atau kelas', function () {
    $admin = User::factory()->admin()->create();
    Siswa::create(['nis' => '00001', 'nama_siswa' => 'Ahmad Fauzi', 'kelas' => 'X-A']);
    Siswa::create(['nis' => '00002', 'nama_siswa' => 'Budi Santoso', 'kelas' => 'X-B']);

    $this->actingAs($admin)->get('/admin/siswa?search=00001')
        ->assertInertia(fn ($page) => $page->component('admin/siswa/index'));

    $this->actingAs($admin)->get('/admin/siswa?search=Ahmad')
        ->assertInertia(fn ($page) => $page->component('admin/siswa/index'));

    $this->actingAs($admin)->get('/admin/siswa?kelas=X-A')
        ->assertInertia(fn ($page) => $page->component('admin/siswa/index'));
});
