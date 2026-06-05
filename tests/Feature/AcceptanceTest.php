<?php

use App\Models\Nilai;
use App\Models\Siswa;
use App\Models\User;

test('AC-01: Login dengan kredensial valid role Admin redirect ke dashboard admin', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->post('/login', [
        'username' => $admin->username,
        'password' => 'password',
    ]);

    $response->assertRedirect('/redirect-by-role');
    $this->assertAuthenticatedAs($admin);

    $this->get('/redirect-by-role')->assertRedirect('/admin/dashboard');
});

test('AC-01b: Login dengan kredensial valid role Guru redirect ke dashboard guru', function () {
    $guru = User::factory()->guru()->create();

    $response = $this->post('/login', [
        'username' => $guru->username,
        'password' => 'password',
    ]);

    $response->assertRedirect('/redirect-by-role');
    $this->assertAuthenticatedAs($guru);

    $this->get('/redirect-by-role')->assertRedirect('/guru/dashboard');
});

test('AC-01c: Login dengan kredensial valid role Siswa redirect ke dashboard siswa', function () {
    $siswa = User::factory()->siswa()->create();

    $response = $this->post('/login', [
        'username' => $siswa->username,
        'password' => 'password',
    ]);

    $response->assertRedirect('/redirect-by-role');
    $this->assertAuthenticatedAs($siswa);

    $this->get('/redirect-by-role')->assertRedirect('/siswa/dashboard');
});

test('AC-02: Login dengan password salah ditolak', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->post('/login', [
        'username' => $admin->username,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors();
    $this->assertGuest();
});

test('AC-02b: Login dengan username kosong ditolak', function () {
    $response = $this->post('/login', [
        'username' => '',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('username');
    $this->assertGuest();
});

test('Halaman awal / redirect ke /login untuk guest, ke dashboard sesuai role untuk user terautentikasi', function () {
    $admin = User::factory()->admin()->create();
    $guru = User::factory()->guru()->create();
    $siswa = User::factory()->siswa()->create();

    $this->get('/')->assertRedirect('/login');

    $this->actingAs($admin)->get('/')->assertRedirect('/admin/dashboard');
    $this->actingAs($guru)->get('/')->assertRedirect('/guru/dashboard');
    $this->actingAs($siswa)->get('/')->assertRedirect('/siswa/dashboard');
});
