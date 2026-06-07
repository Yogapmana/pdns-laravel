<?php

declare(strict_types=1);

use App\Models\Guru;
use App\Models\GuruMengajar;
use App\Models\User;
use Tests\Traits\SeedsAkademikMasters;

uses(SeedsAkademikMasters::class);

beforeEach(function () {
    $this->seedKelas();
    $this->seedMataPelajaran();
    $this->seedKelasMataPelajaran();
});

test('Admin buat guru baru OTOMATIS membuat akun User (username=derived dari nama)', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post('/admin/guru', [
        'nama_guru' => 'Ibu Test Guru',
        'mengajar' => [
            ['kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')],
        ],
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
    ]);

    $response->assertRedirect('/admin/guru');
    $response->assertSessionHas('success');

    $guru = Guru::where('nama_guru', 'Ibu Test Guru')->first();
    expect($guru)->not->toBeNull();
    expect($guru->user_id)->not->toBeNull();
    expect($guru->mengajar()->count())->toBe(1);
    expect($guru->mengajar()->first()->kelas?->nama)->toBe('X-A');
    expect($guru->mengajar()->first()->mataPelajaran?->nama)->toBe('Matematika');

    $user = User::find($guru->user_id);
    expect($user->username)->toBe('testguru');
    expect($user->name)->toBe('Ibu Test Guru');
    expect($user->role)->toBe('guru');
    expect($user->is_active)->toBeTrue();
    expect(Hash::check('rahasia123', $user->password))->toBeTrue();
});

test('Admin buat guru dengan multiple kombinasi mengajar tersimpan', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post('/admin/guru', [
        'nama_guru' => 'Ibu Multi Mapel',
        'mengajar' => [
            ['kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')],
            ['kelas_id' => $this->kelasId('X-B'), 'mata_pelajaran_id' => $this->mapelId('Matematika')],
            ['kelas_id' => $this->kelasId('XI-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')],
        ],
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
    ]);

    $response->assertRedirect('/admin/guru');
    $guru = Guru::where('nama_guru', 'Ibu Multi Mapel')->first();
    expect($guru)->not->toBeNull();
    expect($guru->mengajar()->count())->toBe(3);
});

test('Admin tidak bisa buat guru tanpa mengajar (validasi min:1)', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post('/admin/guru', [
        'nama_guru' => 'Ibu Tanpa Mengajar',
        'mengajar' => [],
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
    ]);

    $response->assertSessionHasErrors('mengajar');
    expect(Guru::where('nama_guru', 'Ibu Tanpa Mengajar')->count())->toBe(0);
});

test('Admin buat guru dengan nama duplikat → username auto-increment counter', function () {
    $admin = User::factory()->admin()->create();

    User::factory()->guru()->create(['username' => 'sariwahyuni', 'name' => 'Ibu Sari Wahyuni']);

    $this->actingAs($admin)->post('/admin/guru', [
        'nama_guru' => 'Ibu Sari Wahyuni',
        'mengajar' => [
            ['kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')],
        ],
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
    ])->assertRedirect('/admin/guru');

    $guru = Guru::where('nama_guru', 'Ibu Sari Wahyuni')->where('user_id', '!=', null)->first();
    expect($guru)->not->toBeNull();
    expect(User::find($guru->user_id)->username)->toBe('sariwahyuni2');
});

test('Admin buat guru dengan password < 6 karakter ditolak', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post('/admin/guru', [
        'nama_guru' => 'Ibu Pass Lemah',
        'mengajar' => [
            ['kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')],
        ],
        'password' => 'abc',
        'password_confirmation' => 'abc',
    ]);

    $response->assertSessionHasErrors('password');
    expect(Guru::where('nama_guru', 'Ibu Pass Lemah')->count())->toBe(0);
});

test('Admin buat guru dengan konfirmasi password tidak cocok ditolak', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post('/admin/guru', [
        'nama_guru' => 'Ibu Mismatch',
        'mengajar' => [
            ['kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')],
        ],
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia999',
    ]);

    $response->assertSessionHasErrors('password');
    expect(Guru::where('nama_guru', 'Ibu Mismatch')->count())->toBe(0);
});

test('Guru yang baru dibuat bisa login dengan username dan password', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/admin/guru', [
        'nama_guru' => 'Ibu Login Test',
        'mengajar' => [
            ['kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')],
        ],
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
    ])->assertRedirect('/admin/guru');

    $guru = Guru::where('nama_guru', 'Ibu Login Test')->first();
    $username = User::find($guru->user_id)->username;

    auth()->logout();

    $this->post('/login', [
        'username' => $username,
        'password' => 'rahasia123',
    ])->assertRedirect();

    expect(auth()->check())->toBeTrue();
    expect(auth()->user()->role)->toBe('guru');
});

test('Hapus guru juga menghapus user account terkait (kalau tidak punya nilai)', function () {
    $admin = User::factory()->admin()->create();
    $userGuru = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Dihapus']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('IPA')]);

    $this->actingAs($admin)->delete("/admin/guru/{$guru->id}");

    expect(Guru::find($guru->id))->toBeNull();
    expect(User::find($userGuru->id))->toBeNull();
});

test('Edit guru mengganti kombinasi mengajar dengan benar', function () {
    $admin = User::factory()->admin()->create();
    $guru = Guru::create(['nama_guru' => 'Ibu Edit']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-A'), 'mata_pelajaran_id' => $this->mapelId('Matematika')]);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas_id' => $this->kelasId('X-B'), 'mata_pelajaran_id' => $this->mapelId('Matematika')]);

    $response = $this->actingAs($admin)->put("/admin/guru/{$guru->id}", [
        'nama_guru' => 'Ibu Edit Updated',
        'mengajar' => [
            ['kelas_id' => $this->kelasId('XI-A'), 'mata_pelajaran_id' => $this->mapelId('IPA')],
            ['kelas_id' => $this->kelasId('XI-B'), 'mata_pelajaran_id' => $this->mapelId('IPA')],
        ],
    ]);

    $response->assertRedirect('/admin/guru');
    $guru->refresh();
    expect($guru->nama_guru)->toBe('Ibu Edit Updated');
    expect($guru->mengajar()->count())->toBe(2);
    expect($guru->mengajar()->get()->map(fn ($m) => $m->kelas?->nama)->sort()->values()->all())->toBe(['XI-A', 'XI-B']);
});
