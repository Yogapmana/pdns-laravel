<?php

declare(strict_types=1);

use App\Models\Guru;
use App\Models\GuruMengajar;
use App\Models\Nilai;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Traits\SeedsAkademikMasters;

uses(SeedsAkademikMasters::class);

beforeEach(function () {
    $this->seedKelas();
    $this->seedMataPelajaran();
    $this->seedKelasMataPelajaran();
});

test('Admin bisa edit siswa (nama & kelas) tanpa mengubah NIS', function () {
    $admin = User::factory()->admin()->create();
    $userSiswa = User::factory()->siswa()->create();
    $siswa = Siswa::create(['nis' => '00001', 'user_id' => $userSiswa->id, 'nama_siswa' => 'Ahmad', 'kelas' => 'X-A']);

    $response = $this->actingAs($admin)->put("/admin/siswa/{$siswa->nis}", [
        'nis' => '99999',
        'nama_siswa' => 'Ahmad Updated',
        'kelas' => 'XI-B',
    ]);

    $response->assertRedirect(route('admin.siswa.index'));
    $response->assertSessionHas('success');

    $fresh = $siswa->fresh();
    expect($fresh->nis)->toBe('00001');
    expect($fresh->nama_siswa)->toBe('Ahmad Updated');
    expect($fresh->kelas)->toBe('XI-B');
});

test('Admin edit siswa: nis duplikat di body ditolak (unique rule aktif), siswa lain tidak ter-overwrite', function () {
    $admin = User::factory()->admin()->create();
    $userA = User::factory()->siswa()->create();
    $userB = User::factory()->siswa()->create();
    $a = Siswa::create(['nis' => '00001', 'user_id' => $userA->id, 'nama_siswa' => 'A', 'kelas' => 'X-A']);
    Siswa::create(['nis' => '00002', 'user_id' => $userB->id, 'nama_siswa' => 'B', 'kelas' => 'X-A']);

    $this->actingAs($admin)->put("/admin/siswa/{$a->nis}", [
        'nis' => '00002',
        'nama_siswa' => 'A Updated',
        'kelas' => 'XI-B',
    ])->assertSessionHasErrors('nis');

    expect($a->fresh()->nama_siswa)->toBe('A');
    expect(Siswa::where('nis', '00002')->value('nama_siswa'))->toBe('B');
});

test('Admin edit guru: nama & kombinasi mengajar ter-update', function () {
    $admin = User::factory()->admin()->create();
    $userGuru = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Lama']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);

    $this->actingAs($admin)->put("/admin/guru/{$guru->id}", [
        'nama_guru' => 'Ibu Baru',
        'mengajar' => [
            ['kelas' => 'XI-B', 'mata_pelajaran' => 'IPA'],
            ['kelas' => 'XII-A', 'mata_pelajaran' => 'IPA'],
        ],
    ])->assertSessionHas('success');

    $fresh = $guru->fresh();
    expect($fresh->nama_guru)->toBe('Ibu Baru');
    $mengajar = $fresh->mengajar()->orderBy('kelas')->orderBy('mata_pelajaran')->get();
    expect($mengajar)->toHaveCount(2);
    expect($mengajar->pluck('kelas')->all())->toBe(['XI-B', 'XII-A']);
    expect($mengajar->pluck('mata_pelajaran')->all())->toBe(['IPA', 'IPA']);
});

test('Toggle active akun admin mengubah is_active', function () {
    $superAdmin = User::factory()->admin()->create();
    $target = User::factory()->admin()->create(['is_active' => true]);

    $this->actingAs($superAdmin)->patch("/admin/akun/{$target->id}/toggle-active")
        ->assertSessionHas('success');

    expect($target->fresh()->is_active)->toBeFalse();

    $this->actingAs($superAdmin)->patch("/admin/akun/{$target->id}/toggle-active")
        ->assertSessionHas('success');

    expect($target->fresh()->is_active)->toBeTrue();
});

test('Admin tidak bisa menonaktifkan akunnya sendiri', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->patch("/admin/akun/{$admin->id}/toggle-active")
        ->assertSessionHas('error');

    expect($admin->fresh()->is_active)->toBeTrue();
});

test('Admin reset password untuk akun lain', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->siswa()->create();

    $this->actingAs($admin)->post("/admin/akun/{$target->id}/reset-password", [
        'password' => 'newpass123',
    ])->assertSessionHas('success');

    expect(Hash::check('newpass123', $target->fresh()->password))->toBeTrue();
});

test('Reset password gagal untuk password terlalu pendek', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->siswa()->create();
    $oldHash = $target->password;

    $this->actingAs($admin)->post("/admin/akun/{$target->id}/reset-password", [
        'password' => '123',
    ])->assertSessionHasErrors();

    expect($target->fresh()->password)->toBe($oldHash);
});

test('Guru rekap: guru hanya bisa akses kelas & mapel yang diajar', function () {
    $userGuru = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    Siswa::create(['nis' => '00001', 'nama_siswa' => 'A', 'kelas' => 'X-A']);

    $this->actingAs($userGuru)
        ->get('/guru/rekap?kelas=X-A&mata_pelajaran=Matematika')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guru/rekap/index')
            ->where('kelas', 'X-A')
            ->where('mata_pelajaran', 'Matematika')
        );

    $this->actingAs($userGuru)
        ->get('/guru/rekap?kelas=X-B&mata_pelajaran=Matematika')
        ->assertInertia(fn ($page) => $page
            ->component('guru/rekap/index')
            ->where('kelas', 'X-B')
            ->where('rows', [])
        );
});

test('Guru rekap tanpa mengajar tampil has_mengajar=false', function () {
    $userGuru = User::factory()->guru()->create();
    Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Guru Tanpa Jadwal']);

    $this->actingAs($userGuru)
        ->get('/guru/rekap')
        ->assertInertia(fn ($page) => $page
            ->component('guru/rekap/index')
            ->where('has_mengajar', false)
        );
});

test('Guru rekap menampilkan statistik lulus/tidak_lulus/belum', function () {
    $userGuru = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Sari']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    $sLulus = Siswa::create(['nis' => '00001', 'nama_siswa' => 'L', 'kelas' => 'X-A']);
    $sTidak = Siswa::create(['nis' => '00002', 'nama_siswa' => 'TL', 'kelas' => 'X-A']);
    $sBelum = Siswa::create(['nis' => '00003', 'nama_siswa' => 'B', 'kelas' => 'X-A']);

    Nilai::create([
        'nis' => $sLulus->nis, 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika',
        'nilai_tugas' => 80, 'nilai_uts' => 80, 'nilai_uas' => 80, 'nilai_akhir' => 80, 'status_lulus' => 'Lulus',
    ]);
    Nilai::create([
        'nis' => $sTidak->nis, 'id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika',
        'nilai_tugas' => 50, 'nilai_uts' => 50, 'nilai_uas' => 50, 'nilai_akhir' => 50, 'status_lulus' => 'Tidak Lulus',
    ]);

    $this->actingAs($userGuru)
        ->get('/guru/rekap?kelas=X-A&mata_pelajaran=Matematika')
        ->assertInertia(fn ($page) => $page
            ->component('guru/rekap/index')
            ->where('stats.lulus', 1)
            ->where('stats.tidak_lulus', 1)
            ->where('stats.belum', 1)
        );
});
