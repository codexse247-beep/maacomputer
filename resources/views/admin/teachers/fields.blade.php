@php $sfx = $t->exists ? '_'.$t->id : '_new'; @endphp
<div class="fgrid">
    @include('admin.partials.field', ['name' => 'name', 'label' => 'নাম', 'req' => true, 'value' => $t->name, 'id' => 'tn'.$sfx])
    <div class="ff">
        <label for="td{{ $sfx }}">বিভাগ</label>
        <select id="td{{ $sfx }}" name="department_id" class="in">
            <option value="">—</option>
            @foreach (\App\Models\Department::orderBy('sort')->get() as $d)<option value="{{ $d->id }}" @selected((int) old('department_id', $t->department_id) === $d->id)>{{ $d->name }}</option>@endforeach
        </select>
    </div>
    @include('admin.partials.field', ['name' => 'subject', 'label' => 'বিষয় / পদবি', 'value' => $t->subject, 'ph' => 'যেমন: ফটোশপ ও ইলাস্ট্রেটর', 'id' => 'ts'.$sfx])
    @include('admin.partials.field', ['name' => 'phone', 'label' => 'মোবাইল', 'type' => 'tel', 'value' => $t->phone, 'id' => 'tp'.$sfx])
    @include('admin.partials.field', ['name' => 'sort', 'label' => 'ক্রম', 'value' => $t->sort ?? 0, 'mode' => 'numeric', 'id' => 'to'.$sfx, 'hint' => 'ছোট সংখ্যা আগে দেখাবে'])
    @include('admin.partials.upload', ['name' => 'photo', 'label' => 'ছবি', 'btn' => $t->photo ? 'নতুন ছবি বাছুন' : 'ছবি বাছুন', 'accept' => 'image/*', 'id' => 'tph'.$sfx])
</div>
<div style="padding:0 24px 16px" class="ff">
    <label for="tb{{ $sfx }}">অভিজ্ঞতা ও যোগ্যতা (ওয়েবসাইটে দেখাবে)</label>
    <textarea id="tb{{ $sfx }}" name="bio" class="ta">{{ old('bio', $t->bio) }}</textarea>
</div>
<div style="padding:0 24px 16px" class="row">
    <label class="sw"><input type="checkbox" name="active" value="1" @checked(old('active', $t->active))>সক্রিয়</label>
    <label class="sw"><input type="checkbox" name="show_on_site" value="1" @checked(old('show_on_site', $t->show_on_site))>ওয়েবসাইটে দেখান</label>
</div>
