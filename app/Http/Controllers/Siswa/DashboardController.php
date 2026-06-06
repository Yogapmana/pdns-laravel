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
     * whether the siswa has any Final (validated) nilai rows yet.
     *
     * The `has_nilai` flag deliberately ignores Draft rows: a guru that
     * has only entered scores but not yet locked the combo is not
     * surfaced to the siswa, mirroring the visibility rules of the
     * `/siswa/nilai` page and the rapor PDF.
     *
     * @return Response Inertia response rendering `siswa/dashboard`.
     */
    public function index(): Response
    {
        $siswa = Siswa::with('user')
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $hasNilai = Nilai::where('nis', $siswa->nis)
            ->where('status_validasi', Nilai::STATUS_FINAL)
            ->exists();

        return Inertia::render('siswa/dashboard', [
            'siswa' => $siswa,
            'has_nilai' => $hasNilai,
        ]);
    }
}
