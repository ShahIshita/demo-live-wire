@extends('layouts.admin')

@section('page-title', 'Payment Management')
@section('title', 'Payments')

@section('content')
    @livewire('admin.payment-list')
@endsection
