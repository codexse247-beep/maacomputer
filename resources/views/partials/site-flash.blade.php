@if (session('ok'))
    <div class="flash ok" role="status">{{ session('ok') }}</div>
@endif
@if ($errors->any())
    <div class="flash err" role="alert">{{ __('ফরমে কিছু সমস্যা আছে।') }}
        <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif
