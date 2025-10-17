<div class="ui card form-card w-full p-1">
    <div class="content">
        <div class="header">
            <div class="d-flex items-center justify-between">
                <div>
                    <h3 class="ui header">Object #{{$object->idObject}} - {{$object->nameLayerType}}</h3>
                </div>
                <div>
                    <button
                        class="ui tiny icon button"
                        hx-post="/annotation/image/cloneObject"
                        hx-vals='js:{"idDocument":{{$object->idDocument}},"idObject":{{$object->idObject}},"annotationType":"{{$annotationType}}"}'
                    >
                        Clone object
                    </button>
                    <button
                        class="ui tiny icon button danger"
                        @click.prevent="messenger.confirmDelete('Removing object #{{$object->idObject}}.', '/annotation/{{$annotationType}}/{{$object->idDocument}}/{{$object->idObject}}')"
                    >
                        Delete Object
                    </button>
                    <button
                        id="btnClose"
                        class="ui tiny icon button"
                        title="Close Object"
                        @click="window.location.assign('/annotation/{{$annotationType}}/{{$object->idDocument}}')"
                    >
                        <i class="close small icon"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="content pt-0">
        <input type="hidden" id="idObject" value="{{$object->idObject}}" />
        <div
            class="objectPane ui pointing secondary menu tabs mt-0"
        >
            <a
                class="item active"
                data-tab="edit-object"
                :class="isPlaying && 'disabled'"
            >Annotate object</a>
            <a
                class="item"
                data-tab="create-bbox"
                :class="isPlaying && 'disabled'"
            >BBox</a>
            <a
                class="item"
                data-tab="comment"
                :class="isPlaying && 'disabled'"
            ><i class="comment dots outline icon"></i>Comment</a>
        </div>
        <div
            class="gridBody"
            x-init="$('.menu .item').tab()"
        >
            <div
                class="ui tab h-full w-full active"
                data-tab="edit-object"
            >
                @include("Annotation.Image.Forms.formAnnotation")
            </div>
            <div
                class="ui tab h-full w-full"
                data-tab="create-bbox"
            >
                @include("Annotation.Image.Forms.formBBox")
            </div>
            <div
                class="ui tab h-full w-full"
                data-tab="comment"
            >
                @include("Annotation.Comment.formComment")
            </div>
        </div>
    </div>


</div>
<script type="text/javascript">
    $(function() {
        document.dispatchEvent(new CustomEvent("object-loaded", { detail: { object: {!! Js::from($object) !!} } }));
    });
</script>
