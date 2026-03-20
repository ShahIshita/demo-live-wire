@extends('layouts.admin')

@section('page-title', 'Edit Category')
@section('title', 'Edit Category')

@section('content')
    @livewire('admin.category-form', ['categoryId' => $categoryId])
@endsection
