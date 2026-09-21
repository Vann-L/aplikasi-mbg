@extends('layouts.app')

@section('title', 'Tambah Menu')
@section('subtitle', 'Master data menu MBG')

@section('content')
    <div class="mx-auto max-w-2xl">
        <x-card title="Tambah Menu">
            @include('admin.menu._form', [
                'action' => route('admin.menu.store'),
                'menu' => null,
                'submitLabel' => 'Simpan',
            ])
        </x-card>
    </div>
@endsection
