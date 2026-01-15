@props([
    'title' => '',
    'url' => '',
    'data' => [],
    'bordered' => false
])

<div class="ui tree-container"
     x-data="treeComponent()"
     x-init="init()"
        {{--     data-title="{{ $title }}"--}}
        {{--     data-search-endpoint="{{ $url }}"--}}
        {{--     data-items="{{ json_encode($data) }}"--}}
>
    <!-- Header -->
    @if($title != '')
        <div class="ui tree-header">{{$title}}</div>
    @endif

    @if (count($data) == 0)
        <div class="search-result-empty" id="emptyState">
            <i class="search icon empty-icon"></i>
            <h3 class="empty-title">No results found.</h3>
            <p class="empty-description">
                Enter your search term above.
            </p>
        </div>
    @endif



    {{--    <!-- Loading State -->--}}
    {{--    <div x-show="loading" class="ui segment">--}}
    {{--        <div class="ui active loader"></div>--}}
    {{--        <p>Loading tree data...</p>--}}
    {{--    </div>--}}

    {{--    <!-- Error State -->--}}
    {{--    <div x-show="error && !loading" class="ui negative message">--}}
    {{--        <i class="close icon" @click="error = null"></i>--}}
    {{--        <div class="header">Error loading data</div>--}}
    {{--        <p x-text="error"></p>--}}
    {{--    </div>--}}

    <!-- Tree Body -->
    @fragment("tree")
        <div class="tree-body">
            <table class="ui {{ $bordered ? '' : 'very' }} basic table tree-table">
                <tbody>
                @foreach($data as $item)
                    @php($idNode = $item['type'] . '_' . $item['id'])
                    <tr class="row-data transition-enabled">
                        @if(!($item['leaf'] ?? false))
                            <!-- Toggle Cell -->
                            <td class="toggle center aligned"
                                @click="toggleNode('{{$idNode}}')"
                            >
                                <i class="toggle-icon transition"
                                   :class="expandedNodes['{{$idNode}}'] ? 'expanded' : 'collapsed'"
                                ></i>
                            </td>
                        @else
                            <td class="leaf">
{{--                                <i class="ui icon"></i>--}}
                            </td>
                        @endif

                        <!-- Content Cells -->
                        @if(isset($item['formatedId']))
                            <td class="content-cell">
                            <span class="ui tree-item-text clickable"
                                  @click="selectItem({{$item['id']}},'{{$item['type']}}',{!! $item['leaf'] ?? 'false' !!})"
                            >
                                {!! $item['formatedId'] !!}
                            </span>
                            </td>
                        @endif
                        @if(isset($item['extra']))
                            <td class="content-cell">
                            <span class="ui tree-item-text clickable"
                                  @click="selectItem({{$item['id']}},'{{$item['type']}}',{!! $item['leaf'] ?? 'false' !!})"
                            >
                                {!! $item['extra'] !!}
                            </span>
                            </td>
                        @endif
                        <td class="content-cell">
                        <span class="ui tree-item-text clickable"
                              @click="selectItem({{$item['id']}},'{{$item['type']}}', {{ Js::from($item) }})"
                        >
                            {!! $item['text'] !!}
                        </span>
                        </td>
                    </tr>
                    <tr
                            id="row_{{$idNode}}"
                            :class="expandedNodes['{{$idNode}}'] ? '' : 'hidden'"
                    >
                        <td
                                class="ident"
                        ></td>
                        <td
                                class="content"
                                colspan="999"
                        >
                            <!-- Tree Content Container -->
                            <div id="tree_{{$idNode}}"
                                 class="tree-content"
                                 :class="{ 'hidden': !expandedNodes['{{$idNode}}'] }"
                                 x-show="expandedNodes['{{$idNode}}']"
                                 x-transition>

                                <!-- Loading indicator -->
                                <div x-show="loadingNodes[{{$item['id']}}]" class="loading">
                                    <div class="ui segment border-none">
                                        <div class="ui active inverted dimmer">
                                            <div class="ui text loader">Loading</div>
                                        </div>
                                        <p></p>
                                    </div>
                                </div>

                                <!-- HTMX will populate this area -->
                                <div hx-post="{{$url}}"
                                     hx-vals='{"type": "{{$item['type']}}", "id" : "{{$item['id']}}"}'
                                     hx-target="#tree_{{$idNode}}"
                                     hx-swap="innerHTML"
                                     hx-trigger="load-{{$idNode}} from:body"
                                     @htmx:before-request="loadingNodes['{{$idNode}}'] = true"
                                     @htmx:after-request="loadingNodes['{{$idNode}}'] = false; processLoadedContent($event.target)">
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endfragment
</div>
