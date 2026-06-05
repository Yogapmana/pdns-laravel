<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Nilai;
use App\Models\Siswa;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $siswa = Siswa::with('user')
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $hasNilai = Nilai::where('nis', $siswa->nis)->exists();

        return Inertia::render('siswa/dashboard', [
            'siswa' => $siswa,
            'has_nilai' => $hasNilai,
        ]);
    }
}
