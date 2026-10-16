@extends('layouts.admin')

@section('title', 'অনলাইন পেমেন্ট')
@section('crumbs')<span>অ্যাডমিন</span>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>অনলাইন পেমেন্ট</strong>@endsection

@section('content')
<div class="pagehead">
    <div>
        <h1 class="h1">অনলাইন পেমেন্ট</h1>
        <p class="lead">স্টুডেন্টরা পোর্টাল থেকে বিকাশ/নগদ/রকেটের ট্রানজেকশন আইডি জমা দেয়। নিজের অ্যাপে টাকা এসেছে কিনা মিলিয়ে তারপর গ্রহণ করুন।</p>
    </div>
</div>

@if (empty(\App\Models\OnlinePayment::available()))
    <div class="flash" style="background:#FFF3DB;color:#8A5300" role="status">পোর্টালে অনলাইন পেমেন্ট এখনো বন্ধ — @can('admin')<a href="{{ route('admin.settings.index') }}#pay">সেটিংসে</a>@else অ্যাডমিনকে বলুন সেটিংসে@endcan বিকাশ/নগদ/রকেট নম্বর দিলে চালু হবে।</div>
@endif

<nav style="display:flex;flex-wrap:wrap;gap:4px;padding:6px;background:#fff;border:1px solid #DFE5DB;border-radius:14px;width:max-content;max-width:100%">
    @foreach (\App\Models\OnlinePayment::STATUSES as $k => [$label])
        <a class="tab {{ $status === $k ? 'on' : '' }}" href="{{ route('admin.online-payments.index', ['status' => $k]) }}">{{ $label }} · {{ bn($counts[$k] ?? 0) }}</a>
    @endforeach
</nav>

<div class="card" style="overflow:hidden">
    @forelse ($items as $o)
        <div style="display:flex;flex-wrap:wrap;gap:12px 24px;padding:18px 24px;border-bottom:1px solid #DFE5DB;align-items:center">
            <div style="flex:1 1 280px;min-width:0;display:flex;flex-direction:column;gap:4px;line-height:1.5">
                <div><a href="{{ route('admin.students.show', $o->student) }}#fees" style="font-weight:700;color:#0E3A05;text-decoration:none">{{ $o->student->name_bn }}</a> <span class="mute">· {{ $o->student->student_code }} · {{ $o->student->batch->code }}</span></div>
                <div style="font-size:15px"><b style="font-size:20px">{{ taka($o->amount) }}</b> · {{ $o->methodLabel() }} · পাঠানোর নম্বর <b>{{ bn($o->sender) }}</b></div>
                <div style="font-size:15px">TrxID: <b style="font-family:monospace;font-size:16px;letter-spacing:.04em">{{ $o->trx_id }}</b></div>
                <div class="mute" style="font-size:13px">জমা: {{ bn_date($o->created_at) }}, {{ bn($o->created_at->format('h:i A')) }}
                    @if ($o->reviewer) · {{ $o->statusLabel() }} করেছেন {{ $o->reviewer->name }}@endif
                    @if ($o->payment) · রসিদ <a href="{{ route('admin.payments.receipt', $o->payment) }}">{{ $o->payment->receipt_no }}</a>@endif
                    @if ($o->note) · কারণ: {{ $o->note }}@endif
                </div>
            </div>
            @if ($o->status === 'pending')
                <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
                    <form method="POST" action="{{ route('admin.online-payments.approve', $o) }}" onsubmit="return confirm('{{ $o->methodLabel() }} অ্যাপে TrxID {{ $o->trx_id }} মিলিয়েছেন? {{ taka($o->amount) }} গ্রহণ করবেন?')">@csrf<button class="btn p">গ্রহণ করুন</button></form>
                    <form method="POST" action="{{ route('admin.online-payments.reject', $o) }}" style="display:flex;gap:8px;align-items:center">
                        @csrf
                        <label class="sr" for="why{{ $o->id }}">বাতিলের কারণ</label>
                        <input class="in" id="why{{ $o->id }}" name="note" placeholder="বাতিলের কারণ" required style="width:200px;height:44px">
                        <button class="btn d">বাতিল</button>
                    </form>
                </div>
            @else
                <span class="badge {{ $o->statusTone() }}">{{ $o->statusLabel() }}</span>
            @endif
        </div>
    @empty
        <div class="mute" style="padding:24px">এখানে কিছু নেই।</div>
    @endforelse
    <div class="pager" style="display:block">{{ $items->links('partials.pager') }}</div>
</div>
@endsection
