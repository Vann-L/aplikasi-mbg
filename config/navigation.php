<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Role Navigation
    |--------------------------------------------------------------------------
    |
    | Sidebar items per role. Each item points to a named route so the active
    | state and URL generation stay consistent with the route definitions.
    |
    */

    'roles' => [

        'admin' => [
            ['label' => 'Dashboard', 'route' => 'admin.dashboard'],
            ['label' => 'Pegawai', 'route' => 'admin.pegawai.index'],
            ['label' => 'Sekolah', 'route' => 'admin.sekolah.index'],
            ['label' => 'Menu', 'route' => 'admin.menu.index'],
            ['label' => 'Bahan Baku', 'route' => 'admin.bahan-baku.index'],
            ['label' => 'Stok', 'route' => 'admin.stok.index'],
            ['label' => 'Produksi', 'route' => 'admin.produksi.index'],
            ['label' => 'Distribusi', 'route' => 'admin.distribusi.index'],
            ['label' => 'Penerimaan', 'route' => 'admin.penerimaan.index'],
            ['label' => 'Absensi', 'route' => 'admin.absensi.index'],
            ['label' => 'Laporan', 'route' => 'admin.laporan.index'],
            ['label' => 'User', 'route' => 'admin.user.index'],
        ],

        'kepala' => [
            ['label' => 'Dashboard', 'route' => 'kepala.dashboard'],
            ['label' => 'Stok', 'route' => 'kepala.stok.index'],
            ['label' => 'Produksi', 'route' => 'kepala.produksi.index'],
            ['label' => 'Distribusi', 'route' => 'kepala.distribusi.index'],
            ['label' => 'Penerimaan', 'route' => 'kepala.penerimaan.index'],
            ['label' => 'Absensi', 'route' => 'kepala.absensi.index'],
            ['label' => 'Laporan', 'route' => 'kepala.laporan.index'],
        ],

        'petugas' => [
            ['label' => 'Dashboard', 'route' => 'petugas.dashboard'],
            ['label' => 'Stok', 'route' => 'petugas.stok.index'],
            ['label' => 'Jadwal', 'route' => 'petugas.jadwal.index'],
            ['label' => 'Absensi', 'route' => 'petugas.absensi.index'],
            ['label' => 'Produksi', 'route' => 'petugas.produksi.index'],
            ['label' => 'Distribusi', 'route' => 'petugas.distribusi.index'],
            ['label' => 'Penerimaan', 'route' => 'petugas.penerimaan.index'],
        ],

    ],

];
