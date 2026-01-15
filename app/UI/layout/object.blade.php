<div class="flex flex-column align-content-start h-full">
    <div class="object-header flex">
        <div class="col-12 sm:col-12 md:col-12 lg:col-7 xl:col-6">
            <h2 class="ui header">
                {{$name}}
            </h2>
        </div>
        <div class="col-12 sm:col-12 md:col-12 lg:col-5 xl:col-6 flex gap-1 flex-wrap align-items-center justify-content-end">
            {{$detail}}
        </div>
    </div>
    <div class="object-description pl-2">
        {{$description}}
    </div>
    <div class="flex flex-grow-1 mt-3">
        <div
            id="objectMainArea"
            class="objectMainArea w-full"
        >
            {{$main}}
        </div>
    </div>
</div>
