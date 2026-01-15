@props([
    'sections' => []
])
@php
    $isLogged = Auth::check();
    $hrefLogin = (env('APP_AUTH') == 'auth0') ? '/auth0Login' : '/';
@endphp
<div class="app-breadcrumb">
    <div class="d-flex justify-between">
        <div class="ui breadcrumb">
            @foreach($sections as $section)
                @if ($loop->last)
                    <div class="active section">{{$section[1]}}</div>
                @else
                    <a href="{{$section[0]}}" class="section">{{$section[1]}}</a>
                    <span class="divider">/</span>
                @endif
            @endforeach
        </div>
        @if(!$isLogged)
            <div class="ui breadcrumb">
                <a href="{{$hrefLogin}}" class="active section">Login</a>
            </div>
        @endif
    </div>
</div>
