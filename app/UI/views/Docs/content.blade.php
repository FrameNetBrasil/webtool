@if($document['found'])
    <article>
        {{-- Document Content --}}
        <div class="doc-markdown-content">
            {!! $document['html'] !!}
        </div>

        {{-- Page Navigation (Previous/Next) --}}
        @include('Docs.partials.page-nav', ['previousPage' => $previousPage ?? null, 'nextPage' => $nextPage ?? null])
    </article>
@else
    <div class="ui message warning">
        <div class="header">Document Not Found</div>
        <p>The requested documentation page could not be found.</p>
        <a href="/docs" class="ui button">Return to Documentation</a>
    </div>
@endif
