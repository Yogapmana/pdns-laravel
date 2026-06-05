<?php

use App\Models\Guru;
use App\Models\GuruMengajar;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('Admin buat guru baru TANPA akun login (akun dibuat terpisah di halaman Manajemen Akun)', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post('/admin/guru', [
        'nama_guru' => 'Ibu Test Guru',
        'mengajar' => [
            ['kelas' => 'X-A', 'mata_pelajaran' => 'Matematika'],
        ],
    ]);

    $response->assertRedirect('/admin/guru');
    $response->assertSessionHas('success');

    $guru = Guru::where('nama_guru', 'Ibu Test Guru')->first();
    expect($guru)->not->toBeNull();
    expect($guru->user_id)->toBeNull();
    expect($guru->mengajar()->count())->toBe(1);
    expect($guru->mengajar()->first()->kelas)->toBe('X-A');
    expect($guru->mengajar()->first()->mata_pelajaran)->toBe('Matematika');
});

test('Admin buat guru dengan multiple kombinasi mengajar tersimpan', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post('/admin/guru', [
        'nama_guru' => 'Ibu Multi Mapel',
        'mengajar' => [
            ['kelas' => 'X-A', 'mata_pelajaran' => 'Matematika'],
            ['kelas' => 'X-B', 'mata_pelajaran' => 'Matematika'],
            ['kelas' => 'XI-A', 'mata_pelajaran' => 'Matematika'],
        ],
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
    ]);

    $response->assertSessionHasErrors('mengajar');
    expect(Guru::where('nama_guru', 'Ibu Tanpa Mengajar')->count())->toBe(0);
});

test('Admin buat akun untuk guru yang sudah ada (tanpa akun)', function () {
    $admin = User::factory()->admin()->create();
    $guru = Guru::create(['nama_guru' => 'Ibu Lama']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Biologi']);
    expect($guru->user_id)->toBeNull();

    $response = $this->actingAs($admin)->post("/admin/guru/{$guru->id}/create-account", [
        'username' => 'guru.lama',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
    ]);

    $response->assertRedirect('/admin/guru');
    $response->assertSessionHas('success');

    $guru->refresh();
    expect($guru->user_id)->not->toBeNull();
    $user = User::find($guru->user_id);
    expect($user->username)->toBe('guru.lama');
    expect($user->role)->toBe('guru');
});

test('Guru yang sudah punya akun tidak dapat dibuatkan akun lagi', function () {
    $admin = User::factory()->admin()->create();
    $userGuru = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Punya Akun']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Kimia']);

    $response = $this->actingAs($admin)->get("/admin/guru/{$guru->id}/create-account");
    $response->assertNotFound();

    $this->actingAs($admin)->post("/admin/guru/{$guru->id}/create-account", [
        'username' => 'guru.lain',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
    ])->assertNotFound();
});

test('Hapus guru juga menghapus user account terkait (kalau tidak punya nilai)', function () {
    $admin = User::factory()->admin()->create();
    $userGuru = User::factory()->guru()->create();
    $guru = Guru::create(['user_id' => $userGuru->id, 'nama_guru' => 'Ibu Dihapus']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Fisika']);

    $this->actingAs($admin)->delete("/admin/guru/{$guru->id}");

    expect(Guru::find($guru->id))->toBeNull();
    expect(User::find($userGuru->id))->toBeNull();
});

test('Edit guru mengganti kombinasi mengajar dengan benar', function () {
    $admin = User::factory()->admin()->create();
    $guru = Guru::create(['nama_guru' => 'Ibu Edit']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-A', 'mata_pelajaran' => 'Matematika']);
    GuruMengajar::create(['id_guru' => $guru->id, 'kelas' => 'X-B', 'mata_pelajaran' => 'Matematika']);

    $response = $this->actingAs($admin)->put("/admin/guru/{$guru->id}", [
        'nama_guru' => 'Ibu Edit Updated',
        'mengajar' => [
            ['kelas' => 'XI-A', 'mata_pelajaran' => 'IPA'],
            ['kelas' => 'XI-B', 'mata_pelajaran' => 'IPA'],
        ],
    ]);

    $response->assertRedirect('/admin/guru');
    $guru->refresh();
    expect($guru->nama_guru)->toBe('Ibu Edit Updated');
    expect($guru->mengajar()->count())->toBe(2);
    expect($guru->mengajar()->pluck('kelas')->sort()->values()->all())->toBe(['XI-A', 'XI-B']);
});
