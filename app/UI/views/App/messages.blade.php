@php
    use App\Database\Criteria;use App\Repositories\User;use App\Services\AppService; use App\Services\MessageService;
    $idUser = AppService::getCurrentIdUser();
    $messages = MessageService::getMessagesToUser($idUser);
@endphp
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
