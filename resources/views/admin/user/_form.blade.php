@php
    $isEdit = isset($user) && $user !== null;
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="name" class="block text-sm font-medium text-slate-700">Nama</label>
            <input id="name" name="name" type="text" value="{{ old('name', $user?->name) }}" required
                class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            @error('name')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $user?->email) }}" required
                class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            @error('email')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="role" class="block text-sm font-medium text-slate-700">Role</label>
            <select id="role" name="role" required
                class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                <option value="admin" @selected(old('role', $user?->role ?? 'petugas') === 'admin')>Admin</option>
                <option value="kepala" @selected(old('role', $user?->role ?? 'petugas') === 'kepala')>Kepala</option>
                <option value="petugas" @selected(old('role', $user?->role ?? 'petugas') === 'petugas')>Petugas</option>
            </select>
            @error('role')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
            <select id="status" name="status" required
                class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                <option value="active" @selected(old('status', $user?->status ?? 'active') === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $user?->status ?? 'active') === 'inactive')>Inactive</option>
            </select>
            @error('status')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="pegawai_id" class="block text-sm font-medium text-slate-700">Pegawai Terkait</label>
        <select id="pegawai_id" name="pegawai_id"
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            <option value="">Tanpa Pegawai</option>
            @foreach ($pegawai as $item)
                <option value="{{ $item->id }}" @selected(old('pegawai_id', $user?->pegawai?->id) == $item->id)>
                    {{ $item->nama }} ({{ $item->id_pegawai }})
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">Pilih pegawai yang akan terhubung dengan akun ini. Pegawai yang sudah memiliki akun tidak ditampilkan.</p>
        @error('pegawai_id')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    @if (! $isEdit)
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                <input id="password" name="password" type="password" required
                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                <p class="mt-1 text-xs text-slate-500">Minimal 8 karakter.</p>
                @error('password')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Konfirmasi Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            </div>
        </div>
    @endif

    <div class="flex items-center gap-4 pt-2">
        <button type="submit"
            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
            {{ $submitLabel }}
        </button>
        <a href="{{ route('admin.user.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Batal</a>
    </div>
</form>