<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Traits\SeedsAkademikMasters;

uses(SeedsAkademikMasters::class);

beforeEach(function () {
    $this->seedKelas();
    $this->seedMataPelajaran();
});

test('AC-AKUN-01: Admin buat akun admin baru dengan username + password sendiri', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post('/admin/akun/create-admin', [
        'username' => 'admin.kepsek',
        'name' => 'Kepala Sekolah',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
    ]);

    $response->assertRedirect('/admin/akun');
    $response->assertSessionHas('success');

    $user = User::where('username', 'admin.kepsek')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Kepala Sekolah');
    expect($user->role)->toBe('admin');
    expect($user->is_active)->toBeTrue();
    expect(Hash::check('rahasia123', $user->password))->toBeTrue();
});

test('AC-AKUN-02: Buat akun admin dengan username duplikat ditolak', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->admin()->create(['username' => 'admin.lama']);

    $response = $this->actingAs($admin)->post('/admin/akun/create-admin', [
        'username' => 'admin.lama',
        'name' => 'Admin Duplikat',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
    ]);

    $response->assertSessionHasErrors('username');
    expect(User::where('username', 'admin.lama')->count())->toBe(1);
});

test('AC-AKUN-03: Buat akun admin dengan password < 6 karakter ditolak', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post('/admin/akun/create-admin', [
        'username' => 'admin.baru',
        'name' => 'Admin Lemah',
        'password' => 'abc',
        'password_confirmation' => 'abc',
    ]);

    $response->assertSessionHasErrors('password');
    expect(User::where('username', 'admin.baru')->count())->toBe(0);
});

test('AC-AKUN-04: Buat akun admin dengan konfirmasi password tidak cocok ditolak', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post('/admin/akun/create-admin', [
        'username' => 'admin.baru',
        'name' => 'Admin Mismatch',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia999',
    ]);

    $response->assertSessionHasErrors('password');
    expect(User::where('username', 'admin.baru')->count())->toBe(0);
});

test('AC-AKUN-05: GET /admin/akun/create-admin menampilkan form', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/admin/akun/create-admin')
        ->assertInertia(fn ($page) => $page->component('admin/accounts/create-admin'));
});

test('AC-AKUN-06: Admin yang baru dibuat bisa login', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/admin/akun/create-admin', [
        'username' => 'admin.login',
        'name' => 'Admin Login',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
    ])->assertRedirect('/admin/akun');

    auth()->logout();

    $this->post('/login', [
        'username' => 'admin.login',
        'password' => 'rahasia123',
    ])->assertRedirect();

    expect(auth()->check())->toBeTrue();
    expect(auth()->user()->role)->toBe('admin');
});

test('AC-AKUN-07: Route /admin/akun/create sudah dihapus (replaced by create-admin)', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/admin/akun/create')->assertNotFound();
});
