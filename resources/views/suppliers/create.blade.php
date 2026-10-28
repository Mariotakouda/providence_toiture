@extends('layouts.app')

@section('title', 'Nouveau fournisseur')

@section('content')
    <form method="POST" action="{{ route('suppliers.store') }}" class="w-full max-w-6xl rounded-xl bg-white p-4 shadow sm:p-6">
        @csrf
        @include('suppliers._form', ['supplier' => null])
    </form>
@endsection
