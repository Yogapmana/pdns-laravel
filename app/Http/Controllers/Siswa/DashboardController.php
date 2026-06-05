<?php

declare(strict_types=1);

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Nilai;
use App\Models\Siswa;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the siswa dashboard with the profile and a flag indicating
     * whether the siswa has any nilai rows yet.
     *
     * @return Response Inertia response rendering `siswa/dashboard`.
     */
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
