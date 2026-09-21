<form method="POST" action="{{ $action }}" class="space-y-4">
    @csrf

    <div>
        <label for="menu_id" class="block text-sm font-medium text-slate-700">Menu</label>
        <select id="menu_id" name="menu_id" required
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            <option value="">Pilih menu</option>
            @foreach ($menus as $menu)
                <option value="{{ $menu->id }}" @selected(old('menu_id') == $menu->id)>{{ $menu->nama }}</option>
            @endforeach
        </select>
        @error('menu_id')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="tanggal" class="block text-sm font-medium text-slate-700">Tanggal Produksi</label>
        <input id="tanggal" name="tanggal" type="date" value="{{ old('tanggal', now()->toDateString()) }}" required
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
        @error('tanggal')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center gap-4 pt-2">
        <button type="submit"
            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
            Simpan
        </button>
        <a href="{{ route($routePrefix . '.produksi.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Batal</a>
    </div>
</form>