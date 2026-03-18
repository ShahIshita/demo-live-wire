@extends('layouts.admin')

@section('title', 'Edit Product')

@section('content')
    @livewire('admin.product-edit', ['productId' => $productId])
@endsection
