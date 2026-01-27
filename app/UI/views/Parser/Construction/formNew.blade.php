<form class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <div class="header">Create New Construction</div>
        </div>
        <div class="content">
            <div class="field">
                <label for="idGrammarGraph">Grammar Graph</label>
                <select id="idGrammarGraph" name="idGrammarGraph" class="ui dropdown" required>
                    <option value="">Select Grammar...</option>
                    @foreach($grammars as $grammar)
                        <option value="{{ $grammar->idGrammarGraph }}">{{ $grammar->name }} ({{ $grammar->language }})</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="name">Name</label>
                <div class="ui small input">
                    <input
                        type="text"
                        id="name"
                        name="name"
                        placeholder="e.g., HEAD_NOUN"
                        required
                    >
                </div>
                <small class="text-gray-600">Construction name (3-100 characters, uppercase recommended)</small>
            </div>

            <div class="field">
                <label for="constructionType">Construction Type</label>
                <select id="constructionType" name="constructionType" class="ui dropdown" required>
                    <option value="phrasal">Phrasal (50-99)</option>
                    <option value="mwe">MWE (100-199)</option>
                    <option value="clausal">Clausal (20-49)</option>
                    <option value="sentential">Sentential (1-19)</option>
                </select>
            </div>

            <div class="field">
                <label for="pattern">Pattern (BNF)</label>
                <textarea
                    id="pattern"
                    name="pattern"
                    rows="3"
                    placeholder="e.g., {NOUN} | [{DET}] {NOUN}"
                    required
                    style="font-family: 'Courier New', monospace;"
                ></textarea>
                <small class="text-gray-600">BNF pattern using {POS}, literals, [], (), |, *, +</small>
            </div>

            <div class="field">
                <label for="priority">Priority</label>
                <input
                    type="number"
                    id="priority"
                    name="priority"
                    value="50"
                    min="1"
                    max="199"
                    required
                >
                <small class="text-gray-600">Priority within type range</small>
            </div>

            <div class="field">
                <label for="description">Description</label>
                <textarea
                    id="description"
                    name="description"
                    rows="2"
                    placeholder="Describe this construction..."
                ></textarea>
            </div>
        </div>
        <div class="extra content">
            <x-submit
                label="Create Construction"
                color="primary"
                hx-post="/parser/construction"
            ></x-submit>
        </div>
    </div>
</form>
