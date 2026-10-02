@extends('layouts.app')

@section('title', 'Edit Pola Mingguan')
@section('subtitle', 'Pola jadwal kerja mingguan')

@section('content')
    <div class="mx-auto max-w-3xl">
        <x-card title="Edit Pola Mingguan">
            @include('jadwal._pola_form', [
                'action' => route('admin.jadwal.pola.update', $pola),
                'submitLabel' => 'Perbarui',
                'kembali' => route('admin.jadwal.pola'),
            ])
        </x-card>
    </div>
@endsection