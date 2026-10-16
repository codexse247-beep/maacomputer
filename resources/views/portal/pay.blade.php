@extends('layouts.portal')

@section('title', 'অনলাইনে ফি দিন')

@php $due = $student->totalDue(); @endphp

@section('content')
<div class="pagehead">
    <div>
        <h1 class="h1">অনলাইনে ফি দিন</h1>
        <p class="lead">বকেয়া <b style="color:{{ $due ? '#B00E28' : '#176008' }}">{{ taka($due) }}</b>@if ($student->next_installment_date && $due) · পরবর্তী কিস্তি {{ $student->next_installment_amount ? taka($student->next_installment_amount).', ' : '' }}{{ bn_date($student->next_installment_date) }}@endif</p>
    </div>
</div>

@if (! $due)
    <div class="card" style="padding:24px">আপনার কোনো বকেয়া নেই। ধন্যবাদ!</div>
@elseif (empty($methods))
    <div class="card" style="padding:24px">অনলাইন পেমেন্ট এখনো চালু হয়নি। অফিসে এসে ফি দিন।</div>
@else
<div style="display:flex;flex-wrap:wrap;gap:24px;align-items:flex-start">
    <div class="pcard" style="flex:1 1 300px;min-width:0;display:flex;flex-direction:column;gap:14px">
        <h2 style="margin:0;font-size:20px;color:#0E3A05">১. টাকা পাঠান</h2>
        @foreach ($methods as $key => [$label, , $setting])
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:14px 16px;border-radius:12px;background:#F5F7F3">
                <span><b>{{ $label }}</b><br><span style="font-size:22px;font-weight:700;letter-spacing:.03em">{{ bn(setting($setting)) }}</span></span>
                <button type="button" class="btn sm" onclick="navigator.clipboard.writeText('{{ setting($setting) }}');this.textContent='কপি হয়েছে'">কপি</button>
            </div>
        @endforeach
        <p style="margin:0;font-size:15px;line-height:1.7;color:#3F4D44;white-space:pre-line">{{ setting('pay_note') }}</p>
    </div>

    <form method="POST" action="{{ route('portal.pay.store') }}" class="pcard" style="flex:1 1 320px;min-width:0;display:flex;flex-direction:column;gap:16px">
        @csrf
        <h2 style="margin:0;font-size:20px;color:#0E3A05">২. পেমেন্টের তথ্য দিন</h2>
        <div class="ff">
            <label for="pm">কোন মাধ্যমে পাঠিয়েছেন</label>
            <select class="in" id="pm" name="method" required>@foreach ($methods as $key => [$label])<option value="{{ $key }}" @selected(old('method') === $key)>{{ $label }}</option>@endforeach</select>
        </div>
        <div class="ff">
            <label for="pa">পরিমাণ (৳)</label>
            <input class="in {{ $errors->has('amount') ? 'bad' : '' }}" id="pa" name="amount" inputmode="numeric" required value="{{ old('amount', $student->next_installment_amount && $student->next_installment_amount <= $due ? $student->next_installment_amount : $due) }}">
            @error('amount')<span class="errm">{{ $message }}</span>@enderror
        </div>
        <div class="ff">
            <label for="ps">যে নম্বর থেকে পাঠিয়েছেন</label>
            <input class="in {{ $errors->has('sender') ? 'bad' : '' }}" id="ps" name="sender" inputmode="tel" required placeholder="01XXXXXXXXX" value="{{ old('sender', $student->phone) }}">
            @error('sender')<span class="errm">{{ $message }}</span>@enderror
        </div>
        <div class="ff">
            <label for="pt">ট্রানজেকশন আইডি (TrxID)</label>
            <input class="in {{ $errors->has('trx_id') ? 'bad' : '' }}" id="pt" name="trx_id" required autocomplete="off" placeholder="যেমন: 9JK7XC2PQA" value="{{ old('trx_id') }}" style="text-transform:uppercase;font-family:monospace;letter-spacing:.05em">
            <small>টাকা পাঠানোর পর মেসেজে যে আইডি আসে</small>
            @error('trx_id')<span class="errm">{{ $message }}</span>@enderror
        </div>
        <button class="btn p">জমা দিন</button>
    </form>
</div>
@endif

@if ($history->isNotEmpty())
<section class="card" style="overflow:hidden">
    <div class="sh"><h2>আমার অনলাইন পেমেন্ট</h2></div>
    <div class="scroll">
        <table class="tbl" style="min-width:620px">
            <thead><tr><th class="th">তারিখ</th><th class="th">মাধ্যম</th><th class="th">পরিমাণ</th><th class="th">TrxID</th><th class="th">অবস্থা</th></tr></thead>
            <tbody>
            @foreach ($history as $o)
                <tr>
                    <td class="td">{{ bn_date($o->created_at) }}</td><td class="td">{{ $o->methodLabel() }}</td><td class="td"><b>{{ taka($o->amount) }}</b></td><td class="td" style="font-family:monospace">{{ $o->trx_id }}</td>
                    <td class="td"><span class="badge {{ $o->statusTone() }}">{{ $o->statusLabel() }}</span>
                        @if ($o->payment) <a href="{{ route('portal.receipt', $o->payment) }}">রসিদ</a>@endif
                        @if ($o->note)<br><span class="mute" style="font-size:13px">{{ $o->note }}</span>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</section>
@endif
@endsection
