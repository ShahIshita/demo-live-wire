@extends('layouts.app')

@section('title', 'Order Confirmation')

@section('content')
    @livewire('user.order-detail', ['orderId' => $orderId])
@endsection
