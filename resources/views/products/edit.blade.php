@extends('layouts.app')

@section('title', 'Modifier le produit')

@section('content')
    <form method="POST" action="{{ route('products.update', $product) }}" class="w-full max-w-6xl rounded-xl bg-white p-4 shadow sm:p-6">
        @csrf
        @method('PUT')
        @include('products._form', ['product' => $product])
    </form>
@endsection
