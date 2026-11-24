@extends('layouts.admin')
@section('title', __('admin.edit'))
@section('content')
    <h1 class="text-3xl font-bold tracking-tight">{{ __('admin.edit') }}</h1>
    <form method="POST" action="{{ route('admin.categories.update', $category) }}" enctype="multipart/form-data" class="mt-8 max-w-3xl rounded-xl border border-slate-200 bg-white p-5 sm:p-8">
        @csrf
        @method('PUT')
        @include('admin.categories._form')
    </form>
@endsection
