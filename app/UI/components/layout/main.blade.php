<x-layout.index>
    @include('components.layout.head')
    <div id="content">
        <div class="contentContainer ui pushable">
            <div class="menuLeft ui left vertical menu sidebar">
                @include("components.layout.menu")
            </div>
            <div class="pusher closing pusher-full">
                <main role="main" class="main" style="width:calc(100% - 260px)">
                    <header>
                        {{$head}}
                    </header>
                    <section id="work" class="h-full w-full">
                        {{$main}}
                    </section>
                    <wt-go-top id="btnTop" label="Top" offset="64"></wt-go-top>
                </main>
            </div>
        </div>
    </div>
    <footer>
        @include("components.layout.footer")
    </footer>
    <script>
        $(".menuLeft")
            .sidebar({
                context: $(".contentContainer"),
                dimPage: false,
                transition: "push",
                mobileTransition: "overlay",
                closable: false,
            })
            .sidebar("attach events", ".menuIcon")
            .sidebar("show")
        ;
    </script>
</x-layout.index>
