<?php

namespace App\Http\Controllers\Dashboard;

use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Services\AppService;
use App\Services\Dashboard\DashboardService;
use App\Services\Dashboard\GTService;
use App\Services\Dashboard\McGovernService;
use App\Services\Dashboard\UpdateService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;

#[Middleware(name: 'web')]
class DashboardController extends Controller
{
    #[Get(path: '/dashboard')]
    public function main()
    {
        $lang = AppService::getCurrentLanguageCode();
        App::setLocale($lang);
        session(['currentController' => "Reinventa"]);
        session(["dashboard_must_calculate" => DashboardService::mustCalculate()]);
//        session(["dashboard_must_calculate" => false]);
        return view('Dashboard.main', []);
    }

    #[Get(path: '/dashboard/frame2')]
    public function frame2()
    {
        App::setLocale(AppService::getCurrentLanguageCode());
        if (session("dashboard_must_calculate")) {
            UpdateService::frame2();
        }
        $annotation = DashboardService::frame2();
        return view('Dashboard.frame2', [
            'annotation' => $annotation,
        ]);
    }

    #[Get(path: '/dashboard/frame2PPM')]
    public function frame2PPM()
    {
        App::setLocale(AppService::getCurrentLanguageCode());
        if (session("dashboard_must_calculate")) {
            UpdateService::frame2PPM();
        }
        $annotation = DashboardService::frame2PPM();
        return view('Dashboard.frame2PPM', [
            'annotation' => $annotation,
        ]);
    }

    #[Get(path: '/dashboard/frame2NLG')]
    public function frame2NLG()
    {
        App::setLocale(AppService::getCurrentLanguageCode());
        if (session("dashboard_must_calculate")) {
            UpdateService::frame2NLG();
        }
        $annotation = DashboardService::frame2NLG();
        return view('Dashboard.frame2NLG', [
            'annotation' => $annotation,
        ]);
    }

    #[Get(path: '/dashboard/frame2Gesture')]
    public function frame2Gesture()
    {
        App::setLocale(AppService::getCurrentLanguageCode());
        if (session("dashboard_must_calculate")) {
            UpdateService::frame2Gesture();
        }
        $annotation = DashboardService::frame2Gesture();
        return view('Dashboard.frame2Gesture', [
            'annotation' => $annotation,
        ]);
    }

    #[Get(path: '/dashboard/audition')]
    public function audition()
    {
        App::setLocale(AppService::getCurrentLanguageCode());
        if (session("dashboard_must_calculate")) {
            UpdateService::audition();
        }
        $annotation = DashboardService::audition();
        return view('Dashboard.audition', [
            'annotation' => $annotation,
        ]);
    }

    #[Get(path: '/dashboard/multi30k')]
    public function multi30k()
    {
        App::setLocale(AppService::getCurrentLanguageCode());
        //if (session("dashboard_must_calculate")) {
            UpdateService::multi30kAll();
        //}
        $multi30k = DashboardService::multi30k();
        $multi30kEntity = DashboardService::multi30kEntity();
        $multi30kEvent = DashboardService::multi30kEvent();
        $multi30kChart = DashboardService::multi30kChart();
        return view('Dashboard.multi30k', [
            'multi30k' => $multi30k,
            'multi30kEntity' => $multi30kEntity,
            'multi30kEvent' => $multi30kEvent,
            'multi30kChart' => $multi30kChart,
        ]);
    }


//    #[Get(path: '/changeLanguage/{language}')]
//    public function changeLanguage(Request $request, string $language)
//    {
//        $currentURL = $request->header("Hx-Current-Url");
//        $data = Criteria::byFilter("language", ['language', '=', $language])->first();
//        AppService::setCurrentLanguage($data->idLanguage);
//        return $this->redirect($currentURL);
//    }

    #[Get(path: '/dashboard/mcgovern')]
    public function mcgovern()
    {
        session(['currentController' => "McGovern"]);
        $this->data->mcgovern = McGovernService::dashboard();
        return $this->render("dashboard/mcgovern");
    }

    #[Get(path: '/dashboard/gt')]
    public function gt()
    {
        session(['currentController' => "GT"]);
        $this->data->gt = GTService::dashboard();
        ddump($this->data->gt);
        return $this->render("dashboard/gt");
    }
}

