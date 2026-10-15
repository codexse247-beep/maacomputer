@extends('print.layout')

@section('title', 'আইডি কার্ড')

@push('head')
<style>@media print{@page{margin:8mm}}</style>
@endpush

@section('content')
<div class="idgrid" style="display:flex;flex-wrap:wrap;gap:20px;justify-content:center;padding:0 16px 32px">
    @foreach ($students as $s)
        @include('partials.id-card', ['s' => $s])
    @endforeach
</div>
@endsection
