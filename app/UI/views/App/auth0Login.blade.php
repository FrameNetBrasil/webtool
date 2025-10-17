<x-layout::index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['','Home']]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container h-full">
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
        <x-layout::footer></x-layout::footer>
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
