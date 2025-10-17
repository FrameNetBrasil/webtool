<?php

namespace App\Services\Annotation;

use App\Data\Annotation\Session\SessionData;
use App\Database\Criteria;

class SessionService
{
    public static function startSession(SessionData $data): object
    {
        // End any existing active sessions for this user
        Criteria::table("annotation_session")
            ->where("idUser", $data->idUser)
            ->where('active', true)
            ->update([
                'endedAt' => $data->timestamp,
                'active' => false
            ]);

        // Create new session
        $idAnnotationSession = Criteria::create("annotation_session", [
            'idUser' => $data->idUser,
            'idDocumentSentence' => $data->idDocumentSentence,
            'startedAt' => $data->timestamp,
//            'last_heartbeat_at' => now(),
            'active' => true,
        ]);
        $session = Criteria::byId("annotation_session","idAnnotationSession", $idAnnotationSession);
        return $session;
    }

    public static function endSession(SessionData $data): object
    {
        $session = Criteria::table("annotation_session")
            ->where("idUser", $data->idUser)
            ->where('active', true)
            ->where("idDocumentSentence", $data->idDocumentSentence)
            ->first();
        Criteria::table("annotation_session")
            ->where("idAnnotationSession", $session->idAnnotationSession)
            ->update([
                'endedAt' => $data->timestamp,
                'active' => false
            ]);
        return $session;
    }

    public static function isActive(int $idDocumentSentence, int $idUser): bool {
        $session = Criteria::table("annotation_session")
            ->where("idUser", $idUser)
            ->where('idDocumentSentence', $idDocumentSentence)
            ->where('active', true)
            ->first();
        return !is_null($session);
    }

}
