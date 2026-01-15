@php
    use App\Database\Criteria;use App\Repositories\User;use App\Services\AppService; use App\Services\MessageService;
    $idUser = AppService::getCurrentIdUser();
    $messages = MessageService::getMessagesToUser($idUser);
@endphp
<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['','Messages']]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <div class="ui container page">
                <div class="page-header">
                    <div class="page-header-content">
                        <div class="page-title">
                            Messages
                        </div>
                    </div>

                </div>
                <div class="page-content">

                    <div
                        id="mainMessages"
                        hx-trigger="reload-gridMainMessages from:body"
                        hx-target="this"
                        hx-swap="outerHTML"
                        hx-get="/messages"
                        class="p-2"
                    >
                        @foreach($messages as $message)
                            <div class="ui {{$message->class}} message">
                                <i
                                    class="close icon"
                                    onclick="messenger.confirmDelete(`Dismiss message from {{$message->fromName}}.`, '/message/{{$message->idMessage}}')"
                                ></i>
                                <div class="header">
                                    From: {{$message->fromName}} [{{$message->fromEmail}}] at {{$message->createdAt}}
                                </div>
                                {!! $message->text !!}
                            </div>
                        @endforeach
                    </div>

                </div>
            </div>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>

