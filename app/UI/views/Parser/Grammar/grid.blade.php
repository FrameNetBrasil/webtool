<div class="h-full"
    id="gridGrammar"
    hx-trigger="reload-gridGrammar from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/parser/v4/grammar/grid"
>
    <div class="relative h-full overflow-auto">
        <table class="ui striped small compact table absolute top-0 left-0 bottom-0 right-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Language</th>
                    <th>Constructions</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
            @fragment('search')
                @forelse($grammars as $grammar)
                    <tr
                        hx-get="/parser/v4/grammar/{{ $grammar->idGrammarGraph }}/edit"
                        hx-target="#editArea"
                        hx-swap="innerHTML"
                        class="cursor-pointer"
                    >
                        <td>
                            <span class="text-blue-900 font-bold">{{ $grammar->name }}</span>
                        </td>
                        <td>
                            <span class="ui label tiny">{{ strtoupper($grammar->language) }}</span>
                        </td>
                        <td>
                            <span class="ui label tiny blue">{{ $grammar->constructionCount }} constructions</span>
                        </td>
                        <td>
                            <span class="text-gray-600">{{ Str::limit($grammar->description ?? '', 60) }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-gray-500">
                            No grammar graphs found. Click "New Grammar Graph" to create one.
                        </td>
                    </tr>
                @endforelse
            @endfragment
            </tbody>
        </table>
    </div>
</div>
