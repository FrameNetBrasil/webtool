@php
    // Normalize paths for comparison (remove .md and _main.md)
    $normalizedItemPath = str_replace('.md', '', $item['path']);
    $normalizedCurrentPath = isset($currentPath) ? str_replace(['.md', '/_main.md'], '', $currentPath) : null;
@endphp

@if($item['leaf'])
    <li class="nav-item">
        <a href="/docs/{{ str_replace('.md', '', $item['path']) }}"
           class="{{ $normalizedCurrentPath === $normalizedItemPath ? 'active' : '' }}">
            {{ $item['text'] }}
        </a>
    </li>
@elseif($item['type'] === 'parent')
    <li class="nav-item nav-parent">
        <a href="/docs/{{ $item['path'] }}"
           class="nav-parent-link {{ $normalizedCurrentPath === $normalizedItemPath ? 'active' : '' }}">
            {{ $item['text'] }}
        </a>
        @if(!empty($item['children']))
            <ul class="nav-children">
                @foreach($item['children'] as $child)
                    @php
                        $childNormalizedPath = str_replace('.md', '', $child['path']);
                    @endphp
                    <li class="nav-item nav-child">
                        <a href="/docs/{{ $childNormalizedPath }}"
                           class="{{ $normalizedCurrentPath === $childNormalizedPath ? 'active' : '' }}">
                            {{ $child['text'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </li>
@elseif($item['type'] === 'folder')
    <li class="nav-item">
        <div class="folder-title">{{ $item['text'] }}</div>
        <ul class="nav-nested">
            @foreach(App\Services\DocsService::buildTree($item['path']) as $child)
                @include('Docs.partials.nav-item', ['item' => $child, 'currentPath' => $currentPath ?? null])
            @endforeach
        </ul>
    </li>
@endif
