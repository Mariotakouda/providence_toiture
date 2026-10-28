@extends('layouts.app')

@section('title', 'Nouvelle catégorie')

@section('content')
    <form method="POST" action="{{ route('categories.store') }}" enctype="multipart/form-data" class="w-full max-w-3xl rounded-xl bg-white p-4 shadow sm:p-6">
        @csrf
        @include('categories._form', ['category' => null])
    </form>
@endsection
