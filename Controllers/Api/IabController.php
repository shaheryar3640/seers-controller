<?php

namespace App\Http\Controllers\Api;

use App\Models\CbCookieCategories;
use App\Models\CbCookies;
use App\Models\CbUsersDomains;
use App\Models\CookieXrayDefaultDialogueLanguage;
use App\Models\CookieXrayDialogue;
use App\Models\CookieXrayDialogueLanguage;
use App\Models\CookieXrayPolicy;
use App\Models\CookieXrayScript;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Auth;

class IabController extends Controller
{
    private $user = null;
    private $features = null;
    private $product_name = 'cookie_consent';
    private $report = null;

    public function __construct() {
        //    $this->middleware('auth:api');
    }

    public function getApiData(Request $request){
        $url = $this->removeProtocol($request->header('Referer'));

        $script = CookieXrayScript::where('key','=',$request->get('key'))->first();

        $domain_id = $script->domain_id;
        $domain = $script->domain;
        $this->user =  User::find($script->user_id);

        // Allow Test Banner
        if($this->user && $this->user->email == 'test.banner@seersco.com'){
            //proceed further...
        } else if($domain->name !== $url){
            return response()->json([
                'eligible' => false,
                'message' => 'Invalid Domain Name.'
            ]);
        } else{
            if($domain->name == $url && $domain->verified !== 1) {

                CbUsersDomains::whereNameAndVerified($domain->name, 1)
                    ->update(['verified' => 0, 'verification_req' => 0]);

                $domain->verified = 1;
                $domain->save();

                return response()->json([
                    'reload' => true,
                ]);
            }
        }

//        if($domain->name == $url && $domain->verified !== 1) {
//
//            CbUsersDomains::where('verified', 1)
//                ->update(['verified' => 0, 'verification_req' => 0]);
//
//            $domain->verified = 1;
//            $domain->save();
//
//            return response()->json([
//                'reload' => true,
//            ]);
//        }

        // if(!CbUsersDomains::select()->whereIdAndVerified($domain_id, true)->exists()){
        //     return response()->json([
        //         'eligible' => false,
        //         'message' => 'Your domain does not exist or non-verified.'
        //     ]);
        // }

        if(!$this->user){
            return response()->json([
                'eligible' => false,
                'message' => 'Invalid script for your domain.'
            ]);
        }

        $plan = $this->getPlan();
        if(!$plan){
            return response()->json([
                'eligible' => false,
                'message' => 'You didn\'t subscribe valid plan.'
            ]);
        }
        if ($plan && $plan->name != 'free') {
            $today = Carbon::now();
            $expire_on = new Carbon($plan->expired_on);

            if($today > $expire_on){
                return response()->json([
                    'eligible' => false,
                    'message' => 'You subscription has been expired.'
                ]);
            }
        }

        $this->fetchCookiesWithCategory($domain_id);
        $this->getFeatures($script->user_id);

        $dialogue = CookieXrayDialogue::find($script->dialogue_id);

        if($dialogue->is_cookie_banner === 0){
            return response()->json([
                'eligible' => false,
                'message' => 'You didn\'t allowed for banner.'
            ]);
        }

        $banner = null;
        if($dialogue){
            $banner = $dialogue->banners()->whereIsActive(true)->first();
        }

        if($script){
            // if auto-detection mode is active
            $location = null;
            $ip = null;
            if($dialogue->is_auto_detection == 1 && $this->features['territory_preferences'] != 0) {
                $ip = request()->ip();
                $location = $this->getCountryCode($ip);

                $country_codes = [
//                    'AL',   // Albanian
                    'FR',   // French
                    'IT',   // Italian
                    'ES',   // Spanish
                    'DE',   // German
                    'PT',   // Portuguese
                    'GB',   // English UK
                    'US',   // English US
                    'CN',   // Chinese
                    'SA'    // Arabic
                ];
                $dialogue_languages = $dialogue->languages;
                $language = null;
                $skip = false;

                if($location && $this->features['territory_preferences'] == 1){
                    if(!is_null($dialogue_languages)){
                        $language = $dialogue_languages->where('country_code', '=', 'GB')->first();
                        if (is_null($language)) {
                            $language = CookieXrayDefaultDialogueLanguage::whereIsActiveAndCountryCode(1, 'GB')->first();
                        }
                    }
                    $skip = true;
                }

                if (!$skip && $location && $this->features['territory_preferences'] == 2 && ($location[0]->code == 'GB' || $location[0]->code == 'US')) {
                    if(!is_null($dialogue_languages)){
                        $language = $dialogue_languages->firstWhere('country_code', $location[0]->code);
                        if(is_null($language)){
                            $language = CookieXrayDefaultDialogueLanguage::whereIsActiveAndCountryCode(1, $location[0]->code)->first();
                        }
                    }
                }

                if(!$skip && $location && $this->features['territory_preferences'] == 3 && in_array($location[0]->code, $country_codes)){
                    if(!is_null($dialogue_languages)){
                        $language = $dialogue_languages->firstWhere('country_code', $location[0]->code);
                        if(is_null($language)){
                            $language = CookieXrayDefaultDialogueLanguage::whereIsActiveAndCountryCode(1, $location[0]->code)->first();
                        }
                    }
                }

                if(is_null($language)){
                    $language = $dialogue_languages->firstWhere('country_code', 'GB');
                    if(is_null($language)){
                        $language = CookieXrayDefaultDialogueLanguage::whereIsActiveAndCountryCode(1, 'GB')->first();
                    }
                }
            } else {
                $language = CookieXrayDialogueLanguage::whereDialogueIdAndCountryCode($dialogue->id, $dialogue->default_language)->first();

                if(is_null($language)){
                    $language = CookieXrayDefaultDialogueLanguage::whereIsActiveAndCountryCode(1, 'GB')->first();
                    $dialogue->default_language = $language->country_code;
                    $dialogue->save();
                    $dialogue->languages = $language;
                }   // end if
            }   // end else
            return response()->json([
                'dialogue' => $dialogue,
                'language' => $language,
                'banner' => $banner,
                'cookies' => $this->report,
                'eligible' => true,
                'location' => $location,
            ]);
        }   // end script if
    }   // end function

    public function getPolicy(Request $request){
        $script = CookieXrayScript::where('key','=',$request->get('key'))->first();

        $plan = User::find($script->user_id)->membership_plan_id;

//        if($plan == 3){
//            return response()->json([
//                'eligible' => false
//            ]);
//        }

        $domain_id = $script->domain_id;
        $this->fetchCookiesWithCategory($domain_id);

        if($script){
            $policy = CookieXrayPolicy::where('cb_users_domain_id','=',$script->domain_id)->first();
            $policy_design = CookieXrayDialogue::select('policy_design')->whereId($script->dialogue_id)->first();
            return response()->json([
                'policy' => $plan == 3 ? null : $policy->policy,
                'cookies' => $this->report,
                'policy_design' => $policy_design->policy_design
            ]);
        }
    }

    public function verifyDomain(Request $request){
//        $url = $request->url();
        $url = $request->header('Referer');
        $message_heading = 'Domain Verification Failed.';
        $message_content = 'Sorry, this domain is already verified. If this domain belongs to you, please contact us on support@seersco.com.';
        $show_popup = false;

        $script = CookieXrayScript::where('key','=',$request->get('key'))->first();
        $domain = $script->domain;
        if($domain && $domain->verified !== 1) {
            $domain->verified = 1;
            $domain->save();
            return response([
                'reload' => true
            ], 200);
        }
        $domain = CbUsersDomains::whereNameAndVerified($this->removeProtocol($url), true)->first();

        if(!$domain){
            $script = CookieXrayScript::where('key','=',$request->get('key'))->first();

            if($script){
                $domain = CbUsersDomains::whereNameAndUserId($this->removeProtocol($url), $script->user_id)->first();
                if($domain){
                    $domain->verified = true;
                    $domain->verified_on = Carbon::now();
                    $domain->save();
                    $message_heading = 'Your domain ' . $this->removeProtocol($url) . ' has been verified successfully.';
                    $message_content = 'Seers Cookie Consent script has been successfully installed on ' . $this->removeProtocol($url) . '. You can go back to Seers Cookie Consent admin panel and customise your banner..';
                    $show_popup = $domain->verification_req ? true : false;
                }
            }
        }

        return response()->json([
            'show_popup'      => $show_popup,
            'message_heading' => $message_heading,
            'message_content' => $message_content
        ], 200);
    }

    public function fetchCookiesWithCategory($domainId){
        $categories = CbCookieCategories::where('enabled', true)->get();
        foreach($categories as $category){
            $cookies = CbCookies::where(['dom_id' => $domainId, 'cb_cat_id' => $category->id])->get();
            $this->report[$category->slug] = $cookies;
        }
    }

    public function removeProtocol($url){
        $remove = array("http://","https://", "www.", "WWW.");
        $final_url =  str_replace($remove,"",$url);
        if(strpos($final_url, '/') !== false){
            $host_name = explode('/', $final_url);
            return $host_name[0];
        }else{
            return $final_url;
        }
    }

    public function getCountryCode($ip){
        $query = "SELECT `code`,`countryName` FROM ip2countrylist WHERE INET_ATON('$ip') BETWEEN `ip_range_start_int` AND `ip_range_end_int` LIMIT 1";

        $country_code = DB::connection('mysql2')->select($query);

        return $country_code ? $country_code : null;
    }


    /**
     * @param $user_id
     * @return int
     */
    public function getBannerVisibilityDuration($user_id)
    {
        $days = 0;
        $user = User::find($user_id);
        $u_product = $user->currentProduct('cookie_consent');
        if ($u_product != null) {
            $features = $u_product->plan->features;
            if ($features->count() > 0) {
                foreach ($features as $feature) {
                    if($feature->name == 'banner_visibility_duration') {
                        $days = $feature->value;
                        break;
                    }
                }
            }
        } else {
            $current_date = new \DateTime("now");
            $expire_on = date("Y-m-d H:i:s", strtotime("+1 ".$user->plan_expiry, strtotime($user->upgraded_at)));
            $expiry_date = new \DateTime($expire_on);

            /* format('%a') shows only number of days but not in way we want */
            /* if current date > expiry date then days should be in + (positive) */
            /* if current date is < expiry date then days should be in - (negative) */
            $days = $current_date->diff($expiry_date)->format('%a');

            /* invert returns 1, 0 */
            /* if current date > expiry date then invert returns 0 */
            /* if current date < expiry date then invert returns 1 */
            $invert = $current_date->diff($expiry_date)->invert;
            if($invert == 1) {
                $days = 0;
            }
        }
        return $days;
    }

    private function getFeatures($user_id){
        $user = User::find($user_id);
        $product = $user->currentProduct($this->product_name);
        if($product){
            $features = $product->plan->features;
            if($features->count() > 0){
                foreach ($features as $feature){
                    $this->features[$feature->name] = (int)$feature->value;
                }
            }
        }
        return $this->features;
    }

    private function getPlan(){
        $product = $this->user->currentProduct($this->product_name);
        return $product ? $product->plan : null;
    }


}
