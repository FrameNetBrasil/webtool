<?php

namespace App\Services\Microframe;

use App\Database\Criteria;
use App\Repositories\Frame;
use App\Services\AnnotationStaticEventService;
use App\Services\AppService;
use App\Services\RelationService;

class ReportService
{
    public static function report(int|string $idFrame, string $lang = ''): array
    {
        $report = [];
        if ($lang != '') {
            $language = Criteria::byId('language', 'language', $lang);
            $idLanguage = $language->idLanguage;
            AppService::setCurrentLanguage($idLanguage);
        } else {
            $idLanguage = AppService::getCurrentIdLanguage();
        }
        if (is_numeric($idFrame)) {
            $frame = Criteria::byFilterLanguage("view_microframe", ['idFrame', '=', $idFrame])->first();
        } else {
            $frame = Criteria::table('view_frame')
                ->where('name', $idFrame)
                ->where('idLanguage', $idLanguage)
                ->first();
        }
        $report['frame'] = $frame;
        $report['fe'] = self::getFEData($frame, $idLanguage);
//        $report['fecoreset'] = self::getFECoreSet($frame);
        $report['frame']->description = self::decorate($frame->description, $report['fe']['styles']);
//        $report['relations'] = self::getRelations($frame);
//        $report['classification'] = Frame::getClassificationLabels($idFrame);
//        $report['lus'] = self::getLUs($frame, $idLanguage);
//        $report['vus'] = self::getVUs($frame, $idLanguage);

        return $report;
    }

    public static function getFEData($frame, int $idLanguage): array
    {
        $fes = Criteria::table('view_frameelement')
            ->where('idLanguage', '=', $idLanguage)
            ->where('idFrame', '=', $frame->idFrame)
            ->all();
        $domain = [];
        $range = [];
        $feByEntry = [];
        foreach ($fes as $fe) {
            $feByEntry[$fe->entry] = $fe;
        }
        // $config = config('webtool.relations');
//        $relations = RelationService::listRelationsFEInternal($frame->idFrame);
//        $relationsByIdFE = [];
//        foreach ($relations as $relation) {
//            $relationsByIdFE[$relation->feIdFrameElement][] = [
//                'relatedFEName' => $relation->relatedFEName,
//                'relatedFEIdColor' => $relation->relatedFEIdColor,
//                'name' => $relation->name,
//                'color' => $relation->color,
//            ];
//        }
//        $semanticTypes = RelationService::listFEST($frame->idFrame);
        $styles = [];
        foreach ($fes as $fe) {
            $styles[strtolower($fe->name)] = "color_{$fe->idColor}";
        }
        foreach ($fes as $fe) {
            $fe->relations = $relationsByIdFE[$fe->idFrameElement] ?? [];
            $fe->lower = strtolower($fe->name);
            $fe->description = self::decorate($fe->description, $styles);
            if ($fe->coreType == 'cty_domain') {
                $domain[] = $fe;
            } elseif ($fe->coreType == 'cty_range') {
                $range[] = $fe;
            }
        }

        return [
            'styles' => $styles,
            'domain' => $domain,
            'range' => $range,
//            'core_unexpressed' => $coreun,
//            'peripheral' => $coreper,
//            'extra_thematic' => $coreext,
//            'noncore' => $noncore,
//            'semanticTypes' => $semanticTypes,
        ];
    }

    public static function getFECoreSet($frame): string
    {
        $feCoreSet = Frame::listFECoreSet($frame->idFrame);
        $s = [];
        foreach ($feCoreSet as $i => $cs) {
            $s[$i] = '{'.implode(',', $cs).'}';
        }
        $result = implode(', ', $s);

        return $result;
    }

    public static function getRelations($frame): array
    {
        $relations = [];
        $result = RelationService::listRelationsMicroframe($frame->idFrame);
        foreach ($result as $row) {
            $relationName = $row->relationType . '|' . $row->name;
            $relations[$row->direction][$relationName][$row->idFrameRelated] = [
                'idEntityRelation' => $row->idEntityRelation,
                'idFrame' => $row->idFrameRelated,
                'name' => $row->related,
                'color' => $row->color
            ];
        }
        //ksort($relations);
        return $relations;
    }

    public static function getLUs($frame, $idLanguage)
    {
        $lus = Criteria::table('view_lu as lu')
            ->join('pos', 'lu.idPOS', '=', 'pos.idPOS')
            ->where('idFrame', $frame->idFrame)
            ->where('idLanguage', $idLanguage)
            ->orderBy('name')
            ->treeResult('POS')->all();
        ksort($lus);

        return $lus;
    }

    public static function getVUs($frame, $idLanguage)
    {
        $vus = AnnotationStaticEventService::getDocumentsForVU($frame->idFrame, $idLanguage);

        return $vus;
    }

    public static function getClassification($frame)
    {
        $classification = [];
        $result = Frame::getClassification($frame->idFrame);
        foreach ($result as $framal => $values) {
            foreach ($values as $row) {
                $classification[$framal][] = $row->name;
            }
        }
        $classification['id'][] = '#'.$frame->idFrame;

        return $classification;
    }

    public static function decorate($description, $styles)
    {
        $sentence = utf8_decode($description);
        $decorated = preg_replace_callback(
            "/\#([^\s\.\,\;\?\!\']*)/i",
            function ($matches) use ($styles) {
                $m = substr($matches[0], 1);
                $l = strtolower($m);
                foreach ($styles as $fe => $s) {
                    if (utf8_encode($l) == $fe) {
                        return "<span class='{$s}'>{$m}</span>";
                    }
                }

                return $m;
            },
            $sentence
        );
        $partial = utf8_encode($decorated);
        $final = preg_replace_callback(
            "/\[([^\]]*)\]/i",
            function ($matches) use ($styles) {
                $m = substr($matches[0], 1, -1);
                $l = strtolower($m);
                foreach ($styles as $fe => $s) {
                    if (str_contains(utf8_encode($l), '|target')) {
                        $m = substr($m, 0, strpos($m, '|'));

                        return "<span class='color_target'>{$m}</span>";
                    } else {
                        if (str_contains(utf8_encode($l), '|'.$fe)) {
                            $m = substr($m, 0, strpos($m, '|'));

                            return "<span class='{$s}'>{$m}</span>";
                        }
                    }
                }

                return $m;
            },
            $partial
        );

        return $final;
    }
}
