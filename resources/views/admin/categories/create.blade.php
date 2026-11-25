@extends('layouts.admin')
@section('title', __('admin.new'))
@section('content')
    <h1 class="text-3xl font-bold tracking-tight">{{ __('admin.new') }}</h1>
    <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data" class="mt-8 max-w-3xl rounded-xl border border-slate-200 bg-white p-5 sm:p-8">
        @csrf
        @include('admin.categories._form')
    </form>
@endsection
