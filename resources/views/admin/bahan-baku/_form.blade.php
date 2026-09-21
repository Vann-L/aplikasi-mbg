@php
    $isEdit = isset($bahanBaku) && $bahanBaku !== null;
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div>
        <label for="nama" class="block text-sm font-medium text-slate-700">Nama Bahan</label>
        <input id="nama" name="nama" type="text" value="{{ old('nama', $bahanBaku?->nama) }}" required
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
        @error('nama')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="satuan" class="block text-sm font-medium text-slate-700">Satuan</label>
            <input id="satuan" name="satuan" type="text" value="{{ old('satuan', $bahanBaku?->satuan) }}"
                list="satuan-umum" required
                class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            <datalist id="satuan-umum">
                @foreach (['kg', 'gram', 'liter', 'pcs', 'box'] as $satuan)
                    <option value="{{ $satuan }}"></option>
                @endforeach
            </datalist>
            @error('satuan')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="stok_minimum" class="block text-sm font-medium text-slate-700">Stok Minimum</label>
            <input id="stok_minimum" name="stok_minimum" type="number" step="0.01" min="0"
                value="{{ old('stok_minimum', $bahanBaku?->stok_minimum ?? 0) }}" required
                class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            <p class="mt-1 text-xs text-slate-500">Batas minimum stok untuk indikator stok rendah.</p>
            @error('stok_minimum')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
        <select id="status" name="status" required
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            <option value="active" @selected(old('status', $bahanBaku?->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $bahanBaku?->status ?? 'active') === 'inactive')>Inactive</option>
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
        <a href="{{ route('admin.bahan-baku.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Batal</a>
    </div>
</form>
