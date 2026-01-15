@extends('Docs.layout')

@section('content')
    @if($document['found'])
        {{-- Show document content --}}
        @include("Docs.content", ['document' => $document])
    @else
        {{-- Welcome page when no document is selected --}}
        <div class="docs-welcome">
            <h1>Welcome to Webtool 4.2 Documentation</h1>
            <p>Select a document from the navigation menu on the left to get started, or browse by topic below:</p>

            <div class="docs-sections">
                @foreach($tree as $section)
                    @if($section['type'] === 'folder')
                        <div class="docs-section-card">
                            <div class="section-title">{{ $section['text'] }}</div>
                            <ul class="section-items">
                                @foreach(App\Services\DocsService::buildTree($section['path']) as $doc)
                                    @if($doc['leaf'])
                                        <li>
                                            <a href="/docs/{{ $doc['path'] }}">
                                                {{ $doc['text'] }}
                                            </a>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    @elseif($section['leaf'])
                        <div class="docs-section-card">
                            <div class="section-title">
                                <a href="/docs/{{ $section['path'] }}">
                                    {{ $section['text'] }}
                                </a>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
@endsection
