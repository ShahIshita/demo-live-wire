@extends('layouts.app')

@section('content')
    <div class="welcome-banner">
        <h2>Subscription products</h2>
        <p>Pick up to four featured products. Choose a subscription plan in the modal — daily, trial + monthly, or monthly.</p>
    </div>

    @livewire('user.subscription-product-grid')
@endsection
