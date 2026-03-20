@extends('layouts.admin')

@section('page-title', 'Review Management')
@section('title', 'Reviews')

@section('content')
    @livewire('admin.review-list')
@endsection
