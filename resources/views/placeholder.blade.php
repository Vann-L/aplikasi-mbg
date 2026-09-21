@extends('layouts.app')

@section('title', $label)
@section('subtitle', 'Modul belum tersedia')

@section('content')
    <div class="mx-auto max-w-2xl">
        <x-empty-state
            :title="$label"
            description="Modul ini akan tersedia pada phase pengembangan berikutnya."
        />
    </div>
@endsection
