<form class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idSentence" value="{{$sentence->idSentence}}">
            @if($hasAS)
                <div class="ui warning message">
                    <div class="header">
                        Attention
                    </div>
                    This sentence has AnnotationSets. Sentence modification will remove annotations.
                </div>
            @endif
            <div class="field">
                <label for="name">Text</label>
                <div class="ui small input">
                    <textarea id="text" name="text">{{$sentence->text ?? ''}}</textarea>
                </div>
            </div>
        </div>
        <div class="extra content">
            <button
                type="submit"
                class="ui primary button"
                hx-post="/sentence"
            >
                Save
            </button>
        </div>
    </div>
</form>
