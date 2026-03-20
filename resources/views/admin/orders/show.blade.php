@extends('layouts.admin')

@section('page-title', 'Order #' . $orderId)
@section('title', 'Order Detail')

@section('content')
    @livewire('admin.order-detail', ['orderId' => $orderId])
@endsection
