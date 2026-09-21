@extends('layouts.app')

@section('title', 'Tambah Pegawai')
@section('subtitle', 'Master data pegawai SPPG')

@section('content')
    <div class="mx-auto max-w-2xl">
        <x-card title="Tambah Pegawai">
            @include('admin.pegawai._form', [
                'action' => route('admin.pegawai.store'),
                'pegawai' => null,
                'submitLabel' => 'Simpan',
            ])
        </x-card>
    </div>
@endsection
