<div class="h-full"
    id="gridConstruction"
    hx-trigger="reload-gridConstruction from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/parser/v4/construction/grid"
>
    <div class="relative h-full overflow-auto">
        <table class="ui striped small compact table absolute top-0 left-0 bottom-0 right-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>CE Labels</th>
                    <th>Pattern</th>
                </tr>
            </thead>
            <tbody>
            @fragment('search')
                @forelse($constructions as $construction)
                    <tr
                        hx-get="/parser/v4/construction/{{ $construction->idConstruction }}/edit"
                        hx-target="#editArea"
                        hx-swap="innerHTML"
                        class="cursor-pointer"
                    >
                        <td>
                            <span class="text-blue-900 font-bold">{{ $construction->name }}</span>
                        </td>
                        <td>
                            @if($construction->constructionType === 'mwe')
                                <span class="ui label tiny blue">MWE</span>
                            @elseif($construction->constructionType === 'phrasal')
                                <span class="ui label tiny green">Phrasal</span>
                            @elseif($construction->constructionType === 'clausal')
                                <span class="ui label tiny orange">Clausal</span>
                            @elseif($construction->constructionType === 'sentential')
                                <span class="ui label tiny red">Sentential</span>
                            @endif
                        </td>
                        <td>
                            <span class="ui label tiny">{{ $construction->priority }}</span>
                        </td>
                        <td>
                            <button
                                class="ui mini button {{ $construction->enabled ? 'green' : 'grey' }}"
                                hx-post="/parser/v4/construction/{{ $construction->idConstruction }}/toggle"
                                hx-swap="none"
                                onclick="event.stopPropagation()"
                            >
                                {{ $construction->enabled ? 'Enabled' : 'Disabled' }}
                            </button>
                        </td>
                        <td>
                            @if($construction->phrasalCE)
                                <span class="ui label tiny teal">P: {{ $construction->phrasalCE }}</span>
                            @endif
                            @if($construction->clausalCE)
                                <span class="ui label tiny violet">C: {{ $construction->clausalCE }}</span>
                            @endif
                            @if($construction->sententialCE)
                                <span class="ui label tiny pink">S: {{ $construction->sententialCE }}</span>
                            @endif
                        </td>
                        <td>
                            <code class="text-xs">{{ Str::limit($construction->pattern, 50) }}</code>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-gray-500">
                            No constructions found. Click "New Construction" to create one.
                        </td>
                    </tr>
                @endforelse
            @endfragment
            </tbody>
        </table>
    </div>
</div>
