@extends('layouts.app')

@section('title', 'Tambah Mutasi Stok')
@section('subtitle', 'Catat mutasi masuk, keluar, atau penyesuaian stok')

@section('content')
    <div class="mx-auto max-w-2xl">
        <x-card title="Tambah Mutasi Stok">
            @include('stok._form', [
                'action' => route($routePrefix . '.stok.store'),
                'submitLabel' => 'Simpan Mutasi',
            ])
        </x-card>
    </div>
@endsection