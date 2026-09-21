@php
    $isEdit = isset($sekolah) && $sekolah !== null;
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="npsn" class="block text-sm font-medium text-slate-700">NPSN</label>
            <input id="npsn" name="npsn" type="text" value="{{ old('npsn', $sekolah?->npsn) }}" required
                class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            @error('npsn')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="nama" class="block text-sm font-medium text-slate-700">Nama</label>
            <input id="nama" name="nama" type="text" value="{{ old('nama', $sekolah?->nama) }}" required
                class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            @error('nama')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="alamat" class="block text-sm font-medium text-slate-700">Alamat</label>
        <textarea id="alamat" name="alamat" rows="3"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">{{ old('alamat', $sekolah?->alamat) }}</textarea>
        @error('alamat')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="kontak" class="block text-sm font-medium text-slate-700">Kontak</label>
            <input id="kontak" name="kontak" type="text" value="{{ old('kontak', $sekolah?->kontak) }}"
                class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            @error('kontak')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="jumlah_penerima" class="block text-sm font-medium text-slate-700">Jumlah Penerima</label>
            <input id="jumlah_penerima" name="jumlah_penerima" type="number" min="0"
                value="{{ old('jumlah_penerima', $sekolah?->jumlah_penerima ?? 0) }}" required
                class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            <p class="mt-1 text-xs text-slate-500">Jumlah penerima manfaat MBG di sekolah ini (data referensi).</p>
            @error('jumlah_penerima')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
        <select id="status" name="status" required
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            <option value="active" @selected(old('status', $sekolah?->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $sekolah?->status ?? 'active') === 'inactive')>Inactive</option>
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
        <a href="{{ route('admin.sekolah.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Batal</a>
    </div>
</form>
