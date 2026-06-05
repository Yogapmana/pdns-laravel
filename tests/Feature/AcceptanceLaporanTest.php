<?php

use App\Models\Guru;
use App\Models\GuruMengajar;
use App\Models\Nilai;
use App\Models\Siswa;
use App\Models\User;

test('AC-09: Admin generate laporan kelas menampilkan semua siswa dengan nilai', function () {
    $admin = User::factory()->admin()->create();

    $guruUser = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $guruUser->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);

    $s1 = Siswa::create(['nis' => '00001', 'nama_siswa' => 'Ahmad', 'kelas' => 'X-A']);
    $s2 = Siswa::create(['nis' => '00002', 'nama_siswa' => 'Budi', 'kelas' => 'X-A']);
    $s3 = Siswa::create(['nis' => '00003', 'nama_siswa' => 'Citra', 'kelas' => 'X-B']);

    Nilai::create(['nis' => '00001', 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika', 'nilai_tugas' => 80, 'nilai_uts' => 70, 'nilai_uas' => 90, 'nilai_akhir' => 81, 'status_lulus' => 'Lulus']);
    Nilai::create(['nis' => '00002', 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika', 'nilai_tugas' => 50, 'nilai_uts' => 60, 'nilai_uas' => 65, 'nilai_akhir' => 59, 'status_lulus' => 'Tidak Lulus']);
    Nilai::create(['nis' => '00003', 'id_guru' => $guru->id, 'kelas' => 'X-B', 'mata_pelajaran' => 'Matematika', 'nilai_tugas' => 90, 'nilai_uts' => 85, 'nilai_uas' => 95, 'nilai_akhir' => 90.5, 'status_lulus' => 'Lulus']);

    $response = $this->actingAs($admin)->get('/admin/laporan/preview?kelas=X-A');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/reports/preview')
        ->where('kelas', 'X-A')
        ->where('stats.jumlah_siswa', 2)
        ->where('stats.jumlah_lulus', 1)
        ->where('stats.jumlah_tidak_lulus', 1)
        ->has('rows', 2)
    );
});

test('AC-10: Admin ekspor laporan ke PDF menghasilkan file PDF', function () {
    $admin = User::factory()->admin()->create();
    $guruUser = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $guruUser->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    Siswa::create(['nis' => '00001', 'nama_siswa' => 'Ahmad', 'kelas' => 'X-A']);
    Nilai::create(['nis' => '00001', 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika', 'nilai_tugas' => 80, 'nilai_uts' => 70, 'nilai_uas' => 90, 'nilai_akhir' => 81, 'status_lulus' => 'Lulus']);

    $response = $this->actingAs($admin)->get('/admin/laporan/export/pdf?kelas=X-A');

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('laporan_kelas_X_A_');
});

test('Admin ekspor laporan ke HTML menghasilkan file HTML', function () {
    $admin = User::factory()->admin()->create();
    $guruUser = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $guruUser->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    Siswa::create(['nis' => '00001', 'nama_siswa' => 'Ahmad', 'kelas' => 'X-A']);
    Nilai::create(['nis' => '00001', 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika', 'nilai_tugas' => 80, 'nilai_uts' => 70, 'nilai_uas' => 90, 'nilai_akhir' => 81, 'status_lulus' => 'Lulus']);

    $response = $this->actingAs($admin)->get('/admin/laporan/export/html?kelas=X-A');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/html');
    expect($response->getContent())->toContain('Ahmad');
});

test('Guru yang sudah punya nilai tidak dapat dihapus (RESTRICT)', function () {
    $admin = User::factory()->admin()->create();
    $guruUser = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $guruUser->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    Siswa::create(['nis' => '00001', 'nama_siswa' => 'Ahmad', 'kelas' => 'X-A']);
    Nilai::create(['nis' => '00001', 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika', 'nilai_tugas' => 80, 'nilai_akhir' => 80, 'status_lulus' => 'Lulus']);

    $response = $this->actingAs($admin)->delete("/admin/guru/{$guru->id}");

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect(Guru::find($guru->id))->not->toBeNull();
});

test('Guru yang belum punya nilai dapat dihapus', function () {
    $admin = User::factory()->admin()->create();
    $guruUser = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $guruUser->id, 'nama_guru' => 'Ibu Sari']);

    $this->actingAs($admin)->delete("/admin/guru/{$guru->id}");

    expect(Guru::find($guru->id))->toBeNull();
});

test('Manajemen Akun: index memuat daftar akun dengan relasi siswa & guru (no Column not found on siswa.id)', function () {
    $admin = User::factory()->admin()->create();

    $guruUser = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $guruUser->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);

    $siswaUser = User::factory()->siswa()->create();
    Siswa::create(['nis' => '00001', 'user_id' => $siswaUser->id, 'nama_siswa' => 'Ahmad', 'kelas' => 'X-A']);

    $this->actingAs($admin)->get('/admin/akun')->assertOk();
});
