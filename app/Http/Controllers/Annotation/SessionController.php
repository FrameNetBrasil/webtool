<?php

namespace App\Http\Controllers\Annotation;

use App\Data\Annotation\Session\SearchData;
use App\Data\Annotation\Session\SessionData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Services\Annotation\SessionService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware('auth')]
class SessionController extends Controller
{
    #[Get(path: '/annotation/session/script/{folder}')]
    public function jsObjects(string $folder)
    {
        return response()
            ->view("Annotation.Session.Scripts.{$folder}")
            ->header('Content-type', 'text/javascript');
    }

    #[Post(path: '/annotation/session/start')]
    public function sessionStart(SessionData $data)
    {
        //        debug("start",$data);
        $session = SessionService::startSession($data);

        //        return $this->renderNotify("success", "Session started.");
        return response()->json([
            'success' => true,
            'session_token' => '',
            'startedAt' => $data->timestamp->toJSON(),
        ]);
    }

    #[Post(path: '/annotation/session/end')]
    public function sessionEnd(SessionData $data)
    {
        //        debug("end",$data);
        $session = SessionService::endSession($data);

        //        return $this->renderNotify("success", "Session ended.");
        return response()->json([
            'success' => true,
            'session_token' => '',
            'endedAt' => $data->timestamp->toJSON(),
        ]);
    }

    #[Get(path: '/annotation/session')]
    public function report()
    {
        $annotators = Criteria::table('annotation_session as a')
            ->join('user as u', 'a.idUser', '=', 'u.idUser')
            ->select('u.idUser', 'u.email')
            ->distinct()
            ->keyBy('idUser')
            ->all();

        // Get users with their total time
        $users = Criteria::table('annotation_session as a')
            ->join('user as u', 'a.idUser', '=', 'u.idUser')
            ->select('u.idUser', 'u.email')
            ->selectRaw("TIME_FORMAT(SEC_TO_TIME(sum(endedAt - startedAt)), '%i:%s') AS totalTime")
            ->groupBy('u.idUser', 'u.email')
            ->all();

        // Transform to tree structure
        $data = collect($users)->map(function ($user) {
            return [
                'type' => 'user',
                'id' => $user->idUser,
                'text' => $user->email,
                'leaf' => false,
            ];
        })->toArray();

        return view('Annotation.Session.report', [
            'annotators' => $annotators,
            'data' => $data,
        ]);
    }

    #[Post(path: '/annotation/session/search')]
    public function search(SearchData $search)
    {
        debug($search);
        $annotators = [];

        // Check if this is a tree node expansion request
        if (isset($search->type) && $search->type === 'sentence' && isset($search->id)) {
            // Get annotation sets for the selected sentence
            $annotationSets = Criteria::table('view_annotationset')
                ->select('idAnnotationSet', 'status', 'email')
                ->where('idDocumentSentence', '=', $search->id)
                ->all();

            $data = collect($annotationSets)->map(function ($annotationSet) {
                return [
                    'type' => 'annotationset',
                    'id' => $annotationSet->idAnnotationSet,
                    'formatedId' => '#'.$annotationSet->idAnnotationSet,
                    'extra' => $annotationSet->status,
                    'text' => $annotationSet->email ?? 'No user',
                    'leaf' => true,
                ];
            })->toArray();
        } elseif (isset($search->type) && $search->type === 'user' && isset($search->id)) {
            // Get sentences for the selected user
            $sentences = Criteria::table('annotation_session as a')
                ->join('document_sentence as ds', 'a.idDocumentSentence', '=', 'ds.idDocumentSentence')
                ->join('sentence as s', 'ds.idSentence', '=', 's.idSentence')
                ->select('a.idDocumentSentence', 's.text')
                ->selectRaw("TIME_FORMAT(SEC_TO_TIME(sum(endedAt - startedAt)), '%i:%s') AS time")
                ->where('a.idUser', '=', $search->id)
                ->groupBy('a.idDocumentSentence', 's.text')
                ->all();

            $data = collect($sentences)->map(function ($sentence) {
                return [
                    'type' => 'sentence',
                    'id' => $sentence->idDocumentSentence,
                    'formatedId' => '#'.$sentence->idDocumentSentence,
                    'text' => substr($sentence->text, 0, 120),
                    'extra' => $sentence->time,
                    'leaf' => false,
                ];
            })->toArray();
        } else {
            // Initial search or filter by user
            $userQuery = Criteria::table('annotation_session as a')
                ->join('user as u', 'a.idUser', '=', 'u.idUser')
                ->select('u.idUser', 'u.email')
                ->selectRaw("TIME_FORMAT(SEC_TO_TIME(sum(endedAt - startedAt)), '%i:%s') AS totalTime")
                ->groupBy('u.idUser', 'u.email');

            if (isset($search->idUser) && $search->idUser > 0) {
                $userQuery = $userQuery->where('a.idUser', '=', $search->idUser);
            }

            $users = $userQuery->all();

            $data = collect($users)->map(function ($user) {
                return [
                    'type' => 'user',
                    'id' => $user->idUser,
                    'text' => $user->email,
                    'leaf' => false,
                ];
            })->toArray();
        }

        return view('Annotation.Session.tree', [
            'data' => $data,
        ]);
    }
}
