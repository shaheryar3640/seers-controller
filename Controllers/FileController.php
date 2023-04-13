<?php

namespace App\Http\Controllers;

use App\Models\CbUsersDomains;
use App\Models\CookieXrayDialogue;
use App\Models\DomainScript;
use App\Models\ScriptCategory;
use http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FileController extends Controller
{
    public function __construct()
    {
//        $this->middleware('auth');
    }

    public function getFile($filename, Request $request)
    {
//        $url = $request->url();
        $host = 'let cx_host = "' . config('app.COOKIE_CONSENT_HOST') .'";';
        $url = $request->header('Referer');
        $banner = $dialogue = null;
        // for test banner -- start
        if($filename == 'tb.js'){
            $url = 'testbanner.com';
            $filename = 'cb.js';
        }
        if($filename == 'tbstyles.min.css'){
            $url = 'testbanner.com';
            $filename = 'styles.min.css';
        }
        // for test banner -- end
        $domain = CbUsersDomains::select('id')->whereNameAndVerified($this->removeProtocol($url), true)->first();
        // $domain_id = CbUsersDomains::select('id', 'verified')->whereName($this->removeProtocol($url))->first();
        if($domain){
            // $domain_id->verified = true;
            // $domain_id->save();
            $dialogue = CookieXrayDialogue::whereCbUsersDomainId($domain->id)->first();
            if ($dialogue->is_cookie_banner == 0) {
                $content = file_get_contents(storage_path('scripts/not-allowed.js'));
                return \Response::make($content, 200)->header('Content-Type', 'application/javascript');
            }
            $banner = $dialogue->banners()->whereIsActive(true)->first();
        }else{
            $filename = 'verify_domain.js';
            $type = "application/javascript";
            $content = file_get_contents(storage_path('scripts/'.$filename));

            // Attaching host in the start of the contents
            $content = $host . $content;
            return \Response::make($content, 200)->header('Content-Type', $type);
        }
        // $this->removeProtocol($url) == 'staging.roofingsuperstore.co.uk' ||

        if ($banner) {
            if($this->removeProtocol($url) == 'softcrackx.com'  || $this->removeProtocol($url) == 'rseedstaging.wpengine.com'){
                // $content = file_get_contents(storage_path('scripts/not-allowed.js'));
                // return \Response::make($content, 200)->header('Content-Type', 'application/javascript');
                $banner->name = 'free-test';
                // $location = $this->getCountryCode($request->ip());

                // return $location;

                // return \Response::make(JSON.stringify($location), 200)->header('Content-Type', 'application/javascript');
            }
            if($filename == 'cb.js') {
                $type = "application/javascript";
                $filename = $banner->name . '.js';
            }
            elseif($filename == 'styles.min.css'){
                $type = "text/css";
                $filename = $banner->name . '.min.css';
                $content = file_get_contents(storage_path('scripts/'.$filename));
                return \Response::make($content, 200)->header('Content-Type', $type);
            }else {
                return '';
            }
            $content = file_get_contents(storage_path('scripts/'.$filename));
            try {
                $location = $this->getCountryCode($request->ip());
            } catch (\Exception $e) {
                $location = null;
            }
            if ($dialogue && $dialogue->do_prior_consent === 1) {

                if($this->removeProtocol($url) == 'softcrackx.com'  || $this->removeProtocol($url) == 'rseedstaging.wpengine.com'){
                    $common_trackers = $this->writeCommonTrackers($domain->id);
                    $prior_consent_content = file_get_contents(storage_path('scripts/prior_consent_test.js'));
                    $content =  $common_trackers . $prior_consent_content . $content;
                }

                else{
                    $content = $this->writePriorConsent($content, $domain->id);
                    // if ($location && $location[0]->code !== 'US') {
                    //     $content = $this->writePriorConsent($content, $domain->id);
                    // }
                }


            }

            // Attaching host in the start of the contents
            $content = $host . $content;
            return \Response::make($content, 200)->header('Content-Type', $type);
        }

//        $headers = ['Content-Type: text/css'];
//        $headers = [];
//        return response()->download(storage_path('scripts\\'.$filename), null,$headers);

//        $js_content = storage_path('scripts\\'.$filename);
//
//        return Response::make($js_content, 200)
//            ->header('Content-Type', 'application/javascript');
    }

    private function writePriorConsent ($content, $domain_id) {
        $common_trackers = $this->writeCommonTrackers($domain_id);
        $prior_consent_content = file_get_contents(storage_path('scripts/prior_consent.js'));
        return $common_trackers . $prior_consent_content . $content;
    }

    private function writeCommonTrackers ($domain_id) {

        $domain_scripts = DomainScript::where(['domain_id' => $domain_id, 'enabled' => 1])->get();
        $script_categories = ScriptCategory::select('id', 'slug')->get()->toArray();

        if (!$domain_scripts || $domain_scripts->count() === 0) {
            // return $this->getCommonTrackers();
            return 'const commonTrackers = { domains: []};';
        }

        $scriptList = [];
        foreach ($domain_scripts as $key => $script) {
            $script_tag = [];
            if ($script_categories['0']['id'] != $script->script_category_id && $script_categories['1']['id'] != $script->script_category_id){
                if ($script->src !== '') {
                    $script_tag = [ 'd' => $script->src, 'c' => $script->script_category_id];
                    array_push($scriptList, $script_tag);
                }
            }
        }
        return 'const commonTrackers = { domains: '. json_encode($scriptList) .'};';
    }

    private function getCommonTrackers () {
        return 'const commonTrackers = {
            domains: [{
                d: "googletagmanager.com",
                c: 3
            }, {
                d: "google-analytics.com",
                c: 3
            }, {
                d: "youtube.com",
                c: 4
            }, {
                d: "youtube-nocookie.com",
                c: 4
            }, {
                d: "googleadservices.com",
                c: 4
            }, {
                d: "googlesyndication.com",
                c: 4
            }, {
                d: "doubleclick.net",
                c: 4
            }, {
                d: "facebook.*",
                c: 4
            }, {
                d: "linkedin.com",
                c: 4
            }, {
                d: "twitter.com",
                c: 4
            }, {
                d: "addthis.com",
                c: 4
            }, {
                d: "bing.com",
                c: 4
            }, {
                d: "vimeo.com",
                c: 4
            }, {
                d: "sharethis.com",
                c: 4
            }, {
                d: "yahoo.com",
                c: 4
            }, {
                d: "addtoany.com",
                c: 4
            }, {
                d: "dailymotion.com",
                c: 4
            }, {
                d: "amazon-adsystem.com",
                c: 4
            }]
        };';
    }

    public function getCountryCode ($ip) {
        $query = "SELECT `code`,`countryName` FROM ip2countrylist WHERE INET_ATON('$ip') BETWEEN `ip_range_start_int` AND `ip_range_end_int` LIMIT 1";
        $country_code = DB::connection('mysql2')->select($query);
        return $country_code ? $country_code : null;
    }

//    public function getFile($filename, Request $request)
//    {
//        $url = $request->url();
//
//        $banner =  null;
//        $domain_id = CbUsersDomains::select('id')->whereNameAndVerified($this->removeProtocol($url), true)->first();
//        if($domain_id){
//            $dialogue = CookieXrayDialogue::whereCbUsersDomainId($domain_id->id)->first();
//            $banner = $dialogue->banners()->whereIsActive(true)->first();
//        }else{
//            $filename = 'verify_domain.js';
//            $type = "application/javascript";
//            $content = file_get_contents(storage_path('scripts/'.$filename));
//            return \Response::make($content, 200)->header('Content-Type', $type);
//        }
//
//        if($banner){
//            if($filename == 'cb.js') {
//                $type = "application/javascript";
//                $filename = $banner->name . '.js';
//            }
//            elseif($filename == 'styles.min.css'){
//                $type = "text/css";
//                $filename = $banner->name . '.min.css';
//            }else {
//                return '';
//            }
//            $content = file_get_contents(storage_path('scripts/'.$filename));
//            return \Response::make($content, 200)->header('Content-Type', $type);
//        }
//
////        $headers = ['Content-Type: text/css'];
////        $headers = [];
////        return response()->download(storage_path('scripts\\'.$filename), null,$headers);
//
////        $js_content = storage_path('scripts\\'.$filename);
////
////        return Response::make($js_content, 200)
////            ->header('Content-Type', 'application/javascript');
//    }

    public function getLogo($name)
    {

        $logo_path = base_path('images/logo');
        if(file_exists($logo_path . '/' . $name)){
            return \Response::download($logo_path . '/' . $name);
        }
    }

    public function removeProtocol($url){
        //dd('url');

        $remove = array("http://","https://", "www.", "WWW.");
        $final_url =  str_replace($remove,"",$url);
        if(strpos($final_url, '/') !== false){
            $host_name = explode('/', $final_url);
            return $host_name[0];
        }else{
            return $final_url;
        }
    }
}
