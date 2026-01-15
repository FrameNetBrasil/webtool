<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/admin','Admin'],['/layers','Layer/GenericLabel'],['','New Layer Group']]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <div class="ui container page">
                <div class="page-content">
                    <form class="ui form">
                        <div class="ui card form-card w-full p-1">
                            <div class="content">
                                <div class="header">
                                    Create new Layer Group
                                </div>
                            </div>
                            <div class="content">
                                <div class="field">
                                    <label for="name">Name</label>
                                    <div class="ui small input">
                                        <input type="text" id="name" name="name" value="">
                                    </div>
                                </div>
                                <div class="field">
                                    <label for="type">Type</label>
                                    <div class="ui small input">
                                        <input type="text" id="type" name="type" value="">
                                    </div>
                                </div>
                            </div>
                            <div class="extra content">
                                <button
                                    type="submit"
                                    class="ui primary button"
                                    hx-post="/layers/new"
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
