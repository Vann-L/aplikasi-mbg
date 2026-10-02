@extends('layouts.app')

@section('title', 'Tambah Jadwal')
@section('subtitle', 'Jadwal kerja pegawai SPPG')

@section('content')
    <div class="mx-auto max-w-2xl">
        <x-card title="Tambah Jadwal">
            @include('jadwal._form', [
                'action' => route('admin.jadwal.store'),
                'submitLabel' => 'Simpan',
                'kembali' => route('admin.jadwal.index'),
            ])
        </x-card>
    </div>
@endsection