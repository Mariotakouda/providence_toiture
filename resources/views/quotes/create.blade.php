@extends('layouts.app')

@section('title', 'Nouveau devis')

@section('content')
    <form method="POST" action="{{ route('quotes.store') }}" class="max-w-6xl rounded-xl bg-white p-6 shadow">
        @csrf
        @include('quotes._form')
    </form>
@endsection
