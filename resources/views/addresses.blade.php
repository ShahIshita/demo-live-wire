@extends('layouts.app')

@section('title', 'My Addresses')

@section('content')
    <div class="addresses-page">
        <div class="addresses-header">
            <h2>My Addresses</h2>
            <a href="{{ route('cart.index') }}" class="btn btn-secondary">Back to Cart</a>
        </div>
        @livewire('user.address-manager')
    </div>
@endsection
