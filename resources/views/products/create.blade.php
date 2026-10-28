@extends('layouts.app')

@section('title', 'Nouveau produit')

@section('content')
    <form method="POST" action="{{ route('products.store') }}" class="w-full max-w-6xl rounded-xl bg-white p-4 shadow sm:p-6">
        @csrf
        @include('products._form', ['product' => null])
    </form>
@endsection
