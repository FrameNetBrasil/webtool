<x-layout.page>
    <header>
        <div class="flex flex align-items-center justify-content-between">
            <div>
                {{$head}}
            </div>
            <div>
                <div id="y" class="ui top attached demo menu">
                    <a class="item">
                        <i class="sidebar icon"></i>
                        Toggle flyout
                    </a>
                </div>
            </div>
        </div>
    </header>
    <section id="work" class="h-full">
        <div id="x" class="ui pushable">
            <div class="ui right vertical flyout">
                <div class="ui header">
                    <i class="question icon"></i>
                    <div class="content">
                        Archive Old Messages
                    </div>
                </div>
                <div class="content" style="min-height: 240.578px;">
                    <p>Your inbox is getting full, would you like us to enable automatic archiving of old messages?</p>
                </div>
                <div class="actions">
                    <div class="ui red cancel button">
                        <i class="remove icon"></i>
                        No
                    </div>
                    <div class="ui green ok button">
                        <i class="checkmark icon"></i>
                        Yes
                    </div>
                </div>
            </div>
            <div class="pusher closing">
                <div class="flex flex-row align-content-start flex-wrap h-full">
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
                </div>
            </div>
        </div>
    </section>
    <script>
        $(function() {
            $(".ui.flyout")
                .flyout({
                    context: $("#x"),
                    dimPage:false
                })
                .flyout("attach events", "#y")
            ;
        });
    </script>
</x-layout.page>
