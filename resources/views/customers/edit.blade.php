@extends('layouts.app')

@section('title', 'Modifier le client')

@section('content')
    <form method="POST" action="{{ route('customers.update', $customer) }}" class="w-full max-w-6xl rounded-xl bg-white p-4 shadow sm:p-6">
        @csrf
        @method('PUT')
        @include('customers._form', ['customer' => $customer])
    </form>
@endsection
