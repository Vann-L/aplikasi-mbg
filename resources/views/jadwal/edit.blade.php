@extends('layouts.app')

@section('title', 'Edit Jadwal')
@section('subtitle', 'Jadwal kerja pegawai SPPG')

@section('content')
    <div class="mx-auto max-w-2xl">
        <x-card title="Edit Jadwal">
            @include('jadwal._form', [
                'action' => route('admin.jadwal.update', $jadwal),
                'submitLabel' => 'Perbarui',
                'kembali' => route('admin.jadwal.index'),
            ])
        </x-card>
    </div>
@endsection