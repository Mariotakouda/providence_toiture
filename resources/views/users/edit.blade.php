@extends('layouts.app')

@section('title', 'Modifier l’utilisateur')

@section('content')
    <form method="POST" action="{{ route('users.update', $user) }}" class="w-full max-w-6xl rounded-xl bg-white p-4 shadow sm:p-6">
        @csrf
        @method('PUT')
        @include('users._form', ['user' => $user, 'currentRole' => $currentRole])
    </form>
@endsection
