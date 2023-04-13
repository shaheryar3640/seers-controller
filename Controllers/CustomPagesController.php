<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\FaqProduct;
use App\Models\MembershipPlans;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Page;
use App\Models\UFeature;
use App\Models\UPlan;
use App\Models\UProduct;
use App\Models\User;
use App\Models\UserPaymentMethodDetails;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\PricePlanDeactive;
use Auth;
use App\Models\CookieBank;
use App\Models\CbCookies;
use App\Models\UserGuideDownload;
use App\Models\PressRelease;
use PDF;
use App\Models\WebUrl;
use App\Models\Testimonial;
use App\Models\Newsletter;
use App\Models\DepartmentSeat;
use App\Models\SubService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App;

use App\Mail\NewsGuideMail;


class CustomPagesController extends Controller
{
    public function cyberSecurity(){
        return view('cyber-security');
    }

    public function homePage() {

        return redirect()->route('home');
        $sub_services = \App\Models\SubService::all();
        $testimonials = \App\Models\Testimonial::all();
        $advisors = \App\Models\User::with(['SellerServices'])->onlyAdvisor()->has('SellerServices')->inRandomOrder()->limit(20)->get();

        //return response()->json($advisors);
        return view('home-page')->with(compact('sub_services','testimonials', 'advisors'));
        // return view('home-page');
    }

    public function testing(){

        $user_domain = 'abc.cookieconsent.co.uks';
        $domains_to_skip = array(
            'agpr.com',
            'cookieconsent.co.uk',
            'cookiebanner.uk',
            'eprivacy.ai',
            'iaccountants.co'
        );

        $host_names = explode(".", $this->removeProtocol($user_domain));
        if(sizeof($host_names) > 3){
            $user_domain = $host_names[count($host_names)-3] . "." . $host_names[count($host_names)-2] . "." . $host_names[count($host_names)-1];
        }else{
            $user_domain = $host_names[count($host_names)-2] . "." . $host_names[count($host_names)-1];
        }


        if(!in_array($user_domain, $domains_to_skip)) {
            dump('Not Found!');
        }else{
            dump('Found!');
        }
    }

    private function removeProtocol($URL) {
        $remove = array("http://", "https://", "www.", "WWW.");
        $final_url =  str_replace($remove, "", $URL);
        if(strpos($final_url, '/') !== false){
            $final_url = explode('/', $final_url);
            return $final_url[0];
        }else{
            return $final_url;
        }
    }

    public function seersVideos(){
        $videos = \App\Models\Video::paginate(10);
        return view('seers-videos', compact('videos'));
    }

    public function priceInvoiceEmail(){
        return redirect()->route('price-plan');
        return view('emails.price-invoice');
    }

    public function cookieConsentBanner(){
        // $activePlans = \App\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
         if(App::environment('production')){
            if(\Session::get('internal')==null || \Session::get('internal')!=true){
        $ip = $_SERVER['REMOTE_ADDR'];
        rescue(function() use($ip){

            $dataArray = json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=".$ip));

        if($dataArray->geoplugin_countryName === "Brazil"){
            return redirect()->route('cookie-consent-management-lang',['br']);
        }
        if($dataArray->geoplugin_countryName === "Germany"){
            return redirect()->route('cookie-consent-management-lang',['de']);
        }
        if($dataArray->geoplugin_countryName === "France"){
            return redirect()->route('cookie-consent-management-lang',['fr']);
        }
        if($dataArray->geoplugin_countryName === "Spain"){
            return redirect()->route('cookie-consent-management-lang',['es']);
        }
        },function(){
        },true);
    }
    }
        $page = request()->path();
        $web_url = WebUrl::where(['name'=> $page])->pluck('id');
        $testimonials = Testimonial::where(['web_urls_id' => $web_url])->get();
        $newscript = true;
        return view('cookie-consent-popup')->with(['testimonials' => $testimonials,'newscript' => $newscript,'lang'=>'en-uk']);
    }

      public function cookieConsentBannerEn()
{
    \Session::put('internal', true);
    // \Session::forget('internal');
    return redirect()->route('cookie-consent-management');
}

    public function cookieConsentBannerLang($lang){
        // $activePlans = \App\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
        $page = request()->path();
//        $web_url = WebUrl::where(['name'=> $page])->pluck('id');
//        $testimonials = Testimonial::where(['web_urls_id' => $web_url])->get();
        $newscript = true;
        return view('cookie-consent-popup')->with(['testimonials' => '','newscript' => $newscript,'lang' => $lang]);
        // switch($lang){
        //     case 'br':
        //         return view('cookie-consent-popup-br')->with(['testimonials' => '','newscript' => $newscript,'lang' => $lang]);
        //     break;
        //     case 'de':
        //         return view('cookie-consent-popup-de')->with(['testimonials' => '','newscript' => $newscript,'lang' => $lang]);
        //     break;
        //     case 'fr':
        //         return view('cookie-consent-popup-fr')->with(['testimonials' => '','newscript' => $newscript,'lang' => $lang]);
        //     break;
        //     case 'en-us':
        //         return view('cookie-consent-popup')->with(['testimonials' => '','newscript' => $newscript,'lang' => $lang]);
        //     break;
        //     case 'en-uk':
        //         return view('cookie-consent-popup')->with(['testimonials' => '','newscript' => $newscript,'lang' => $lang]);
        //     break;
        //     default:
        //      return view('cookie-consent-popup')->with(['testimonials' => '','newscript' => $newscript,'lang' => $lang]);
        // }
    }


    public function cookieXray(){
        return redirect()->route('indexCookieXray');
        $activePlans = \App\Models\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
        return view('cookie-xray')->with(['activePlans' => $activePlans, 'data_control' => 'data_control']);
    }

    public function cookies(){
        return redirect()->route('cookie-consent-management');
        return view('cookies');
    }

    public function cookie(){
        return redirect()->to('/cookie-consent-banner.html');
       // return view('cookie');
    }


    public function cookiePolicyGenerator(){
        $page = request()->path();
        $web_url = WebUrl::where(['name'=> $page])->pluck('id');
        $testimonials = Testimonial::where(['web_urls_id' => $web_url])->get();
        $newscript = true;
        return view('cookie-policy-generator')->with(['data_control' => 'data_control','testimonials'=>$testimonials,'newscript'=> $newscript]);
    }

    public function ultimateGuideToGDPR(){
        return view('ultimate-guide');
    }

    public function gdprGuideForCharity(){
        return view('gdpr-guide-for-charity');
    }

    public function gdprGuideForAccountant(){
        return view('gdpr-guide-for-accountant');
    }

    public function gdprGuideForLawyer(){
        return view('gdpr-guide-for-lawyer');
    }

    public function gdprGuideForSchool(){
        return view('gdpr-guide-for-school');
    }

    public function gdprGuideForMarketing(){
        return view('gdpr-guide-for-marketing');
    }

    public function ProcessUserGuideDownload(Request $request){


        request()->validate([
            'fname' => 'required',
            'lname' => 'required',
            'email' => 'required',
            'bookname' => 'required'
        ],
        [
            'fname.required' => 'First Name is required',
            'lname.required' => 'Last Name is required',
            'email.required' => 'Email is required',
            'bookname.required' => 'Book Name is required'
        ]
        );
        //dd($request);
        $message = null;
        $status = null;
        $user_id = $request->get('user_id');
        $fname = $request->get('fname');
        $lname = $request->get('lname');
        $email = $request->get('email');
        $bookname = $request->get('bookname');
        if ((!isset($fname) && ($fname != "")) && (!isset($lname) && ($lname != "")) && (!isset($email) && ($email != "")) && (!isset($bookname) && ($bookname != ""))){
            $message = 'Please fill the missing fields';
            $status = 401;
        } else {
            $userguide = new UserGuideDownload();
            if($user_id != 'Null') {
                $userguide->user_id = $user_id;
            }
            $userguide->fname = $fname;
            $userguide->lname = $lname;
            $userguide->email = $email;
            $userguide->bookname = $bookname;
            $userguide->oldornew = "new";
            $userguide->save();

            Mail::to($userguide->email)->bcc(config('app.hubspot_bcc'))->send(new NewsGuideMail($userguide));
            $message = 'Email sent to you successfully';
            $status = 200;
        }

        return response()->json([
            'url' => 'thank-you-give-away-ebook-fines-n-compliance',
            'message' => $message,
            'book' => $bookname
        ], $status);
    }

    public function freeGdprForBusiness(){
        return redirect()->route('assessmentToolkits');
        $activePlans = \App\Models\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
        return view('free-gdpr-for-business')->with(['cyberSecures' => 'cyberSecures', 'activePlans' => $activePlans]);
    }

    public function lawyerPartners(){
        return view('lawyer-sale-page');
    }

    public function companyPartners(){
        return view('company-partners');
    }

    public function lawyersPartners(){
        return view('lawyers-partners');
    }

    // public function aboutUs(){
    //     return view('about-us');
    // }
    public function aboutUs(){
        return view('about-us');
    }

    public function accountantsPartnership(){
        return view('ifa');
    }

    public function accountantPartnership(){
        return view('new-accountants-partnership');
    }

    public function outsourcedDPOForBusiness(){
        return redirect()->to('/advisors/Outsourced-DPO');
        return view('salepages.outsourced-dpo-for-business');
    }

    public function gdprGuideAssessment(){
        return redirect()->route('assessmentToolkits');
        $toolkits = \App\Models\Toolkit::orderBy('sort_order')->get();
        return view('gdpr-audit-assessment', compact('toolkits'));
    }

    public function thankYouDpoEnquiry(){
        if(session()->get('dpo') || session()->get('eu')){
            session()->forget('dpo');
            session()->forget('eu');
            return view('successful-registration');
        }else{
            return view('errors.404');
        }

    }

    public function thankYou(){
        return view('cookiebot.cookie-thankyou');
    }

    public function policyGeneratorSales(){
        // $activePlans = \App\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
        $page = request()->path();
        $web_url = WebUrl::where(['name'=> $page])->pluck('id');
        $testimonials = Testimonial::where(['web_urls_id' => $web_url])->get();
        $category = \App\Models\PolicyGeneratorCategory::where('enabled', 1)->orderBy('created_at', 'asc')->select('slug')->first();
        $newscript = true;
        return view('business.policy-generator-sales', compact('testimonials', 'category','newscript'));
    }

    public function searchPage($pageName){
        $pagesnew = [
            'cctv-policy',
            'consent-policy',
            'cookie-policy',
            'data-breach-policy',
            'data-protection-policy',
            'data-retention-policy',
            'data-subject-access-request-policy',
            'no-responsibility-disclaimer',
            'funny-disclaimer',
            'information-security-policy',
            'terms-and-conditions-policy',
            'privacy-policy',
            'privacy-notice-policy',
            'disclaimer-policy',
            'gdpr-policy',
        ];

        $pagesold =[
            'cctv-policy-sales-page',
            'consent-policy-sales-page',
            'cookie-policy-sales-page',
            'data-breach-policy-sales-page',
            'data-protection-policies-for-organisation',    //  not found.
            'data-protection-policies-for-website',         //  not found.
            'data-protection-policy-sales-page',
            'data-retention-policy-sales-page',
            'data-subject-access-request-sales-page',
            'information-security-policy-sales-page',
            'data-subject-access-request-policy',
            'gdpr-privacy-policy-generator',
            'terms-and-conditions-policy-sales-page',
            'privacy-policy-sales-page',
            'privacy-notice-policy-sales-page',
            'disclaimer-policy-sales-page',
            'work-from-home-policy',
        ];
        //route('business.policy-generator.index')
        //All testimonials will be according to the Policy Pack Page thus giving a static value
        $web_url = WebUrl::where(['name'=> 'policy-generator-sales.html'])->pluck('id');
        $testimonials = Testimonial::where(['web_urls_id' => $web_url])->get();
        $newscript = true;
        //$category = \App\PolicyGeneratorCategory::orderBy('sort_order', 'desc')->first();
        if (in_array($pageName, $pagesnew)){
            abort_if(!view()->exists('salepages.policy-sales-page.'.$pageName), 404);
            return view('salepages.policy-sales-page.'.$pageName)->with(['testimonials'=>$testimonials,'newscript'=>$newscript]);

        }else if(in_array($pageName, $pagesold)){
            // abort_if(!view()->exists('salepages.policy-sales-page.'.$pageName), 404);
            return view('errors.404');
        }
        else{

            $redirectedPages = [
                'gdpr-privacy-policy-generator' => 'privacy-policy',
                // 'terms-and-conditions-policy-sales-page' => 'terms-and-conditions-policy',
                'privacy-notice-policy-sales-page' => 'privacy-policy',
                // 'disclaimer-policy-sales-page' => 'disclaimer-policy',
                'work-from-home-policy' => 'privacy-policy',
                'cctv-policy-sales-page' => 'cctv-policy',
                'consent-policy-sales-page' => 'privacy-policy',
                'cookie-policy-sales-page' => 'cookie-policy',
                // 'data-breach-policy-sales-page' => 'data-breach-policy',
                'data-protection-policies-for-organisation' => 'data-protection-policy',
                'data-protection-policies-for-website' => 'data-protection-policy',
                // 'data-protection-policy-sales-page' => 'data-protection-policy',
                'data-retention-policy-sales-page' => 'data-retention-policy',
                'data-subject-access-request-sales-page' => 'data-protection-policy',
                'information-security-policy-sales-page' => 'information-security-policy',
                'privacy-notice-policy' => 'privacy-policy',
            ];

            return redirect()->route('policies-sales-pages', ['path' => $redirectedPages[$pageName]]);
//            abort(404);
        }
    }
    public function assessmentToolkits(){
        $assessmentPricePlan = Plan::where(['product_id' => 4, 'name' => 'platinum'])->value('price');
        $toolkits = \App\Models\Toolkit::where('sort_order', '>', '0')->orderBy('sort_order')->get();
        // $activePlans = \App\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
        $page = request()->path();
        $web_url = WebUrl::where(['name'=> $page])->pluck('id');
        $testimonials = Testimonial::where(['web_urls_id' => $web_url])->get();
        $newscript = true;
        return view('business.assessments-toolkits')->with(['toolkits' => $toolkits,'testimonials'=>$testimonials,'newscript' => $newscript, 'assessmentPricePlan' => $assessmentPricePlan]);
    }

    public function assessmentCyberSecure(){
        $cyberSecures = \App\Models\CyberSecure::where('sort_order', '>', '0')->orderBy('sort_order')->get();
        $page = request()->path();
        $web_url = WebUrl::where(['name'=> $page])->pluck('id');
        $testimonials = Testimonial::where(['web_urls_id' => $web_url])->get();
        // $activePlans = \App\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
        // dd($testimonials);
        $newscript = true;
        return view('business.assessments-cyber-secures')->with(['cyberSecures' => $cyberSecures,'testimonials'=>$testimonials,'newscript'=>$newscript]);
    }

    public function pecrAudit(){
        // $activePlans = \App\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
        $newscript = true;
        return view('business.pecr-sales')->with(['newscript'=>$newscript]);
    }

    public function gdprStaffEtraining(){
        // $activePlans = \App\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
        $page = request()->path();
        $web_url = WebUrl::where(['name'=> $page])->pluck('id');
        $testimonials = Testimonial::where(['web_urls_id' => $web_url])->get();
        $newscript = true;
        return view('business.gdpr-staff-e-training')->with(['testimonials'=>$testimonials,'newscript'=> $newscript]);
    }

    public function elearningSales(){
        return redirect()->route('gdpr-staff-e-training');
        $activePlans = \App\Models\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
        return view('business.elearning-sales')->with(['activePlans' => $activePlans]);
    }

    public function gdprAudit(){
        $toolkits = \App\Models\Toolkit::where('sort_order', '>', '0')->orderBy('sort_order')->get();
        return view('business.assessments-toolkits')->with(['toolkits' => $toolkits]);
    }

    // public function pricePlan(){
    //     $activePlans = \App\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
    //     return view('price-plan')->with(['activePlans' => $activePlans]);
    // }

    public function pricePlan(){
        if(App::environment('production')){
            if(\Session::get('internal')==null || \Session::get('internal')!=true){
            $ip = $_SERVER['REMOTE_ADDR'];
            rescue(function() use($ip){

                $dataArray = json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=".$ip));

                $is_brazil = false;
            if($dataArray->geoplugin_countryName === "Brazil"){
                $is_brazil = true;
                return redirect()->route('price-plan-lang',['br']);
            }
            if($dataArray->geoplugin_countryName === "France"){
                $is_france = true;
                return redirect()->route('price-plan-lang',['fr']);
            }
            if($dataArray->geoplugin_countryName === "Germany"){
                $is_france = true;
                return redirect()->route('price-plan-lang',['de']);
            }
            if($dataArray->geoplugin_countryName === "Spain"){
                $is_france = true;
                return redirect()->route('price-plan-lang',['es']);
            }
            },function(){
                },true);
        }
    }
        $activePlans = \App\Models\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
        $data=[
            'is_login' => Auth::check() ? true : false,
            'lang' => 'en-uk',
            'region'=> 'en'
        ];
        return view('price-plan-new')->with($data);
    }
    public function pricePlanEn()
{
    \Session::put('internal', true);
    // \Session::forget('internal');
    return redirect()->route('price-plan');
}

    public function pricePlanLang($lang){
        if($lang == 'business'){
            $lang = 'en-uk';
        }
        $data=[
            'is_login' => Auth::check() ? true : false,
            'region' => $lang,
            'lang' => $lang
        ];
        return view('price-plan-new')->with($data);
        // switch($lang){
        //     case 'br':
        //         return view('price-plan-br')->with($data);
        //     break;
        //     case 'de':
        //         return view('price-plan-de')->with($data);
        //     break;
        //     case 'fr':
        //         return view('price-plan-fr')->with($data);
        //     break;
        //     case 'en-us':
        //         return view('price-plan-new')->with($data);
        //     break;
        //     case 'en-uk':
        //         return view('price-plan-new')->with($data);
        //     break;
        //     default:
        //      return view('price-plan-new')->with($data);
        // }
    }

    // public function pricePlanBr(){
    //     $data=[
    //         'is_login' => Auth::check() ? true : false,
    //         'region' => 'br'
    //     ];
    //     return view('price-plan-br')->with($data);
    // }
    // public function pricePlanDe(){
    //     $data=[
    //         'is_login' => Auth::check() ? true : false,
    //         'region' => 'de'
    //     ];
    //     return view('price-plan-de')->with($data);
    // }
    // public function pricePlanFr(){
    //     $data=[
    //         'is_login' => Auth::check() ? true : false,
    //         'region' => 'fr'
    //     ];
    //     return view('price-plan-fr')->with($data);
    // }

    public function pricePlanNew(){
        $activePlans = \App\Models\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
        $data=[
            'is_login' => Auth::check() ? true : false
        ];

//        dd($data);
        return view('new-price-plan-new')->with($data);
    }

    // public function contact(){
    //     return view('contact');
    // }

    public function contact(){

        return view('contact');
    }

    public function joinPrivacyExpertsPlatform() {
        return view('join-privacy-experts-platform');
    }
    public function benefitsAndFeatures(){
        return view('benefits-and-features');
    }

    public function howItWorks(){
        return view('how-it-works');
    }

    public function howItWorksBusiness(){
        return redirect()->route('about-us');
        return view('how-it-works-business');
    }

    public function howItWorksAdvisor(){
        return redirect()->route('join-privacy-experts-platform');
        return view('how-it-works-advisor');
    }

    public function comingSoon(){
        return view('coming-soon');
    }

    public function listOfDPAs(){
        $dpalists = \App\Models\DpaList::orderBy('title', 'asc')->get();
        $updated = \App\Models\DpaList::latest()->first();
        $updated_at = \Carbon\Carbon::parse($updated->updated_at)->format('d M Y');
        return view('list-of-dpas')->with(compact('dpalists', 'updated_at'));
    }

    public function career(){
        $department = \App\Models\Department::first();
        $allDepartments = \App\Models\Department::with('DepartmentSeats')->get();
        $job = DepartmentSeat::first();
        return view('career')->with(compact('department', 'allDepartments','job'));
    }

    public function showJobs($jobTitle){
        $allDepartments = \App\Models\Department::all();
        $department = \App\Models\Department::where('title', '=', $jobTitle)->first();
        if(!$department)
            return view('errors.404');
        return view('career')->with(compact('department', 'allDepartments'))->with('title', $jobTitle);
    }
    public function showJobswithId($title,$id){
        $job = DepartmentSeat::where('id', '=', $id)->first();
        $department = \App\Models\Department::first();
        $allDepartments = \App\Models\Department::with('DepartmentSeats')->get();
        if(!$job)
            return view('errors.404');
        return view('career')->with(compact('department', 'allDepartments','job'))->with('title', $job->title);
    }

    public function icoResources(){
        return view('ico-resources');
    }
    public function icoResource(){
        return redirect()->route('ico-resources');
    }

    public function pageNotFound(){
        return view('errors.404');
    }

    public function registerWithTypeAndSlug($type, $slug){
        if (Auth::User() != null) {
            if (Auth::User()->isBusiness) {
                return redirect(route('business.dashboard'));
            } else if (Auth::User()->isAdvisor) {
                return redirect(route('advisor.dashboard'));
            }
        }
        return view('registration', ['type' => $type, 'slug' => $slug]);
    }

    public function registerWithType($type){
        if (Auth::User() != null) {
            if (Auth::User()->isBusiness) {
                return redirect(route('business.dashboard'));
            } else if (Auth::User()->isAdvisor) {
                return redirect(route('advisor.dashboard'));
            }
        }
        return view('registration', ['type' => $type]);
    }

    public function register(){
        if (Auth::User() != null) {
            if (Auth::User()->isBusiness) {
                return redirect(route('business.dashboard'));
            } else if (Auth::User()->isAdvisor) {
                return redirect(route('advisor.dashboard'));
            }
        }
        return redirect(route('register', ['type' => 'business']));
    }

    public function cookieDirectory(Request $request){
        $data = $request->all();
        //dd($data);
        //return view('cookie-directory');
        if(count($data) == 0) {
//            return view('cookie-directory')->with(['cookieSearch'=>null]);
            return view('cookie-directory-new')->with(['cookieSearch'=>null]);
        }else{
            //dd($data["cookieSearch"]);
            $cbCookie = CbCookies::where(['name'=>$data["cookieSearch"]])->first();
            if(isset($cbCookie)) {
                $cookieResult = CookieBank::where(['name' => $cbCookie->name])->first();
                if(isset($cookieResult->id)){
                    $cookiePaginate = $cookieResult->CookieBankDomains()->paginate(50);
                }else{
                    $cookieResult = null;
                    $cookiePaginate = null;
                }
            }else{
                $cookieResult = null;
                $cookiePaginate = null;
            }
            //dd($cookiePaginate);
//            return view('cookie-directory')->with(['Search'=>$data["cookieSearch"],'cbCookie'=>$cbCookie, 'cookieSearch'=>$cookieResult, '_token'=>$data['_token'], 'cookiePaginate'=>$cookiePaginate]);
            return view('cookie-directory-new')->with([
                'Search' => $data["cookieSearch"],
                'cbCookie' => $cbCookie,
                'cookieSearch' => $cookieResult,
                '_token' => $data['_token'],
                'cookiePaginate' => $cookiePaginate
            ]);
        }
    }

    // public function wordpressCookiePlugin(){
    //     $activePlans = \App\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
    //     return view('wordpress-cookie-plugin')->with(['activePlans' => $activePlans, 'data_control' => 'data_control']);
    // }

    public function wordpressCookiePlugin(){
        $page = request()->path();
        $web_url = WebUrl::where(['name'=> $page])->pluck('id');
        $testimonials = Testimonial::where(['web_urls_id' => $web_url])->get();
        $newscript = true;
        // $activePlans = \App\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
        return view('wordpress-cookie-plugin')->with(['testimonials' => $testimonials, 'data_control' => 'data_control','newscript'=>$newscript]);
    }

    public function wordpressCookiePluginMarketplace(){
        return redirect()->route('wp-cookie-plugin');
        $activePlans = \App\Models\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
        return view('wordpress-cookie-plugin-marketplace')->with(['activePlans' => $activePlans, 'data_control' => 'data_control']);
    }

    public function ppcPages($slug){
        $actualSlug = str_slug($slug);
        return view('cookie-consent-ppc-page')->with('slug', $slug);
    }

    public function cookieConsentBannerCcpa(){
        return redirect()->route('ccpa-sales-page');
        $activePlans = \App\Models\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
        return view('cookie-consent-banner-ccpa')->with(['activePlans' => $activePlans, 'cookie_consent' => 'cookie_consent']);
    }

    public function awBannerHtmlJquery(){
        return redirect()->route('wp-cookie-plugin');
        $activePlans = \App\Models\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
        return view('aw-banner-html-jquery')->with(['activePlans' => $activePlans, 'data_control' => 'data_control']);
    }

    public function wpCookiePolicyGenerator(){
        return redirect()->route('wp-cookie-plugin');
        $activePlans = \App\Models\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
        return view('wp-cookie-policy-generator')->with(['activePlans' => $activePlans, 'policy_generator' => 'policy_generator']);
    }

    public function privacyTemplate(){
        $page = request()->path();
        $web_url = WebUrl::where(['name'=> $page])->pluck('id');
        $testimonials = Testimonial::where(['web_urls_id' => $web_url])->get();
        $newscript = true;
        return view('salepages.privacy-template')->with(['testimonials' => $testimonials, 'privacy_templates' => 'privacy_templates','newscript'=>$newscript]);
    }

    public function gdprFinesAndCompliance(){
        return redirect()->route('assessmentToolkits');
        $activePlans = \App\Models\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
        return view('gdpr-fines-and-compliance')->with(['activePlans' => $activePlans, 'privacy_templates' => 'privacy_templates']);
    }

    public function gdprFinesAndComplianceV2(){
        return redirect()->route('assessmentToolkits');
        $activePlans = \App\Models\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
        return view('gdpr-fines-and-compliance-v2')->with(['activePlans' => $activePlans, 'privacy_templates' => 'privacy_templates']);
    }

    public function awBannerWebsiteChecker(){
        return redirect()->route('indexCookieXray');
        $activePlans = \App\Models\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
        return view('aw-banner-website-checker')->with(['activePlans' => $activePlans, 'data_control' => 'data_control']);
    }

    public function gdprCookieConsent(){
        return redirect()->route('indexCookieXray');
        $activePlans = \App\Models\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
        return view('gdpr-cookie-consent')->with(['activePlans' => $activePlans, 'data_control' => 'data_control']);
    }

    public function awBannerWebsiteCookieScanner(){
        return redirect()->route('indexCookieXray');
        $activePlans = \App\Models\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
        return view('aw-banner-website-cookie-scanner')->with(['activePlans' => $activePlans, 'data_control' => 'data_control']);
    }


    public function sendTrialEmail(Request $request)
    {
        $user = \App\Models\User::where('email', $request->get('email'))->first();
        if($user && ($user->on_trial == 1)){
            Mail::to($user->email)->bcc(config('app.hubspot_bcc'))->send(new PricePlanDeactive($user, $request->get('emailday')));
        } else {
            dd('user not found');
        }
    }

    // public function cookiePolicy(){
    //     return view('cookie-policy');
    // }
    public function cookiePolicy(){
        $newscript = true;
        return view('cookie-policy')->with(['newscript'=>$newscript]);
    }



    public function pluginIndex(){
        return view('plugin');
    }

    public function showPlugin($pageName)
    {
        $pages = [
            'how-to-implement-cookie-consent-dialogue-and-privacy-policy-in-weebly-website',
            'how-to-install-cookie-consent-banner-and-privacy-policy-in-joomla-website',
            'how-to-install-cookie-consent-banner-and-privacy-policy-in-magento-website',
            'how-to-install-cookie-consent-plugin-in-drupal',
            'how-to-install-cookie-consent-banner-and-privacy-policy-in-prestashop-website',
            'how-to-install-cookie-consent-banner-and-privacy-policy-in-square-space-website',
            'how-to-install-cookie-consent-banner-and-privacy-policy-in-blogger-website',
            'how-to-install-cookie-consent-banner-and-privacy-policy-in-wordpress-website',
            'how-to-install-cookie-consent-banner-and-privacy-policy-in-wix-website',
            'how-to-install-cookie-consent-banner-and-privacy-policy-in-lightspeed-website',
            'how-to-install-cookie-consent-banner-and-privacy-policy-in-ecwid',
            'how-to-install-cookie-consent-banner-and-privacy-policy-in-bigcommerce-website',
            'how-to-install-cookie-consent-banner-and-privacy-policy-in-godaddy-website',
            'how-to-install-cookie-consent-banner-and-privacy-policy-in-3dcart-website',
            'how-to-install-cookie-consent-banner-and-privacy-policy-in-storenvy-website',
            'how-to-install-cookie-consent-banner-and-privacy-policy-in-shopify-website',
          'how-to-install-cookie-consent-banner-and-privacy-policy-using-google-tag-manager',
        ];

        if(in_array($pageName, $pages)){
            return view($pageName);
        } else {
            return view('errors.404');
        }

    }

    public function seersCookieConsentUserGuide(){
        return view('seers-cookie-consent-user-guide');
    }

    public function implementationOfSeersCookieConsentBanner(){
        return view('implementation-of-seers-cookie-consent-banner');
    }


    public function blogVideos(){
        $videos = \App\Models\Video::orderBy('created_at', 'desc')->paginate(9);
        return view('show-blog-videos')->with('videos', $videos);
    }

    public function blogVideosloadmore(Request $request){
        $videos = \App\Models\Video::orderBy('created_at', 'desc')->paginate(6);
        $html='';
        $counter = 0;
        foreach($videos as $video)
        {
            $counter++;
            if($counter %2 == 0){
                $html.= '<div class="col-md-2 col-lg-2 col-xl-2 p-0" >
                <div class="progressbar-hol w-100 h-100">
                    <div class="progress-line mb-5">
                    </div>
                </div>
                </div>';

            }
                $html.= '<div class="col-12 col-sm-12 col-md-5 col-lg-5 col-xl-5 p-0">
                        <ul class="list-unstyled cards">
                        <li class="cards_item mb-4 mb-sm-4 mb-md-4 mb-lg-5 mb-xl-5 ' .($counter %2 == 0? "mt-0 mt-sm-0 mt-md-4 mt-lg-5 mt-xl-5" : ""). ' box-shadow-same">
                            <div class="card border-0">
                            <div class="card_image img-responsive">
                                <iframe width="100%" height="250" src="'.$video->url.'" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen=""></iframe>
                            </div>
                            <div class="card_content p-4 mt-n2">
                                <a href="/seers-video/'.$video->slug.'" class="video-Category text-capitalize">'.$video->title.'</a>
                            </div>
                            </div>
                            </li>
                        </ul>
                    </div>';
        }
        if ($request->ajax()) {
            return $html;
        }


        return view('show-blog-videos-new')->with('videos', $videos);
    }

    public function singleBlogVideo($slug){
        if($slug == "null"){
            dd("No record found against this slug");
        }

        $video = \App\Models\Video::where(['slug'=>$slug])->first();

        //dd($video->category);
        $relatedVideos = "null";
        if($video){
            $relatedVideos = \App\Models\Video::where('id', '!=', $video->id)->where(['category'=>$video->category])->orderBy("updated_at", "desc")->limit("2")->get();
        }
        return view('single-blog-video')->with(['video'=>$video, "relatedVideos"=>$relatedVideos]);
    }

    public function squareSpacePlugin()
    {
        return view('square-space-plugin');
    }

    public function productGroups()
    {
        return view('product-groups');
    }

    public function elearningSalesNewPage() {
        return redirect()->route('gdpr-staff-e-training');
        $product = \App\Models\Product::whereName('gdpr-training')->first();
        return view('elearning-sales-new-page')->with('product', $product);
    }

    public function downloadPlugin ($name) {

        if ($name=='wordpress')
        {
            $plugin_name = 'seers-cookie-consent-banner-privacy-policy-' . ($name);
        }
        else
        {
            $plugin_name = 'Seers_Cookie_Consent_Banner_and_Privacy_Policy_' . ucwords($name);
        }

        $plugin_name_zip = $plugin_name . '.zip';

        if(file_exists(storage_path('plugins/'.$plugin_name_zip))) {
            $file = storage_path('plugins/'.$plugin_name_zip);
            return response()->download($file, $plugin_name_zip);
        } else {
            return response()->json('file not found', 201);
        }
    }

    public function newPricing() {
        return view('new-pricing');
    }

    public function checkExistingUser($email) {
        $user = \App\Models\User::where(['email'=>$email])->first();
        if($user === null) {
            // doesn't exist
            return response()->json(['1' => 1], 200);
        } else {
            // already exists a user
            return response()->json(['0' => 0], 401);
        }
    }

    public function setProductInSession(Request $request) {
        if (session()->has('products')) {
            $products = session()->get('products');
            $realProducts = [];
            $found = false;
            foreach ($products as $pro) {
                if($pro->display_name == $request->get('name')) {
                    if($pro->display_name == 'GDPR Staff Training') {
                        $pro->numberOfMembers = $request->get('numberOfMembers');
                    } else {
                        $pro->plan_id = $request->get('plan_id');
                    }
                    $found = true;
                }
                array_push($realProducts, $pro);
            }
            if (!$found) {
                $product = \App\Models\Product::where(['display_name' => $request->get('name')])->first();
                if($product->display_name == 'GDPR Staff Training') {
                    $product->numberOfMembers = $request->get('numberOfMembers');
                } else {
                    $product->plan_id = $request->get('plan_id');
                }
                array_push($realProducts, $product);
            }
        } else {
            $realProducts = [];
            $product = \App\Models\Product::where(['display_name' => $request->get('name')])->first();
            if($product->display_name == 'GDPR Staff Training') {
                $product->numberOfMembers = $request->get('numberOfMembers');
            }
            array_push($realProducts, $product);
        }
        session()->put('products', $realProducts);
        return response()->json([
            'success' => 1,
            'session_products' => session()->get('products')
        ], 200);
    }

    public function removeProduct (Request $request) {
        $products = session()->get('products');
        $counter = 0;
        foreach ($products as $product) {
            if($product->display_name == $request->get('display_name')) {
                array_splice($product[$counter], 1, 1);
            }
            $counter++;
        }
        return response()->json([
            'success' => $request->all(),
            'products' => $products
        ], 200);
    }


    public function newEURepPage() {
        $page = request()->path();
        $web_url = WebUrl::where(['name'=> $page])->pluck('id');
        $testimonials = Testimonial::where(['web_urls_id' => $web_url])->get();
        $newscript = true;
        return view('new-eu-rep-page', compact('testimonials','newscript'));
    }

    public function pressRelease()
    {
        $pressReleases = PressRelease::where("release_on", "<=", Now())->orderBy("release_on", "desc")->orderBy("created_at", "desc")->paginate(4);
        return view('press-release')->with('pressReleases', $pressReleases);
    }

    public function singlePressRelease($slug = '')
    {
        $pressRelease = PressRelease::where(['slug'=>$slug])->first();
        return view('single-press-release')->with('pressRelease', $pressRelease);
    }

    public function newCookieScanReportPDF()
    {
        $pdf = PDF::loadView('business.cookiebot.newcookiescanreport_pdf');
//        $pdf->SetProtection(['copy', 'print'], '', 'pass');
        return $pdf->download('Cookie Scan Report.pdf');
    }

    public function bookEuRepresentative() {
        return view('book_eu_representative');
    }

    public function thankYouEuRepresentative(){
        return view('thank-you-eu-representative');
    }

    public function contactUsThankYou(){
        return view('contact-us-thank-you');
    }

     public function lpGiveAwayAbookFinesNCompliance(){
        return view('lp-give-away-ebook-fines-n-compliance');
    }

    public function dpiaSalesPage() {
        $page = request()->path();
        $web_url = WebUrl::where(['name'=> $page])->pluck('id');
        $testimonials = Testimonial::where(['web_urls_id' => $web_url])->get();
        $newscript = true;
        return view('dpia-sales-page', compact('testimonials','newscript'));
    }

    public function thankYouForCheckout() {
        return view('thank-you-for-checkout');
    }


    public function ccpaSalesPage() {
        $page = request()->path();
        $web_url = WebUrl::where(['name'=> $page])->pluck('id');
        $testimonials = Testimonial::where(['web_urls_id' => $web_url])->get();
        $newscript = true;
        return view('ccpa-sales-page',compact('testimonials','newscript'));
    }

    public function newHomePage() {
        $testimonials = Testimonial::all();
        return view('new-homepage', compact('testimonials'));
    }

    public function newAboutUs() {
        return view('new-about-us');
    }
    public function newAboutUsNew() {
        return view('new-about-us-new');
    }

    public function cookiePolicyHTML(){
        $product = FaqProduct::where('slug', 'Cookies-Knowledge')->first();
        $productFaqs = $product->faqs;
//        return view('knowledge-base');
        return view('cookie-policy-generator-2', compact('productFaqs'));
    }

    public function dpo(){
        $product = FaqProduct::where('slug', 'Cookies-Knowledge')->first();
        $productFaqs = $product->faqs;
//        return view('knowledge-base');
        return view('dpo', compact('productFaqs'));
    }

    public function thankYouUserGuide()
    {
        return view('thank-you-user-guide-new');
    }

    public function thankYouUserGuideForDpo()
    {
        return view('thank-you-give-away-ebook-for-dpo');
    }

    public function showGDPRCookieConsentPricePage() {
        $product = Product::whereNameAndIsActive('cookie_consent', true)->first();

        $user = Auth::User();
        if($user){
            $user_product = $user->products()->whereNameAndIsActive('cookie_consent', true)->first();
            $purchaseplan = $user_product ? $user_product->plan->name : null;
        }else{
            $purchaseplan = null;
        }
        return view('gdpr-cookie-consent-pricing', compact('product','purchaseplan'));
    }

    public function showCookieConsentProductsPlans()
    {
        $realProduct = [];

        $product = Product::where([
            'name' => 'cookie_consent',
            'is_active' => 1
        ])->with(['plans' => function ($query){
            $query->whereIsActive(true)->orderBy('sort_order', 'asc')->select('id', 'name', 'display_name', 'price', 'product_id', 'is_featured','price_id_monthly','price_id_yearly')
                ->with(['features' => function ($query){
                    $query->whereIsVisible(true)->orderBy('sort_order', 'asc')->select('id', 'name', 'display_name', 'value', 'plan_id', 'is_visible');
            }]);
        }])->first();

        // $user = Auth::User();
        // $tenure = null;
        // if($user){
        //     $user_product = $user->products()->whereNameAndIsActive('cookie_consent', true)->first();
        //     $purchaseplan = $user_product ? $user_product->plan->name : array();
        //     if($user_product){
        //         $purchased_on = new \DateTime($user_product->plan->purchased_on);
        //         $expired_on =  new \DateTime($user_product->plan->expired_on);
        //         $diff = date_diff($purchased_on, $expired_on)->format('%a');
        //         $tenure = $diff == 365 ? 'yearly' : 'monthly';
        //     }

        // }else{
            $purchasedPlan = array();
            $tenure = null;
            if (auth()->check()) {
            $uProduct = auth()->user()->products()->whereNameAndIsActive('cookie_consent', true)->first();
            if ($uProduct) {
                $tenure = $uProduct->recursive_status;
                $purchasedPlan = $uProduct->plan->name;
            }
        }
        // array_push($realProduct, $product);
        $faq = FaqProduct::with('faqs')->where(['slug' => 'Cookies-Knowledge'])->orderBy('created_at', 'DESC')->first();
        return response()->json([
            'success' => true,
            'product' => $product,
            'purchasedPlan' => $purchasedPlan,
            'tenure' => $tenure,
            'faqs' => $faq->faqs,
            // 'plans'=>$product->plan(true),
        ], 200);
    }

    public function getCountry () {

        if (!request()->ajax()) {
            return response(['Bad Request'], 400);
        }
        $ip = request()->ip();
        try {
            $query = "SELECT `code`,`countryName` FROM ip2countrylist WHERE INET_ATON('$ip') BETWEEN `ip_range_start_int` AND `ip_range_end_int` LIMIT 1";
            $country_code = DB::connection('mysql2')->select($query);
            $country_code = $country_code && $country_code[0]->code != '-' ? $country_code : null;
            return response(['country' => $country_code], 200);
        } catch (\Excecption $e) {
            return response(['country' => null], 200);
        }
    }

    public function showAssessmentPackPricing()
    {
//        $realProduct = [];
        // $product = Product::whereNameAndIsActive('assessment', true)->first();
        $product = Product::where(['name' => 'assessment', 'is_active' => 1])
            ->with(['plans' => function ($query) {
                $query->whereIsActive(true)->orderBy('sort_order', 'asc')->select('id', 'name', 'display_name', 'price', 'product_id', 'is_featured','is_active','price_id_yearly','price_id_monthly')
                    ->with(['features' => function ($query) {
                        $query->whereIsVisible(true)->orderBy('sort_order', 'asc')->select('id', 'name', 'display_name', 'value', 'plan_id', 'is_visible', 'description','is_active');
                }]);
        }])->first();

        // $user = Auth::User();
        // $tenure = null;
        // if($user){
        //     $user_product = $user->products()->whereNameAndIsActive('assessment', true)->first();
        //     $purchaseplan = $user_product ? $user_product->plan->name : array();
        //     if($user_product) {
        //         $purchased_on = new \DateTime($user_product->plan->purchased_on);
        //         $expired_on = new \DateTime($user_product->plan->expired_on);
        //         $diff = date_diff($purchased_on, $expired_on)->format('%a');
        //         $tenure = $diff == 365 ? 'yearly' : 'monthly';
        $purchasedPlan = '';
        $tenure = '';

        if (auth()->check()) {
            $uProduct = auth()->user()->products()->whereNameAndIsActive('assessment', true)->first();
            if ($uProduct) {
                $tenure = $uProduct->recursive_status;
                $purchasedPlan = $uProduct->plan->name;
            }
        // }else{
        //     $purchaseplan = array();
        //     $tenure = null;
        }
//        array_push($realProduct, $product);
        //array_push($realProduct, $purchaseplan);

            // $faq = FaqProduct::with('faqs')->where(['slug' => 'assessment-and-policies'])->orderBy('created_at', 'DESC')->first();
            $faq = FaqProduct::with('faqs')->where(['slug' => 'assessment-and-policies'])->orderBy('created_at', 'desc')->first();

        return response()->json([
            'success' => true,
            'product' => $product,
            'faqs' => $faq?$faq->faqs:[],
            'tenure' => $tenure,
            'purchasedPlan' => $purchasedPlan,
            //'plans'=>$product->plan(true),
        ], 200);
    }
    public function showDpiaPricingPlans()
    {
        // $realProduct = [];
        // $product = Product::whereNameAndIsActive('dpia', true)->first();
        $product = Product::where(['name' => 'dpia', 'is_active' => 1])
            ->with(['plans' => function ($query) {
                $query->whereIsActive(true)->orderBy('sort_order', 'asc')->select('id', 'name', 'display_name', 'price', 'product_id', 'is_featured')
                    ->with(['features' => function ($query) {
                        $query->whereIsVisible(true)->orderBy('sort_order', 'asc')->select('id', 'name', 'display_name', 'value', 'plan_id', 'is_visible');
                }]);
        }])->first();

        // $user = Auth::User();
        // $tenure = null;
        // if($user){
        //     $user_product = $user->products()->whereNameAndIsActive('dpia', true)->first();
        //     $purchaseplan = $user_product ? $user_product->plan->name : array();
        //     if($user_product) {
        //         $purchased_on = new \DateTime($user_product->plan->purchased_on);
        //         $expired_on = new \DateTime($user_product->plan->expired_on);
        //         $diff = date_diff($purchased_on, $expired_on)->format('%a');
        //         $tenure = $diff == 365 ? 'yearly' : 'monthly';
        //     }
        // }else{
        //     $purchaseplan = array();
        //     $tenure = null;
        $purchasedPlan = '';
        $tenure = '';

        if (auth()->check()) {
            $uProduct = auth()->user()->products()->whereNameAndIsActive('cookie_consent', true)->first();
            if ($uProduct) {
                $tenure = $uProduct->recursive_status;
                $purchasedPlan = $uProduct->plan->name;
            }
        }
        // array_push($realProduct, $product);
        //array_push($realProduct, $purchaseplan);
        $faq = FaqProduct::with('faqs')->where(['slug' => 'data-protection-impact-assessment-knowledge'])->orderBy('created_at','DESC')->first();
        return response()->json([
            'success' => true,
            'product' => $product,
            'faqs' => $faq->faqs,
            'tenure' => $tenure,
            'purchasePlan' => $purchasedPlan,
        ], 200);
    }
    public function showSrmPlans()
    {
        // $realProduct = [];

        // $product = Product::where([
        //     'name' => 'subject_request_management',
        //     'is_active' => 1
        // ])->with(['plans' => function ($query){
        //     $query->whereIsActive(true)->orderBy('sort_order', 'asc')
        //         ->with(['features' => function ($query){
        //             $query->whereIsActiveAndIsVisible(true,true)->orderBy('sort_order', 'asc');
        $product = Product::where(['name' => 'subject_request_management', 'is_active' => 1])
            ->with(['plans' => function ($query) {
                $query->whereIsActive(true)->orderBy('sort_order', 'asc')->select('id', 'name', 'display_name', 'price', 'product_id', 'is_featured')
                    ->with(['features' => function ($query) {
                        $query->whereIsVisible(true)->orderBy('sort_order', 'asc')->select('id', 'name', 'display_name', 'value', 'plan_id', 'is_visible');
                }]);
        }])->first();

        // $user = Auth::User();
        // if($user){
        //     $user_product = $user->products()->whereNameAndIsActive('subject_request_management', true)->first();
        //     $purchaseplan = $user_product ? $user_product->plan->name : array();
        // }else{
        //     $purchaseplan = array();
        $purchasedPlan = '';
        $tenure = '';

        if (auth()->check()) {
            $uProduct = auth()->user()->products()->whereNameAndIsActive('subject_request_management', true)->first();
            if ($uProduct) {
                $tenure = $uProduct->recursive_status;
                $purchasedPlan = $uProduct->plan->name;
            }
        }
        // array_push($realProduct, $product);
        $faq = FaqProduct::with('faqs')->where(['slug' => 'dsar'])->orderBy('created_at','DESC')->first();
        return response()->json([
            'success' => true,
            'product' => $product,
            'tenure' => $tenure,
            'purchasedPlan' => $purchasedPlan,
            'faqs' => $faq->faqs ?? [],
            // 'plans'=>$product->plan(true),
        ], 200);
    }
    public function showBmsPlans()
    {
        // $realProduct = [];

        // $product = Product::where([
        //     'name' => 'breach_management_system',
        //     'is_active' => 1
        // ])->with(['plans' => function ($query){
        //     $query->whereIsActive(true)->orderBy('sort_order', 'asc')
        //         ->with(['features' => function ($query){
        //             $query->whereIsActiveAndIsVisible(true,true)->orderBy('sort_order', 'asc');
        $product = Product::where(['name' => 'breach_management_system', 'is_active' => 1])
            ->with(['plans' => function ($query) {
                $query->whereIsActive(true)->orderBy('sort_order', 'asc')->select('id', 'name', 'display_name', 'price', 'product_id', 'is_featured')
                    ->with(['features' => function ($query) {
                        $query->whereIsVisible(true)->orderBy('sort_order', 'asc')->select('id', 'name', 'display_name', 'value', 'plan_id', 'is_visible');
                }]);
        }])->first();

        // $user = Auth::User();
        // if($user){
        //     $user_product = $user->products()->whereNameAndIsActive('breach_management_system', true)->first();
        //     $purchaseplan = $user_product ? $user_product->plan->name : array();
        // }else{
        //     $purchaseplan = array();
         $purchasedPlan = '';
        $tenure = '';

        if (auth()->check()) {
            $uProduct = auth()->user()->products()->whereNameAndIsActive('breach_management_system', true)->first();
            if ($uProduct) {
                $tenure = $uProduct->recursive_status;
                $purchasedPlan = $uProduct->plan->name;
            }
        }
        // array_push($realProduct, $product);
        $faq = FaqProduct::with('faqs')->where(['slug' => 'dsar'])->orderBy('created_at','DESC')->first();
        return response()->json([
            'success' => true,
            // 'realProducts' => $product,
            // 'purchasePlan' => $purchaseplan,
            'tenure' => $tenure,
            'product' => $product,
            'purchasedPlan' => $purchasedPlan,
            'faqs' => $faq->faqs ?? [],
            // 'plans'=>$product->plan(true),
        ], 200);
    }
    public function getGDPRfaqs(){
        $faq = FaqProduct::with('faqs')->where(['slug' => 'gdpr-staff-training-knowledge'])->orderBy('created_at','desc')->first();
        return response()->json([
            'success' => true,
            'faqs' => $faq->faqs
        ], 200);
    }
    public function getProductPlanPrice(Request $request)
    {

        $product = Product::where([
            'display_name' => $request->name,
            'is_active' => 1
        ])->first();
        $plans = Plan::where([
            'id' =>$request->plan_id,
            'product_id'=>$product->id,
            'is_active' => 1
        ])->first();
        //array_push($product, $plan);
        return response()->json([
            'success' => true,
            'purchase_product' => $product,
            'purchase_plan' => $plans,
        ], 200);
    }
   // Cookie consent pages
    public function pecrHTML()
    {
        return redirect()->route('pecr-audit');
        return view('pecr-new-page');
    }
    public function privacyHTML()
    {
        return redirect()->route('policies-sales-pages', ['path' => 'privacy-policy']);
        return view('privacy-policy-page');
    }
    public function dpoHTML()
    {
        return redirect()->to('/advisors/Outsourced-DPO');
        return view('dpo-page');
    }
    public function gdprHTML()
    {
        return view('gdpr-training-page');
    }
    public function eurepoHTML()
    {
        return redirect()->route('eu-representative-service');
        return view('eu-representative-page');
    }
    public function newEurepoHTML()
    {
        return redirect()->route('eu-representative-service');
        return view('new-eu-repo-page');
    }
    public function newConsentHTML()
    {
        return redirect()->route('cookie-consent-management');
        return view('new-cookie-consent-page');
    }
    public function newDsaRHTML()
    {
        return redirect()->route('dsar-sales');
        return view('new-dsar-page');
    }
    public function newDpiaHTML()
    {
        return redirect()->route('dpia-sales-page');
        return view('new-dpia-page');
    }
    public function newDproHTML()
    {
        return redirect()->route('policies.custom', ['path' => 'data-protection-policy']);
        return view('new-data-protection-policy-page');
    }
    public function newDbppHTML()
    {
        return redirect()->route('policies.custom', ['path' => 'data-breach-policy']);
        return view('new-data-breach-policy-page');
    }
    public function newInfoaHTML()
    {
        return redirect()->route('policies.custom', ['path' => 'information-security-policy']);
        return view('new-information-security-page');
    }
    public function newDrppHTML()
    {
        return redirect()->route('policies.custom', ['path' => 'data-retention-policy']);
        return view('new-data-retention-policy-page');
    }
    public function newGdprpHTML()
    {
        return redirect()->route('policies.custom', ['path' => 'gdpr-policy']);
        return view('new-gdpr-policy-page');
    }
    public function newCoppHTML()
    {
        return redirect()->route('policies.custom', ['path' => 'cookie-policy']);
        return view('new-cookie-policy-page');
    }
    public function newTacpHTML()
    {
        return redirect()->route('policies.custom', ['path' => 'privacy-policy']);
        return view('term-and-condition');
    }
    public function newGdtpHTML()
    {
        return redirect()->route('gdpr-staff-e-training');
        return view('new-gdpr-training-page');
    }
    public function disclaimerHTML()
    {
        return redirect()->route('policies.custom', ['path' => 'disclaimer-policy']);
        return view('disclaimer-page');
    }
    public function auditHTML()
    {
        return redirect()->route('indexCookieXray');
        return view('cookie-audit-page');
    }
    public function cctvHTML()
    {
        return redirect()->route('policies.custom', ['path' => 'cctv-policy']);
        return view('cctv-policy-page');
    }

    public function lpGiveAwayAbookguidefordpo()
    {
        return view('lp-give-away-ebook-guide-for-dpo');
    }

    public function gdprAuditNew()
    {
        return redirect()->route('assessmentToolkits');
        return view('gdpr-audit');
    }


    public function downloadEBook($book) {
        if($book != NULL){
            $file = storage_path("file\\newsguide")."\\" . $book;
            if($file){
                return response()->download($file, $book);
            }else{
                return view('errors.404');
            }
        }
    }

    public function migrateUsersToTheirProduct()
    {
        $current_date = date('Y-m-d H:i:s');

        // Get all users with Plan > 'Free'
        $users = User::where('membership_plan_id', '>', 1)
                ->where('plan_expiry', '!=', null)
                ->where('stripe_id', '!=', null)
                ->get();

        if ($users->count() > 0) {
            foreach ($users as $user) {

                $plan_expire_on = date("Y-m-d H:i:s", strtotime("+1 ".$user->plan_expiry, strtotime($user->upgraded_at)));

                if ($plan_expire_on > $current_date) { // users those memberships are not expired yet.

                    $domains = $user->userDomains;
                    if ($domains->count() > 0) {
                      $this->migrateToCookieConsentProduct($user);
                    } else {
                        dump($user->email .' => has no domains');
                    }

                    $staffMembers = $user->staffMembers;

                    if($staffMembers->count() > 0) {
                        $this->migrateToGdprStaffTrainingProduct($user);
                    } else {
                        dump($user->email .' => has no staff members');
                    }
                } else {
                    dump($user->email.' =>  plan has been expired');
                }
            }
        }
    }

    public function migrateUsersToAssessments()
    {
        $current_date = date('Y-m-d H:i:s');
        // Get all users with Plan > 'Free'
        $users = User::where('membership_plan_id', '>', 1)
                ->where('plan_expiry', '!=', null)
                ->where('stripe_id', '!=', null)
                ->get();

        if ($users->count() > 0) {
            foreach ($users as $user) {

                $plan_expire_on = date("Y-m-d H:i:s", strtotime("+1 ".$user->plan_expiry, strtotime($user->upgraded_at)));

                if ($plan_expire_on > $current_date) { // users those memberships are not expired yet.
                    $this->migrateToAssessmentProducts($user);
                } else {
                    dump($user->email.' =>  plan has been expired');
                }
            }
        }
    }

    public function migrateSpecificUserToNewPricing($email, $strict)
    {
        $auth_user = Auth::User();
        if(!$auth_user){dump('You are not authorized for this action');return;}

        $current_date = date('Y-m-d H:i:s');

        // Get all users with Plan > 'Free'
        $user = User::where('email', '=', $email)
            ->where('membership_plan_id', '>', 1)
            ->where('plan_expiry', '!=', null)
            ->where('stripe_id', '!=', null)
            ->first();

        if ($user) {
            $plan_expire_on = date("Y-m-d H:i:s", strtotime("+1 ".$user->plan_expiry, strtotime($user->upgraded_at)));

            if ($plan_expire_on > $current_date) { // users those memberships are not expired yet.
                if($strict == 'true'){
                    $domains = $user->userDomains;
                    if ($domains->count() > 0) {
                        $this->migrateToCookieConsentProduct($user);
                    } else {
                        dump($user->email .' => has no domains');
                    }

                    $staffMembers = $user->staffMembers;

                    if($staffMembers->count() > 0) {
                        $this->migrateToGdprStaffTrainingProduct($user);
                    } else {
                        dump($user->email .' => has no staff members');
                    }
                }else{
                    $this->migrateToCookieConsentProduct($user);
                    $this->migrateToGdprStaffTrainingProduct($user);
                }
            } else {
                dump($user->email.' =>  plan has been expired');
            }
        }else{
            dump($email . ' not found in our records or this user is not eligible.');
        }
    }

    public function migrateToCookieConsentProduct (User $user) {

        $product = Product::where(['name' => 'cookie_consent', 'is_active' => 1])->first();
        $user_product = $user->currentProduct($product->name);
        $u_product = null;
        $u_plan = null;
        if (is_null($user_product)) {
            $u_product = new UProduct();
            $u_product->fill($product->toArray());
            $u_product->purchased_on = date("Y-m-d H:i:s", strtotime($user->upgraded_at));
            $u_product->expired_on = date("Y-m-d H:i:s", strtotime("+1 ".$user->plan_expiry, strtotime($user->upgraded_at)));
            $u_product->upgraded_on = $u_product->purchased_on;
            $u_product->on_trial = 0;
            $u_product->user_id = $user->id;
            $u_product->trial_days = 0;
            $u_product->recursive_status = $user->plan_expiry == 'year' ? 'yearly' : 'monthly';
            $u_product->save();

            $plan = null;
            $features = null;

            /* New plan names with Old membership_plan_id's */
            /* Old membership_plan_id => New plan name */
            $cookie_consent_plans = [
                3 => 'standard',
                4 => 'pro',
                5 => 'premium'
            ];

            $plan = $product->plans()->whereNameAndIsActive($cookie_consent_plans[$user->membership_plan_id], true)->first();

            if (!is_null($plan)) {

                $expire_on = date("Y-m-d H:i:s", strtotime("+1 ".$user->plan_expiry, strtotime($user->upgraded_at)));

                $u_plan = new UPlan();
                $u_plan->fill($plan->toArray());
                $u_plan->u_product_id = $u_product->id;
                $u_plan->purchased_on = date("Y-m-d H:i:s", strtotime($user->upgraded_at));
                $u_plan->expired_on = $expire_on;
                $u_plan->upgraded_on = $u_plan->purchased_on;
                $u_plan->save();
                $u_product->price = $u_plan->price;
                $u_product->save();

                $features = $plan->features;

                if($features->count() > 0) {
                    $features_to_be_added = [];
                    $time_stamp = Carbon::now();
                    foreach ($features->toArray() as $feature) {
                        unset($feature['id']);
                        unset($feature['plan_id']);
                        $feature['created_at'] = $time_stamp;
                        $feature['updated_at'] = $time_stamp;
                        $feature['u_plan_id']  = $u_plan->id;
                        if ($feature['name'] == 'banner_visibility_duration') {
                            $current_date = new \DateTime("now");
                            $expiry_date = new \DateTime($expire_on);
                            $days = $current_date->diff($expiry_date)->format('%a');
                            $feature['value'] = $days;
                        }
                        array_push($features_to_be_added,$feature);

//                        $u_feature = new UFeature();
//                        $u_feature->fill($feature->toArray());
//
//                        if ($u_feature->name == 'banner_visibility_duration') {
//                            $current_date = new \DateTime("now");
//                            $expiry_date = new \DateTime($expire_on);
//                            $days = $current_date->diff($expiry_date)->format('%a');
//                            $u_feature->value = $days;
//                        }
//
//                        $u_feature->u_plan_id = $u_plan->id;
//                        $u_feature->save();
                    }
                    $inserted = false;
                    if(count($features_to_be_added) > 0){
                        $inserted = UFeature::insert($features_to_be_added);
                    }
                    if($inserted){
                        $user->is_new = true;
                        $user->save();
                        dump($user->email . ' has been migrated to '. $u_product->display_name . ' product with plan: ' . $u_plan->display_name);
                        ActivityLog::add('Product Migration', $user->email . ' has migrated to ' . $u_product->display_name . ' product with plan: ' . $u_plan->display_name . ' & expiry: ' . $u_plan->expired_on, 'success', $u_product->name);
                    }
                } else {
                    dump('No features found');
                    if($u_product && $u_product->delete()){
                        if($u_plan && $u_plan->delete()){
                            dump('Product with plan is deleting ...');
                        }
                    }
                }
            } else {
                dump('No specified plan');
                if($u_product && $u_product->delete()){
                    dump('Product is deleting ...');
                }
            }
        } else {
            dump('User already have Cookie Consent product');
        }
    }

    public function migrateToGdprStaffTrainingProduct (User $user) {
        if($user->membership_plan_id <= 3){
            return;
        }

        $product = Product::where(['name' => 'gdpr_training', 'is_active' => 1])->first();
        $user_product = $user->currentProduct($product->name);
        //var_dump($user_product);
        $u_product = null;
        $u_plan = null;
        if (is_null($user_product)) {

            $u_product = new UProduct();
            $u_product->fill($product->toArray());
            $u_product->purchased_on = date("Y-m-d H:i:s", strtotime($user->upgraded_at));
            $u_product->on_trial = 0;
            $u_product->user_id = $user->id;
            $u_product->trial_days = 0;
            $u_product->recursive_status = 'none';
            $u_product->save();

            $plan = null;
            $features = null;

            /* New plan names with Old membership_plan_id's */
            /* Old membership_plan_id => New plan name */
            $cookie_consent_plans = [
                4 => 'gold',
                5 => 'platinum'
            ];

            $plan = $product->plans()->whereNameAndIsActive($cookie_consent_plans[$user->membership_plan_id], true)->first();

            if (!is_null($plan)) {

                $expire_on = date("Y-m-d H:i:s", strtotime("+1 ".$user->plan_expiry, strtotime($user->upgraded_at)));

                $u_plan = new UPlan();
                $u_plan->fill($plan->toArray());
                $u_plan->u_product_id = $u_product->id;
                $u_plan->purchased_on = date("Y-m-d H:i:s", strtotime($user->upgraded_at));
                $u_plan->expired_on = $expire_on;
                $u_plan->save();
                $u_product->price = $u_plan->price;
                $u_product->save();

                $features = $plan->features(true);

                if($features->count() > 0) {
                    $features_to_be_added = [];
                    $time_stamp = Carbon::now();
                    foreach ($features->toArray() as $feature) {
                        unset($feature['id']);
                        unset($feature['plan_id']);
                        $feature['created_at'] = $time_stamp;
                        $feature['updated_at'] = $time_stamp;
                        $feature['u_plan_id']  = $u_plan->id;

                        if($u_plan->name=='gold')
                        {
                            if($feature['name']=='min_limit'){
                                $feature['value']=1;
                            }elseif($feature['name']=='max_limit')
                            {
                                $feature['value']=10;
                            }elseif($feature['name']=='unit_price_of_user')
                            {
                                $feature['value']=25;
                            }
                        }elseif($u_plan->name=='platinum'){
                            if($feature['name']=='min_limit'){
                                $feature['value']=1;
                            }elseif($feature['name']=='max_limit')
                            {
                                $feature['value']=50;
                            }elseif($feature['name']=='unit_price_of_user')
                            {
                                $feature['value']=20;
                            }
                        }

                        array_push($features_to_be_added,$feature);

                    }
                    $inserted = false;
                    if(count($features_to_be_added) > 0){
                        $inserted = UFeature::insert($features_to_be_added);
                    }
                    if($inserted){
                        $user->is_new = true;
                        $user->save();
                        dump($user->email . ' has been migrated to '. $u_product->display_name . ' product with plan: ' . $u_plan->display_name);
                        ActivityLog::add('Product Migration', $user->email . ' has migrated to ' . $u_product->display_name . ' product with plan: ' . $u_plan->display_name . ' & expiry: ' . $u_plan->expired_on, 'success', $u_product->name);
                    }
                } else {
                    dump('No features found');
                    if($u_product && $u_product->delete()){
                        if($u_plan && $u_plan->delete()){
                            dump('Product with plan is deleting ...');
                        }
                    }
                }
            } else {
                dump('No specified plan');
                if($u_product && $u_product->delete()){
                    dump('Product is deleting ...');
                }
            }
        } else {
            dump('User already have GDPR Staff Training product');
        }
    }

    public function migrateToAssessmentProducts (User $user){
        $product = Product::where(['name' => 'assessment', 'is_active' => 1])->first();
        $user_product = $user->currentProduct($product->name);
        $u_product = null;
        $u_plan = null;
        if (is_null($user_product)) {
            $u_product = new UProduct();
            $u_product->fill($product->toArray());
            $u_product->purchased_on = date("Y-m-d H:i:s", strtotime($user->upgraded_at));
            $u_product->expired_on = date("Y-m-d H:i:s", strtotime("+1 ".$user->plan_expiry, strtotime($user->upgraded_at)));
            $u_product->upgraded_on = $u_product->purchased_on;
            $u_product->on_trial = 0;
            $u_product->user_id = $user->id;
            $u_product->trial_days = 0;
            $u_product->recursive_status = $user->plan_expiry == 'year' ? 'yearly' : 'monthly';
            $u_product->save();

            $plan = null;
            $features = null;

            /* New plan names with Old membership_plan_id's */
            /* Old membership_plan_id => New plan name */
            $cookie_consent_plans = [
                3 => 'silver',
                4 => 'gold',
                5 => 'platinum'
            ];

            $plan = $product->plans()->whereNameAndIsActive($cookie_consent_plans[$user->membership_plan_id], true)->first();

            if (!is_null($plan)) {

                $expire_on = date("Y-m-d H:i:s", strtotime("+1 ".$user->plan_expiry, strtotime($user->upgraded_at)));

                $u_plan = new UPlan();
                $u_plan->fill($plan->toArray());
                $u_plan->u_product_id = $u_product->id;
                $u_plan->purchased_on = date("Y-m-d H:i:s", strtotime($user->upgraded_at));
                $u_plan->expired_on = $expire_on;
                $u_plan->upgraded_on = $u_plan->purchased_on;
                $u_plan->save();
                $u_product->price = $u_plan->price;
                $u_product->save();

                $features = $plan->features;

                if($features->count() > 0) {
                    $features_to_be_added = [];
                    $time_stamp = Carbon::now();
                    foreach ($features->toArray() as $feature) {
                        unset($feature['id']);
                        unset($feature['plan_id']);
                        $feature['created_at'] = $time_stamp;
                        $feature['updated_at'] = $time_stamp;
                        $feature['u_plan_id']  = $u_plan->id;
                        if ($feature['name'] == 'banner_visibility_duration') {
                            $current_date = new \DateTime("now");
                            $expiry_date = new \DateTime($expire_on);
                            $days = $current_date->diff($expiry_date)->format('%a');
                            $feature['value'] = $days;
                        }
                        array_push($features_to_be_added,$feature);
                    }
                    $inserted = false;
                    if(count($features_to_be_added) > 0){
                        $inserted = UFeature::insert($features_to_be_added);
                    }
                    if($inserted){
                        $user->is_new = true;
                        $user->save();
                        dump($user->email . ' has been migrated to '. $u_product->display_name . ' product with plan: ' . $u_plan->display_name);
                        ActivityLog::add('Product Migration', $user->email . ' has migrated to ' . $u_product->display_name . ' product with plan: ' . $u_plan->display_name . ' & expiry: ' . $u_plan->expired_on, 'success', $u_product->name);
                        $user_payment_method_details = UserPaymentMethodDetails::firstOrCreate(
                            [
                                'user_id'       => $user->id,
                                'u_product_id'  => $u_product->id
                            ]);
                        $user_payment_method_details->stripe_id = $user->stripe_id;
                        $user_payment_method_details->stripe_card_type = $user->card_brand;
                        $user_payment_method_details->stripe_card_last_four_digits = $user->card_last_four;
                        $user_payment_method_details->stripe_payment_method_id = $user->payment_method;
                        $user_payment_method_details->amount_deducted = 0.00;
                        $user_payment_method_details->save();
                    }
                } else {
                    dump('No features found');
                    if($u_product && $u_product->delete()){
                        if($u_plan && $u_plan->delete()){
                            dump('Product with plan is deleting ...');
                        }
                    }
                }
            } else {
                dump('No specified plan');
                if($u_product && $u_product->delete()){
                    dump('Product is deleting ...');
                }
            }
        } else {
            dump('User already have Cookie Consent product');
        }

    }
    public function dsarSales(){
    if(App::environment('production')){
            if(\Session::get('internal')==null || \Session::get('internal')!=true){
        $ip = $_SERVER['REMOTE_ADDR'];

        rescue(function() use($ip){

            $dataArray = json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=".$ip));

        if($dataArray->geoplugin_countryName === "Brazil"){
            return redirect()->route('dsar-sales-lang',['br']);
        }
        if($dataArray->geoplugin_countryName === "Germany"){
            return redirect()->route('dsar-sales-lang',['de']);
        }
        if($dataArray->geoplugin_countryName === "France"){
            return redirect()->route('dsar-sales-lang',['fr']);
        }
        if($dataArray->geoplugin_countryName === "Spain"){
            return redirect()->route('dsar-sales-lang',['es']);
        }
        },function(){
        },true);
    }
    }
        $url = request()->path();
        $web_url = WebUrl::where(['name'=> $url])->pluck('id');
        $array = explode('.',$url);
        $name = $array[0];
        $page = Page::where(['seo_url'=>$name])->first();
        $testimonials = Testimonial::where(['web_urls_id' => $web_url])->get();
        $newscript = true;
        $lang = 'en-uk';
        if($page){
            return view('dsar-sales-page',compact('testimonials','page','newscript','lang'));
        }
        return view('dsar-sales-page',compact('testimonials','newscript','lang'));
    }

          public function dsarSalesEn()
            {
                \Session::put('internal', true);
                // \Session::forget('internal');
                return redirect()->route('dsar-sales');
            }

    public function dsarSalesLang($lang){
        $url = 'data-subject-access-requests-dsar.html';
        $web_url = WebUrl::where(['name'=> $url])->pluck('id');
        $array = explode('.',$url);
        $name = $array[0];
        $page = Page::where(['seo_url'=>$name])->first();
        $lang = $lang;
        $testimonials = Testimonial::where(['web_urls_id' => $web_url])->get();

        $newscript = true;
        if($page){
            return view('dsar-sales-page',compact('testimonials','page','newscript','lang'));
        }
        return view('dsar-sales-page',compact('testimonials','newscript','lang'));
    }



    public function covid19Page(){
        $url = request()->path();
        $web_url = WebUrl::where(['name'=> $url])->pluck('id');
        $testimonials = Testimonial::where(['web_urls_id' => $web_url])->get();
        $newscript = true;
        return view('covid19-new',compact('testimonials','newscript'));
    }


    public function icoBannerCookieConsent(){
        $url = request()->path();
        $web_url = WebUrl::where(['name'=> $url])->pluck('id');
        $testimonials = Testimonial::where(['web_urls_id' => $web_url])->get();
        $newscript = true;
        return view('ico-banner-cookie-consent',compact('testimonials','newscript'));
    }

    public function testPDF()
    {
        $pdf = PDF::loadView('test-pdf');

        return $pdf->download('Test PDF.pdf');
    }

    public function newsLetter(){
        return view('newsletter');
    }
    public function getUserEmailNewsletter(Request $request){
        //dd($request);
         $secret = config('app.RECAPTCHA_SECRET_KEY');
        $captcha = $request->token;
        $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $secret . '&response=' . $captcha);
        //dd($verifyResponse);
        $responseData = json_decode($verifyResponse);
        if($responseData->success == true){
 if($request->ajax()){
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => 'Please fill the email field'],400);
        }
        $email = $request->get('email');
        $newsletter = Newsletter::where('email', 'like', "%{$email}%")->count();
        $user = User::where('email','like',"%{$email}%")->count();
        $errors['email'][] = 'Email address is already taken!';
        if($newsletter <= 0 && $user <= 0){
            $Newsletter = new Newsletter();
            $Newsletter->email = $email;
            $Newsletter->save();
            return response()->json(['url'=>'\thank-you-newsletter'],200);
        }else{
            return response()->json(['errors'=> $errors],400);
        }
    }
    else{
        return response()->json(['errors'=>'Not a valid request'],400);
    }
        }else{
            return response()->json(['errors'=>'ReCaptcha Failed'],400);
        }

    }
    public function thankyouNewsletter(){
        return view('thank-you-newsletter');
    }

    public function areaofSpecialization(){
        return view('area-of-specialization');
    }

    public function seersGuide(){
        return view('seers-gdpr-guides');
    }
    // public function showPage($seo_url){
    //     $page = Page::where(['seo_url'=>$seo_url])->first();
    //     if($page)
    //         return view('pages')->with(['page'=>$page]);

    //     return view('404');
    // }

    public function privacyPolicyGDPR(){
        return view('privacy-policy-gdpr');
    }

    public function SiteMap(){
        return view('sitemap');
    }

    public function showJoomlaPage() {
        return view('joomla-sale-page')->with('testimonials', []);
    }

    public function showMagentoPage() {
        return view('magento-sale-page')->with('testimonials', []);
    }
    public function routeCookieConsentManagement() {
        return redirect()->route('faq.cookie-consent');
    }

    public function routeCompanyPartners() {
         return view('company-partners');
    }

    public function routeSearchCompanies($name) {
         $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, 'https://api.companieshouse.gov.uk/search/companies?q=' . urlencode($name));
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_USERPWD, "4Fu0Z8iOBX7K4GinmkwiBpccIvQeI4cS8AMx31Yh");
        $response = curl_exec($curl);
        if ($errno = curl_errno($curl)) {
            $error_message = curl_strerror($errno);
            return "cURL error ({$errno}):\n {$error_message}";
        }
        curl_close($curl);
        header("Access-Control-Allow-Origin: *");
        return $response;
    }
    public function routeAbc_pdf() {
        return view('abc_pdf');
    }
    public function routegetAvailableServices() {
        return response()->json(['sub_services' => SubService::all()]);
    }
    public function routePreRegistration() {
        return view('pre-registration');
    }
    public function routeRequestLogin() {
        return view('request-login');
    }
    public function routeWordpressFooter() {
        return view('wordpress-footer-new');
    }
    public function routeScholarshipForm() {
        return view('scholarshipform');
    }
    public function routePhpCookieConsent() {
        return view('php-cookie-consent', ['testimonials' => []]);
    }
    public function routeBreachAndIncidentManagement() {
        return view('breach-and-incident');
    }
    public function routeChildPrivacyConsentManagement() {
        return view('child-privacy-consent-management-page');
    }
    public function routeDataDiscovery() {
        return view('data-discovery-page');
    }
  public function routeDataMapping() {
        return view('data-mapping-page');
    }

    public function routeVendorRiskManagement() {
        return view('vendor-risk-management-page');
    }
    public function routeCookieConsent() {
        return view('cookie-consent.index');
    }
    public function routePayementMethodPopup() {
        return view('payement-method-popup');
    }
    public function routesubscription() {
        return view('errors.subscription');
    }
    // review page
    public function reviewUsAndGetRewarded() {
        return view('review-us');
    }
    // public function requestLoginPage(){
    //     return view('request-login-page');
    // }
}


//CustomPagesController@
