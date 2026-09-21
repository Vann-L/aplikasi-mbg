@php
    $isEdit = isset($menu) && $menu !== null;
    $fotoUrl = $menu?->fotoUrl();
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-4">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div>
        <label for="nama" class="block text-sm font-medium text-slate-700">Nama</label>
        <input id="nama" name="nama" type="text" value="{{ old('nama', $menu?->nama) }}" required
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
        @error('nama')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="deskripsi" class="block text-sm font-medium text-slate-700">Deskripsi</label>
        <textarea id="deskripsi" name="deskripsi" rows="3"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">{{ old('deskripsi', $menu?->deskripsi) }}</textarea>
        @error('deskripsi')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="foto" class="block text-sm font-medium text-slate-700">Foto</label>

        <img data-foto-preview src="{{ $fotoUrl }}" alt="Pratinjau foto menu"
            class="mt-2 h-32 w-32 rounded-lg border border-slate-200 object-cover {{ $fotoUrl ? '' : 'hidden' }}">

        <input id="foto" name="foto" type="file" accept="image/jpeg,image/png,image/webp" data-foto-input
            class="mt-2 block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-emerald-700 hover:file:bg-emerald-100">
        <p class="mt-1 text-xs text-slate-500">Format jpg/jpeg, png, atau webp. Maksimal 2 MB. Kosongkan untuk
            mempertahankan foto lama.</p>
        @error('foto')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
        <select id="status" name="status" required
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            <option value="active" @selected(old('status', $menu?->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $menu?->status ?? 'active') === 'inactive')>Inactive</option>
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
        <a href="{{ route('admin.menu.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Batal</a>
    </div>
</form>
