@extends('layouts.admin')

@section('page-title', 'Category Management')
@section('title', 'Categories')

@section('content')
    @livewire('admin.category-list')
@endsection
