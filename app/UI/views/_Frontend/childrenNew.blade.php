{{--Layout of form to create new child object --}}
{{--Goal: Template for create operation - used with children.blade.php --}}
<form id="formNewChild" class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            {{-- id of parent object --}}
            <input type="hidden" name="id" value="{{$id}}">
            {{-- fields --}}
            <div class="ui fields">
                <div class="field">
                </div>
                <div class="field">
                </div>
            </div>
        </div>
        <div class="extra content">
            {{-- save button --}}
            <button
                type="submit"
                class="ui primary button"
                hx-post="/usr/for/object/children/new"
            >
                Save
            </button>
        </div>
    </div>
</form>
