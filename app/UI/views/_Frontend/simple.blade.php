{{--Layout for simplest page--}}
{{--Goal: Show simple content within scrollable area --}}
<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['','Home']]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <div class="ui container page-simple">
                <div class="page-content">
                    <div class="content-container">
                        <div class="content-section">
                            Page content for simplest page
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>

