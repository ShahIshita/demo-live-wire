@extends('layouts.admin')

@section('page-title', 'User Management')
@section('title', 'Users')

@section('content')
    @livewire('admin.user-list')
@endsection
