@extends('layouts.app')

@section('title', 'Modifier le devis '.$quote->quote_number)

@section('content')
    <form method="POST" action="{{ route('quotes.update', $quote) }}" class="max-w-6xl rounded-xl bg-white p-6 shadow">
        @csrf
        @method('PUT')
        @include('quotes._form')
    </form>
@endsection
