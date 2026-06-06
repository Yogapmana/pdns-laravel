<?php

declare(strict_types=1);

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
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
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
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
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
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
    ]);

    $response->assertRedirect('/admin/siswa');
    $response->assertSessionHas('success');
    $siswa = Siswa::find('00699');
    expect($siswa)->not->toBeNull();
    expect($siswa->kelas)->toBe('XII-C');
});

test('AC-03-akun: Tambah siswa otomatis membuat akun User (username=NIS, role=siswa)', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/admin/siswa', [
        'nis' => '00999',
        'nama_siswa' => 'Siswa Akun',
        'kelas' => 'X-A',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
    ])->assertRedirect('/admin/siswa');

    $siswa = Siswa::find('00999');
    expect($siswa->user_id)->not->toBeNull();
    $user = User::find($siswa->user_id);
    expect($user->username)->toBe('00999');
    expect($user->name)->toBe('Siswa Akun');
    expect($user->role)->toBe('siswa');
    expect($user->is_active)->toBeTrue();
    expect(Hash::check('rahasia123', $user->password))->toBeTrue();
});

test('AC-03-password: Tambah siswa dengan password < 6 karakter ditolak', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post('/admin/siswa', [
        'nis' => '00999',
        'nama_siswa' => 'Siswa Lemah',
        'kelas' => 'X-A',
        'password' => 'abc',
        'password_confirmation' => 'abc',
    ]);

    $response->assertSessionHasErrors('password');
    expect(Siswa::where('nis', '00999')->count())->toBe(0);
    expect(User::where('username', '00999')->count())->toBe(0);
});

test('AC-03-confirm: Tambah siswa dengan konfirmasi password tidak cocok ditolak', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post('/admin/siswa', [
        'nis' => '00999',
        'nama_siswa' => 'Siswa Mismatch',
        'kelas' => 'X-A',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia999',
    ]);

    $response->assertSessionHasErrors('password');
    expect(Siswa::where('nis', '00999')->count())->toBe(0);
});

test('AC-03-login: Siswa yang baru dibuat bisa login dengan NIS dan password', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/admin/siswa', [
        'nis' => '00999',
        'nama_siswa' => 'Siswa Login',
        'kelas' => 'X-A',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
    ])->assertRedirect('/admin/siswa');

    auth()->logout();

    $response = $this->post('/login', [
        'username' => '00999',
        'password' => 'rahasia123',
    ]);

    $response->assertRedirect();
    expect(auth()->check())->toBeTrue();
    expect(auth()->user()->role)->toBe('siswa');
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
        'status_validasi' => Nilai::STATUS_FINAL,
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
