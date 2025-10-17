<div class="flex flex-column sm:col-11 md:col-11 lg:col-4 xl:col-4 mx-auto h-full p-0 wt-card-error">
    <div class="wt-card-{{$type}}">
        <div class="card-header">{{$title}}</div>
        <div class="card-body">
            <div class="m-3">
                {{$message}}
            </div>
            <div class="ml-2">
                <button class="btn btn-primary"><a href="{{$goto}}">{{$gotoLabel}}</a></button>
            </div>
        </div>
    </div>
</div>
