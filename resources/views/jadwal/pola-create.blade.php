@extends('layouts.app')

@section('title', 'Buat Pola Mingguan')
@section('subtitle', 'Pola jadwal kerja mingguan')

@section('content')
    <div class="mx-auto max-w-3xl">
        <x-card title="Buat Pola Mingguan">
            @include('jadwal._pola_form', [
                'action' => route('admin.jadwal.pola.store'),
                'submitLabel' => 'Simpan',
                'kembali' => route('admin.jadwal.pola'),
            ])
        </x-card>
    </div>
@endsection