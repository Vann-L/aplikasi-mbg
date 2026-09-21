@php
    $badges = [
        \App\Models\Absensi::STATUS_HADIR => 'bg-emerald-50 text-emerald-700',
        \App\Models\Absensi::STATUS_TERLAMBAT => 'bg-amber-50 text-amber-700',
        \App\Models\Absensi::STATUS_IZIN => 'bg-sky-50 text-sky-700',
        \App\Models\Absensi::STATUS_ALPA => 'bg-red-50 text-red-700',
        'libur' => 'bg-slate-100 text-slate-600',
    ];
    $labels = [
        \App\Models\Absensi::STATUS_HADIR => 'Hadir',
        \App\Models\Absensi::STATUS_TERLAMBAT => 'Terlambat',
        \App\Models\Absensi::STATUS_IZIN => 'Izin',
        \App\Models\Absensi::STATUS_ALPA => 'Alpa',
        'libur' => 'Libur',
    ];
    $badge = $badges[$status] ?? 'bg-slate-100 text-slate-600';
    $label = $labels[$status] ?? ucwords(str_replace('_', ' ', $status));
@endphp

<span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $badge }}">{{ $label }}</span>