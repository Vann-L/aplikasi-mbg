@php
    $statusLabels = [
        \App\Models\JadwalPegawai::STATUS_TERJADWAL => 'Terjadwal',
        \App\Models\JadwalPegawai::STATUS_IZIN => 'Izin',
        \App\Models\JadwalPegawai::STATUS_LIBUR => 'Libur',
    ];

    $statusBadges = [
        \App\Models\JadwalPegawai::STATUS_TERJADWAL => 'bg-emerald-50 text-emerald-700',
        \App\Models\JadwalPegawai::STATUS_IZIN => 'bg-sky-50 text-sky-700',
        \App\Models\JadwalPegawai::STATUS_LIBUR => 'bg-slate-100 text-slate-600',
    ];
@endphp

<span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $statusBadges[$status] ?? 'bg-slate-100 text-slate-600' }}">
    {{ $statusLabels[$status] ?? $status }}
</span>