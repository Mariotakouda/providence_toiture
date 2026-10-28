@extends('layouts.app')

@section('title', 'Modifier la variante : '.$product->name)

@section('content')
    <form method="POST" action="{{ route('products.variants.update', [$product, $variant]) }}" class="w-full max-w-6xl rounded-xl bg-white p-4 shadow sm:p-6">
        @csrf
        @method('PUT')
        @include('products.variants._form', ['product' => $product, 'variant' => $variant])
    </form>
@endsection
