<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['','Home']]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <div class="ui container page">
                <div class="page-center">
                    <div>
                        <div class="pb-4">
                            <img src="/images/fnbr_logo_alpha.png" width="240"/>
                        </div>
                        <div>
                            <a class="ui button login">Sign In</a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <x-partial::footer></x-partial::footer>
        <script>
            $(function () {
                $(".ui.button.login").click(function (e) {
                    e.preventDefault();
                    window.location = "/auth0Login";
                });
            });
        </script>
    </div>
</x-layout::index>
