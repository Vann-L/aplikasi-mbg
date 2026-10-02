@extends('layouts.app')

@section('title', 'Jadwal Mingguan')
@section('subtitle', 'Pola jadwal kerja mingguan pegawai SPPG')

@section('content')
    <div class="space-y-4">
        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <div class="no-print">
            @include('jadwal._nav', ['routePrefix' => $routePrefix, 'active' => $active])
        </div>

        @if ($canManage)
            <div class="flex justify-end">
                <a href="{{ route($routePrefix . '.jadwal.pola.create') }}"
                    class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                    Buat Pola Mingguan
                </a>
            </div>
        @endif

        <x-card title="Matriks Pola Aktif">
            <p class="mb-3 text-xs text-slate-500">
                Pola yang ditampilkan adalah pola terbaru yang berlaku. Hari kerja menampilkan rentang jam masuk &ndash;
                jam pulang. Kosongkan baris untuk melihat detail pola di bawah.
            </p>

            @if ($matriks->isEmpty())
                <x-empty-state title="Belum ada pegawai aktif"
                    description="Tambahkan pegawai aktif terlebih dahulu sebelum membuat pola jadwal." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-3 py-2 font-medium">Pegawai</th>
                                @foreach ($namaHari as $hari)
                                    <th class="px-3 py-2 text-center font-medium">{{ $hari }}</th>
                                @endforeach
                                @if ($canManage)
                                    <th class="px-3 py-2 text-right font-medium">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($matriks as $baris)
                                <tr>
                                    <td class="px-3 py-2">
                                        <p class="font-medium text-slate-700">{{ $baris['pegawai']->nama }}</p>
                                        <p class="text-xs text-slate-500">{{ $baris['pegawai']->jabatan ?? '-' }}</p>
                                    </td>
                                    @foreach (range(0, 6) as $index)
                                        @php
                                            $item = $baris['hari'][$index] ?? ['status' => null];
                                            $status = $item['status'] ?? null;
                                        @endphp
                                        <td class="px-3 py-2 text-center">
                                            @if ($status === \App\Models\PolaJadwal::STATUS_KERJA)
                                                <span class="text-slate-700">
                                                    {{ isset($item['jam_masuk']) ? \Illuminate\Support\Str::of($item['jam_masuk'])->substr(0, 5) : '-' }}&ndash;{{ isset($item['jam_pulang']) ? \Illuminate\Support\Str::of($item['jam_pulang'])->substr(0, 5) : '-' }}
                                                </span>
                                            @elseif ($status === \App\Models\PolaJadwal::STATUS_LIBUR)
                                                <span class="text-slate-400">Libur</span>
                                            @else
                                                <span class="text-slate-300">-</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    @if ($canManage)
                                        <td class="px-3 py-2 text-right">
                                            @if ($baris['pola'] !== null)
                                                <a href="{{ route($routePrefix . '.jadwal.pola.edit', $baris['pola']) }}"
                                                    class="text-emerald-700 hover:text-emerald-800">Edit</a>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        <x-card title="Riwayat Pola">
            @if ($polas->isEmpty())
                <x-empty-state title="Belum ada pola jadwal"
                    description="Pola mingguan mengatur jam kerja tetap setiap pegawai. Buat pola baru untuk memulai." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-3 py-2 font-medium">Mulai Berlaku</th>
                                <th class="px-3 py-2 font-medium">Pegawai</th>
                                <th class="px-3 py-2 font-medium">Hari Kerja</th>
                                <th class="px-3 py-2 font-medium">Keterangan</th>
                                @if ($canManage)
                                    <th class="px-3 py-2 text-right font-medium">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($polas as $pola)
                                @php
                                    $kerja = collect($pola->hari)->filter(fn ($item) => ($item['status'] ?? null) === \App\Models\PolaJadwal::STATUS_KERJA)->count();
                                @endphp
                                <tr>
                                    <td class="px-3 py-2 text-slate-600">{{ $pola->berlaku_mulai->format('d/m/Y') }}</td>
                                    <td class="px-3 py-2 font-medium text-slate-700">{{ $pola->pegawai->nama }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $kerja }} hari</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $pola->keterangan ?: '-' }}</td>
                                    @if ($canManage)
                                        <td class="px-3 py-2">
                                            <div class="flex items-center justify-end gap-3">
                                                <a href="{{ route($routePrefix . '.jadwal.pola.edit', $pola) }}"
                                                    class="text-emerald-700 hover:text-emerald-800">Edit</a>

                                                <form method="POST"
                                                    action="{{ route($routePrefix . '.jadwal.pola.destroy', $pola) }}"
                                                    onsubmit="return confirm('Hapus pola jadwal ini? Pola tidak dapat dihapus jika sudah ada absensi pada periode yang dicakupnya.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-700">
                                                        Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
@endsection