@extends('layouts.admin')

@section('title', 'খরচ')
@section('crumbs')<span>অ্যাডমিন</span>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>খরচ</strong>@endsection

@php
    $e = $expense ?? new \App\Models\Expense(['spent_on' => now(), 'method' => \App\Models\Payment::METHODS[0]]);
    $profit = $income - $spent;
@endphp

@section('content')
<div class="pagehead">
    <div>
        <h1 class="h1">খরচ</h1>
        <p class="lead">অফিসের খরচ লিখে রাখুন — মাসের আয়, খরচ ও লাভ একসাথে দেখুন</p>
    </div>
    <form method="GET" class="row">
        <label class="sr" for="month">মাস</label>
        <input class="in" type="month" id="month" name="month" value="{{ $month->format('Y-m') }}" style="width:auto">
        <button class="btn">দেখুন</button>
        @can('area', 'reports')<a class="btn" href="{{ route('admin.reports.show', 'finance') }}">আয়-ব্যয় রিপোর্ট</a>@endcan
    </form>
</div>

<div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(min(220px,100%),1fr))">
    <div class="card statc"><span class="k">ফি আদায় (আয়)</span><span class="v" style="color:#176008">{{ taka($income) }}</span><span class="s">{{ bn($month->format('m')) }}/{{ bn($month->format('Y')) }}</span></div>
    <div class="card statc"><span class="k">খরচ</span><span class="v" style="color:#B00E28">{{ taka($spent) }}</span><span class="s">{{ bn($expenses->count()) }}টি খরচ</span></div>
    <div class="card statc"><span class="k">{{ $profit >= 0 ? 'লাভ' : 'ক্ষতি' }}</span><span class="v" style="color:{{ $profit >= 0 ? '#176008' : '#B00E28' }}">{{ taka(abs($profit)) }}</span><span class="s">আয় − খরচ</span></div>
</div>

<div style="display:flex;flex-wrap:wrap;gap:24px;align-items:flex-start">
    <form method="POST" action="{{ $e->exists ? route('admin.expenses.update', $e) : route('admin.expenses.store') }}" class="card" style="flex:1 1 320px;min-width:0;padding:24px;display:flex;flex-direction:column;gap:16px">
        @csrf
        @if ($e->exists) @method('PUT') @endif
        <h2 style="margin:0;font-size:20px;color:#0E3A05">{{ $e->exists ? 'খরচ সম্পাদনা' : 'নতুন খরচ' }}</h2>
        @include('admin.partials.field', ['name' => 'spent_on', 'label' => 'তারিখ', 'req' => true, 'type' => 'date', 'value' => $e->spent_on, 'id' => 'x_date'])
        @include('admin.partials.select', ['name' => 'category', 'label' => 'খাত', 'req' => true, 'value' => $e->category, 'options' => \App\Models\Expense::CATEGORIES, 'blank' => ! $e->exists, 'id' => 'x_cat'])
        @include('admin.partials.field', ['name' => 'amount', 'label' => 'পরিমাণ (৳)', 'req' => true, 'value' => $e->amount, 'mode' => 'numeric', 'id' => 'x_amount'])
        @include('admin.partials.select', ['name' => 'method', 'label' => 'মাধ্যম', 'value' => $e->method, 'options' => \App\Models\Payment::METHODS, 'id' => 'x_method'])
        @include('admin.partials.field', ['name' => 'note', 'label' => 'বিবরণ (ঐচ্ছিক)', 'value' => $e->note, 'ph' => 'যেমন: অক্টোবরের দোকান ভাড়া', 'id' => 'x_note'])
        <div class="row">
            @if ($e->exists)<a class="btn" href="{{ route('admin.expenses.index', ['month' => $month->format('Y-m')]) }}">বাতিল</a>@endif
            <button class="btn p">{{ $e->exists ? 'সংরক্ষণ' : 'খরচ যোগ করুন' }}</button>
        </div>
    </form>

    <div style="flex:2 1 460px;min-width:0;display:flex;flex-direction:column;gap:24px">
        @if ($byCategory->isNotEmpty())
            <div class="card" style="padding:20px 24px;display:flex;flex-direction:column;gap:12px">
                <b style="color:#0E3A05">খাত অনুযায়ী</b>
                @foreach ($byCategory as $cat => $sum)
                    <div style="display:grid;grid-template-columns:minmax(110px,190px) 1fr auto;gap:12px;align-items:center;font-size:15px">
                        <span>{{ $cat }}</span><span class="bar" style="background:#FCEBEC"><span style="width:{{ round($sum / max(1, $byCategory->max()) * 100) }}%;background:#B00E28"></span></span><b>{{ taka($sum) }}</b>
                    </div>
                @endforeach
            </div>
        @endif
        <div class="card" style="overflow:hidden">
            <div class="scroll">
                <table class="tbl" style="min-width:620px">
                    <thead><tr><th class="th">তারিখ</th><th class="th">খাত</th><th class="th">বিবরণ</th><th class="th">পরিমাণ</th><th class="th"></th></tr></thead>
                    <tbody>
                    @forelse ($expenses as $x)
                        <tr>
                            <td class="td">{{ bn_date($x->spent_on, false) }}</td>
                            <td class="td"><b>{{ $x->category }}</b><br><span class="mute" style="font-size:13px">{{ $x->method }}{{ $x->user ? ' · '.$x->user->name : '' }}</span></td>
                            <td class="td">{{ $x->note }}</td>
                            <td class="td"><b>{{ taka($x->amount) }}</b></td>
                            <td class="td" style="text-align:right;white-space:nowrap">
                                <a class="el" href="{{ route('admin.expenses.index', ['month' => $month->format('Y-m'), 'edit' => $x->id]) }}">সম্পাদনা</a>
                                <form method="POST" action="{{ route('admin.expenses.destroy', $x) }}" style="display:inline" onsubmit="return confirm('খরচটি মুছবেন?')">@csrf @method('DELETE')<button class="el" style="color:#B00E28">মুছুন</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="td mute" colspan="5">এই মাসে কোনো খরচ লেখা হয়নি।</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
