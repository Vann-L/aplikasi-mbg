@extends('layouts.app')

@section('title', 'Tambah Bahan Baku')
@section('subtitle', 'Master data bahan baku SPPG')

@section('content')
    <div class="mx-auto max-w-2xl">
        <x-card title="Tambah Bahan Baku">
            @include('admin.bahan-baku._form', [
                'action' => route('admin.bahan-baku.store'),
                'bahanBaku' => null,
                'submitLabel' => 'Simpan',
            ])
        </x-card>
    </div>
@endsection
