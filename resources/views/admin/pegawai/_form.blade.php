@php
    $isEdit = isset($pegawai) && $pegawai !== null;
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div>
        <label for="id_pegawai" class="block text-sm font-medium text-slate-700">ID Pegawai</label>
        <input id="id_pegawai" name="id_pegawai" type="text" value="{{ old('id_pegawai', $pegawai?->id_pegawai) }}"
            required
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
        @error('id_pegawai')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="nama" class="block text-sm font-medium text-slate-700">Nama</label>
        <input id="nama" name="nama" type="text" value="{{ old('nama', $pegawai?->nama) }}" required
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
        @error('nama')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="jabatan" class="block text-sm font-medium text-slate-700">Jabatan</label>
            <input id="jabatan" name="jabatan" type="text" value="{{ old('jabatan', $pegawai?->jabatan) }}"
                class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            @error('jabatan')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="bagian" class="block text-sm font-medium text-slate-700">Bagian</label>
            <input id="bagian" name="bagian" type="text" value="{{ old('bagian', $pegawai?->bagian) }}"
                class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            @error('bagian')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="no_hp" class="block text-sm font-medium text-slate-700">No. HP</label>
        <input id="no_hp" name="no_hp" type="text" value="{{ old('no_hp', $pegawai?->no_hp) }}"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
        @error('no_hp')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
        <select id="status" name="status" required
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            <option value="active" @selected(old('status', $pegawai?->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $pegawai?->status ?? 'active') === 'inactive')>Inactive</option>
        </select>
        @error('status')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center gap-4 pt-2">
        <button type="submit"
            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
            {{ $submitLabel }}
        </button>
        <a href="{{ route('admin.pegawai.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Batal</a>
    </div>
</form>
