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
     * Menampilkan dashboard siswa beserta profil dan flag yang menandakan
     * apakah siswa tersebut sudah memiliki baris nilai berstatus Final (tervalidasi).
     *
     * Flag `has_nilai` sengaja mengabaikan baris Draft: jika guru baru menginput skor
     * tetapi belum mengunci kombinasi tersebut, data tidak akan ditampilkan ke siswa,
     * menyerupai aturan visibilitas halaman `/siswa/nilai` dan PDF rapor.
     *
     * @return Response Respon Inertia yang merender view `siswa/dashboard`.
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
