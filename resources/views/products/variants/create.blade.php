@extends('layouts.app')

@section('title', 'Nouvelle variante : '.$product->name)

@section('content')
    <form method="POST" action="{{ route('products.variants.store', $product) }}" class="w-full max-w-6xl rounded-xl bg-white p-4 shadow sm:p-6">
        @csrf
        @include('products.variants._form', ['product' => $product, 'variant' => null])
    </form>
@endsection
