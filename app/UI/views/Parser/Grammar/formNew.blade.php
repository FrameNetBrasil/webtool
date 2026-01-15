<div class="header">
{{--    <i class="edit icon"></i>--}}
{{--    Create New Grammar Graph--}}
</div>
<div class="scrolling content">
    <form class="ui form">
        <div class="field">
            <label for="name">Name</label>
            <div class="ui small input">
                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="e.g., Portuguese V4 Grammar"
                    required
                >
            </div>
            <small class="text-gray-600">A descriptive name for this grammar (3-100 characters)</small>
        </div>

        <div class="field">
            <label for="language">Language Code</label>
            <div class="ui small input">
                <input
                    type="text"
                    id="language"
                    name="language"
                    placeholder="e.g., pt, en, es"
                    maxlength="10"
                    required
                >
            </div>
            <small class="text-gray-600">ISO language code (2-10 characters)</small>
        </div>

        <div class="field">
            <label for="description">Description</label>
            <textarea
                id="description"
                name="description"
                rows="4"
                placeholder="Describe the purpose and scope of this grammar..."
            ></textarea>
            <small class="text-gray-600">Optional description (max 1000 characters)</small>
        </div>
    </form>
</div>
<div class="actions">
    <button class="ui cancel button">Cancel</button>
    <button class="ui primary button" type="submit" hx-post="/parser/v4/grammar">Create Grammar Graph</button>
</div>
