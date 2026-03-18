@extends('layouts.app')

@section('content')
    <div class="welcome-banner">
        <h2>Hello, {{ Auth::user()->name }}!</h2>
        <p>Browse our products and add to cart or favourites.</p>
    </div>

    @livewire('user.product-grid')
@endsection
