@extends('layouts.admin')

@section('title', 'বকেয়া ও রিমাইন্ডার')
@section('crumbs')<span>অ্যাডমিন</span>@include('partials.icon', ['n' => 'chev', 'size' => 16])<strong>বকেয়া ও রিমাইন্ডার</strong>@endsection

@section('content')
<div class="pagehead">
    <div>
        <h1 class="h1">বকেয়া ও রিমাইন্ডার</h1>
        <p class="lead">মোট বকেয়া {{ taka($total) }} · কিস্তির তারিখ অনুযায়ী</p>
    </div>
    @if ($mailOn && $overdue->isNotEmpty())
        <form method="POST" action="{{ route('admin.dues.remind-all') }}" onsubmit="return confirm('তারিখ পেরোনো {{ bn($overdue->count()) }} জনকে রিমাইন্ডার ইমেইল পাঠাবেন?')">@csrf<button class="btn p">তারিখ পেরোনো সবাইকে ইমেইল</button></form>
    @endif
</div>

@unless ($mailOn)
    <div class="flash" style="background:#FFF3DB;color:#8A5300" role="status">ইমেইল এখনো চালু হয়নি — @can('admin')<a href="{{ route('admin.settings.index') }}#email">সেটিংস › ইমেইল</a>-এ ইমেইল অ্যাকাউন্ট দিন।@else অ্যাডমিনকে সেটিংসে ইমেইল চালু করতে বলুন।@endcan ততক্ষণ নিচের নম্বরে ফোন করুন।</div>
@endunless

@foreach ([['overdue', 'কিস্তির তারিখ পেরিয়ে গেছে', '#B00E28'], ['soon', 'আগামী ৭ দিনে কিস্তি', '#8A5300'], ['later', 'অন্যান্য বকেয়া', '#0E3A05']] as [$key, $title, $color])
    <section class="card" style="overflow:hidden">
        <div class="sh"><h2 style="color:{{ $color }}">{{ $title }} · {{ bn($$key->count()) }} জন</h2></div>
        <div class="scroll">
            <table class="tbl" style="min-width:860px">
                <thead><tr><th class="th">স্টুডেন্ট</th><th class="th">ব্যাচ</th><th class="th">যোগাযোগ</th><th class="th">বকেয়া</th><th class="th">পরবর্তী কিস্তি</th><th class="th">শেষ রিমাইন্ডার</th><th class="th"></th></tr></thead>
                <tbody>
                @forelse ($$key as $s)
                    <tr>
                        <td class="td"><a href="{{ route('admin.students.show', $s) }}#fees" style="font-weight:600;color:#0E3A05;text-decoration:none">{{ $s->name_bn }}</a><br><span class="mute" style="font-size:13px">{{ $s->student_code }}</span></td>
                        <td class="td">{{ $s->batch->code }}</td>
                        <td class="td"><a href="tel:{{ $s->phone }}">{{ bn($s->phone) }}</a><br><span class="mute" style="font-size:13px">{{ $s->email ?: 'ইমেইল নেই' }}</span></td>
                        <td class="td"><b style="color:#B00E28">{{ taka($s->totalDue()) }}</b></td>
                        <td class="td">{{ $s->next_installment_date ? bn_date($s->next_installment_date) : '—' }}@if ($s->next_installment_amount)<br><span class="mute" style="font-size:13px">{{ taka($s->next_installment_amount) }}</span>@endif</td>
                        <td class="td">{{ isset($reminded[$s->id]) ? bn_date(\Illuminate\Support\Carbon::parse($reminded[$s->id])) : '—' }}</td>
                        <td class="td" style="text-align:right">@if ($mailOn && $s->email)<form method="POST" action="{{ route('admin.dues.remind', $s) }}">@csrf<button class="btn sm">ইমেইল পাঠান</button></form>@endif</td>
                    </tr>
                @empty
                    <tr><td class="td mute" colspan="7">কেউ নেই।</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endforeach
@endsection
