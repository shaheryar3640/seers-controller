<?php

namespace App\Http\Controllers\API;

use App\Models\CookieXrayScript;
use App\Models\CookieXrayDialogue;
use App\Mail\ConsentLogLimitMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Models\CbUsersDomains;
use App\Models\CookieXrayConsentLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ConsentLogController extends Controller
{
    /**
     *
     * creates or update user domain consent into table.
     * @param Request $request
     * @return response
     */

    private $user = null;
    private $features = null;
    private $product_name = 'cookie_consent';

    public function updateConsentLog (Request $request) {

        $msg = 'Unauthenticated';
        $msg_code = 401;

        $domain = null;

        $script = CookieXrayScript::where('key','=',$request->get('key'))->first();
        // dd($script);
        if($script){
            $this->user = User::find($script->user_id);
            // dd($this->user);
            if($this->user){
                $domain = $this->user->userDomains()->whereIdAndVerified($script->domain_id, true)->first();
                // dd($domain);
            }else{
                return response()->json([
                    'eligible' => false
                ]);
            }
        }
        //Code Added For Cookie Consent Log Limit Start
        if(!$domain->enabled){
            //$diff_in_days;
            return response()->json([
                'eligible' => false,
                'message' => 'Your consent limit has been reached.'
            ]);
        }
        //Code Added For Cookie Consent Log Limit End
        if(!$domain){
            return response()->json([
                'eligible' => false,
                'message' => 'Your domain doesn\'t exist or non-verified.'
            ]);
        }

        $plan = $this->getPlan();
        // dd($plan);

        if(!$plan){
            return response()->json([
                'eligible' => false
            ]);
        }

        $this->getFeatures($script->user_id);

        $today = Carbon::now();
        $expire_on = new Carbon($plan->expired_on);

        if($today > $expire_on){
            return response()->json([
                'eligible' => false
            ]);
        }

        $url = $request->header('Referer');

        // dd($url);

        if($domain->name == $this->removeProtocol($url) && count($this->features) > 0 &&
            array_key_exists("consent_log",$this->features) && $this->features['consent_log'] != 0){

            $can_create_log = true;
            // dd($can_create_log);

            $permissions = json_decode($request->get('permissions'));
            $ip = request()->ip();
            try {
                $location = $this->getCountryCode($ip);
            } catch (\Exception $e) {
                $location = null;
            }

            /* Get consent limit from the purchased limit */
            $consent_limit = $this->getConsentLimitFeature($this->user);



            $domain->last_consent_time = Carbon::now()->format('Y-m-d H:i:s');
            $domain->total_consents += 1;
            $domain->save();

            /* Calculate the percentage of remaining consent limit */
            $limit_percent = ($domain->total_consents / $consent_limit) * 100;
            $limit_percent = ($limit_percent - intval($limit_percent) > 0.5 ? ceil($limit_percent) : intval($limit_percent));

            /* send email at the end of consent limit */
            //When consent limit reached equal to total consent of total domain
            if ($consent_limit == $domain->total_consents) {
                //$consent_limit = 0;
                $can_create_log = false;
                //if($domain->is_emailed == false || $domain->is_emailed == 0) {
                    //Code Added For Cookie Consent Log Limit Start
                   Mail::to($this->user->email)->bcc(config('app.hubspot_bcc'))->send(new ConsentLogLimitMail($this->user, $limit_percent,$domain->name,false));
                    $this->setCookieBannerFalse($script->domain_id);
                    $domain->enabled = 0;
                    $domain->last_limit_reached = Carbon::now()->format('Y-m-d H:i:s');
                    //Code Added For Cookie Consent Log Limit End 
                    //$domain->is_emailed = 1;
                    $domain->save();
                //}
                //Code Added For Cookie Consent Log Limit Start
                return response()->json([
                    'eligible' => false,
                    'message' => 'Your consent limit has been reached.'
                ]);
                //Code Added For Cookie Consent Log Limit End 
            }

            if($can_create_log) {
                $consentLog = CookieXrayConsentLog::create([
                    'url' => $request->get('webUrl'),
                    'country' => $location ? $location[0]->countryName : 'Unknown',
                    'ip_address' => $ip,
                    'date_and_time' => date('Y-m-d h:i:s', strtotime("now")),
                    'cookie_policy_version' => '273-1284163',
                    'browser' => $request->header('User-Agent'),
                    'dom_id' => $domain->id,
                    'user_id' => md5($domain->id . date('Y-m-d h:i:s', strtotime("now")))
                ]);

                if($permissions) {
                    $consentLog->preferences = $permissions->pref ? 1 : 0;
                    $consentLog->statistics = $permissions->stat ? 1 : 0;
                    $consentLog->marketing = $permissions->market ? 1 : 0;
                    $consentLog->unclassified = 0;
                    $consentLog->necessary = 0;
                }
                if($request->has('doNotSell')){
                    $consentLog->do_not_sell = $request->get('doNotSell') == 'true' ? true : false;
                }
                $consentLog->save();


                /* send email at 20% remaining consent limit */
                if (((100 - $limit_percent) == 20) && ($domain->is_emailed == 0 || $domain->is_emailed == false)) {
                   Mail::to($this->user->email)->bcc(config('app.hubspot_bcc'))->send(new ConsentLogLimitMail($this->user, $limit_percent, $domain->name, true));
                   $domain->is_emailed = 1;
                   $domain->save();
                }

                $msg = 'Submitted Successfully.';
                $msg_code = 200;
            } else {
                $msg = 'Consent limit exceed';
                $msg_code = 201;
            }
        }

        return response([
            'message' => $msg,
        ], $msg_code);
    }

    private function removeProtocol($url) {
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

        return $country_code && $country_code[0]->code != '-' ? $country_code : null;

    }

    /**
     * @param User $this->user
     * @return int
     */
    public function getConsentLimitFeature(User $user) {
        $limit = 0;
        $features = $user->products()->where('name', '=', 'cookie_consent')->first()->plan->features;
        if($features->count() > 0) {
            foreach ($features as $feature) {
                if($feature->name == 'consent_log_limit') {
                    $limit = $feature->value;
                    break;
                }
            }
        }
        return $limit;
    }

    private function getFeatures($user_id){
        $product = $this->user->currentProduct($this->product_name);
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

    private function setCookieBannerFalse($domain_id){
        $cookie_banner = CookieXrayDialogue::Where(['user_id'=> $this->user->id, 'cb_users_domain_id'=> $domain_id])->first();
        $cookie_banner->is_cookie_banner = 0;
        $cookie_banner->save();       
    }

}

