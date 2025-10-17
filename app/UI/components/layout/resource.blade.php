<x-layout.page>
    <x-slot:head>
        {{$head}}
    </x-slot:head>
    <x-slot:main>
        <section id="work" class="flex flex-row align-content-start flex-wrap h-full">
            <div class="col-12 sm:col-12 md:col-6 lg:col-5 xl:col-5 h-full">
                <div class="ui card h-full w-full">
                    <div class="flex-grow-0 content h-4rem bg-gray-100">
                        <div class="flex flex align-items-center justify-content-between">
                            <div><h2 class="ui header">{{$title}}</h2></div>
                            <div>
                                {{$actions}}
                            </div>
                        </div>
                    </div>
                    <div class="flex-grow-0 content h-4rem bg-gray-100">
                        {{$search}}
                    </div>
                    <div class="flex-grow-1 content h-full bg-gray-100">
                        {{$grid}}
                    </div>
                </div>
            </div>
            <div class="col-12 sm:col-12 md:col-6 lg:col-7 xl:col-7 pl-3 h-full">
                <div class="flex flex-column align-content-start h-full">
                    {{$edit}}
                </div>
            </div>
        </section>
    </x-slot:main>
</x-layout.page>
