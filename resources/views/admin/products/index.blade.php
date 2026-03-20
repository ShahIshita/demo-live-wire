@extends('layouts.admin')

@section('page-title', 'Product Management')
@section('title', 'Products')

@section('content')
    @livewire('admin.product-list')
@endsection
