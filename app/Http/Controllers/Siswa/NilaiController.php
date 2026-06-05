<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Nilai;
use App\Models\Siswa;
use Inertia\Inertia;
use Inertia\Response;

class NilaiController extends Controller
{
    public function index(): Response
    {
        $siswa = Siswa::where('user_id', auth()->id())->firstOrFail();

        $nilai = Nilai::with('guru:id,nama_guru')
            ->where('nis', $siswa->nis)
            ->get()
            ->groupBy(fn ($n) => $n->kelas.'|'.$n->mata_pelajaran);

        $guruMap = Guru::whereIn('id', $nilai->flatten()->pluck('id_guru')->unique())
            ->get()
            ->keyBy('id');

        $mapelList = $nilai->keys()
            ->map(fn ($k) => explode('|', $k)[1])
            ->unique()
            ->values();

        return Inertia::render('siswa/nilai/index', [
            'siswa' => $siswa,
            'nilai' => $nilai,
            'mapel_list' => $mapelList,
            'guru_map' => $guruMap,
        ]);
    }
}
