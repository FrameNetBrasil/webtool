<x-layout.index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        {{$head}}
        <main class="app-main">
            <div class="page-content h-full">
                <div class="ui container h-full">
                    @if(isset($title))
                        <div class="page-header">
                            <div class="page-header-content">
                                <div class="page-header-main">
                                    {{$title}}
                                </div>
                            </div>
                        </div>
                    @endif
                    {{$main}}
                </div>
            </div>
        </main>
        <x-layout::footer></x-layout::footer>
    </div>
</x-layout.index>
