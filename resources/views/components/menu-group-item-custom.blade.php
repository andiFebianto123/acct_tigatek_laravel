@props([
    'link' => '#',
    'activeClass' => 'active',
    'title' => '',
    'logo_url' => '',
    'badge' => null,
    'id' => null
])

@php
    $currentUrl = request()->fullUrl();
    $expectedBase = $link;
    $active = false;

    if (Str::startsWith($currentUrl, $expectedBase)) {
        $active = true;
    }
@endphp

<li class="nav-item" @if($id) id="{{$id}}" @endif>
    <a class="nav-link {{ $active ? 'active':'' }}" href="{{$link}}">
        <i class="nav-icon la la-circle-notch"></i> <span>{{$title}}</span>
        <span class="badge badge-sm rounded-pill bg-danger ms-auto" style="box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3); font-weight: 700; line-height: 1; {{ (!$badge || $badge <= 0) ? 'display: none;' : '' }}">{{$badge ?? ''}}</span>
    </a>
</li>
