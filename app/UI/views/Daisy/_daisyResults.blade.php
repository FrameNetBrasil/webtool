@if(isset($error))
    <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">Error</h3>
                <div class="mt-2 text-sm text-red-700">
                    {{ $error }}
                </div>
            </div>
        </div>
    </div>
@endif

@if(!empty($result))
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium mb-4">Disambiguation Results</h3>

        <div class="mb-4">
            <p class="text-sm text-gray-600 mb-2">
                <strong>Sentence:</strong> {{ $sentence }}
            </p>
        </div>

        @foreach($result as $windowId => $words)
            <div class="mb-6">
                <h4 class="text-md font-medium text-gray-700 mb-3">Window {{ $windowId }}</h4>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Word</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lexical Unit</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Frame</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Energy</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Equivalence</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($words as $word => $winners)
                                @if(!empty($winners))
                                    @foreach($winners as $winner)
                                        <tr>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $word }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ $winner['lu'] ?? '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ $winner['frame'] ?? '-' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                <span class="font-mono">{{ $winner['value'] ?? '-' }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ $winner['equivalence'] ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $word }}</td>
                                        <td colspan="4" class="px-4 py-3 text-sm text-gray-500 italic">
                                            Ambiguous (tie) or no frame found
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

        @if(!empty($sentenceUD))
            <div class="mt-6 pt-6 border-t border-gray-200">
                <h4 class="text-md font-medium text-gray-700 mb-3">Universal Dependencies Parsing</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Word</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Lemma</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">POS</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Relation</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Parent</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-sm">
                            @foreach($sentenceUD as $node)
                                <tr>
                                    <td class="px-4 py-2">{{ $node['id'] ?? '-' }}</td>
                                    <td class="px-4 py-2 font-medium">{{ $node['word'] ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $node['lemma'] ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $node['pos'] ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $node['rel'] ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $node['parent'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@elseif(!isset($error))
    <div class="text-center text-gray-500 py-8">
        Enter a sentence and click "Parse & Show Results" to see disambiguation results.
    </div>
@endif
