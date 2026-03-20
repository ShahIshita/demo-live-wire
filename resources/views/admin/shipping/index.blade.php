@extends('layouts.admin')

@section('page-title', 'Shipping Management')
@section('title', 'Shipping')

@section('content')
    @livewire('admin.shipping-list')
@endsection
