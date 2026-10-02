@php
    $jadwal ??= null;
    $isEdit = $jadwal !== null;
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4" data-jadwal-form>
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div>
        <label for="pegawai_id" class="block text-sm font-medium text-slate-700">Pegawai</label>
        <select id="pegawai_id" name="pegawai_id" required @disabled($hasAbsensi ?? false)
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 disabled:bg-slate-100">
            <option value="">Pilih pegawai</option>
            @foreach ($pegawaiList as $pegawai)
                <option value="{{ $pegawai->id }}" @selected(old('pegawai_id', $jadwal?->pegawai_id) == $pegawai->id)>{{ $pegawai->nama }}</option>
            @endforeach
        </select>
        @error('pegawai_id')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="tanggal" class="block text-sm font-medium text-slate-700">Tanggal</label>
        <input id="tanggal" name="tanggal" type="date" value="{{ old('tanggal', $jadwal?->tanggal?->toDateString()) }}"
            required @disabled($hasAbsensi ?? false)
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 disabled:bg-slate-100">
        @error('tanggal')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
        <select id="status" name="status" required data-status @disabled($hasAbsensi ?? false)
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 disabled:bg-slate-100">
            @foreach (\App\Models\JadwalPegawai::STATUSES as $item)
                <option value="{{ $item }}" @selected(old('status', $jadwal?->status ?? \App\Models\JadwalPegawai::STATUS_TERJADWAL) === $item)>{{ ucwords($item) }}</option>
            @endforeach
        </select>
        @error('status')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="jam_masuk" class="block text-sm font-medium text-slate-700">Jam Masuk</label>
            <input id="jam_masuk" name="jam_masuk" type="time" value="{{ old('jam_masuk', $jadwal?->jam_masuk?->format('H:i')) }}"
                data-jam-input @disabled($hasAbsensi ?? false)
                class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 disabled:bg-slate-100">
            @error('jam_masuk')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="jam_pulang" class="block text-sm font-medium text-slate-700">Jam Pulang</label>
            <input id="jam_pulang" name="jam_pulang" type="time" value="{{ old('jam_pulang', $jadwal?->jam_pulang?->format('H:i')) }}"
                data-jam-input @disabled($hasAbsensi ?? false)
                class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 disabled:bg-slate-100">
            @error('jam_pulang')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="catatan" class="block text-sm font-medium text-slate-700">Catatan</label>
        <textarea id="catatan" name="catatan" rows="2" maxlength="255"
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">{{ old('catatan', $jadwal?->catatan) }}</textarea>
        @error('catatan')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    @if ($hasAbsensi ?? false)
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-700">
            Jadwal ini sudah memiliki absensi. Pegawai, tanggal, jam, dan status tidak dapat diubah; hanya catatan yang
            boleh diperbarui.
        </div>
    @endif

    <div class="flex items-center gap-4 pt-2">
        <button type="submit"
            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
            {{ $submitLabel }}
        </button>
        <a href="{{ $kembali }}" class="text-sm text-slate-600 hover:text-slate-900">Batal</a>
    </div>
</form>

<script>
    (function () {
        const form = document.querySelector('[data-jadwal-form]');
        if (!form) {
            return;
        }

        const statusSelect = form.querySelector('[data-status]');
        const jamInputs = form.querySelectorAll('[data-jam-input]');

        function syncJamFields() {
            const aktif = statusSelect.value === 'terjadwal' && !statusSelect.disabled;
            jamInputs.forEach((input) => {
                input.disabled = !aktif;
                if (!aktif) {
                    input.value = '';
                }
            });
        }

        statusSelect.addEventListener('change', syncJamFields);
        syncJamFields();
    })();
</script>