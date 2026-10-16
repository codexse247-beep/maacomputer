{{-- English text for the English website. Needs: $model (Translatable) and $fields = [field => [label, 'text'|'textarea']] --}}
@php $open = collect(array_keys($fields))->contains(fn ($f) => filled($model->enValue($f)) || $errors->has('en.'.$f)); @endphp
<details class="panel" @if ($open) open @endif style="{{ $style ?? '' }}">
    <summary>English (ইংরেজি ওয়েবসাইটের জন্য — ঐচ্ছিক)</summary>
    <div style="padding:0 24px 20px;display:flex;flex-direction:column;gap:14px">
        <p class="mute" style="margin:0;font-size:14px">খালি রাখলে ইংরেজি ওয়েবসাইটেও বাংলা লেখাটাই দেখাবে।</p>
        @foreach ($fields as $field => [$label, $type])
            <div class="ff">
                <label for="en_{{ $field }}_{{ $model->getKey() ?? 'new' }}">{{ $label }}</label>
                @if ($type === 'textarea')
                    <textarea class="ta" id="en_{{ $field }}_{{ $model->getKey() ?? 'new' }}" name="en[{{ $field }}]" lang="en" rows="3">{{ old('en.'.$field, $model->enValue($field)) }}</textarea>
                @else
                    <input class="in" id="en_{{ $field }}_{{ $model->getKey() ?? 'new' }}" name="en[{{ $field }}]" lang="en" value="{{ old('en.'.$field, $model->enValue($field)) }}">
                @endif
                @error('en.'.$field)<span class="errm">{{ $message }}</span>@enderror
            </div>
        @endforeach
    </div>
</details>
