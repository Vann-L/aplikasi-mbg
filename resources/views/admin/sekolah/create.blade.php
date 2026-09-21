@extends('layouts.app')

@section('title', 'Tambah Sekolah')
@section('subtitle', 'Master data sekolah penerima MBG')

@section('content')
    <div class="mx-auto max-w-2xl">
        <x-card title="Tambah Sekolah">
            @include('admin.sekolah._form', [
                'action' => route('admin.sekolah.store'),
                'sekolah' => null,
                'submitLabel' => 'Simpan',
            ])
        </x-card>
    </div>
@endsection
