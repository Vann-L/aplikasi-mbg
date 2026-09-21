@extends('layouts.app')

@section('title', 'Edit Menu')
@section('subtitle', 'Master data menu MBG')

@section('content')
    <div class="mx-auto max-w-2xl">
        <x-card title="Edit Menu">
            @include('admin.menu._form', [
                'action' => route('admin.menu.update', $menu),
                'menu' => $menu,
                'submitLabel' => 'Perbarui',
            ])
        </x-card>
    </div>
@endsection
