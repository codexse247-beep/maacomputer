<section class="pagehero">
    <div class="wrap">
        <div class="eyebrow">@include('partials.icon', ['n' => 'star', 'size' => 14, 'fill' => '#C8102E', 'color' => 'none']){{ $eyebrow }}</div>
        <h1>{{ $title }}</h1>
        @if (! empty($lead))<p>{{ $lead }}</p>@endif
    </div>
</section>
