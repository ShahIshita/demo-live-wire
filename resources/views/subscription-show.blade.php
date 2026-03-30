@extends('layouts.app')

@section('title', 'Subscription Confirmation')

@section('content')
    <div class="order-confirmation card">
        <div class="order-success-banner">
            <h2>Subscription Started Successfully!</h2>
            <p>Your plan is active and automatic renewals are set up securely.</p>
        </div>

        <div class="order-details">
            <h3>Subscription {{ $subscription->id }}</h3>
            <p class="order-status">Status: <strong>{{ ucfirst($subscription->status) }}</strong></p>

            <div class="order-address">
                <h4>Plan Details</h4>
                <p><strong>Product:</strong> {{ $product ? $product->name : 'Subscription Product' }}</p>
                <p><strong>Plan:</strong> {{ $planLabel }}</p>
                <p><strong>Auto renew:</strong> {{ $subscription->cancel_at_period_end ? 'Off' : 'On' }}</p>
                @if ($trialEndsAt)
                    <p><strong>Trial ends:</strong> {{ $trialEndsAt->format('F d, Y') }}</p>
                @endif
                @if ($currentPeriodEndsAt)
                    <p><strong>Current period ends:</strong> {{ $currentPeriodEndsAt->format('F d, Y') }}</p>
                @endif
            </div>
        </div>

        <div class="order-actions">
            <a href="{{ route('profile.index') }}?tab=subscription" class="btn btn-secondary">My Subscription</a>
            <a href="{{ route('products.tab') }}" class="btn btn-primary">Back to Products</a>
        </div>
    </div>
@endsection
