<x-layout::index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['','New Video']]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container">
                <div class="page-content">
                    <form id="formNewVideo" class="ui form" hx-encoding='multipart/form-data'>
                        <div class="ui card form-card w-full p-1">
                            <div class="content">
                                <div class="header">
                                    Create new Video
                                </div>
                                <div class="description">

                                </div>
                            </div>
                            <div class="content">
                                <div class="field">
                                    <label for="title">Title</label>
                                    <div class="ui small input">
                                        <input type="text" id="title" name="title" value="">
                                    </div>
                                </div>

                                <div class="field">
                                    <x-combobox.language
                                        id="idLanguage"
                                        label="Language"
                                        value=""
                                    >
                                    </x-combobox.language>
                                </div>

                                <div class="field">
                                    <x-file-field
                                        id="file"
                                        label="File"
                                        value=""
                                    >
                                    </x-file-field>
                                </div>

                                <div class="field">
                                    <progress id='progress' value='0' max='100'></progress>
                                </div>
                            </div>
                            <div class="extra content">
                                <button
                                    type="submit"
                                    class="ui primary button"
                                    hx-post="/video/new"
                                >
                                    Save
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</x-layout::index>
<script>
    htmx.on("#formNewVideo", "htmx:xhr:progress", function(evt) {
        htmx.find("#progress").setAttribute("value", evt.detail.loaded / evt.detail.total * 100);
    });
</script>
