<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SiswaController;
use App\Http\Controllers\Admin\GuruController;
use App\Http\Controllers\Guru\DashboardController as GuruDashboardController;
use App\Http\Controllers\Guru\NilaiController;
use App\Http\Controllers\Siswa\DashboardController as SiswaDashboardController;
use App\Http\Controllers\Siswa\NilaiController as SiswaNilaiController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route(Auth::user()->dashboardRoute());
    }

    return redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'role:admin,guru,siswa'])->get('/redirect-by-role', function () {
    $user = Auth::user();

    return redirect()->route($user->dashboardRoute());
})->name('redirect-by-role');

Route::prefix('admin')->middleware(['auth', 'role:admin'])->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/siswa', [SiswaController::class, 'index'])->name('siswa.index');
    Route::get('/siswa/create', [SiswaController::class, 'create'])->name('siswa.create');
    Route::post('/siswa', [SiswaController::class, 'store'])->name('siswa.store');
    Route::get('/siswa/{siswa}/edit', [SiswaController::class, 'edit'])->name('siswa.edit');
    Route::put('/siswa/{siswa}', [SiswaController::class, 'update'])->name('siswa.update');
    Route::delete('/siswa/{siswa}', [SiswaController::class, 'destroy'])->name('siswa.destroy');

    Route::get('/guru', [GuruController::class, 'index'])->name('guru.index');
    Route::get('/guru/create', [GuruController::class, 'create'])->name('guru.create');
    Route::post('/guru', [GuruController::class, 'store'])->name('guru.store');
    Route::get('/guru/{guru}/edit', [GuruController::class, 'edit'])->name('guru.edit');
    Route::put('/guru/{guru}', [GuruController::class, 'update'])->name('guru.update');
    Route::delete('/guru/{guru}', [GuruController::class, 'destroy'])->name('guru.destroy');
    Route::patch('/guru/{guru}/toggle-active', [GuruController::class, 'toggleActive'])->name('guru.toggle-active');
    Route::get('/guru/{guru}/create-account', [GuruController::class, 'createAccountForm'])->name('guru.create-account');
    Route::post('/guru/{guru}/create-account', [GuruController::class, 'createAccount'])->name('guru.create-account.store');

    Route::get('/akun', [AccountController::class, 'index'])->name('accounts.index');
    Route::get('/akun/create', [AccountController::class, 'create'])->name('accounts.create');
    Route::post('/akun', [AccountController::class, 'store'])->name('accounts.store');
    Route::patch('/akun/{user}/toggle-active', [AccountController::class, 'toggleActive'])->name('accounts.toggle-active');
    Route::post('/akun/{user}/reset-password', [AccountController::class, 'resetPassword'])->name('accounts.reset-password');

    Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/laporan/preview', [ReportController::class, 'preview'])->name('reports.preview');
    Route::get('/laporan/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
    Route::get('/laporan/export/html', [ReportController::class, 'exportHtml'])->name('reports.export.html');
});

Route::prefix('guru')->middleware(['auth', 'role:guru'])->name('guru.')->group(function () {
    Route::get('/dashboard', [GuruDashboardController::class, 'index'])->name('dashboard');

    Route::get('/input-nilai', [NilaiController::class, 'index'])->name('nilai.index');
    Route::post('/input-nilai/save', [NilaiController::class, 'save'])->name('nilai.save');
    Route::post('/input-nilai/validate-final', [NilaiController::class, 'validateFinal'])->name('nilai.validate-final');
    Route::delete('/input-nilai/{nilai}', [NilaiController::class, 'destroy'])->name('nilai.destroy');

    Route::get('/rekap', [NilaiController::class, 'rekap'])->name('rekap.index');
});

Route::prefix('siswa')->middleware(['auth', 'role:siswa'])->name('siswa.')->group(function () {
    Route::get('/dashboard', [SiswaDashboardController::class, 'index'])->name('dashboard');
    Route::get('/nilai', [SiswaNilaiController::class, 'index'])->name('nilai.index');
});
