@extends('layouts.admin')

@section('page-title', 'Edit Product')
@section('title', 'Edit Product')

@section('content')
    @livewire('admin.product-edit', ['productId' => $productId])
@endsection
