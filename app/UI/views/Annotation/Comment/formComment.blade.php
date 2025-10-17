<form id="formComment" class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idAnnotationComment" value="{{$comment->idAnnotationComment}}">
            <input type="hidden" name="idDocument" value="{{$comment->idDocument}}">
            <input type="hidden" name="createdAt" value="{{$comment->createdAt}}">
            <input type="hidden" name="idObject" value="{{$comment->idObject}}">
            <input type="hidden" name="annotationType" value="{{$comment->annotationType}}">
            @if($comment?->email != '')
                <div class="field">
                    <label>[{{$comment->email}}] at [{{$comment->updatedAt}}]</label>
                </div>
            @endif
            <div class="field mr-1">
                <textarea
                    name="comment"
                    rows="2"
                >{!! $comment->comment ?? '' !!}</textarea>
            </div>
        </div>
        <div class="extra content">
            <button
                type="submit"
                class="ui primary button"
                hx-post="/annotation/comment/update"
                hx-target="#formComment"
                hx-swap="outerHTML"
            >
                Save
            </button>
            @if($comment?->idAnnotationComment != '')
                <button
                    class="ui medium button danger"
                    type="reset"
                    hx-delete="/annotation/comment/{{$comment->idAnnotationComment}}"
                    hx-target="#formComment"
                    hx-swap="outerHTML"
                >Delete
                </button>
            @endif
        </div>
    </div>
</form>
