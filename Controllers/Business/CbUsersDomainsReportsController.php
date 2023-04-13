<?php

namespace App\Http\Controllers\Business;

use App\Models\CbCookieCategories;
use App\Models\CbCookieRisks;
use App\Models\CbCookies;
use App\Models\CbDomainLinks;
use App\Http\Controllers\Controller;
use App\Mail\CookiebotReportMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

use Auth;
use App\Models\CbUsersDomains;
use App\Models\CbReportsReceivers;
use PDF;


use App\Http\Requests;
use Illuminate\Http\Request;
use App\Models\CbUsersDomainsReports;


class CbUsersDomainsReportsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('business');
        $report = '';
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('business.cookiebot.teaser_report');
    }

    public function getReports($id){


        $domainReports = CbUsersDomainsReports::Where([ 'user_id'=> Auth::User()->id,'dom_id'=>$id, 'enabled'=>1])->orderBy('created_at', 'desc')->get();
        foreach($domainReports as $domainReport){
            $domainReport->content = json_decode($domainReport->content);
        }


        //$domainReport = CbUsersDomainsReports::Where([ 'user_id'=> Auth::User()->id, 'dom_id'=>4])->get();
        //$domain = CbUsersDomains::Where([ 'user_id'=> 383, 'id'=> 4])->firstOrFail();
        /*if(isset($domainReports[0])) {
            dd($domainReports);
        }*/
        //return response()->json(['domainReports' => $domainReports]);
        return $domainReports;
    }

    public function createReport($id){
        $user_id = Auth::User()->id;
        $domain_id = $id;

        $this->report['domain'] = '';

        $domain = CbUsersDomains::where(['user_id' => $user_id, 'id' => $domain_id])->firstOrFail();

        if($domain != null){
            $this->report['domain'] = $domain;
            $this->fetchCookiesWithCategory($domain->id);
            $this->fetchCookiesWithRisks($domain->id);
            $domain->is_viewed = true;
            $domain->save();
        }
//        dd('$this->getReports($domain_id) ',$this->getReports($domain_id));

        return view('business.cookiebot.report')->with(['report' => $this->report, 'domainReports'=>$this->getReports($domain_id)]);
    }


    public function createReportWithNew($id){
        $user_id = Auth::User()->id;
        $domain_id = $id;
        $pagesScanned = 0;
        $domain_country = "";
        $domainReports = '';
        $this->report['domain'] = '';
        $url = null;
        $domain = CbUsersDomains::where(['user_id' => $user_id, 'id' => $domain_id])->firstOrFail();
        // dd($domain);
        if($domain != null){
            $this->report['domain'] = $domain;
            $this->fetchCookiesWithCategory($domain->id);
            $this->fetchCookiesWithRisks($domain->id);
            $domain->is_viewed = true;
            $domain->save();
            $url = $domain->dialogue ? $domain->dialogue->cookie_policy_url : null;
            $pagesScanned = CbDomainLinks::where(['user_id' => $user_id, 'dom_id' => $domain_id])->count();

            $domainReports = $this->getReports($domain_id);

            $records = @dns_get_record($domainReports[0]->title, DNS_A);

            $domain_country = "";
            if(!$records){
                $domain_country = "Unknown Location";
            }else{

                foreach ($records as $record){
                    if(isset($record['ip'])){
                        $domain_country = $this->getCountryCode($record['ip']);
//                        $domain_country = file_get_contents('https://ipapi.co/'.$record['ip'].'/country_name/');
                    }

                    if($domain_country != ""){
                        break;
                    }
                }
            }

            if($domain_country == ""){
                $domain_country = "Unknown Location";
            }

        }

        return view('business.cookiebot.newcookiescanreport')->with([
            'report' => $this->report,
            'domainReports' => $domainReports,
            'domain_country' => $domain_country,
            'pagesScanned' => $pagesScanned,
            'policy_url' => $url
        ]);
    }

    public function createFormReport($id){
        $user_id = Auth::User()->id;
        $domain_id = $id;

        $this->report['domain'] = '';

        $domain = CbUsersDomains::where(['user_id' => $user_id, 'id' => $domain_id])->firstOrFail();

        $cookies_categories = CbCookieCategories::OrderBy('sort_order', 'asc')->get();

        //dd($cookies_categories);
        $cookies_risks = CbCookieRisks::OrderBy('sort_order', 'asc')->get();

        if($domain != null){
            $this->report['domain'] = $domain;

            $this->fetchCookiesWithCategory($domain->id);

            $this->fetchCookiesWithRisks($domain->id);
        }

        return view('business.cookiebot.report')->with(['report' => $this->report, 'cookie_categories' => $cookies_categories, 'cookie_risks' => $cookies_risks]);
    }
    public function downloadPdf($id){

        $domainReport = CbUsersDomainsReports::where([ 'user_id'=> Auth::User()->id,'dom_id'=>$id, 'enabled'=>1])->orderBy('created_at', 'desc')->firstOrFail();
        $domainReportsContent= json_decode($domainReport->content);

        $domainReports = $this->getReports($id);
//        return response([
//            'domain' => $domainReports
//        ]);

        //dd($this->getReports($domainReports->id));
        //dd($domainReports);
        //dd($domainReportsContent);
        //return view('business.cookiebot.report_pdf')->with(['domainReportsContent'=>$domainReportsContent , 'domainReports'=>$domainReports]);
        $pdf = PDF::loadView('business.cookiebot.report_pdf', ['domainReportsContent'=>$domainReportsContent , 'domainReports'=>$domainReports->reverse(), 'dom_user'=>Auth::User()]);
        return $pdf->download('Cookie Consent Report.pdf');
        //return $pdf->download('Assessment_Report.pdf');
    }

    public function sendEmail($id,Request $request){
        //dd('hello');
        $domainReport = CbUsersDomainsReports::where([ 'user_id'=> Auth::User()->id,'dom_id'=>$id, 'enabled'=>1])->orderBy('created_at', 'desc')->firstOrFail();
        $domainReportsContent= json_decode($domainReport->content);

        $domainReports = $this->getReports($id);

        $pdf = PDF::loadView('business.cookiebot.report_pdf', ['domainReportsContent'=>$domainReportsContent , 'domainReports'=>$domainReports->reverse(), 'dom_user'=>Auth::User()]);
        //dd($pdf);
        $dom_id =$id;
        Mail::to($request->get('email'))->bcc(config('app.hubspot_bcc'))->send(new CookiebotReportMail($domainReports,$pdf,$dom_id));
        //dd($domainReports);
    }

    public function fetchCookiesWithCategory($domainId){
        $categories = CbCookieCategories::where('enabled', true)->get();
        foreach($categories as $category){
            $cookies = CbCookies::where(['dom_id' => $domainId, 'cb_cat_id' => $category->id])->get();
            $this->report[$category->slug] = $cookies;
        }
    }


    public function fetchCookiesWithRisks($domainId){
        $risks = CbCookieRisks::where('enabled', true)->get();
        foreach($risks as $risk){
            $cookies = CbCookies::where(['dom_id' => $domainId, 'cb_risk_id' => $risk->id])->get();
            $this->report[$risk->slug] = $cookies;
        }
    }

    public function storeReportCharts($dom_id, Request $request)
    {
        $data = $request->all();
        $domainReports = CbUsersDomainsReports::Where([ 'user_id'=> Auth::User()->id,'dom_id'=>$dom_id, 'enabled'=>1])->orderBy('created_at', 'asc')->get();
        $report = $domainReports->reverse();
        $domainReport = $report[0];
        if($domainReport->image == null){
            $domainReport->image = json_encode($data);
        } else {
            $image = $domainReport->image;
            $domainReport->image = json_encode(array_merge(json_decode($image, true), json_decode(json_encode($data), true)));
        }
        $domainReport->save();
        return response([
            'images' => $domainReport
        ]);
    }

    public function getDomainReportImages($domain_id)
    {        
        $domainReports = CbUsersDomainsReports::Where(['user_id'=> Auth::User()->id,'dom_id'=>$domain_id, 'enabled'=>1])->orderBy('created_at', 'desc')->get();

        $domainReports->each(function($report){
            if($report->image != NULL)
            {
                return response([
                    'images' => $report->image
                ]);
            }
        });
    }

    public function getCountryCode($ip){
        $query = "SELECT `code`,`countryName` FROM ip2countrylist WHERE INET_ATON('$ip') BETWEEN `ip_range_start_int` AND `ip_range_end_int` LIMIT 1";
        $country_code = DB::connection('mysql2')->select($query);
        return $country_code && $country_code[0]->countryName != '-' ? $country_code[0]->countryName : null;
    }

    public function scan_now($id){
       $update = DB::table('cb_users_domains')->where('id',$id)->update(['scan_done'=>0]);
       if($update){
           return response(['message'=>'Well done! Your scan is under way','type'=>'success']);
       } else{
           return response(['message'=>'already scaning','type'=>'error']);
       }
       
    }
}