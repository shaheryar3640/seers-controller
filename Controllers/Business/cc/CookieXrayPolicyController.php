<?php

namespace App\Http\Controllers\Business\cc;

use App\Models\CookieXrayAnswer;
use App\Models\CookieXrayDialogue;
use App\Models\CookieXrayPolicy;
use App\Models\CookieXrayPolicyDoc;
use App\Models\CookieXrayScript;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Controller;
use App;
use Auth;
use PDF;

class CookieXrayPolicyController extends Controller
{
    public function __construct()
    {
        //$this->middleware('business');
        $this->report = array();
    }

    public function getPolicyDoc($id)
    {
        $policy_doc = CookieXrayPolicyDoc::where('cookie_xray_id','=',$id)->first();

        return response()->json([
            'policy_doc' => $policy_doc
            ]);
    }

    public function savePolicy(Request $request)
    {
        $policy = CookieXrayPolicy::Create([
            'policy'                => $request->get('policy_doc'),
            'user_id'               => Auth::User()->id,
            'cb_users_domain_id'    => $request->get('domain_id'),
        ]);

        if($policy){
            return response()->json([
                'message' => 'success',
            ]);
        }else {
            return response()->json([
                'message' => 'failed',
            ]);
        }
    }

    public function getPolicy($id)
    {
        $policy = CookieXrayPolicy::where('cb_users_domain_id','=',$id)
            ->where('user_id', '=', Auth::User()->id)->first();
        return response()->json([
            'policy' => $policy
        ]);
    }

    public function checkForSurvey($id)
    {
        $policy = CookieXrayAnswer::where('domain_id','=',$id)
            ->where('user_id', '=', Auth::User()->id)->first();
        return response()->json([
            'take_survey' => $policy ? ($policy->done ? false : true) : true
        ]);
    }

    public function resetPolicy(Request $request)
    {
        $answers = CookieXrayAnswer::where('domain_id','=',$request->get('domain_id'))
            ->where('user_id', '=', Auth::User()->id)->first();

        $policy = CookieXrayPolicy::where('cb_users_domain_id','=',$request->get('domain_id'))
            ->where('user_id', '=', Auth::User()->id)->first();
        if($policy && $policy->exists()){ $policy->delete();}
        if($answers && $answers->exists()){ $answers->delete();}

        return response()->json([ 'message' => 'success']);
    }

    public function downloadCookiePolicy($id)
    {
        $this->fetchCookiesWithCategory($id);
        $policy = CookieXrayPolicy::where('cb_users_domain_id','=',$id)
        ->where('user_id', '=', Auth::User()->id)->first();

        $data = str_replace("<p></p>", "", $policy->policy);
        $pdf = PDF::loadView('business.cookiebot.cookie-policy-pdf', ['data' => $data, 'cookies' => $this->report]);
        return $pdf->download('Cookie Consent Document.pdf');        
    }

    public function fetchCookiesWithCategory($domainId){
        $categories = \App\Models\CbCookieCategories::where('enabled', true)->get();
        foreach($categories as $category){
            $cookies = \App\Models\CbCookies::where(['dom_id' => $domainId, 'cb_cat_id' => $category->id])->get();
            $this->report[$category->slug] = $cookies;
        }
    }

    public function buildHTML()
    {
        $html = '';
        foreach ($this->report as $cookie_type)
        {
            $html .= '<table class=\"cx-table-table\">';
                $html .= '<thead>';
                    $html .= '<tr class=\"cx-table-row\">';
                        $html .= '<th class=\"cx-table-heading\">Name</th>';
                        $html .= '<th class=\"cx-table-heading\">Provider</th>';  
                        $html .= '<th class=\"cx-table-heading\">Purpose</th>';  
                        $html .= '<th class=\"cx-table-heading\">Expiry</th>';  
                        $html .= '<th class=\"cx-table-heading\">Type</th>';  
                    $html .= '</tr>';
                $html .= '</thead>';
                $html .= '<tbody class=\"cx-table-body\">';
            foreach ($cookie_type as $cookie)
            {
                $html .= '<tr class=\"cx-table-row\">';
                $html .= '<td>'. $cookie->name          .'</td>';
                $html .= '<td>'. $cookie->provider      .'</td>';

                if($cookie->purpose_desc){
                    $html .= '<td>'. $cookie->purpose_desc  .'</td>';
                } else {
                    $html .= '<td>Undefined by the provider</td>';
                }
                $html .= '<td>'. $cookie->expiry        .'</td>';
                $html .= '<td>'. $cookie->type          .'</td>';
                $html .= '</tr>';
            }
                $html .= '</tbody>';
            $html .= '</table>';
        }
        return $html;
    }
}