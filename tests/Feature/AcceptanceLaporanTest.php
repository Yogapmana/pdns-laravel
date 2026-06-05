<?php

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

    $response = $this->actingAs($admin)->get('/admin/laporan/preview?kelas%5B%5D=X-A');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/reports/preview')
        ->where('kelas_list.0', 'X-A')
        ->where('stats.jumlah_siswa', 2)
        ->where('stats.jumlah_lulus', 1)
        ->where('stats.jumlah_tidak_lulus', 1)
        ->has('sections', 1)
        ->has('sections.0.rows', 2)
    );
});

test('AC-10: Admin ekspor laporan ke PDF menghasilkan file PDF', function () {
    $admin = User::factory()->admin()->create();
    $guruUser = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $guruUser->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    Siswa::create(['nis' => '00001', 'nama_siswa' => 'Ahmad', 'kelas' => 'X-A']);
    Nilai::create(['nis' => '00001', 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika', 'nilai_tugas' => 80, 'nilai_uts' => 70, 'nilai_uas' => 90, 'nilai_akhir' => 81, 'status_lulus' => 'Lulus']);

    $response = $this->actingAs($admin)->get('/admin/laporan/export/pdf?kelas%5B%5D=X-A');

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('laporan_X_A_');
});

test('Admin ekspor laporan ke HTML menghasilkan file HTML', function () {
    $admin = User::factory()->admin()->create();
    $guruUser = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $guruUser->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    Siswa::create(['nis' => '00001', 'nama_siswa' => 'Ahmad', 'kelas' => 'X-A']);
    Nilai::create(['nis' => '00001', 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika', 'nilai_tugas' => 80, 'nilai_uts' => 70, 'nilai_uas' => 90, 'nilai_akhir' => 81, 'status_lulus' => 'Lulus']);

    $response = $this->actingAs($admin)->get('/admin/laporan/export/html?kelas%5B%5D=X-A');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/html');
    expect($response->getContent())->toContain('Ahmad');
});

test('AC-09b: Laporan multi-kelas: gabung sections dari beberapa kelas', function () {
    $admin = User::factory()->admin()->create();
    $guruUser = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $guruUser->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-B', 'mata_pelajaran' => 'Matematika']);

    Siswa::create(['nis' => '00001', 'nama_siswa' => 'A', 'kelas' => 'X-A']);
    Siswa::create(['nis' => '00002', 'nama_siswa' => 'B', 'kelas' => 'X-B']);

    Nilai::create(['nis' => '00001', 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika', 'nilai_tugas' => 80, 'nilai_akhir' => 80, 'status_lulus' => 'Lulus']);
    Nilai::create(['nis' => '00002', 'id_guru' => $guru->id, 'kelas' => 'X-B', 'mata_pelajaran' => 'Matematika', 'nilai_tugas' => 50, 'nilai_akhir' => 50, 'status_lulus' => 'Tidak Lulus']);

    $response = $this->actingAs($admin)->get('/admin/laporan/preview?kelas%5B%5D=X-A&kelas%5B%5D=X-B');

    $response->assertInertia(fn ($page) => $page
        ->component('admin/reports/preview')
        ->where('kelas_list', ['X-A', 'X-B'])
        ->where('stats.jumlah_siswa', 2)
        ->where('stats.jumlah_lulus', 1)
        ->where('stats.jumlah_tidak_lulus', 1)
        ->has('sections', 2)
    );
});

test('AC-09c: Laporan filter multi-mapel: hanya mapel dipilih yang tampil', function () {
    $admin = User::factory()->admin()->create();
    $guruUser = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $guruUser->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'IPA']);

    Siswa::create(['nis' => '00001', 'nama_siswa' => 'A', 'kelas' => 'X-A']);
    Nilai::create(['nis' => '00001', 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika', 'nilai_tugas' => 80, 'nilai_akhir' => 80, 'status_lulus' => 'Lulus']);
    Nilai::create(['nis' => '00001', 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'IPA', 'nilai_tugas' => 60, 'nilai_akhir' => 60, 'status_lulus' => 'Tidak Lulus']);

    $response = $this->actingAs($admin)->get('/admin/laporan/preview?kelas%5B%5D=X-A&mata_pelajaran%5B%5D=Matematika');

    $response->assertInertia(fn ($page) => $page
        ->component('admin/reports/preview')
        ->where('mapel_list', ['Matematika'])
    );
});

test('Admin ekspor laporan ke CSV menghasilkan file CSV dengan header kelas+mapel', function () {
    $admin = User::factory()->admin()->create();
    $guruUser = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $guruUser->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    Siswa::create(['nis' => '00001', 'nama_siswa' => 'Ahmad', 'kelas' => 'X-A']);
    Nilai::create(['nis' => '00001', 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika', 'nilai_tugas' => 80, 'nilai_uts' => 70, 'nilai_uas' => 90, 'nilai_akhir' => 81, 'status_lulus' => 'Lulus']);

    $response = $this->actingAs($admin)->get('/admin/laporan/export/csv?kelas%5B%5D=X-A');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
    expect($response->headers->get('content-disposition'))->toContain('laporan_X_A_');
    expect($response->headers->get('content-disposition'))->toContain('.csv');
    $body = $response->streamedContent();
    expect($body)->toContain('Kelas');
    expect($body)->toContain('Ahmad');
    expect($body)->toContain('Matematika');
});

test('Admin ekspor laporan ke XLSX menghasilkan file XLSX valid (Office Open XML)', function () {
    $admin = User::factory()->admin()->create();
    $guruUser = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $guruUser->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    Siswa::create(['nis' => '00001', 'nama_siswa' => 'Ahmad', 'kelas' => 'X-A']);
    Nilai::create(['nis' => '00001', 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika', 'nilai_tugas' => 80, 'nilai_akhir' => 80, 'status_lulus' => 'Lulus']);

    $response = $this->actingAs($admin)->get('/admin/laporan/export/xlsx?kelas%5B%5D=X-A');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('spreadsheetml.sheet');
    expect($response->headers->get('content-disposition'))->toContain('.xlsx');

    $body = $response->getContent();
    // Office Open XML files are ZIP archives starting with PK\x03\x04
    expect(substr($body, 0, 2))->toBe('PK');
});

test('Laporan: validasi gagal jika kelas kosong', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/laporan/preview')
        ->assertSessionHasErrors('kelas');

    $this->actingAs($admin)
        ->get('/admin/laporan/export/csv')
        ->assertSessionHasErrors('kelas');
});

test('Laporan: validasi gagal jika kelas berisi nilai yang tidak dikenal', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/laporan/preview?kelas%5B%5D=INVALID')
        ->assertSessionHasErrors('kelas.0');
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
