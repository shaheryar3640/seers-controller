<?php

namespace App\Http\Controllers;

use App\Models\CbCookieCategories;
use App\Models\CbCookieRisks;
use App\Models\CbCookies;
use App\Models\CbDomainLinks;
use App\Models\CbUsersDomains;
use App\Models\CbUsersDomainsReports;
use App\Models\User;
use Composer\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CookieXrayDomainReport extends Controller
{
    public function __construct() {
        $report = '';
    }

    public function show($url)
    {
        $domain = CbUsersDomains::where(['scan_done' => 1, 'web_url' => $url])->first();
        if (isset($domain)) {
            $iframeCall = false;
            if (request()->has("active")) {
                $iframeCall = true;
            }

            $url = config('app.cmp_url') . '/api/auth/createReport/' . $domain->id . '/' . $domain->user_id;
            $curl_reponse = curl_request('GET', $url, "");
            $curl_reponse = json_decode($curl_reponse, true);

            $report = [];
            if (count($curl_reponse['domainReports']) > 0)  {
                $report = $curl_reponse['domainReports'][0];
                $report = $report['content'];
            }
            return view('business.cookie_consent.newcookiescanreport')->with([
                'report' => $report,
                'domainCbCookies' => $domain,
                'domainReports' => $curl_reponse['domainReports'],
                'pagesScanned' => $curl_reponse['pagesScanned'],
                'policy_url' => $curl_reponse['policy_url'],
                'user' => User::find($domain->user_id),
                "iframeCall" => $iframeCall
            ]);
        } else {
            return view('errors.404');
        }
    }

    public function createReport(CbUsersDomains $domain){

        if($domain != null){
            $this->report['domain'] = $domain;
            $this->fetchCookiesWithCategory($domain->id);
            $this->fetchCookiesWithRisks($domain->id);
            $domain->is_viewed = true;
            $domain->save();
        }

    }

    public function getReports($id) {
        $domainReports = CbUsersDomainsReports::Where(['dom_id'=> $id, 'enabled'=>1])->orderBy('created_at', 'asc')->get();
        foreach($domainReports as $domainReport){
            $domainReport->content = json_decode($domainReport->content);
        }
        return $domainReports;
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
}
