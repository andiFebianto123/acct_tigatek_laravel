@props([
    'link' => '#',
    'activeClass' => 'active',
    'title' => '',
    'logo_url' => '',
    'badge' => null
])

@php
    $currentUrl = request()->fullUrl();
    $expectedBase = $link;
    $active = false;

    if (Str::startsWith($currentUrl, $expectedBase)) {
        $active = true;
    }
@endphp
<li class="nav-group nav-root {{ $active ? 'active show':'' }}">
    <a class="nav-link nav-group-toggle" href="{{$link}}">
        <span class="dash-micon" style="position: relative;">
            <img src="{{$logo_url}}" alt="{{$title}}" width="18px" height="18px">
            @if($badge !== null && $badge > 0)
                <span class="group-badge-dot"></span>
            @endif
        </span>
        <span>{{$title}}</span>
    </a>
    <ul class="nav-dropdown-items nav-group-items">
        {!! $slot !!}
    </ul>
</li>
