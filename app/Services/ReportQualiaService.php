<?php

namespace App\Services;

use App\Database\Criteria;
use App\Repositories\Frame;
use App\Repositories\FrameElement;
use App\Repositories\Qualia;
use App\Repositories\SemanticType;

class ReportQualiaService
{

    public static function report(int|string $idQualia, string $lang = ''): array
    {
        $report = [];
        if ($lang != '') {
            $language = Criteria::byId("language", "language", $lang);
            $idLanguage = $language->idLanguage;
            AppService::setCurrentLanguage($idLanguage);
        } else {
            $idLanguage = AppService::getCurrentIdLanguage();
        }
        if (is_numeric($idQualia)) {
            $qualia = Qualia::byId($idQualia);
        } else {
            $qualia = Criteria::table("view_qualia")
                ->where("name", $idQualia)
                ->where("idLanguage", $idLanguage)
                ->first();
        }
        $report['qualia'] = $qualia;
        $report['fe1'] = $qualia->idFrameElement1 ? FrameElement::byId($qualia->idFrameElement1) : null;
        $report['fe2'] = $qualia->idFrameElement2 ? FrameElement::byId($qualia->idFrameElement2) : null;
        //$report['relations'] = self::getRelations($semanticType);
        return $report;
    }

}
