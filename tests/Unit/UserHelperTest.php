<?php

declare(strict_types=1);

use App\Models\User;

test('hasRole mengecek apakah user memiliki role tertentu', function () {
    $user = new User(['role' => User::ROLE_ADMIN]);

    expect($user->hasRole(User::ROLE_ADMIN))->toBeTrue();
    expect($user->hasRole(User::ROLE_GURU, User::ROLE_SISWA))->toBeFalse();
    expect($user->hasRole(User::ROLE_GURU, User::ROLE_ADMIN))->toBeTrue();
});

test('isAdmin mengembalikan true hanya jika role adalah admin', function () {
    $admin = new User(['role' => User::ROLE_ADMIN]);
    $guru = new User(['role' => User::ROLE_GURU]);

    expect($admin->isAdmin())->toBeTrue();
    expect($guru->isAdmin())->toBeFalse();
});

test('isGuru mengembalikan true hanya jika role adalah guru', function () {
    $guru = new User(['role' => User::ROLE_GURU]);
    $siswa = new User(['role' => User::ROLE_SISWA]);

    expect($guru->isGuru())->toBeTrue();
    expect($siswa->isGuru())->toBeFalse();
});

test('isSiswa mengembalikan true hanya jika role adalah siswa', function () {
    $siswa = new User(['role' => User::ROLE_SISWA]);
    $admin = new User(['role' => User::ROLE_ADMIN]);

    expect($siswa->isSiswa())->toBeTrue();
    expect($admin->isSiswa())->toBeFalse();
});

test('dashboardRoute mengembalikan route yang sesuai dengan role', function () {
    $admin = new User(['role' => User::ROLE_ADMIN]);
    $guru = new User(['role' => User::ROLE_GURU]);
    $siswa = new User(['role' => User::ROLE_SISWA]);
    $unknown = new User(['role' => 'unknown_role']);

    expect($admin->dashboardRoute())->toBe('admin.dashboard');
    expect($guru->dashboardRoute())->toBe('guru.dashboard');
    expect($siswa->dashboardRoute())->toBe('siswa.dashboard');
    expect($unknown->dashboardRoute())->toBe('login');
});
