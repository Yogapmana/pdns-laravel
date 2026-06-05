<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AccountRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $role = $request->input('role');

        $accounts = User::query()
            ->with(['siswa:nis,user_id,nama_siswa,kelas', 'guru:id,user_id,nama_guru', 'guru.mengajar:id_guru,kelas,mata_pelajaran'])
            ->when($search, fn ($q) => $q->where(function ($qq) use ($search) {
                $qq->where('username', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            }))
            ->when($role, fn ($q) => $q->where('role', $role))
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/accounts/index', [
            'accounts' => $accounts,
            'filters' => [
                'search' => $search,
                'role' => $role,
            ],
        ]);
    }

    public function create(): Response
    {
        $siswa = \App\Models\Siswa::whereNull('user_id')
            ->orderBy('nama_siswa')
            ->get(['nis', 'nama_siswa', 'kelas']);

        $guru = \App\Models\Guru::whereNull('user_id')
            ->with('mengajar:id_guru,kelas,mata_pelajaran')
            ->orderBy('nama_guru')
            ->get();

        return Inertia::render('admin/accounts/create', [
            'siswa_tanpa_akun' => $siswa,
            'guru_tanpa_akun' => $guru,
        ]);
    }

    public function store(AccountRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'username' => $data['username'],
            'name' => $data['name'],
            'role' => $data['role'],
            'is_active' => true,
            'password' => Hash::make($data['password']),
        ]);

        if ($data['role'] === User::ROLE_SISWA && ! empty($data['nis'])) {
            \App\Models\Siswa::where('nis', $data['nis'])->update(['user_id' => $user->id]);
        }

        if ($data['role'] === User::ROLE_GURU && ! empty($data['guru_id'])) {
            \App\Models\Guru::where('id', $data['guru_id'])->update(['user_id' => $user->id]);
        }

        return redirect()->route('admin.accounts.index')->with('success', 'Akun berhasil dibuat.');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Akun berhasil {$status}.");
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'min:6'],
        ]);

        $user->update(['password' => Hash::make($request->input('password'))]);

        return back()->with('success', "Password untuk {$user->username} berhasil direset.");
    }
}
