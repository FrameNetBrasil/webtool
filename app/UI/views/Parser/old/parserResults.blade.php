<div class="parser-results-container">
    <div class="ui success message">
        <div class="header">Parse {{ $result->status === 'complete' ? 'Completed' : 'Status: ' . ucfirst($result->status) }}</div>
        <p>Sentence: <strong>{{ $sentence }}</strong></p>
    </div>

    @if($result->status === 'failed')
        <div class="ui warning message">
            <div class="header">Parse Failed</div>
            <p>{{ $result->errorMessage ?? 'Unknown error' }}</p>
        </div>
    @endif

    <div class="ui statistics">
        <div class="statistic">
            <div class="value">{{ $result->nodeCount }}</div>
            <div class="label">Nodes</div>
        </div>
        <div class="statistic">
            <div class="value">{{ $result->edgeCount }}</div>
            <div class="label">Edges</div>
        </div>
        <div class="statistic">
            <div class="value">{{ $result->focusNodeCount }}</div>
            <div class="label">Focus Nodes</div>
        </div>
        <div class="statistic">
            <div class="value">{{ $result->mweNodeCount }}</div>
            <div class="label">MWE Nodes</div>
        </div>
    </div>

    <div class="ui divider"></div>

    <div class="ui stackable grid">
        <div class="eight wide column">
            <h4 class="ui header">Nodes</h4>
            <table class="ui compact celled table">
                <thead>
                    <tr>
                        <th>Position</th>
                        <th>Label</th>
                        <th>POS</th>
                        <th>Type</th>
                        <th>Activation</th>
                        <th>Focus</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($result->nodes as $node)
                    <tr class="{{ $node->isFocus ? 'positive' : '' }}">
                        <td>{{ $node->positionInSentence }}</td>
                        <td>
                            <strong>{{ $node->label }}</strong>
                            @if($node->type === 'MWE')
                                <span class="ui purple mini label">MWE</span>
                            @endif
                        </td>
                        <td>
                            @if(isset($node->pos))
                                <span class="ui mini basic label">{{ $node->pos }}</span>
                            @else
                                <span class="ui mini basic label grey">-</span>
                            @endif
                        </td>
                        <td>
                            <span class="ui mini label" style="background-color: {{ config('parser.visualization.nodeColors.' . $node->type, '#999') }}; color: white;">
                                {{ $node->type }}
                            </span>
                        </td>
                        <td>
                            {{ $node->activation }} / {{ $node->threshold }}
                            @if($node->activation >= $node->threshold)
                                <i class="check circle icon green"></i>
                            @endif
                        </td>
                        <td>
                            @if($node->isFocus)
                                <i class="star icon yellow"></i>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="eight wide column">
            <h4 class="ui header">Edges</h4>
            <table class="ui compact celled table">
                <thead>
                    <tr>
                        <th>Source</th>
                        <th>Target</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($result->edges as $edge)
                    <tr>
                        <td>
                            {{ collect($result->nodes)->firstWhere('idParserNode', $edge->idSourceNode)->label ?? 'N/A' }}
                        </td>
                        <td>
                            {{ collect($result->nodes)->firstWhere('idParserNode', $edge->idTargetNode)->label ?? 'N/A' }}
                        </td>
                        <td>
                            <span class="ui mini label">{{ $edge->linkType }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="ui divider"></div>

    <div class="parser-actions">
        <button
            class="ui button"
            hx-get="/parser/visualization/{{ $result->idParserGraph }}"
            hx-target="#graphVisualization"
            hx-swap="innerHTML"
        >
            <i class="project diagram icon"></i>
            Show Graph Visualization
        </button>

        <a href="/parser/export/{{ $result->idParserGraph }}/json" class="ui button" download>
            <i class="download icon"></i>
            Export JSON
        </a>

        <a href="/parser/export/{{ $result->idParserGraph }}/graphml" class="ui button" download>
            <i class="download icon"></i>
            Export GraphML
        </a>

        <a href="/parser/export/{{ $result->idParserGraph }}/dot" class="ui button" download>
            <i class="download icon"></i>
            Export DOT
        </a>
    </div>

    <div class="mt-6">
        <div id="graphVisualization"></div>
    </div>
</div>

<style>
    .parser-results-container {
        margin-top: 2rem;
    }

    .parser-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .ui.statistics {
        margin: 1.5rem 0;
    }
</style>
