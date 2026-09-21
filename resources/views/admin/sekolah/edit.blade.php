@extends('layouts.app')

@section('title', 'Edit Sekolah')
@section('subtitle', 'Master data sekolah penerima MBG')

@section('content')
    <div class="mx-auto max-w-2xl">
        <x-card title="Edit Sekolah">
            @include('admin.sekolah._form', [
                'action' => route('admin.sekolah.update', $sekolah),
                'sekolah' => $sekolah,
                'submitLabel' => 'Perbarui',
            ])
        </x-card>
    </div>
@endsection
