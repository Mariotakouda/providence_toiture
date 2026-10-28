@extends('layouts.app')

@section('title', 'Nouveau client')

@section('content')
    <form method="POST" action="{{ route('customers.store') }}" class="w-full max-w-6xl rounded-xl bg-white p-4 shadow sm:p-6">
        @csrf
        @include('customers._form', ['customer' => null])
    </form>
@endsection
