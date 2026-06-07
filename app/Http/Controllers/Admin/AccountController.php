<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    /**
     * Display the paginated user account list with search and role filters.
     *
     * Eager-loads the linked `siswa` and `guru` profiles (and the guru's
     * mengajar combinations) so the table can be rendered without N+1 queries.
     *
     * @param  Request  $request  Current HTTP request; reads `search` and `role` query parameters.
     * @return Response Inertia response rendering `admin/accounts/index`.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $role = $request->input('role');

        $accounts = User::query()
            ->with([
                'siswa:nis,user_id,nama_siswa',
                'guru:id,user_id,nama_guru',
            ])
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

    /**
     * Show the form to create a new admin account.
     *
     * Only the `admin` role is creatable from this form. Guru and siswa
     * accounts are created automatically as part of their respective
     * Tambah Guru / Tambah Siswa flows.
     *
     * @return Response Inertia response rendering `admin/accounts/create-admin`.
     */
    public function showCreateAdmin(): Response
    {
        return Inertia::render('admin/accounts/create-admin');
    }

    /**
     * Persist a new admin account. The admin supplies `username`,
     * `name`, and `password` directly (no auto-generation).
     *
     * @param  Request  $request  Current HTTP request; reads `username`, `name`, `password`, and `password_confirmation`.
     * @return RedirectResponse Redirect to the accounts index with a success flash message.
     */
    public function createAdmin(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:255', Rule::unique(User::class, 'username')],
            'name' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', Password::min(6), 'confirmed'],
        ]);

        $user = User::create([
            'username' => $data['username'],
            'name' => $data['name'] ?? null,
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'password' => Hash::make($data['password']),
        ]);

        return redirect()->route('admin.accounts.index')->with(
            'success',
            "Akun admin {$user->username} berhasil dibuat."
        );
    }

    /**
     * Toggle the `is_active` flag of a user account.
     *
     * Refuses with an error flash if the target account is the currently
     * authenticated user (prevents admins from locking themselves out).
     *
     * @param  User  $user  The target account, resolved by route-model binding.
     * @return RedirectResponse Redirect back with a success or error flash message.
     */
    public function toggleActive(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Akun berhasil {$status}.");
    }

    /**
     * Reset the password of a user account.
     *
     * @param  Request  $request  Current HTTP request; reads the new `password` field (min 6 chars).
     * @param  User  $user  The target account, resolved by route-model binding.
     * @return RedirectResponse Redirect back with a success flash message naming the affected username.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'min:6'],
        ]);

        $user->update(['password' => Hash::make($request->input('password'))]);

        return back()->with('success', "Password untuk {$user->username} berhasil direset.");
    }
}
