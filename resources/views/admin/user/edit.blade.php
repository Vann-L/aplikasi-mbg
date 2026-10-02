@extends('layouts.app')

@section('title', 'Edit Akun Pengguna')
@section('subtitle', 'Kelola akun pengguna dan keterkaitannya dengan pegawai')

@section('content')
    <div class="mx-auto max-w-2xl">
        <x-card title="Edit Akun Pengguna">
            @include('admin.user._form', [
                'action' => route('admin.user.update', $user),
                'user' => $user,
                'submitLabel' => 'Perbarui',
            ])
        </x-card>
    </div>
@endsection