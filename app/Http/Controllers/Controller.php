<?php

declare(strict_types=1);

namespace App\Http\Controllers;

/**
 * Kelas base controller untuk semua HTTP controller di dalam aplikasi.
 *
 * Controller konkret (namespace Admin, Guru, Siswa) mewarisi kelas ini
 * untuk mendapatkan perilaku bersama dan menyediakan satu leluhur umum
 * untuk type-hinting serta binding service-container.
 */
abstract class Controller
{
    //
}
