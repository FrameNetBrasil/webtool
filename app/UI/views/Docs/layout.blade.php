<x-layout::document>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        @if(isset($document['breadcrumbs'][2]))
            <x-partial::breadcrumb
                :sections="[['/','Home'],['/docs','Documentation'],['',$document['breadcrumbs'][1]['text'] . '/' . $document['breadcrumbs'][2]['text']]]"
            ></x-partial::breadcrumb>
        @elseif(isset($document['breadcrumbs'][1]))
            <x-partial::breadcrumb
                :sections="[['/','Home'],['/docs','Documentation'],['',$document['breadcrumbs'][1]['text']]]"
            ></x-partial::breadcrumb>
        @else
            <x-partial::breadcrumb
                :sections="[['/','Home'],['','Documentation']]"
            ></x-partial::breadcrumb>
        @endif
        <main class="app-main">
            <div class="docs-layout">
                <!-- Sidebar Navigation -->
                <aside class="docs-sidebar">
                    <button class="docs-sidebar-toggle"
                            onclick="this.classList.toggle('collapsed'); this.nextElementSibling.classList.toggle('collapsed')">
                        Documentation Menu
                    </button>
                    <nav class="docs-nav">
                        @foreach($tree as $section)
                            @if($section['type'] === 'folder')
                                <div class="nav-folder">
                                    <div class="folder-title">
                                        {{ $section['text'] }}
                                    </div>
                                    <ul class="folder-items">
                                        @foreach(App\Services\DocsService::buildTree($section['path']) as $doc)
                                            @include('Docs.partials.nav-item', ['item' => $doc, 'currentPath' => $currentPath ?? null])
                                        @endforeach
                                    </ul>
                                </div>
                            @elseif($section['type'] === 'parent')
                                <div class="nav-folder">
                                    <ul class="folder-items">
                                        @include('Docs.partials.nav-item', ['item' => $section, 'currentPath' => $currentPath ?? null])
                                    </ul>
                                </div>
                            @elseif($section['leaf'])
                                <div class="nav-folder">
                                    <ul class="folder-items">
                                        @include('Docs.partials.nav-item', ['item' => $section, 'currentPath' => $currentPath ?? null])
                                    </ul>
                                </div>
                            @endif
                        @endforeach
                    </nav>
                </aside>

                <!-- Main Content Area + TOC Sidebar -->
                <div class="docs-content-wrapper">
                    <div class="docs-content">
                        @yield('content')
                    </div>

                    <!-- Right Sidebar - Table of Contents -->
                    @if(isset($document) && $document['found'] && count($document['toc']) > 0)
                        <aside class="docs-toc-sidebar">
                            <div class="docs-toc">
                                <div class="toc-title">
                                    <i class="list icon"></i>
                                    On This Page
                                </div>
                                <ul class="toc-list">
                                    @foreach($document['toc'] as $item)
                                        <li class="{{ $item['level'] === 3 ? 'toc-nested' : '' }}">
                                            <a href="#{{ $item['slug'] }}">{{ $item['text'] }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </aside>
                    @endif
                </div>
            </div>
        </main>

        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::document>
