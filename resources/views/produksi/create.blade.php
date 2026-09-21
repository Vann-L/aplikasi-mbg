@extends('layouts.app')

@section('title', 'Buat Produksi')
@section('subtitle', 'Rencana produksi berdasarkan menu dan tanggal')

@section('content')
    <div class="mx-auto max-w-2xl">
        <x-card title="Buat Produksi">
            @include('produksi._form', [
                'action' => route($routePrefix . '.produksi.store'),
            ])
        </x-card>
    </div>
@endsection