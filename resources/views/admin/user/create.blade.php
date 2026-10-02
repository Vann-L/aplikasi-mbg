@extends('layouts.app')

@section('title', 'Tambah Akun Pengguna')
@section('subtitle', 'Kelola akun pengguna dan keterkaitannya dengan pegawai')

@section('content')
    <div class="mx-auto max-w-2xl">
        <x-card title="Tambah Akun Pengguna">
            @include('admin.user._form', [
                'action' => route('admin.user.store'),
                'user' => null,
                'submitLabel' => 'Simpan',
            ])
        </x-card>
    </div>
@endsection