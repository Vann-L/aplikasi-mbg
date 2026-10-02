@php
    $pola ??= null;
    $isEdit = $pola !== null;
    $hariForm = $hariForm ?? [];
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4" data-pola-form>
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="sm:col-span-2">
            <label for="pegawai_id" class="block text-sm font-medium text-slate-700">Pegawai</label>
            <select id="pegawai_id" name="pegawai_id" required
                class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                <option value="">Pilih pegawai</option>
                @foreach ($pegawaiList as $pegawai)
                    <option value="{{ $pegawai->id }}" @selected(old('pegawai_id', $pola?->pegawai_id) == $pegawai->id)>{{ $pegawai->nama }}</option>
                @endforeach
            </select>
            @error('pegawai_id')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="berlaku_mulai" class="block text-sm font-medium text-slate-700">Mulai Berlaku</label>
            <input id="berlaku_mulai" name="berlaku_mulai" type="date"
                value="{{ old('berlaku_mulai', $pola?->berlaku_mulai?->toDateString()) }}" required
                class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            @error('berlaku_mulai')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="keterangan" class="block text-sm font-medium text-slate-700">Keterangan</label>
        <input id="keterangan" name="keterangan" type="text" maxlength="255"
            value="{{ old('keterangan', $pola?->keterangan) }}"
            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
        @error('keterangan')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <x-card title="Jam Kerja per Hari">
        <div class="space-y-3">
            @foreach (\App\Models\PolaJadwal::NAMA_HARI as $index => $namaHari)
                @php
                    $dayNo = $index + 1;
                    $item = $hariForm[$dayNo] ?? ['status' => \App\Models\PolaJadwal::STATUS_KERJA, 'jam_masuk' => null, 'jam_pulang' => null];
                    $status = old("hari.$dayNo.status", $item['status'] ?? \App\Models\PolaJadwal::STATUS_LIBUR);
                @endphp
                <div data-hari-row>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <div class="w-24 shrink-0">
                            <span class="text-sm font-medium text-slate-700">{{ $namaHari }}</span>
                        </div>

                        <select name="hari[{{ $dayNo }}][status]" data-status
                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            <option value="{{ \App\Models\PolaJadwal::STATUS_KERJA }}" @selected($status === \App\Models\PolaJadwal::STATUS_KERJA)>Kerja</option>
                            <option value="{{ \App\Models\PolaJadwal::STATUS_LIBUR }}" @selected($status === \App\Models\PolaJadwal::STATUS_LIBUR)>Libur</option>
                        </select>

                        <input type="time" name="hari[{{ $dayNo }}][jam_masuk]" data-jam-masuk
                            value="{{ old("hari.$dayNo.jam_masuk", $item['jam_masuk'] ?? null) }}"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">

                        <input type="time" name="hari[{{ $dayNo }}][jam_pulang]" data-jam-pulang
                            value="{{ old("hari.$dayNo.jam_pulang", $item['jam_pulang'] ?? null) }}"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>

                    @error("hari.$dayNo.status")
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    @error("hari.$dayNo.jam_masuk")
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    @error("hari.$dayNo.jam_pulang")
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach
            <p class="text-xs text-slate-500">Jam kerja hanya diisi pada hari berstatus Kerja.</p>
        </div>
    </x-card>

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
        const form = document.querySelector('[data-pola-form]');
        if (!form) {
            return;
        }

        form.querySelectorAll('[data-hari-row]').forEach((row) => {
            const statusSelect = row.querySelector('[data-status]');
            const jamMasuk = row.querySelector('[data-jam-masuk]');
            const jamPulang = row.querySelector('[data-jam-pulang]');

            function syncJamFields() {
                const aktif = statusSelect.value === '{{ \App\Models\PolaJadwal::STATUS_KERJA }}';
                jamMasuk.disabled = !aktif;
                jamPulang.disabled = !aktif;
            }

            statusSelect.addEventListener('change', syncJamFields);
            syncJamFields();
        });
    })();
</script>