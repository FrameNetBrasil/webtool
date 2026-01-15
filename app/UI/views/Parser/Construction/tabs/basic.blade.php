<form class="ui form">
    <div class="field">
        <label>Name</label>
        <input type="text" name="name" value="{{ $construction->name }}" readonly>
    </div>
    <div class="field">
        <label>Construction Type</label>
        <input type="text" value="{{ ucfirst($construction->constructionType) }}" readonly>
    </div>
    <div class="field">
        <label>Description</label>
        <textarea name="description" rows="4" placeholder="Add description...">{{ $construction->description }}</textarea>
    </div>
    <div class="field">
        <label>Created</label>
        <input type="text" value="{{ $construction->created_at }}" readonly>
    </div>
</form>
