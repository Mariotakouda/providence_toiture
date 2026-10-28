@extends('layouts.app')

@section('title', 'Modifier la catégorie')

@section('content')
    <form method="POST" action="{{ route('categories.update', $category) }}" enctype="multipart/form-data" class="w-full max-w-3xl rounded-xl bg-white p-4 shadow sm:p-6">
        @csrf
        @method('PUT')
        @include('categories._form', ['category' => $category])
    </form>
@endsection
