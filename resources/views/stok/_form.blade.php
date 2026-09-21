<form method="POST" action="{{ $action }}" class="space-y-4">
    @csrf

    <div>
        <label for="bahan_baku_id" class="block text-sm font-medium text-slate-700">Bahan Baku</label>
        <select id="bahan_baku_id" name="bahan_baku_id" required
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            <option value="">Pilih bahan baku</option>
            @foreach ($bahanBaku as $item)
                <option value="{{ $item->id }}" @selected(old('bahan_baku_id') == $item->id)>{{ $item->nama }}</option>
            @endforeach
        </select>
        @error('bahan_baku_id')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="tipe" class="block text-sm font-medium text-slate-700">Tipe Mutasi</label>
        <select id="tipe" name="tipe" required
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            <option value="masuk" @selected(old('tipe', 'masuk') === 'masuk')>Masuk</option>
            <option value="keluar" @selected(old('tipe') === 'keluar')>Keluar</option>
            @if ($admin)
                <option value="penyesuaian" @selected(old('tipe') === 'penyesuaian')>Penyesuaian</option>
            @endif
        </select>
        @if ($admin)
            <p class="mt-1 text-xs text-slate-500">
                Penyesuaian digunakan untuk koreksi stok; gunakan angka negatif untuk mengurangi stok.
            </p>
        @else
            <p class="mt-1 text-xs text-slate-500">Petugas dapat mencatat stok masuk dan keluar.</p>
        @endif
        @error('tipe')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="jumlah" class="block text-sm font-medium text-slate-700">Jumlah</label>
        <input id="jumlah" name="jumlah" type="number" step="0.01" value="{{ old('jumlah') }}" required
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
        <p class="mt-1 text-xs text-slate-500">Jumlah harus lebih dari 0. Stok tidak boleh menjadi negatif.</p>
        @error('jumlah')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="tanggal" class="block text-sm font-medium text-slate-700">Tanggal</label>
        <input id="tanggal" name="tanggal" type="date" value="{{ old('tanggal', now()->toDateString()) }}" required
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
        @error('tanggal')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="keterangan" class="block text-sm font-medium text-slate-700">Keterangan</label>
        <textarea id="keterangan" name="keterangan" rows="3"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">{{ old('keterangan') }}</textarea>
        @error('keterangan')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center gap-4 pt-2">
        <button type="submit"
            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
            {{ $submitLabel }}
        </button>
        <a href="{{ route($routePrefix . '.stok.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Batal</a>
    </div>
</form>