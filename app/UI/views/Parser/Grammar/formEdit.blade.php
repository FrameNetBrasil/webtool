<form class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idGrammarGraph" value="{{ $grammar->idGrammarGraph }}">

            <div class="field">
                <label for="name">Name</label>
                <div class="ui small input">
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ $grammar->name }}"
                        required
                    >
                </div>
            </div>

            <div class="field">
                <label for="language">Language Code</label>
                <div class="ui small input">
                    <input
                        type="text"
                        id="language"
                        name="language"
                        value="{{ $grammar->language }}"
                        maxlength="10"
                        required
                    >
                </div>
            </div>

            <div class="field">
                <label for="description">Description</label>
                <textarea
                    id="description"
                    name="description"
                    rows="4"
                >{{ $grammar->description }}</textarea>
            </div>

            <div class="ui message info">
                <div class="header">Construction Status</div>
                <p>This grammar has <strong>{{ $grammar->constructionCount }}</strong> constructions defined.</p>
                @if($grammar->constructionCount > 0)
                    <a href="/parser/v4/construction?grammar={{ $grammar->idGrammarGraph }}" class="ui button tiny blue">
                        View Constructions
                    </a>
                @endif
            </div>
        </div>
        <div class="extra content">
            <x-submit
                label="Save"
                color="primary"
                hx-post="/parser/v4/grammar/{{ $grammar->idGrammarGraph }}"
            ></x-submit>
        </div>
    </div>
</form>
