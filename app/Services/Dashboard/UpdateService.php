<?php

namespace App\Services\Dashboard;

use App\Database\Criteria;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class UpdateService
{
    public static function frame2(): void
    {
        $result = [];
        $corpora = [
            'crp_pedro_pelo_mundo',
            'crp_ppm_nlg',
            'crp_ppm_gesture'
        ];
        $count0 = Criteria::table("annotationset as a")
            ->join("view_document_sentence as ds", "a.idSentence", "=", "ds.idSentence")
            ->join("document as d", "ds.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where("c.entry", "IN", $corpora)
            ->selectRaw("count(distinct a.idSentence) as nSentence")
            ->selectRaw("count(*) as nAS")
            ->selectRaw("count(distinct a.idLU) as nLU")
            ->all();
        $result['sentences'] = $count0[0]->nSentence;
        $result['asText'] = $count0[0]->nAS;
        $result['lusText'] = $count0[0]->nLU;
        $count1 = Criteria::table("view_video_dynamicobject as vd")
            ->join("view_document_video as dv", "vd.idVideo", "=", "dv.idVideo")
            ->join("dynamicobject as do", "vd.idDynamicObject", "=", "do.idDynamicObject")
            ->join("view_annotation as a", "do.idAnnotationObject", "=", "a.idAnnotationObject")
            ->join("frameelement as fe", "a.idEntity", "=", "fe.idEntity")
            ->join("document as d", "dv.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where('c.entry', 'IN', $corpora)
            ->selectRaw("count(distinct do.idDynamicObject) as n")
            ->all();
        $result['bbox'] = $count1[0]->n;
        $count2 = Criteria::table("view_annotation_text_fe as afe")
            ->join("annotationset as a", "afe.idAnnotationSet", "=", "a.idAnnotationSet")
            ->join("view_document_sentence as ds", "a.idSentence", "=", "ds.idSentence")
            ->join("document as d", "ds.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where("c.entry", "IN", $corpora)
            ->join("frameelement as fe", "afe.idFrameElement", "=", "fe.idFrameElement")
            ->selectRaw("count(distinct fe.idFrame) as n")
            ->all();
        $count3 = Criteria::table("view_video_dynamicobject as vd")
            ->join("view_document_video as dv", "vd.idVideo", "=", "dv.idVideo")
            ->join("dynamicobject as do", "vd.idDynamicObject", "=", "do.idDynamicObject")
            ->join("view_annotation as a", "do.idAnnotationObject", "=", "a.idAnnotationObject")
            ->join("frameelement as fe", "a.idEntity", "=", "fe.idEntity")
            ->join("document as d", "dv.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where('c.entry', 'IN', $corpora)
            ->selectRaw("count(distinct fe.idFrame) as n")
            ->all();
        $result['framesText'] = $count2[0]->n;
        $result['framesBBox'] = $count3[0]->n;
        $count4 = Criteria::table("view_annotation_text_fe as afe")
            ->join("annotationset as a", "afe.idAnnotationSet", "=", "a.idAnnotationSet")
            ->join("view_document_sentence as ds", "a.idSentence", "=", "ds.idSentence")
            ->join("document as d", "ds.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where("c.entry", "IN", $corpora)
            ->selectRaw("count(distinct afe.idFrameElement) as n")
            ->all();
        $count5 = Criteria::table("view_video_dynamicobject as vd")
            ->join("view_document_video as dv", "vd.idVideo", "=", "dv.idVideo")
            ->join("dynamicobject as do", "vd.idDynamicObject", "=", "do.idDynamicObject")
            ->join("view_annotation as a", "do.idAnnotationObject", "=", "a.idAnnotationObject")
            ->join("frameelement as fe", "a.idEntity", "=", "fe.idEntity")
            ->join("document as d", "dv.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where('c.entry', 'IN', $corpora)
            ->selectRaw("count(distinct fe.idFrameElement) as n")
            ->all();
        $result['fesText'] = $count4[0]->n;
        $result['fesBBox'] = $count5[0]->n;

        $count6 = Criteria::table("view_video_dynamicobject as vd")
            ->join("view_document_video as dv", "vd.idVideo", "=", "dv.idVideo")
            ->join("dynamicobject as do", "vd.idDynamicObject", "=", "do.idDynamicObject")
            ->join("view_annotation as a", "do.idAnnotationObject", "=", "a.idAnnotationObject")
            ->join("lu", "a.idEntity", "=", "lu.idEntity")
            ->join("document as d", "dv.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where('c.entry', 'IN', $corpora)
            ->selectRaw("count(distinct lu.idLU) as n")
            ->all();
        $result['lusBBox'] = $count6[0]->n;

        $decimal = (App::currentLocale() == 'pt') ? ',' : '.';
        $result['avgAS'] = number_format($result['asText'] / $result['sentences'], 3, $decimal, '');
        $count7 = Criteria::table("view_dynamicobject_boundingbox as bb")
            ->join("view_video_dynamicobject as vd", "bb.idDynamicObject", "=", "vd.idDynamicObject")
            ->join("view_document_video as dv", "vd.idVideo", "=", "dv.idVideo")
            ->join("document as d", "dv.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where('c.entry', 'IN', $corpora)
            ->groupBy("bb.idDynamicObject")
            ->selectRaw("count(*) as n")
            ->all();
        $sum = 0;
        foreach ($count7 as $row) {
            $sum += $row->n;
        }
        $avg = ($sum / count($count7)) * 0.040; // 40 ms por frame
        $result['avgDuration'] = number_format($avg, 3, $decimal, '');
        // update table
        Criteria::$database = 'webtool';
        $avg_sentence = str_replace(',', '.', $result['avgDuration']);
        $avg_obj = str_replace(',', '.', $result['avgAS']);
        Criteria::create("dashboard_frame2", [
            "text_sentence" => $result['sentences'],
            "text_frame" => $result['framesText'],
            "text_ef" => $result['fesText'],
            "text_lu" => $result['lusText'],
            "text_as" => $result['asText'],
            "video_bbox" => $result['bbox'],
            "video_frame" => $result['framesBBox'],
            "video_ef" => $result['fesBBox'],
            "video_obj" => $result['lusBBox'],
            "avg_sentence" => $avg_sentence,
            "avg_obj" => $avg_obj,
        ]);
    }


    public static function frame2PPM(): void
    {
        $result = [];
        $corpora = [
            'crp_pedro_pelo_mundo'
        ];
        $count0 = Criteria::table("annotationset as a")
            ->join("view_document_sentence as ds", "a.idSentence", "=", "ds.idSentence")
            ->join("document as d", "ds.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where("c.entry", "IN", $corpora)
            ->selectRaw("count(distinct a.idSentence) as nSentence")
            ->selectRaw("count(*) as nAS")
            ->selectRaw("count(distinct a.idLU) as nLU")
            ->all();
        $result['sentences'] = $count0[0]->nSentence;
        $result['asText'] = $count0[0]->nAS;
        $result['lusText'] = $count0[0]->nLU;
        $count1 = Criteria::table("view_video_dynamicobject as vd")
            ->join("view_document_video as dv", "vd.idVideo", "=", "dv.idVideo")
            ->join("dynamicobject as do", "vd.idDynamicObject", "=", "do.idDynamicObject")
            ->join("view_annotation as a", "do.idAnnotationObject", "=", "a.idAnnotationObject")
            ->join("frameelement as fe", "a.idEntity", "=", "fe.idEntity")
            ->join("document as d", "dv.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where('c.entry', 'IN', $corpora)
            ->selectRaw("count(distinct do.idDynamicObject) as n")
            ->all();
        $result['bbox'] = $count1[0]->n;
        $count2 = Criteria::table("view_annotation_text_fe as afe")
            ->join("annotationset as a", "afe.idAnnotationSet", "=", "a.idAnnotationSet")
            ->join("view_document_sentence as ds", "a.idSentence", "=", "ds.idSentence")
            ->join("document as d", "ds.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where("c.entry", "IN", $corpora)
            ->join("frameelement as fe", "afe.idFrameElement", "=", "fe.idFrameElement")
            ->selectRaw("count(distinct fe.idFrame) as n")
            ->all();
        $count3 = Criteria::table("view_video_dynamicobject as vd")
            ->join("view_document_video as dv", "vd.idVideo", "=", "dv.idVideo")
            ->join("dynamicobject as do", "vd.idDynamicObject", "=", "do.idDynamicObject")
            ->join("view_annotation as a", "do.idAnnotationObject", "=", "a.idAnnotationObject")
            ->join("frameelement as fe", "a.idEntity", "=", "fe.idEntity")
            ->join("document as d", "dv.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where('c.entry', 'IN', $corpora)
            ->selectRaw("count(distinct fe.idFrame) as n")
            ->all();
        $result['framesText'] = $count2[0]->n;
        $result['framesBBox'] = $count3[0]->n;
        $count4 = Criteria::table("view_annotation_text_fe as afe")
            ->join("annotationset as a", "afe.idAnnotationSet", "=", "a.idAnnotationSet")
            ->join("view_document_sentence as ds", "a.idSentence", "=", "ds.idSentence")
            ->join("document as d", "ds.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where("c.entry", "IN", $corpora)
            ->selectRaw("count(distinct afe.idFrameElement) as n")
            ->all();
        $count5 = Criteria::table("view_video_dynamicobject as vd")
            ->join("view_document_video as dv", "vd.idVideo", "=", "dv.idVideo")
            ->join("dynamicobject as do", "vd.idDynamicObject", "=", "do.idDynamicObject")
            ->join("view_annotation as a", "do.idAnnotationObject", "=", "a.idAnnotationObject")
            ->join("frameelement as fe", "a.idEntity", "=", "fe.idEntity")
            ->join("document as d", "dv.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where('c.entry', 'IN', $corpora)
            ->selectRaw("count(distinct fe.idFrameElement) as n")
            ->all();
        $result['fesText'] = $count4[0]->n;
        $result['fesBBox'] = $count5[0]->n;

        $count6 = Criteria::table("view_video_dynamicobject as vd")
            ->join("view_document_video as dv", "vd.idVideo", "=", "dv.idVideo")
            ->join("dynamicobject as do", "vd.idDynamicObject", "=", "do.idDynamicObject")
            ->join("view_annotation as a", "do.idAnnotationObject", "=", "a.idAnnotationObject")
            ->join("lu", "a.idEntity", "=", "lu.idEntity")
            ->join("document as d", "dv.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where('c.entry', 'IN', $corpora)
            ->selectRaw("count(distinct lu.idLU) as n")
            ->all();
        $result['lusBBox'] = $count6[0]->n;

        $decimal = (App::currentLocale() == 'pt') ? ',' : '.';
        $result['avgAS'] = number_format($result['asText'] / $result['sentences'], 3, $decimal, '');
        $count7 = Criteria::table("view_dynamicobject_boundingbox as bb")
            ->join("view_video_dynamicobject as vd", "bb.idDynamicObject", "=", "vd.idDynamicObject")
            ->join("view_document_video as dv", "vd.idVideo", "=", "dv.idVideo")
            ->join("document as d", "dv.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where('c.entry', 'IN', $corpora)
            ->groupBy("bb.idDynamicObject")
            ->selectRaw("count(*) as n")
            ->all();
        $sum = 0;
        foreach ($count7 as $row) {
            $sum += $row->n;
        }
        $avg = ($sum / count($count7)) * 0.040; // 40 ms por frame
        $result['avgDuration'] = number_format($avg, 3, $decimal, '');
        // update table
        Criteria::$database = 'webtool';
        $avg_sentence = str_replace(',', '.', $result['avgDuration']);
        $avg_obj = str_replace(',', '.', $result['avgAS']);
        Criteria::create("dashboard_frame2ppm", [
            "text_sentence" => $result['sentences'],
            "text_frame" => $result['framesText'],
            "text_ef" => $result['fesText'],
            "text_lu" => $result['lusText'],
            "text_as" => $result['asText'],
            "video_bbox" => $result['bbox'],
            "video_frame" => $result['framesBBox'],
            "video_ef" => $result['fesBBox'],
            "video_obj" => $result['lusBBox'],
            "avg_sentence" => $avg_sentence,
            "avg_obj" => $avg_obj,
        ]);

    }

    public static function frame2NLG(): void
    {
        $result = [];
        $corpora = [
            'crp_ppm_nlg'
        ];
        $count0 = Criteria::table("annotationset as a")
            ->join("view_document_sentence as ds", "a.idSentence", "=", "ds.idSentence")
            ->join("document as d", "ds.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where("c.entry", "IN", $corpora)
            ->selectRaw("count(distinct a.idSentence) as nSentence")
            ->selectRaw("count(*) as nAS")
            ->selectRaw("count(distinct a.idLU) as nLU")
            ->all();
        $result['sentences'] = $count0[0]->nSentence;
        $result['asText'] = $count0[0]->nAS;
        $result['lusText'] = $count0[0]->nLU;
        $count1 = Criteria::table("view_video_dynamicobject as vd")
            ->join("view_document_video as dv", "vd.idVideo", "=", "dv.idVideo")
            ->join("dynamicobject as do", "vd.idDynamicObject", "=", "do.idDynamicObject")
            ->join("view_annotation as a", "do.idAnnotationObject", "=", "a.idAnnotationObject")
            ->join("frameelement as fe", "a.idEntity", "=", "fe.idEntity")
            ->join("document as d", "dv.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where('c.entry', 'IN', $corpora)
            ->selectRaw("count(distinct do.idDynamicObject) as n")
            ->all();
        $result['bbox'] = $count1[0]->n;
        $count2 = Criteria::table("view_annotation_text_fe as afe")
            ->join("annotationset as a", "afe.idAnnotationSet", "=", "a.idAnnotationSet")
            ->join("view_document_sentence as ds", "a.idSentence", "=", "ds.idSentence")
            ->join("document as d", "ds.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where("c.entry", "IN", $corpora)
            ->join("frameelement as fe", "afe.idFrameElement", "=", "fe.idFrameElement")
            ->selectRaw("count(distinct fe.idFrame) as n")
            ->all();
        $count3 = Criteria::table("view_video_dynamicobject as vd")
            ->join("view_document_video as dv", "vd.idVideo", "=", "dv.idVideo")
            ->join("dynamicobject as do", "vd.idDynamicObject", "=", "do.idDynamicObject")
            ->join("view_annotation as a", "do.idAnnotationObject", "=", "a.idAnnotationObject")
            ->join("frameelement as fe", "a.idEntity", "=", "fe.idEntity")
            ->join("document as d", "dv.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where('c.entry', 'IN', $corpora)
            ->selectRaw("count(distinct fe.idFrame) as n")
            ->all();
        $result['framesText'] = $count2[0]->n;
        $result['framesBBox'] = $count3[0]->n;
        $count4 = Criteria::table("view_annotation_text_fe as afe")
            ->join("annotationset as a", "afe.idAnnotationSet", "=", "a.idAnnotationSet")
            ->join("view_document_sentence as ds", "a.idSentence", "=", "ds.idSentence")
            ->join("document as d", "ds.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where("c.entry", "IN", $corpora)
            ->selectRaw("count(distinct afe.idFrameElement) as n")
            ->all();
        $count5 = Criteria::table("view_video_dynamicobject as vd")
            ->join("view_document_video as dv", "vd.idVideo", "=", "dv.idVideo")
            ->join("dynamicobject as do", "vd.idDynamicObject", "=", "do.idDynamicObject")
            ->join("view_annotation as a", "do.idAnnotationObject", "=", "a.idAnnotationObject")
            ->join("frameelement as fe", "a.idEntity", "=", "fe.idEntity")
            ->join("document as d", "dv.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where('c.entry', 'IN', $corpora)
            ->selectRaw("count(distinct fe.idFrameElement) as n")
            ->all();
        $result['fesText'] = $count4[0]->n;
        $result['fesBBox'] = $count5[0]->n;

        $count6 = Criteria::table("view_video_dynamicobject as vd")
            ->join("view_document_video as dv", "vd.idVideo", "=", "dv.idVideo")
            ->join("dynamicobject as do", "vd.idDynamicObject", "=", "do.idDynamicObject")
            ->join("view_annotation as a", "do.idAnnotationObject", "=", "a.idAnnotationObject")
            ->join("lu", "a.idEntity", "=", "lu.idEntity")
            ->join("document as d", "dv.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where('c.entry', 'IN', $corpora)
            ->selectRaw("count(distinct lu.idLU) as n")
            ->all();
        $result['lusBBox'] = $count6[0]->n;

        $decimal = (App::currentLocale() == 'pt') ? ',' : '.';
        $result['avgAS'] = ($result['sentences'] > 0) ? number_format($result['asText'] / $result['sentences'], 3, $decimal, '') : 0;
        $count7 = Criteria::table("view_dynamicobject_boundingbox as bb")
            ->join("view_video_dynamicobject as vd", "bb.idDynamicObject", "=", "vd.idDynamicObject")
            ->join("view_document_video as dv", "vd.idVideo", "=", "dv.idVideo")
            ->join("document as d", "dv.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where('c.entry', 'IN', $corpora)
            ->groupBy("bb.idDynamicObject")
            ->selectRaw("count(*) as n")
            ->all();
        $sum = 0;
        foreach ($count7 as $row) {
            $sum += $row->n;
        }
        $avg = ($sum / count($count7)) * 0.040; // 40 ms por frame
        $result['avgDuration'] = number_format($avg, 3, $decimal, '');
        // update table
        Criteria::$database = 'webtool';
        $avg_sentence = str_replace(',', '.', $result['avgDuration']);
        $avg_obj = str_replace(',', '.', $result['avgAS']);
        Criteria::create("dashboard_frame2nlg", [
            "text_sentence" => $result['sentences'],
            "text_frame" => $result['framesText'],
            "text_ef" => $result['fesText'],
            "text_lu" => $result['lusText'],
            "text_as" => $result['asText'],
            "video_bbox" => $result['bbox'],
            "video_frame" => $result['framesBBox'],
            "video_ef" => $result['fesBBox'],
            "video_obj" => $result['lusBBox'],
            "avg_sentence" => $avg_sentence,
            "avg_obj" => $avg_obj,
        ]);


    }

    public static function frame2Gesture(): void
    {
        $result = [];
        $corpora = [
            'crp_ppm_gesture'
        ];
        $count0 = Criteria::table("annotationset as a")
            ->join("view_document_sentence as ds", "a.idSentence", "=", "ds.idSentence")
            ->join("document as d", "ds.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where("c.entry", "IN", $corpora)
            ->selectRaw("count(distinct a.idSentence) as nSentence")
            ->selectRaw("count(*) as nAS")
            ->selectRaw("count(distinct a.idLU) as nLU")
            ->all();
        $result['sentences'] = $count0[0]->nSentence;
        $result['asText'] = $count0[0]->nAS;
        $result['lusText'] = $count0[0]->nLU;
        $count1 = Criteria::table("view_video_dynamicobject as vd")
            ->join("view_document_video as dv", "vd.idVideo", "=", "dv.idVideo")
            ->join("dynamicobject as do", "vd.idDynamicObject", "=", "do.idDynamicObject")
            ->join("view_annotation as a", "do.idAnnotationObject", "=", "a.idAnnotationObject")
            ->join("frameelement as fe", "a.idEntity", "=", "fe.idEntity")
            ->join("document as d", "dv.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where('c.entry', 'IN', $corpora)
            ->selectRaw("count(distinct do.idDynamicObject) as n")
            ->all();
        $result['bbox'] = $count1[0]->n;
        $count2 = Criteria::table("view_annotation_text_fe as afe")
            ->join("annotationset as a", "afe.idAnnotationSet", "=", "a.idAnnotationSet")
            ->join("view_document_sentence as ds", "a.idSentence", "=", "ds.idSentence")
            ->join("document as d", "ds.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where("c.entry", "IN", $corpora)
            ->join("frameelement as fe", "afe.idFrameElement", "=", "fe.idFrameElement")
            ->selectRaw("count(distinct fe.idFrame) as n")
            ->all();
        $count3 = Criteria::table("view_video_dynamicobject as vd")
            ->join("view_document_video as dv", "vd.idVideo", "=", "dv.idVideo")
            ->join("dynamicobject as do", "vd.idDynamicObject", "=", "do.idDynamicObject")
            ->join("view_annotation as a", "do.idAnnotationObject", "=", "a.idAnnotationObject")
            ->join("frameelement as fe", "a.idEntity", "=", "fe.idEntity")
            ->join("document as d", "dv.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where('c.entry', 'IN', $corpora)
            ->selectRaw("count(distinct fe.idFrame) as n")
            ->all();
        $result['framesText'] = $count2[0]->n;
        $result['framesBBox'] = $count3[0]->n;
        $count4 = Criteria::table("view_annotation_text_fe as afe")
            ->join("annotationset as a", "afe.idAnnotationSet", "=", "a.idAnnotationSet")
            ->join("view_document_sentence as ds", "a.idSentence", "=", "ds.idSentence")
            ->join("document as d", "ds.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where("c.entry", "IN", $corpora)
            ->selectRaw("count(distinct afe.idFrameElement) as n")
            ->all();
        $count5 = Criteria::table("view_video_dynamicobject as vd")
            ->join("view_document_video as dv", "vd.idVideo", "=", "dv.idVideo")
            ->join("dynamicobject as do", "vd.idDynamicObject", "=", "do.idDynamicObject")
            ->join("view_annotation as a", "do.idAnnotationObject", "=", "a.idAnnotationObject")
            ->join("frameelement as fe", "a.idEntity", "=", "fe.idEntity")
            ->join("document as d", "dv.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where('c.entry', 'IN', $corpora)
            ->selectRaw("count(distinct fe.idFrameElement) as n")
            ->all();
        $result['fesText'] = $count4[0]->n;
        $result['fesBBox'] = $count5[0]->n;

        $count6 = Criteria::table("view_video_dynamicobject as vd")
            ->join("view_document_video as dv", "vd.idVideo", "=", "dv.idVideo")
            ->join("dynamicobject as do", "vd.idDynamicObject", "=", "do.idDynamicObject")
            ->join("view_annotation as a", "do.idAnnotationObject", "=", "a.idAnnotationObject")
            ->join("lu", "a.idEntity", "=", "lu.idEntity")
            ->join("document as d", "dv.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where('c.entry', 'IN', $corpora)
            ->selectRaw("count(distinct lu.idLU) as n")
            ->all();
        $result['lusBBox'] = $count6[0]->n;

        $decimal = (App::currentLocale() == 'pt') ? ',' : '.';
        $result['avgAS'] = ($result['sentences'] > 0) ? number_format($result['asText'] / $result['sentences'], 3, $decimal, '') : 0;
        $count7 = Criteria::table("view_dynamicobject_boundingbox as bb")
            ->join("view_video_dynamicobject as vd", "bb.idDynamicObject", "=", "vd.idDynamicObject")
            ->join("view_document_video as dv", "vd.idVideo", "=", "dv.idVideo")
            ->join("document as d", "dv.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where('c.entry', 'IN', $corpora)
            ->groupBy("bb.idDynamicObject")
            ->selectRaw("count(*) as n")
            ->all();
        $sum = 0;
        foreach ($count7 as $row) {
            $sum += $row->n;
        }
        $avg = ($sum / count($count7)) * 0.040; // 40 ms por frame
        $result['avgDuration'] = number_format($avg, 3, $decimal, '');
        // update table
        Criteria::$database = 'webtool';
        $avg_sentence = str_replace(',', '.', $result['avgDuration']);
        $avg_obj = str_replace(',', '.', $result['avgAS']);
        Criteria::create("dashboard_frame2gesture", [
            "text_sentence" => $result['sentences'],
            "text_frame" => $result['framesText'],
            "text_ef" => $result['fesText'],
            "text_lu" => $result['lusText'],
            "text_as" => $result['asText'],
            "video_bbox" => $result['bbox'],
            "video_frame" => $result['framesBBox'],
            "video_ef" => $result['fesBBox'],
            "video_obj" => $result['lusBBox'],
            "avg_sentence" => $avg_sentence,
            "avg_obj" => $avg_obj,
        ]);


    }

    public static function audition(): void
    {
        $result = [];
        $corpora = [
            'crp_curso_dataset', // audition
            'crp_hoje_eu_nao_quero', // Curta-metragem_ENQVS
            'crp_ad alternativa curta_hoje_eu_não_quero', //Audiodescrição_alternativa_ENQVS
        ];
        $count0 = Criteria::table("annotationset as a")
            ->join("view_document_sentence as ds", "a.idSentence", "=", "ds.idSentence")
            ->join("document as d", "ds.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where("c.entry", "IN", $corpora)
            ->selectRaw("count(distinct a.idSentence) as nSentence")
            ->selectRaw("count(*) as nAS")
            ->selectRaw("count(distinct a.idLU) as nLU")
            ->all();
        $result['sentences'] = $count0[0]->nSentence;
        $result['asText'] = $count0[0]->nAS;
        $result['lusText'] = $count0[0]->nLU;
        $count1 = Criteria::table("view_video_dynamicobject as vd")
            ->join("view_document_video as dv", "vd.idVideo", "=", "dv.idVideo")
            ->join("dynamicobject as do", "vd.idDynamicObject", "=", "do.idDynamicObject")
            ->join("view_annotation as a", "do.idAnnotationObject", "=", "a.idAnnotationObject")
            ->join("frameelement as fe", "a.idEntity", "=", "fe.idEntity")
            ->join("document as d", "dv.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where('c.entry', 'IN', $corpora)
            ->selectRaw("count(distinct do.idDynamicObject) as n")
            ->all();
        $result['bbox'] = $count1[0]->n;
        $count2 = Criteria::table("view_annotation_text_fe as afe")
            ->join("annotationset as a", "afe.idAnnotationSet", "=", "a.idAnnotationSet")
            ->join("view_document_sentence as ds", "a.idSentence", "=", "ds.idSentence")
            ->join("document as d", "ds.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where("c.entry", "IN", $corpora)
            ->join("frameelement as fe", "afe.idFrameElement", "=", "fe.idFrameElement")
            ->selectRaw("count(distinct fe.idFrame) as n")
            ->all();
        $count3 = Criteria::table("view_video_dynamicobject as vd")
            ->join("view_document_video as dv", "vd.idVideo", "=", "dv.idVideo")
            ->join("dynamicobject as do", "vd.idDynamicObject", "=", "do.idDynamicObject")
            ->join("view_annotation as a", "do.idAnnotationObject", "=", "a.idAnnotationObject")
            ->join("frameelement as fe", "a.idEntity", "=", "fe.idEntity")
            ->join("document as d", "dv.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where('c.entry', 'IN', $corpora)
            ->selectRaw("count(distinct fe.idFrame) as n")
            ->all();
        $result['framesText'] = $count2[0]->n;
        $result['framesBBox'] = $count3[0]->n;
        $count4 = Criteria::table("view_annotation_text_fe as afe")
            ->join("annotationset as a", "afe.idAnnotationSet", "=", "a.idAnnotationSet")
            ->join("view_document_sentence as ds", "a.idSentence", "=", "ds.idSentence")
            ->join("document as d", "ds.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where("c.entry", "IN", $corpora)
            ->selectRaw("count(distinct afe.idFrameElement) as n")
            ->all();
        $count5 = Criteria::table("view_video_dynamicobject as vd")
            ->join("view_document_video as dv", "vd.idVideo", "=", "dv.idVideo")
            ->join("dynamicobject as do", "vd.idDynamicObject", "=", "do.idDynamicObject")
            ->join("view_annotation as a", "do.idAnnotationObject", "=", "a.idAnnotationObject")
            ->join("frameelement as fe", "a.idEntity", "=", "fe.idEntity")
            ->join("document as d", "dv.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where('c.entry', 'IN', $corpora)
            ->selectRaw("count(distinct fe.idFrameElement) as n")
            ->all();
        $result['fesText'] = $count4[0]->n;
        $result['fesBBox'] = $count5[0]->n;

        $count6 = Criteria::table("view_video_dynamicobject as vd")
            ->join("view_document_video as dv", "vd.idVideo", "=", "dv.idVideo")
            ->join("dynamicobject as do", "vd.idDynamicObject", "=", "do.idDynamicObject")
            ->join("view_annotation as a", "do.idAnnotationObject", "=", "a.idAnnotationObject")
            ->join("lu", "a.idEntity", "=", "lu.idEntity")
            ->join("document as d", "dv.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where('c.entry', 'IN', $corpora)
            ->selectRaw("count(distinct lu.idLU) as n")
            ->all();
        $result['lusBBox'] = $count6[0]->n;

        $decimal = (App::currentLocale() == 'pt') ? ',' : '.';
        $result['avgAS'] = number_format($result['asText'] / $result['sentences'], 3, $decimal, '');
        $count7 = Criteria::table("view_dynamicobject_boundingbox as bb")
            ->join("view_video_dynamicobject as vd", "bb.idDynamicObject", "=", "vd.idDynamicObject")
            ->join("view_document_video as dv", "vd.idVideo", "=", "dv.idVideo")
            ->join("document as d", "dv.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where('c.entry', 'IN', $corpora)
            ->groupBy("bb.idDynamicObject")
            ->selectRaw("count(*) as n")
            ->all();
        $sum = 0;
        foreach ($count7 as $row) {
            $sum += $row->n;
        }
        $avg = ($sum / count($count7)) * 0.040; // 40 ms por frame
        $result['avgDuration'] = number_format($avg, 3, $decimal, '');

        $count8 = Criteria::table("sentence as s")
            ->join("originmm as o", "s.idOriginMM", "=", "o.idOriginMM")
            ->join("view_document_sentence as ds", "s.idSentence", "=", "ds.idSentence")
            ->join("document as d", "ds.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where("c.entry", "IN", $corpora)
            ->groupBy("o.origin")
            ->selectRaw("o.origin")
            ->selectRaw("count(*) as nOrigin")
            ->all();
        $result['origin'] = serialize($count8);

        // update table
        Criteria::$database = 'webtool';
        $avg_sentence = str_replace(',', '.', $result['avgDuration']);
        $avg_obj = str_replace(',', '.', $result['avgAS']);
        Criteria::create("dashboard_audition", [
            "text_sentence" => $result['sentences'],
            "text_frame" => $result['framesText'],
            "text_ef" => $result['fesText'],
            "text_lu" => $result['lusText'],
            "text_as" => $result['asText'],
            "video_bbox" => $result['bbox'],
            "video_frame" => $result['framesBBox'],
            "video_ef" => $result['fesBBox'],
            "video_obj" => $result['lusBBox'],
            "avg_sentence" => $avg_sentence,
            "avg_obj" => $avg_obj,
            "origin" => $result['origin'],
        ]);
    }


    public static function multi30k(): array
    {
        $result = [];
        $corpora = [
            'crp_oficina_com_sentenca_1',
            'crp_oficina_com_sentenca_2',
            'crp_oficina_com_sentenca_3',
            'crp_oficina_com_sentenca_4',
            'crp_oficina_sem_sentenca_1',
            'crp_oficina_sem_sentenca_2',
            'crp_oficina_sem_sentenca_3',
            'crp_oficina_sem_sentenca_4',
            'crp_corpus-prime-sem-sentença',
            'crp_corpus-prime-com-sentença',
        ];

        $count = Criteria::table("view_staticobject_textspan as ts")
            ->join("view_staticobject_boundingbox as bb", "ts.idStaticObject", "=", "bb.idStaticObject")
            ->join("staticobject as sta", "ts.idStaticObject", "=", "sta.idStaticObject")
            ->join("annotation as a", "sta.idAnnotationObject", "=", "a.idAnnotationObject")
            ->join("frameelement as fe", "a.idEntity", "=", "fe.idEntity")
            ->join("document as d", "ts.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where('c.entry', 'IN', $corpora)
            ->selectRaw("count(distinct ts.idStaticObject) as n1")
            ->selectRaw("count(distinct bb.idBoundingBox) as n2")
            ->selectRaw("count(distinct fe.idFrame) as n3")
            ->selectRaw("count(distinct fe.idFrameElement) as n4")
            ->all();

        $result['objects'] = $count[0]->n1;
        $result['bbox'] = $count[0]->n2;
        $result['framesImage'] = $count[0]->n3;
        $result['fesImage'] = $count[0]->n4;
        $result['lusImage'] = 0;

        // sentences
        Criteria::$database = 'webtool';
        $count = Criteria::table("view_document_sentence as ds")
            ->selectRaw("ds.idDocument,count(distinct ds.idSentence) as n")
            ->groupBy("ds.idDocument")
            ->chunkResult("idDocument","n");
        // PTT
        $result['pttSentences'] = $count[1054];
        $cmd = "select count(distinct ls.idFrame) as n
from view_document_sentence ds join lome_result ls on (ds.idDocumentSentence = ls.idDocumentSentence)
where ds.idDocument = 1054";
        $countFrames = DB::select($cmd, []);
        $result['pttFrames'] = $countFrames[0]->n;
        // PTO
        $result['ptoSentences'] = $count[1055];
        $cmd = "select count(distinct ls.idFrame) as n
from view_document_sentence ds join lome_result ls on (ds.idDocumentSentence = ls.idDocumentSentence)
where ds.idDocument = 1055";
        $countFrames = DB::select($cmd, []);
        $result['ptoFrames'] = $countFrames[0]->n;
        // ENO
        $result['enoSentences'] = $count[663];
        $cmd = "select count(distinct ls.idFrame) as n
from view_document_sentence ds join lome_result ls on (ds.idDocumentSentence = ls.idDocumentSentence)
where ds.idDocument = 663";
        $countFrames = DB::select($cmd, []);
        $result['enoFrames'] = $countFrames[0]->n;
//        $result['chart'] = self::multi30kChart();
        return $result;
    }

    public static function multi30kEntity(): array
    {
        $result = [];
        $corpora = [
            'crp_oficina_com_sentenca_1',
            'crp_oficina_com_sentenca_2',
            'crp_oficina_com_sentenca_3',
            'crp_oficina_com_sentenca_4',
            'crp_oficina_sem_sentenca_1',
            'crp_oficina_sem_sentenca_2',
            'crp_oficina_sem_sentenca_3',
            'crp_oficina_sem_sentenca_4',
        ];
        $count = Criteria::table("view_staticobject_textspan as ts")
            ->join("view_staticobject_boundingbox as bb", "ts.idStaticObject", "=", "bb.idStaticObject")
            ->join("staticobject as sta", "ts.idStaticObject", "=", "sta.idStaticObject")
            ->join("annotation as a", "sta.idAnnotationObject", "=", "a.idAnnotationObject")
            ->join("frameelement as fe", "a.idEntity", "=", "fe.idEntity")
            ->join("document as d", "ts.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where('c.entry', 'IN', $corpora)
            ->selectRaw("count(distinct ts.idStaticObject) as n1")
            ->selectRaw("count(distinct bb.idBoundingBox) as n2")
            ->selectRaw("count(distinct fe.idFrame) as n3")
            ->selectRaw("count(distinct fe.idFrameElement) as n4")
            ->all();
        $result['objects'] = $count[0]->n1;
        $result['bbox'] = $count[0]->n2;
        $result['framesImage'] = $count[0]->n3;
        $result['fesImage'] = $count[0]->n4;
        $result['lusImage'] = 0;

        return $result;
    }

    public static function multi30kEvent(): array
    {
        $result = [];
        $corpora = [
            'crp_corpus-prime-sem-sentença',
            'crp_corpus-prime-com-sentença',
        ];
        $count = Criteria::table("view_staticobject_textspan as ts")
            ->join("view_staticobject_boundingbox as bb", "ts.idStaticObject", "=", "bb.idStaticObject")
            ->join("staticobject as sta", "ts.idStaticObject", "=", "sta.idStaticObject")
            ->join("annotation as a", "sta.idAnnotationObject", "=", "a.idAnnotationObject")
            ->join("frameelement as fe", "a.idEntity", "=", "fe.idEntity")
            ->join("document as d", "ts.idDocument", "=", "d.idDocument")
            ->join("corpus as c", "d.idCorpus", "=", "c.idCorpus")
            ->where('c.entry', 'IN', $corpora)
            ->selectRaw("count(distinct ts.idStaticObject) as n1")
            ->selectRaw("count(distinct bb.idBoundingBox) as n2")
            ->selectRaw("count(distinct fe.idFrame) as n3")
            ->selectRaw("count(distinct fe.idFrameElement) as n4")
            ->all();
        $result['objects'] = $count[0]->n1;
        $result['bbox'] = $count[0]->n2;
        $result['framesImage'] = $count[0]->n3;
        $result['fesImage'] = $count[0]->n4;
        $result['lusImage'] = 0;

        return $result;
    }

    public static function multi30kAll(): void
    {
        $multi30k = self::multi30k();
        $multi30kEntity = self::multi30kEntity();
        $multi30kEvent = self::multi30kEvent();
        // update table
        debug($multi30k, $multi30kEntity, $multi30kEvent);
        Criteria::$database = 'webtool';
        Criteria::create("dashboard_multi30k", [
            "multi30k_ptt_sentence" => $multi30k['pttSentences'],
            "multi30k_ptt_lome" => $multi30k['pttFrames'],
            "multi30k_pto_sentence" => $multi30k['ptoSentences'],
            "multi30k_pto_lome" => $multi30k['ptoFrames'],
            "multi30k_eno_sentence" => $multi30k['enoSentences'],
            "multi30k_eno_lome" => $multi30k['enoFrames'],
            "multi30k_image_image" => $multi30k['objects'],
            "multi30k_image_bbox" => $multi30k['bbox'],
            "multi30k_image_frame" => $multi30k['framesImage'],
            "multi30k_image_ef" => $multi30k['fesImage'],
            "multi30kevent_image_image" => $multi30kEvent['objects'],
            "multi30kevent_image_bbox" => $multi30kEvent['bbox'],
            "multi30kevent_image_frame" => $multi30kEvent['framesImage'],
            "multi30kevent_image_ef" => $multi30kEvent['fesImage'],
            "multi30kentity_image_image" => $multi30kEntity['objects'],
            "multi30kentity_image_bbox" => $multi30kEntity['bbox'],
            "multi30kentity_image_frame" => $multi30kEntity['framesImage'],
            "multi30kentity_image_ef" => $multi30kEntity['fesImage']
        ]);

    }


}
