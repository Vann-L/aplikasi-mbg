@extends('layouts.app')

@section('title', 'Edit Pegawai')
@section('subtitle', 'Master data pegawai SPPG')

@section('content')
    <div class="mx-auto max-w-2xl">
        <x-card title="Edit Pegawai">
            @include('admin.pegawai._form', [
                'action' => route('admin.pegawai.update', $pegawai),
                'pegawai' => $pegawai,
                'submitLabel' => 'Perbarui',
            ])
        </x-card>
    </div>
@endsection
