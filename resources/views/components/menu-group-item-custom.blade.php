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

<li class="nav-item">
    <a class="nav-link {{ $active ? 'active':'' }}" href="{{$link}}">
        <i class="nav-icon la la-circle-notch"></i> <span>{{$title}}</span>
        @if($badge !== null && $badge > 0)
            <span class="badge badge-sm rounded-pill bg-danger ms-auto" style="box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3); font-weight: 700; line-height: 1;">{{$badge}}</span>
        @endif
    </a>
</li>
