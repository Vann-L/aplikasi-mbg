<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a filtered and paginated listing of user accounts.
     */
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $role = $request->string('role')->toString();
        $status = $request->string('status')->toString();

        $users = User::query()
            ->with('pegawai')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhereHas('pegawai', fn ($query) => $query->where('id_pegawai', 'like', "%{$search}%"))))
            ->when(
                in_array($role, [User::ROLE_ADMIN, User::ROLE_KEPALA, User::ROLE_PETUGAS], true),
                fn ($query) => $query->where('role', $role),
            )
            ->when(
                in_array($status, [User::STATUS_ACTIVE, User::STATUS_INACTIVE], true),
                fn ($query) => $query->where('status', $status),
            )
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.user.index', [
            'users' => $users,
            'search' => $search,
            'role' => $role,
            'status' => $status,
        ]);
    }

    /**
     * Show the form for creating a new user account.
     */
    public function create(): View
    {
        return view('admin.user.create', [
            'pegawai' => Pegawai::whereNull('user_id')->orderBy('nama')->get(),
        ]);
    }

    /**
     * Store a newly created user account.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create($request->safe()->only(['name', 'email', 'role', 'status', 'password']));

        if ($request->filled('pegawai_id')) {
            Pegawai::whereKey($request->integer('pegawai_id'))
                ->whereNull('user_id')
                ->update(['user_id' => $user->id]);
        }

        return redirect()->route('admin.user.index')->with('status', 'Akun pengguna berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified user account.
     */
    public function edit(User $user): View
    {
        return view('admin.user.edit', [
            'user' => $user,
            'pegawai' => Pegawai::query()
                ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $user->id))
                ->orderBy('nama')
                ->get(),
        ]);
    }

    /**
     * Update the specified user account.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id && $request->string('status')->toString() === User::STATUS_INACTIVE) {
            return redirect()->route('admin.user.edit', $user)
                ->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $user->update($request->safe()->only(['name', 'email', 'role', 'status']));

        $pegawaiId = $request->filled('pegawai_id') ? $request->integer('pegawai_id') : null;
        $this->syncPegawai($user, $pegawaiId);

        return redirect()->route('admin.user.index')->with('status', 'Akun pengguna berhasil diperbarui.');
    }

    /**
     * Show the form to reset the password of the specified user account.
     */
    public function resetPassword(User $user): View
    {
        return view('admin.user.reset', ['user' => $user]);
    }

    /**
     * Move the user link to the given pegawai, detaching any previous link.
     */
    private function syncPegawai(User $user, ?int $pegawaiId): void
    {
        Pegawai::where('user_id', $user->id)->update(['user_id' => null]);

        if ($pegawaiId !== null) {
            Pegawai::whereKey($pegawaiId)->whereNull('user_id')->update(['user_id' => $user->id]);
        }
    }
}
