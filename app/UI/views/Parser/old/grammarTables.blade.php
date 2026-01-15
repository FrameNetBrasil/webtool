@if(empty($filter))
<div class="ui info message">
    <div class="header">No filter provided</div>
    <p>Enter a word in the filter field and click "Show Tables" to view filtered nodes and MWEs.</p>
</div>
@elseif(count($nodes) === 0 && count($mwes) === 0)
<div class="ui warning message">
    <div class="header">No results found</div>
    <p>No nodes or MWEs match the filter "{{ $filter }}".</p>
</div>
@else
<div class="filtered-tables-container">
    @if(count($nodes) > 0)
    <h3 class="ui header">
        Grammar Nodes
        <span class="ui label">{{ count($nodes) }} {{ count($nodes) === 1 ? 'result' : 'results' }}</span>
    </h3>
    <table class="ui celled table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Label</th>
                <th>Type</th>
                <th>Threshold</th>
            </tr>
        </thead>
        <tbody>
            @foreach($nodes as $node)
            <tr>
                <td>{{ $node->idGrammarNode }}</td>
                <td><strong>{{ $node->label }}</strong></td>
                <td>
                    <span class="ui label" style="background-color: {{ config('parser.visualization.nodeColors.' . $node->type, '#999') }}; color: white;">
                        {{ $node->type }}
                    </span>
                </td>
                <td>{{ $node->threshold }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(count($mwes) > 0)
    <h3 class="ui header">
        Multi-Word Expressions
        <span class="ui label">{{ count($mwes) }} {{ count($mwes) === 1 ? 'result' : 'results' }}</span>
    </h3>
    <table class="ui celled table">
        <thead>
            <tr>
                <th>Phrase</th>
                <th>Components</th>
                <th>Semantic Type</th>
                <th>Length</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mwes as $mwe)
            <tr>
                <td><strong>{{ $mwe->phrase }}</strong></td>
                <td>{{ is_string($mwe->components) ? $mwe->components : json_encode($mwe->components) }}</td>
                <td>
                    <span class="ui mini label">{{ $mwe->semanticType }}</span>
                </td>
                <td>{{ $mwe->length }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>
@endif
