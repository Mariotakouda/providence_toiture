@extends('layouts.app')

@section('title', 'Modifier le fournisseur')

@section('content')
    <form method="POST" action="{{ route('suppliers.update', $supplier) }}" class="w-full max-w-6xl rounded-xl bg-white p-4 shadow sm:p-6">
        @csrf
        @method('PUT')
        @include('suppliers._form', ['supplier' => $supplier])
    </form>
@endsection
