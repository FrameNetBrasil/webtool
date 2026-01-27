<form>
    <x-hidden-field id="idDocumentImage" value="{{$idDocumentImage}}"></x-hidden-field>
    <div class="mr-4">
        <x-multiline-field
            id="comment"
            label="Comments"
            value="{{$comment}}"
            class="h-4rem"
        ></x-multiline-field>
        <div>
        </div>
        <x-button
            id="btnSubmitComment"
            label="Submit Comment"
            color="secondary"
            hx-post="/annotation/dynamicMode/comment"
        ></x-button>
    </div>
</form>
