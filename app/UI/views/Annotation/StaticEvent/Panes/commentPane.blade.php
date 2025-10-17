<form>
    <x-hidden-field id="idDocumentSentence" value="{{$idDocumentSentence}}"></x-hidden-field>
    <x-multiline-field
        id="comment"
        label="Comment"
        value="{{$comment}}"
        ></x-multiline-field>
    <div>
        <x-button
            id="btnSubmitComment"
            label="Submit Comment"
            color="secondary"
            hx-post="/annotation/staticEvent/comment"
        ></x-button>
    </div>
</form>
