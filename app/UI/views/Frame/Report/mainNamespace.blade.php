<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/report','Report'],['','Namespace']]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <div class="page-content slide">
                <div
                    class="ui container page"
                    x-init="$('.item').tab()"
                >
                    <div class="ui tab h-full active" data-tab="browse">
                        <div class="ui container page-browse">
                            <div class="page-header">
                                <div class="page-header-content">
                                    <div class="page-title">
                                        Frame Namespace
                                    </div>
                                </div>
                            </div>

                            <div class="page-content">
                                <div class="search-container">
                                    <!-- Search Form -->
                                    <div class="search-input-section">
                                        <div class="search-input-group">
                                            <form
                                                hx-post="/namespace/search"
                                                hx-target=".search-result-section"
                                                hx-trigger="input changed delay:500ms from:#frame-search, submit"
                                                class="ui form"
                                            >
                                                @csrf
                                                <div class="field">
                                                    <div class="ui left icon input w-full">
                                                        <i class="search icon"></i>
                                                        <input
                                                            id="frame-search"
                                                            type="search"
                                                            name="frame"
                                                            placeholder="Search Frame"
                                                            autocomplete="off"
                                                        >
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Results Container -->
                                    <div class="search-result-section">
                                        @fragment('search')
                                            @if(isset($data['namespaces']) && count($data['namespaces']) > 0)
                                                <div class="search-result-data">
                                                    <div class="tabs-component namespace-tabs-context"
                                                         style="display: flex; gap: 1rem; height: 100%;">
                                                        <!-- Left: Vertical Menu -->
                                                        <div
                                                            class="ui vertical menu"
                                                            x-init="$('.namespace-tabs-context .menu .item').tab()"
                                                            style="flex-shrink: 0; width: 200px; overflow-y: auto;"
                                                        >
                                                            @foreach($data['namespaces'] as $idNamespace => $namespace)
                                                                @if($namespace->count > 0)
                                                                    <a
                                                                        class="item {{$loop->first ? 'active' : ''}}"
                                                                        data-tab="ns-{{$namespace->idNamespace}}"
                                                                        style="display: flex; align-items: center; justify-content: space-between;"
                                                                    >
                                                                        <div class="color_{{$namespace->idColor}}"
                                                                             style="font-weight: 600;">
                                                                            {{$namespace->name}}
                                                                        </div>
                                                                        <div class="ui mini label">
                                                                            {{$namespace->count}}
                                                                        </div>
                                                                    </a>
                                                                @endif
                                                            @endforeach
                                                        </div>

                                                        <!-- Right: Tab Content -->
                                                        <div
                                                            style="flex: 1; min-width: 0; display: flex; flex-direction: column;">
                                                            @foreach($data['namespaces'] as $idNamespace => $namespace)
                                                                @if($namespace->count > 0)
                                                                    <div
                                                                        class="ui tab segment {{$loop->first === 0 ? 'active' : ''}}"
                                                                        data-tab="ns-{{$namespace->idNamespace}}"
                                                                        style="flex: 1; overflow-y: auto; margin: 0; border: 1px solid rgba(34, 36, 38, 0.15);"
                                                                    >
                                                                        <div class="card-grid dense-4col">
                                                                            @foreach($data['frames'][$namespace->idNamespace] as $frame)
                                                                                @include('Frame.partials.frame_card_ns', [
                                                                                    'frame' => $frame,
                                                                                    'color' => $namespace->idColor
                                                                                ])
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="search-result-empty">
                                                    <i class="search icon empty-icon"></i>
                                                    <h3 class="empty-title">No results found.</h3>
                                                    <p class="empty-description">
                                                        Enter your search term above to find frames.
                                                    </p>
                                                </div>
                                            @endif
                                        @endfragment
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="ui tab report h-full" data-tab="report">
                    </div>
                </div>
            </div>

        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
