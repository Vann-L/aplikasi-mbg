@extends('layouts.app')

@section('title', 'Absensi Saya')
@section('subtitle', 'Absen masuk dan pulang di hari ini')

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

        @if ($pegawai === null)
            <x-card>
                <x-empty-state title="Akun belum terhubung ke pegawai"
                    description="Hubungkan akun Anda dengan data pegawai agar dapat melakukan absensi." />
            </x-card>
        @else
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-card title="Jadwal Hari Ini">
                    @if ($jadwalHariIni === null)
                        <p class="text-sm text-slate-500">Tidak ada jadwal kerja hari ini.</p>
                    @else
                        <dl class="space-y-3 text-sm">
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Status Jadwal</dt>
                                <dd>
                                    @if ($jadwalHariIni->status === \App\Models\JadwalPegawai::STATUS_TERJADWAL)
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Terjadwal</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ ucfirst($jadwalHariIni->status) }}</span>
                                    @endif
                                </dd>
                            </div>
                            @if ($jadwalHariIni->status === \App\Models\JadwalPegawai::STATUS_TERJADWAL)
                                <div class="flex items-start justify-between gap-4">
                                    <dt class="text-slate-500">Jam Kerja</dt>
                                    <dd class="text-slate-800">
                                        {{ $jadwalHariIni->jam_masuk?->format('H:i') ?? '-' }} &ndash; {{ $jadwalHariIni->jam_pulang?->format('H:i') ?? '-' }}
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    @endif
                </x-card>

                <x-card title="Absensi Hari Ini">
                    @if ($absensiHariIni === null)
                        <p class="text-sm text-slate-500">Belum ada absensi hari ini.</p>
                    @else
                        <dl class="space-y-3 text-sm">
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Jam Masuk</dt>
                                <dd class="font-medium text-slate-800">{{ $absensiHariIni->jam_masuk?->format('H:i') ?? '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Jam Pulang</dt>
                                <dd class="font-medium text-slate-800">{{ $absensiHariIni->jam_pulang?->format('H:i') ?? '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Status</dt>
                                <dd>@include('absensi._status_badge', ['status' => $absensiHariIni->status])</dd>
                            </div>
                        </dl>
                    @endif
                </x-card>
            </div>

            <x-card title="Scan QR untuk Absen">
                @php
                    $statusAbsen = $absensiHariIni === null
                        ? null
                        : ($absensiHariIni->jam_pulang !== null ? 'pulang' : 'masuk');
                @endphp

                <div class="mb-4 flex flex-wrap items-center gap-2">
                    <span class="text-sm text-slate-500">Status:</span>
                    @if ($statusAbsen === null)
                        <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700">Belum Absen</span>
                    @elseif ($statusAbsen === 'masuk')
                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">Sudah Absen Masuk</span>
                    @else
                        <span class="inline-flex rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">Sudah Absen Pulang</span>
                    @endif
                </div>

                <div data-qr-scanner class="space-y-4">
                    <div>
                        <button type="button" data-scan-start
                            class="w-full rounded-xl bg-emerald-600 px-6 py-3 text-base font-semibold text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 sm:w-auto">
                            Mulai Scan QR
                        </button>
                        <p class="mt-2 text-sm text-slate-600">
                            Token QR tersedia di halaman display yang ditampilkan admin / kepala. Token berganti setiap
                            {{ $expirySeconds }} detik.
                        </p>
                    </div>

                    <p data-scan-error
                        class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></p>

                    <p data-scan-insecure
                        class="hidden rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-700">
                        Kamera membutuhkan koneksi HTTPS untuk digunakan. Gunakan menu input manual di bawah.
                    </p>

                    <div data-scan-camera class="hidden space-y-3">
                        <div class="overflow-hidden rounded-xl bg-slate-900">
                            <video data-scan-video playsinline muted class="aspect-video w-full object-cover"></video>
                        </div>
                        <p data-scan-status class="text-sm text-slate-600">Arahkan kamera ke QR Absensi...</p>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" data-scan-retry
                                class="hidden rounded-lg bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-300">
                                Scan Lagi
                            </button>
                            <button type="button" data-scan-cancel
                                class="rounded-lg bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-300">
                                Tutup Kamera
                            </button>
                        </div>
                    </div>

                    <p data-scan-loading
                        class="hidden rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                        Memproses absensi...
                    </p>

                    <div class="border-t border-slate-100 pt-4">
                        <p class="text-sm text-slate-500">Gunakan input manual jika kamera tidak tersedia.</p>
                        <form data-scan-form method="POST" action="{{ route('petugas.absensi.scan') }}"
                            class="mt-2 flex flex-col gap-2 sm:flex-row">
                            @csrf
                            <input data-scan-token-input type="text" name="token" required placeholder="Masukkan token QR"
                                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            <button type="submit"
                                class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                                Absen
                            </button>
                        </form>
                    </div>
                </div>
            </x-card>

            <x-card title="Riwayat Absensi">
                @if ($riwayat->isEmpty())
                    <x-empty-state title="Belum ada riwayat absensi" description="Absensi yang sudah tercatat akan tampil di sini." />
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                    <th class="px-3 py-2 font-medium">Tanggal</th>
                                    <th class="px-3 py-2 font-medium">Jam Masuk</th>
                                    <th class="px-3 py-2 font-medium">Jam Pulang</th>
                                    <th class="px-3 py-2 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($riwayat as $item)
                                    <tr>
                                        <td class="px-3 py-2 text-slate-600">{{ $item->tanggal->format('d/m/Y') }}</td>
                                        <td class="px-3 py-2 text-slate-600">{{ $item->jam_masuk?->format('H:i') ?? '-' }}</td>
                                        <td class="px-3 py-2 text-slate-600">{{ $item->jam_pulang?->format('H:i') ?? '-' }}</td>
                                        <td class="px-3 py-2">@include('absensi._status_badge', ['status' => $item->status])</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>
        @endif
    </div>
@endsection