@extends('layouts.admin')

@section('page-title', 'Order Management')
@section('title', 'Orders')

@section('content')
    @livewire('admin.order-list')
@endsection
