<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GuruRequest;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class GuruController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $kelas = $request->input('kelas');
        $mapel = $request->input('mapel');

        $guru = Guru::query()
            ->with('user:id,username,is_active')
            ->with('mengajar:id_guru,kelas,mata_pelajaran')
            ->withCount('nilai')
            ->when($search, fn ($q) => $q->where(function ($qq) use ($search) {
                $qq->where('nama_guru', 'like', "%{$search}%");
            }))
            ->when($kelas, fn ($q) => $q->whereHas('mengajar', fn ($qq) => $qq->where('kelas', $kelas)))
            ->when($mapel, fn ($q) => $q->whereHas('mengajar', fn ($qq) => $qq->where('mata_pelajaran', $mapel)))
            ->orderBy('nama_guru')
            ->paginate(15)
            ->withQueryString();

        $daftarKelas = Kelas::pluckNamaOrdered();
        $daftarMapel = MataPelajaran::pluckNamaOrdered();

        return Inertia::render('admin/guru/index', [
            'guru' => $guru,
            'daftar_kelas' => $daftarKelas,
            'daftar_mapel' => $daftarMapel,
            'filters' => [
                'search' => $search,
                'kelas' => $kelas,
                'mapel' => $mapel,
            ],
        ]);
    }

    public function create(): Response
    {
        $daftarKelas = Kelas::pluckNamaOrdered();
        $daftarMapel = MataPelajaran::pluckNamaOrdered();

        return Inertia::render('admin/guru/create', [
            'daftar_kelas' => $daftarKelas,
            'daftar_mapel' => $daftarMapel,
        ]);
    }

    public function store(GuruRequest $request): RedirectResponse
    {
        $guru = DB::transaction(function () use ($request) {
            $guru = Guru::create(['nama_guru' => $request->validated()['nama_guru']]);
            $this->syncMengajar($guru, $request->getMengajar());

            return $guru;
        });

        return redirect()->route('admin.guru.index')->with('success', "Guru {$guru->nama_guru} berhasil ditambahkan dengan ".count($request->getMengajar()).' kombinasi mengajar.');
    }

    public function edit(Guru $guru): Response
    {
        $guru->load(['user:id,username,is_active', 'mengajar']);
        $daftarKelas = Kelas::pluckNamaOrdered();
        $daftarMapel = MataPelajaran::pluckNamaOrdered();

        return Inertia::render('admin/guru/edit', [
            'guru' => $guru,
            'daftar_kelas' => $daftarKelas,
            'daftar_mapel' => $daftarMapel,
        ]);
    }

    public function update(GuruRequest $request, Guru $guru): RedirectResponse
    {
        DB::transaction(function () use ($request, $guru) {
            $guru->update(['nama_guru' => $request->validated()['nama_guru']]);
            $this->syncMengajar($guru, $request->getMengajar());
        });

        return redirect()->route('admin.guru.index')->with('success', 'Data guru berhasil diperbarui.');
    }

    /**
     * @param  array<int, array{kelas: string, mata_pelajaran: string}>  $mengajar
     */
    private function syncMengajar(Guru $guru, array $mengajar): void
    {
        $guru->mengajar()->delete();

        $unique = [];
        foreach ($mengajar as $row) {
            $key = $row['kelas'].'|'.$row['mata_pelajaran'];
            if (isset($unique[$key])) {
                continue;
            }
            $unique[$key] = true;
            $guru->mengajar()->create([
                'kelas' => $row['kelas'],
                'mata_pelajaran' => $row['mata_pelajaran'],
            ]);
        }
    }

    public function destroy(Guru $guru): RedirectResponse
    {
        if ($guru->nilai()->exists()) {
            return back()->with('error', 'Guru tidak dapat dihapus karena sudah pernah menginput nilai. Gunakan fitur Nonaktifkan Akun sebagai gantinya.');
        }

        DB::transaction(function () use ($guru) {
            $guru->user?->delete();
            $guru->mengajar()->delete();
            $guru->delete();
        });

        return redirect()->route('admin.guru.index')->with('success', 'Guru berhasil dihapus.');
    }

    public function toggleActive(Guru $guru): RedirectResponse
    {
        $user = $guru->user;
        if ($user) {
            $user->update(['is_active' => ! $user->is_active]);
        }

        $status = $user?->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Akun guru berhasil {$status}.");
    }

    public function createAccountForm(Guru $guru): Response
    {
        abort_if($guru->user_id !== null, 404, 'Guru ini sudah memiliki akun.');

        return Inertia::render('admin/guru/create-account', [
            'guru' => $guru,
        ]);
    }

    public function createAccount(Request $request, Guru $guru): RedirectResponse
    {
        abort_if($guru->user_id !== null, 404, 'Guru ini sudah memiliki akun.');

        $data = $request->validate([
            'username' => ['required', 'string', 'max:255', Rule::unique(User::class, 'username')],
            'name' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', Password::min(6), 'confirmed'],
        ]);

        $user = User::create([
            'username' => $data['username'],
            'name' => $data['name'] ?? $guru->nama_guru,
            'role' => User::ROLE_GURU,
            'is_active' => true,
            'password' => Hash::make($data['password']),
        ]);

        $guru->update(['user_id' => $user->id]);

        return redirect()->route('admin.guru.index')->with('success', "Akun untuk guru {$guru->nama_guru} berhasil dibuat (username: {$data['username']}).");
    }
}
