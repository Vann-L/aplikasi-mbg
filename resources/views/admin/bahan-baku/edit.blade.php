@extends('layouts.app')

@section('title', 'Edit Bahan Baku')
@section('subtitle', 'Master data bahan baku SPPG')

@section('content')
    <div class="mx-auto max-w-2xl">
        <x-card title="Edit Bahan Baku">
            @include('admin.bahan-baku._form', [
                'action' => route('admin.bahan-baku.update', $bahanBaku),
                'bahanBaku' => $bahanBaku,
                'submitLabel' => 'Perbarui',
            ])
        </x-card>
    </div>
@endsection
