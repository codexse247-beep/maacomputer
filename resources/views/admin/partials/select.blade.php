@php
    $id = $id ?? $name;
    $current = old($name, $value);
    $isList = array_is_list($options);
@endphp
<div class="ff">
    <label for="{{ $id }}">{{ $label }} @if (! empty($req))<span class="req" aria-hidden="true">*</span>@endif</label>
    <select id="{{ $id }}" name="{{ $name }}" class="in {{ $errors->has($name) ? 'bad' : '' }}" @if (! empty($req)) required @endif>
        @if (! empty($blank))<option value="">নির্বাচন করুন</option>@endif
        @foreach ($options as $key => $text)
            @php $val = $isList ? $text : $key; @endphp
            <option value="{{ $val }}" @selected((string) $current === (string) $val)>{{ $text }}</option>
        @endforeach
    </select>
    @error($name)<span class="errm">{{ $message }}</span>@enderror
</div>
