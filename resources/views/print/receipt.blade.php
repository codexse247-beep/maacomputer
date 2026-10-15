@extends('print.layout')

@section('title', 'রসিদ '.$payment->receipt_no)

@section('content')
<div style="display:flex;justify-content:center;padding:0 16px 32px">
    @include('partials.receipt-card')
</div>
@endsection
