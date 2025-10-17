<?php

namespace App\Services;

use App\Data\Utils\ImportFullTextData;
use App\Database\Criteria;
use Carbon\Carbon;

class MessageService
{

    public static function sendMessage(object $message): void
    {
        $data = [
            'idUserFrom' => $message->idUserFrom,
            'idUserTo' => $message->idUserTo,
            'text' => $message->text,
            'active' => true,
            'createdAt' => Carbon::now(),
            'class' => $message->class
        ];
        Criteria::create("message", $data);
    }

    public static function getMessagesToUser(int $idUser): array
    {
        $messages = Criteria::table("message as m")
            ->join("user as userFrom", "m.idUserFrom", "=", "userFrom.idUser")
            ->select("m.idMessage","userFrom.name as fromName", "userFrom.email as fromEmail", "m.text", "m.class")
            ->selectRaw("date_format(m.createdAt,'%d/%m/%Y %H:%i:%s') as createdAt")
            ->where("m.idUserTo", $idUser)
            ->whereNull("m.dismissedAt")
            ->all();
        return $messages;
    }

}
