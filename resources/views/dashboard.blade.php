@extends('layouts.app')

@section('content')
    <div class="welcome-banner">
        <h2>Hello, {{ Auth::user()->name }}!</h2>
        <p>Browse the full catalog here. Use <strong>Products</strong> in the navbar for subscription plans (4 featured items).</p>
    </div>

    @livewire('user.product-grid')
@endsection
