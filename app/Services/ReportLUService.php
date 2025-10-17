<?php

namespace App\Services;

use App\Repositories\AnnotationSet;
use App\Repositories\Base;
use App\Repositories\EntityRelation;
use App\Repositories\Entry;
use App\Repositories\Frame;
use App\Repositories\FrameElement;
use App\Repositories\LU;
use App\Repositories\ViewAnnotationSet;
use App\Repositories\ViewFrame;
use App\Repositories\ViewLU;
use Illuminate\Support\Facades\DB;


class ReportLUService
{

    public static function FERealizations($idLU, $idLanguageFE = null)
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        $idLanguageFE = $idLanguageFE ?: $idLanguage;
        $cmd = <<<HERE
select a.idAnnotationSet, IFNULL(afe.startChar,1000) startChar, afe.endChar, afe.layerTypeEntry layerEntry, it.name itName, it.entry itEntry, afe.idEntity feIdEntity,
afe.name feName, afe.idFrameElement feId, afe.coreType feTypeEntry, afe.idColor, gf.name gfName, pt.name ptName
from view_annotationset a
left join view_annotation_text_fe afe on (a.idAnnotationSet = afe.idAnnotationSet)
left join view_instantiationtype it on (afe.idInstantiationType = it.idInstantiationType)
left join (
    select agf.idAnnotationSet, agf.startChar, agf.name
    from view_annotation_text_gl agf
    where ((agf.name is null) or (agf.name <> 'Target')) and (agf.idLanguage = {$idLanguage}) and (agf.layerTypeEntry = 'lty_gf')
) gf on (a.idAnnotationSet = gf.idAnnotationSet) and (afe.startChar = gf.startChar)
left join (
    select apt.idAnnotationSet, apt.startChar, apt.name
    from view_annotation_text_gl apt
    where  ((apt.name is null) or (apt.name <> 'Target')) and (apt.idLanguage = {$idLanguage}) and (apt.layerTypeEntry = 'lty_pt')
) pt on (a.idAnnotationSet = pt.idAnnotationSet) and (afe.startChar = pt.startChar)
where (a.idLU = {$idLU})
and ((afe.idLanguage = {$idLanguageFE}) or (afe.idLanguage is null))
and ((it.idLanguage = {$idLanguageFE}) or (it.idLanguage is null))
order by afe.coreType,afe.name, a.idAnnotationSet, 2, afe.endChar, afe.layerOrder, afe.layerTypeEntry
HERE;

        $rows = DB::select($cmd);
        $fes = [];
        $realizations = [];
        $realizationAS = [];
        $idVP = $idVPFE = null;
        $patterns = [];
        $pattern = [];
        $feEntries = [];
        $vp = [];
        $vpfe = [];
        foreach ($rows as $row) {
            if (!isset($fes[$row->feIdEntity])) {
                $fes[$row->feIdEntity] = [
                    //'entry' => $row->feEntry,
                    'name' => $row->feName,
                    'type' => $row->feTypeEntry,
                    //'iconCls' => config("webtool.fe.icon.grid")[$row->feTypeEntry],
                    'idColor' => $row->idColor,
                    'as' => []
                ];
            }
            $fes[$row->feIdEntity]['as'][$row->idAnnotationSet] = $row->idAnnotationSet;
            $fe = $row->feName;
            $feIdEntity = $row->feIdEntity;
            $feEntries[$feIdEntity] = $fe;
            $gf = $row->gfName ?: '?';
            $pt = $row->ptName ?: '?';
            $it = $row->itEntry ?: '?';
            $startChar = $row->startChar ?: '0';
            if ($it == 'int_normal') {
                $idRealization = 'id' . md5($feIdEntity . $gf . $pt);
                $realizations[$feIdEntity][$gf][$pt] = [$idRealization];
            } else {
                $idRealization = 'id' . md5($feIdEntity . $row->itName . '--');
                $realizations[$feIdEntity][$row->itName]['--'] = [$idRealization];
            }
            $realizationAS[$idRealization][] = $row->idAnnotationSet;
        }
        $idAS = -1;
        $maxCountFE = 0;
        $distinctFE = [];
        $sorted = collect($rows)->sortBy('idAnnotationSet')->all();
        foreach ($sorted as $row) {
            $feIdEntity = $row->feIdEntity;
            $fe = $row->feName;
            $gf = $row->gfName ?: '?';
            $pt = $row->ptName ?: '?';
            $it = $row->itEntry ?: '?';
            if ($row->idAnnotationSet != $idAS) {
                if ($idAS >= 0) {
                    $vpfe[$idVPFE]['feEntries'] = $feEntries;
                    $vpfe[$idVPFE]['count'] = ($vpfe[$idVPFE]['count'] ?? 0) + 1;
                    if (count($distinctFE) > $maxCountFE) {
                        $maxCountFE = count($distinctFE);
                    }
                    $vp[$idVPFE][$idVP][] = $idAS;
                    if (count($vp[$idVPFE][$idVP]) == 1) {
                        $patterns[$idVPFE][$idVP] = $pattern;
                    }
                }
                $idVP = 'id';
                $idVPFE = 'id';
                $pattern = [];
                $feEntries = [];
                $distinctFE = [];
//                $startCharNI = 1000;
            }
            if ($it == 'int_normal') {
                $pattern[$startChar][$feIdEntity][$gf][$pt] = $row->idAnnotationSet;
            } else {
                $pattern[$startChar][$feIdEntity][$row->itName]['--'][] = $row->idAnnotationSet;
            }
            $distinctFE[$feIdEntity] = $feIdEntity;
//            if (count($pattern) > $maxCountFE) {
//                $maxCountFE = count($pattern);
//            }
            $idAS = $row->idAnnotationSet;
            $idVPFE = 'id' . md5($idVPFE . $fe);
            $idVP = 'id' . md5($idVP . $fe . $gf . $pt . $it);
        }
        if ($idVPFE != '') {
            $vpfe[$idVPFE]['feEntries'] = $feEntries;
            $vpfe[$idVPFE]['count'] = ($vpfe[$idVPFE]['count'] ?? 0) + 1;
//            if ($maxCountFE < (count($pattern) + 1)) {
//                $maxCountFE = count($pattern) + 1;
//            }
            $vp[$idVPFE][$idVP][] = $idAS;
            if (count($vp[$idVPFE][$idVP]) == 1) {
                $patterns[$idVPFE][$idVP] = $pattern;
            }
        }
        $patternFEAS = [];
        $patternAS = [];
        foreach ($vp as $idVPFE => $p) {
            foreach ($p as $idVP => $as) {
                $patternAS[$idVP] = $as;
                foreach ($as as $a) {
                    $patternFEAS[$idVPFE][] = $a;
                }
            }
        }
        $feAS = [];
        foreach ($fes as $feIdEntity => $fe) {
            foreach ($fe['as'] as $as) {
                $feAS[$feIdEntity][] = $as;
            }
        }
        $result = [
            'realizations' => $realizations,
            'realizationAS' => $realizationAS,
            'fes' => $fes,
            'vp' => $vp,
            'vpfe' => $vpfe,
            'maxCountFE' => $maxCountFE,
            'patterns' => $patterns,
            'feAS' => $feAS,
            'patternFEAS' => $patternFEAS,
            'patternAS' => $patternAS
        ];
        return $result;
    }

    public static function getSentences(object $data): array
    {
        $sentences = AnnotationSet::listSentencesByAS($data->idAS);
        $annotation = AnnotationSet::listFECEByAS($data->idAS);
        $result = [];
        foreach ($sentences as $sentence) {
            $node = [];
            $node['idAnnotationSet'] = $sentence->idAnnotationSet;
            $node['idSentence'] = $sentence->idSentence;
            $node['idDocumentSentence'] = $sentence->idDocumentSentence;
            if ($annotation[$sentence->idSentence]) {
                $decoratedSentence = self::decorateSentence($sentence->text, $annotation[$sentence->idSentence]);
                $node['text'] = $decoratedSentence->color;
                $node['clean'] = $decoratedSentence->clean;
            } else {
                $node['text'] = $sentence->text;
                $node['clean'] = $sentence->text;
            }
            $node['status'] = '';//$sentence->annotationStatus;
            $node['rgbBg'] = '';//$sentence->rgbBg;
            $result[] = $node;
        }
        //mdump($result);
        return $result;
    }

    public static function decorateSentence($sentence, $labels): object
    {
        //mdump($sentence);
        //$sentence = utf8_decode($sentence);
        //mdump($sentence);
        $layer = [];
        $tempStartChar = -2;
        foreach($labels as $i => $label) {
            $startChar = $label->startChar;
            if ($startChar >= 0) {
                if ($startChar > $tempStartChar) {
                    $layer[0][$i] = $label;
                } else {
                    if (isset($layer[1][$startChar])) {
                        if (isset($layer[2][$startChar])) {
                            if (isset($layer[3][$startChar])) {
                                if (isset($layer[4][$startChar])) {
                                } else {
                                    $layer[4][$startChar] = $label;
                                }
                            } else {
                                $layer[3][$startChar] = $label;
                            }
                        } else {
                            $layer[2][$startChar] = $label;
                        }
                    } else {
                        $layer[1][$startChar] = $label;
                    }
                }
                $tempStartChar = $label->startChar;
            } else {
                $layer[0][$i] = $label;
            }
        }
        $result = '';
        $cleanText = '';
        foreach($layer as $layerNum => $layerLabels) {
            $i = 0;
            $ni = "";
            $decorated = "";
            $clean = "";
            $niClean = "";
            $invisible = 'background-color:#FFFFF;color:#FFFFFF;';
            foreach($layerLabels as $label) {
                if ($label->layerTypeEntry == 'lty_fe') {
                    $class = "color_" . $label->idColor;
                } else {
                    $class = "color_target";
                }
                if ($label->startChar >= 0) {
                    if ($layerNum == 0) {
                        $decorated .= mb_substr($sentence, $i, $label->startChar - $i);
                        $clean .= mb_substr($sentence, $i, $label->startChar - $i);
                    } else {
                        $decorated .= "<span style='{$invisible}'>" . mb_substr($sentence, $i, $label->startChar - $i) . "</span>";
                        $clean .= "<span style='{$invisible}'>" . mb_substr($sentence, $i, $label->startChar - $i) . "</span>";
                    }
                    $decorated .= "<span title=\"{$label->feName}\" class=\"{$class}\">" . mb_substr($sentence, $label->startChar, $label->endChar - $label->startChar + 1) . "</span>";
                    if ($label->layerTypeEntry == 'lty_fe') {
                        $clean .= "<span>[" . "<sub>" . $label->feName . "</sub>". ' ' . mb_substr($sentence, $label->startChar, $label->endChar - $label->startChar + 1) . "]</span>";
                    } else {
                        $clean .= "<span>[" . mb_substr($sentence, $label->startChar, $label->endChar - $label->startChar + 1) . " <sup>Target</sup>". "]</span>";
                    }
                    $i = $label->endChar + 1;
                } else { // null instantiation
                    $ni .= "<span title=\"{$label->feName}\"  class=\"{$class}\">" . $label->instantiationType . "</span> ";
                    $niClean .= "<span>[" . "<sub>" . $label->feName . "</sub>". ' ' . $label->instantiationType . "]</span> ";
                }
            }
            if ($layerNum == 0) {
                $decorated .= mb_substr($sentence, $i) . $ni;
                $clean .= mb_substr($sentence, $i) . $niClean;
            } else {
                $decorated .= "<span style='{$invisible}'>" . mb_substr($sentence, $i) . "</span>";
                $clean .= "<span style='{$invisible}'>" . mb_substr($sentence, $i) . "</span>";
            }
            $result .= ($layerNum > 0 ? '<br/>' : '') . $decorated;
            $cleanText .= ($layerNum > 0 ? '<br/>' : '') . $clean;
        }
        return (object)[
            'color' => $result,
            'clean' => $cleanText
        ];
    }

}
