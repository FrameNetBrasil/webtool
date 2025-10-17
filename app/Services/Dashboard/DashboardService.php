<?php

namespace App\Services\Dashboard;

use App\Database\Criteria;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class DashboardService
{

    public static function frame2(): array
    {
        $decimal = (App::currentLocale() == 'pt') ? ',' : '.';
        $fields = Criteria::table("dashboard_frame2")
            ->orderBy("idDashboardFrame2", "desc")
            ->first();
        return [
            'sentences' => $fields->text_sentence,
            'framesText' => $fields->text_frame,
            'fesText' => $fields->text_ef,
            'lusText' => $fields->text_lu,
            'asText' => $fields->text_as,
            'bbox' => $fields->video_bbox,
            'framesBBox' => $fields->video_frame,
            'fesBBox' => $fields->video_ef,
            'lusBBox' => $fields->video_obj,
            'avgDuration' => number_format($fields->avg_sentence, 3, $decimal, ''),
            'avgAS' => number_format($fields->avg_obj, 3, $decimal, ''),
        ];

    }


    public static function frame2PPM(): array
    {
        $decimal = (App::currentLocale() == 'pt') ? ',' : '.';
        $fields = Criteria::table("dashboard_frame2ppm")
            ->orderBy("idDashboardFrame2PPM", "desc")
            ->first();
        return [
            'sentences' => $fields->text_sentence,
            'framesText' => $fields->text_frame,
            'fesText' => $fields->text_ef,
            'lusText' => $fields->text_lu,
            'asText' => $fields->text_as,
            'bbox' => $fields->video_bbox,
            'framesBBox' => $fields->video_frame,
            'fesBBox' => $fields->video_ef,
            'lusBBox' => $fields->video_obj,
            'avgDuration' => number_format($fields->avg_sentence, 3, $decimal, ''),
            'avgAS' => number_format($fields->avg_obj, 3, $decimal, ''),
        ];

    }

    public static function frame2NLG(): array
    {
        $decimal = (App::currentLocale() == 'pt') ? ',' : '.';
        $fields = Criteria::table("dashboard_frame2nlg")
            ->orderBy("idDashboardFrame2NLG", "desc")
            ->first();
        return [
            'sentences' => $fields->text_sentence,
            'framesText' => $fields->text_frame,
            'fesText' => $fields->text_ef,
            'lusText' => $fields->text_lu,
            'asText' => $fields->text_as,
            'bbox' => $fields->video_bbox,
            'framesBBox' => $fields->video_frame,
            'fesBBox' => $fields->video_ef,
            'lusBBox' => $fields->video_obj,
            'avgDuration' => number_format($fields->avg_sentence, 3, $decimal, ''),
            'avgAS' => number_format($fields->avg_obj, 3, $decimal, ''),
        ];
    }

    public static function frame2Gesture(): array
    {
        $decimal = (App::currentLocale() == 'pt') ? ',' : '.';
        $fields = Criteria::table("dashboard_frame2gesture")
            ->orderBy("idDashboardFrame2Gesture", "desc")
            ->first();
        return [
            'sentences' => $fields->text_sentence,
            'framesText' => $fields->text_frame,
            'fesText' => $fields->text_ef,
            'lusText' => $fields->text_lu,
            'asText' => $fields->text_as,
            'bbox' => $fields->video_bbox,
            'framesBBox' => $fields->video_frame,
            'fesBBox' => $fields->video_ef,
            'lusBBox' => $fields->video_obj,
            'avgDuration' => number_format($fields->avg_sentence, 3, $decimal, ''),
            'avgAS' => number_format($fields->avg_obj, 3, $decimal, ''),
        ];


    }

    public static function audition(): array
    {
        $decimal = (App::currentLocale() == 'pt') ? ',' : '.';
        $fields = Criteria::table("dashboard_audition")
            ->orderBy("idDashboardAudition", "desc")
            ->first();
        return [
            'sentences' => $fields->text_sentence,
            'framesText' => $fields->text_frame,
            'fesText' => $fields->text_ef,
            'lusText' => $fields->text_lu,
            'asText' => $fields->text_as,
            'bbox' => $fields->video_bbox,
            'framesBBox' => $fields->video_frame,
            'fesBBox' => $fields->video_ef,
            'lusBBox' => $fields->video_obj,
            'avgDuration' => number_format($fields->avg_sentence, 3, $decimal, ''),
            'avgAS' => number_format($fields->avg_obj, 3, $decimal, ''),
            'origin' => unserialize($fields->origin),
        ];
    }

    public static function multi30k(): array
    {
        Criteria::$database = 'webtool';
        $decimal = (App::currentLocale() == 'pt') ? ',' : '.';
        $fields = Criteria::table("dashboard_multi30k")
            ->orderBy("idDashboardMulti30k", "desc")
            ->first();
        return [
            'images' => $fields->multi30k_image_image,
            'bbox' => $fields->multi30k_image_bbox,
            'framesImage' => $fields->multi30k_image_frame,
            'fesImage' => $fields->multi30k_image_ef,
            'pttSentences' => $fields->multi30k_ptt_sentence,
            'pttFrames' => $fields->multi30k_ptt_lome,
            'ptoSentences' => $fields->multi30k_pto_sentence,
            'ptoFrames' => $fields->multi30k_pto_lome,
            'enoSentences' => $fields->multi30k_eno_sentence,
            'enoFrames' => $fields->multi30k_eno_lome,
        ];
    }

    public static function multi30kEntity(): array
    {
        Criteria::$database = 'webtool';
        $decimal = (App::currentLocale() == 'pt') ? ',' : '.';
        $fields = Criteria::table("dashboard_multi30k")
            ->orderBy("idDashboardMulti30k", "desc")
            ->first();
        return [
            'images' => $fields->multi30kentity_image_image,
            'bbox' => $fields->multi30kentity_image_bbox,
            'framesImage' => $fields->multi30kentity_image_frame,
            'fesImage' => $fields->multi30kentity_image_ef,
        ];
    }

    public static function multi30kEvent(): array
    {
        Criteria::$database = 'webtool';
        $decimal = (App::currentLocale() == 'pt') ? ',' : '.';
        $fields = Criteria::table("dashboard_multi30k")
            ->orderBy("idDashboardMulti30k", "desc")
            ->first();
        return [
            'images' => $fields->multi30kevent_image_image,
            'bbox' => $fields->multi30kevent_image_bbox,
            'framesImage' => $fields->multi30kevent_image_frame,
            'fesImage' => $fields->multi30kevent_image_ef,
        ];
    }

    public static function multi30kChart(): array
    {
        $dbFnbr = DB::connection('webtool37');
        $cmd = "SELECT year(tlDateTime) y, month(tlDateTime) m, count(*) n
         FROM fnbr_db.timeline t
where (tablename='objectsentencemm') or (tablename='staticannotationmm')
group by year(tlDateTime),month(tlDateTime)
order by 1,2;";
        $rows = $dbFnbr->select($cmd, []);
        $chart = [];
        $sum = 0;
        foreach ($rows as $row) {
            $sum += is_object($row) ? $row->n : $row['n'];
            $m = is_object($row) ? $row->m : $row['m'];
            $y = is_object($row) ? $row->y : $row['y'];
            $chart[] = [
                'm' => $m . '/' . $y,
                'value' => $sum
            ];
        }
        return $chart;
    }

    public static function mustCalculate(): bool
    {
        $now = date('Y-m-d H:i:s');
        $lastAnnotation = Criteria::table("timeline")
            ->orderByDesc("idTimeLine")
            ->first();
        $lastAnnotationTime = is_null($lastAnnotation) ? $now : $lastAnnotation->tlDateTime;
        $dashboard = Criteria::table("dashboard")
            ->first();
        if (is_null($dashboard)) {
            $lastUpdateTime = $now;
            $idDashboard = Criteria::create("dashboard", ["timeLastUpdate" => $now]);
        } else {
            $lastUpdateTime = $dashboard->timeLastUpdate;
            $idDashboard = $dashboard->idDashboard;
        }
        $mustCalculate = $lastAnnotationTime >= $lastUpdateTime;
        if ($mustCalculate) {
            Criteria::table("dashboard")
                ->where("idDashBoard", $idDashboard)
                ->update(["timeLastUpdate" => $now]);
        }
        return $mustCalculate;
    }

}
