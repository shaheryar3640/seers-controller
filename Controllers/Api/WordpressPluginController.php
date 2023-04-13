<?php

namespace App\Http\Controllers\Api;

use App\Models\CbUsersDomains;
use App\Models\CookieXrayDefaultDialogueLanguage;
use App\Models\CookieXrayDialogue;
use App\Models\CookieXrayDialogueBanner;
use App\Models\CookieXrayDialogueLanguage;
use App\Models\CookieXrayScript;
use App\Mail\NewUserRegisteredForPluginMail;
use App\Models\Product;
use App\Models\UFeature;
use App\Models\UPlan;
use App\Models\UProduct;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use DB;

class WordpressPluginController extends Controller
{

    private $user           = null;
    private $domain_name    = null;
    private $isNew          = false;
    private $domain_limit   = 0;
    private $domain_id      = null;
    private $dialogue_id    = null;
    private $key            = null;
    private $removedURL     = null;
    private $req_params = null;
    private $is_update      = false;
    private $req_response   = [];
    private $is_new_user    = false;
    private $is_new_domain    = false;
    private $is_cookie_banner    = false;
    private $test    = null;
    private $curr_country_code = null;
    private $cache_key = null;

    /**
     * Save all the credentials of the user and creates a new FREE banner for the specified domain
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveCredentials(Request $request) {
        Log::channel('wp')->info(json_encode($request->header('Referer')));
        return response(['message' => 'please update your plugin', 200]);
        $validator = Validator::make($request->only('email', 'domain', 'secret', 'platform', 'lang', 'policy_url'), $this->getRules(), $this->getMessages());
        if($request->platform !='wordpress' && $request->platform !='prestashop')
        {
            return true;
        }
        if ($validator->fails()) {
            return response()->json([ 'errors' => $validator->errors()], 400);
        }

        $this->removedURL = $this->removeProtocol($request->get('domain'));
        $referer = $this->removeProtocol($request->header('Referer'));

        if ($this->removedURL !== $referer) {
            return response()->json([ 'message' => 'Referer is not matched'], 400);
        }

        if (!$this->secretValidated()) {
            return response()->json([ 'message' => 'Secret does not match'], 400);
        }

        if (!$this->validateDomain()) {
            return response()->json([
                'message' => 'You have entered an invalid domain. Please enter a valid domain address'
            ], 400);
        }

        if (!$this->isUserExists()) {
            $this->isNew = true;
            $this->createUserAccount();
            try {
                $this->subscribeForProduct();
            } catch (\Exception $e) {

            }
        }

        if (!$this->isNew) {
            if (!$this->validateProduct() ) {
                try {
                    $this->subscribeForProduct();
                } catch (\Exception $e) {

                }
            }
        }

        if (!$this->isDomainExist()) {
            if (!$this->isAllowedToAddDomains()) {
                return response()->json([
                    'message' => 'You are not allowed to add more than ' . $this->domain_limit . ' domains'
                ], 400);
            }
            $this->storeDomain($request->get('platform'));
        }

        $this->createDialogue();
        $this->createLanguage();
        $this->createBanner();
        $this->createScript();
        $product = $this->user->getActiveProduct('cookie_consent');

        if (!$product) {
            $product=null;
        }
        return response()->json(['key' => $this->key,'user_plan' => $product], 200);
    }

    /**
     * Get the rules for the specified input fields
     * @return array
     */
    private function getRules() {
        return  [
            'email' => 'required|string|email',
            'domain' => 'required|string|max:255',
            'secret' => 'required|string|max:255',
            'platform' => 'required|string|max:255',
            'lang' => 'sometimes|string|max:255|min:2',
            'policy_url' => 'sometimes|string|max:255|min:7',
        ];
    }

    /**
     * Get the rules for the specified input fields
     * @return array
     */
    private function getRulesForShopify() {
        return  [
            'email' => 'required|string|email',
            'user_email' => 'required|string|email',
            'domain' => 'required|string|max:255',
            'user_domain' => 'required|string|max:255',
            'secret' => 'required|string|max:255',
            'platform' => 'required|string|max:255',
            'lang' => 'sometimes|string|max:255|min:2',
            'policy_url' => 'sometimes|string|max:255|min:7',
        ];
    }

    /**
     * Get the messages for the specific fields
     * @return array
     */
    private function getMessages() {
        return [
            'email.required' => 'Email field is required',
            'domain.required' => 'Domain name is required',
            'secret.required' => 'Secret field is required',
            'platform.required' => 'Platform field is required',
        ];
    }

    /**
     * checks the use in resource
     * @return boolean
     */
    private function isUserExists() {
        $email = request()->get('email');
        if(request()->get('platform') == 'shopify')
            $email = request()->get('user_email');

        $user = User::where('email', '=', $email)->first();
        if ($user && isset($user->email)) {
            $this->user = $user;
            return true;
        }
        return false;
    }

    /**
     * Checks the domain host that whether this domain belongs to our sites or not.
     * @return boolean
     */
    private function createUserAccount() {
        $user = new User;
        $user->fname = '';
        $user->lname = '';
        $user->email = request()->get('platform') == 'shopify' ? request()->get('user_email') : request()->get('email');
        $user->password = bcrypt($this->getPassword());
        $user->business = true;
        $user->on_trial = 0;
        $user->save();

        $this->user = $user;

        if(!$this->isOurDomain()){
            // Mail::to($user->email)->bcc('5410056@bcc.hubspot.com')->send(new NewUserRegisteredForPluginMail($user, $this->getPassword(), request()->get('platform')));
            $to = ['email' => $this->user->email, 'name' => $this->user->fname];
            $template = [
                'id' => config('sendgridtemplateid.CMS-Registration-Email'),
                'data' => [ 'first_name' => $this->user->fname,'email' => $this->user->email,'password' => $this->getPassword(),'plugin_source' => request()->get('platform') ]

            ];
            sendEmailViaSendGrid($to, $template);
        }

        return true;
    }

    /**
     * creates new User in resource
     * @return boolean
     */
    private function isOurDomain(){

        $user_domain = '';
        $domains_to_skip = [
            'agdpr.com',
            'cookieconsent.co.uk',
            'cookiebanner.uk',
            'eprivacy.ai',
            'iaccountants.co'
        ];

        $found = false;

        $host_names = explode(".", $this->removedURL);

        if(sizeof($host_names) > 3){
            $user_domain = $host_names[count($host_names)-3] . "." . $host_names[count($host_names)-2] . "." . $host_names[count($host_names)-1];
        }else{
            $user_domain = $host_names[count($host_names)-2] . "." . $host_names[count($host_names)-1];
        }

        if(in_array($user_domain, $domains_to_skip)) {
            $found = true;
        }

        return $found;
    }

    /**
     * returns the default free banner
     * @return array
     */
    private function getFreeBanner() {
        return [
            'name'                      => 'default',
            'position'                  => 'seers-cmp-banner-bar',
            'title_text_color'          => '#000000',
            'body_text_color'           => '#000000 ',
            'agree_text_color'          => '#FFFFFF',
            'disagree_text_color'       => '#FFFFFF',
            'preferences_text_color'    => '#000000',
            'agree_btn_color'           => '#808080',
            'disagree_btn_color'        => '#808080',
            'preferences_btn_color'     => '#808080',
            'logo_bg_color'             => '#FFFFFF',
            'banner_bg_color'           => '#FFFFFF ',
            'font_style'                => 'arial',
            'font_size'                 => '12',
            'button_type'               => 'cbtn_default',
            'is_active'                 => 1,
            'dialogue_id'               => null
        ];
    }

    private function getCustomFreeBanner() {
        return [
            'name'                      => 'default',
            'position'                  => 'seers-cmp-banner-bar',
//            'title_text_color'          => $this->req_params['title_text_color'],
            'title_text_color'          => $this->req_params['body_text_color'],
            'body_text_color'           => $this->req_params['body_text_color'],
            'agree_text_color'          => $this->req_params['agree_text_color'],
            'disagree_text_color'       => $this->req_params['disagree_text_color'],
            'preferences_text_color'    => $this->req_params['preferences_text_color'],
            'agree_btn_color'           => $this->req_params['agree_btn_color'],
            'disagree_btn_color'        => $this->req_params['disagree_btn_color'],
            'preferences_btn_color'     => $this->req_params['preferences_btn_color'],
//            'logo_bg_color'             => $this->req_params['logo_bg_color'],
            'logo_bg_color'             => $this->req_params['banner_bg_color'], // same as banner bg color
            'banner_bg_color'           => $this->req_params['banner_bg_color'],
            'font_style'                => $this->req_params['font_style'],
            'font_size'                 => $this->req_params['font_size'],
            'button_type'               => $this->req_params['button_type'],
            'is_active'                 => 1,
            'dialogue_id'               => null
        ];
    }

    /**
     * add a new domain to the resource
     * @param $platform
     * @return void
     */
    private function storeDomain($platform) {
        $domain = new CbUsersDomains;
        $domain->user_id = $this->user->id;
        $domain->name = $this->removedURL;
        $domain->slug = $this->removedURL. '-' . rand(99, 9999) . '-' .$this->user->id;
        $domain->scan_frequency = $this->getScanFrequency();
        $domain->platform = $platform;
        $domain->priority = 0;
        $domain->save();

        $this->domain_id = $domain->id;
        $this->domain_name = $domain->name;
    }

    /**
     * validates the domain according to DNS
     * @return boolean
     */
    private function validateDomain() {
        if (checkdnsrr($this->removedURL, 'A') === false) {
            return false;
        }
        return true;
    }

    /**
     * removes the protocol and extra slashes from the domain.
     * @param $URL
     * @return string
     */
    private function removeProtocol($URL) {
        $remove = ["http://", "https://", "www.", "WWW."];
        $final_url =  str_replace($remove, "", $URL);
        if (strpos($final_url, '/') !== false) {
            $final_url = explode('/', $final_url);
            return $final_url[0];
        } else {
            return $final_url;
        }
    }

    /**
     * Gets the domain URL
     * @return string
     */
    private function getURL() {
        return request()->get('domain');
    }

    /**
     * Gets the scan frequency for the newly entered domain
     * @return string
     */
    private function getScanFrequency() {
        return 'only_once';
    }

    /**
     * Checks whether user is allowed to add domains according to the plan he purchased
     * @return boolean
     */
    private function isAllowedToAddDomains() {
        $total_domains = CbUsersDomains::totalDomains($this->user->id);

        return (($this->domain_limit - $total_domains) > 0);
    }

    /**
     * Subscribe for the new product with free plan in the resource
     * @return boolean
     * @throws \Exception
     */
    private function subscribeForProduct() {
        $product = Product::whereNameAndIsActive('cookie_consent', 1)->first();
        if (!$product) {
            return false;
        }
        $u_product = new UProduct;
        $u_product->fill($product->toArray());
        $u_product->recursive_status = 'monthly';
        $u_product->purchased_on = date("Y-m-d H:i:s", strtotime("now"));
        $u_product->expired_on = $product->on_trial ? date("Y-m-d H:m:s", strtotime($product->trial_days . " days")) : date("Y-m-d H:i:s", strtotime("+1 " .  'month', strtotime($u_product->purchased_on)));
        $u_product->upgraded_on = $u_product->purchased_on;
        $u_product->on_trial = $product->on_trial ? 1 : 0;
        $u_product->trial_days = $product->on_trial ? $product->trial_days : 0;
        $u_product->trial_expire_on = $product->on_trial ? date("Y-m-d H:m:s", strtotime($product->trial_days . " days")) : null;
        $u_product->discount = $product->discount;
        $u_product->user_id = $this->user->id;
        $u_product->save();

        $freePlan = $product->plans()->whereNameAndIsActive('free', 1)->first();

        if (!$freePlan) {
            $u_product->delete();
            return false;
        }
        $u_plan = new UPlan;
        $u_plan->fill($freePlan->toArray());
        $u_plan->price = $u_product->recursive_status == 'yearly'
            ? number_format (($freePlan->price / 1.25) , 2,'.', '')
            : $freePlan->price;

        $u_plan->u_product_id = $u_product->id;
        $u_plan->purchased_on = date("Y-m-d H:i:s", strtotime("now"));
        $u_plan->expired_on = $product->on_trial ? date("Y-m-d H:m:s", strtotime($product->trial_days . " days")) : date("Y-m-d H:i:s", strtotime("+1 " .  'month', strtotime($u_product->purchased_on)));
        $u_plan->save();

        $features = $freePlan->features;

        if (!$features) {
            $u_plan->delete();
            $u_product->delete();
            return false;
        }

        foreach ($features as $feature) {
            $u_feature = new UFeature;
            $u_feature->fill($feature->toArray());
            $u_feature->u_plan_id = $u_plan->id;
            $u_feature->save();

            if ($u_feature->name === 'domain_limit') {
                $this->domain_limit = $u_feature->value;
            }
        }

        return true;
    }

    /**
     * Validates the already purchased product for the existing user.
     * @return boolean
     */
    private function validateProduct() {
        $product = $this->user->getActiveProduct('cookie_consent');

        if (!$product) return false;

        $features = $product->plan->features;

        if (!$features) return false;

        foreach ($features as $feature) {
            if ($feature->name === 'domain_limit') {
                $this->domain_limit = $feature->value;
            }
        }
        return true;
    }

    /**
     * Creates new dialogue for the specified user and domain in resource.
     * @return boolean
     */
    private function createDialogue() {
        DB::transaction(function () {
            $dialogue = CookieXrayDialogue::firstOrCreate([
                'cb_users_domain_id' => $this->domain_id,
                'user_id'            => $this->user->id
            ]);

            $this->curr_country_code = $dialogue->default_language;

            if ($dialogue->wasRecentlyCreated) {
                $dialogue->fill($this->getDialogueProperties());
                $dialogue->save();
            }

            $this->dialogue_id = $dialogue->id;
            $this->is_cookie_banner = $dialogue->is_cookie_banner;

            return true;
        });
    }

    /**
     * Creates new language for the specified user and domain in resource.
     * @return boolean
     */
    private function createLanguage() {
        DB::transaction(function () {
            $language = CookieXrayDefaultDialogueLanguage::where([
                'country_code' => $this->getLanguageAttribute(),
                'is_active' => 1
            ])->firstOrFail();

            if (!$language) {
                return false;
            }

            $dialogue_lang = CookieXrayDialogueLanguage::firstOrCreate([
                'country_code' => $language->country_code,
                'dialogue_id' => $this->dialogue_id
            ]);


            if($dialogue_lang->wasRecentlyCreated)
                $dialogue_lang->fill($language->toArray());

            if ($this->is_update && $this->curr_country_code == $this->getLanguageAttribute()) {
    //            $dialogue_lang->title = $this->req_params['title_text'] == $dialogue_lang->title ? $this->req_params['title_text'] : $dialogue_lang->title;
                $dialogue_lang->body                    = $this->req_params['body_text'] == $dialogue_lang->body ? $dialogue_lang->body : $this->req_params['body_text'];
                $dialogue_lang->btn_agree_title         = $this->req_params['accept_btn_text'] == $dialogue_lang->btn_agree_title ? $dialogue_lang->btn_agree_title : $this->req_params['accept_btn_text'];
                $dialogue_lang->btn_disagree_title      = $this->req_params['reject_btn_text'] == $dialogue_lang->btn_disagree_title ? $dialogue_lang->btn_disagree_title : $this->req_params['reject_btn_text'];
                $dialogue_lang->btn_preference_title    = $this->req_params['setting_btn_text'] == $dialogue_lang->btn_preference_title ? $dialogue_lang->btn_preference_title : $this->req_params['setting_btn_text'];
            }

            $dialogue_lang->save();

            $this->test = (( !empty($this->req_params['body_text']) ) ? $this->req_params['body_text'] : "");

            $this->req_response ['title_text'] = $dialogue_lang->title;
            $this->req_response ['body_text'] = $dialogue_lang->body;
            $this->req_response ['accept_btn_text'] = $dialogue_lang->btn_agree_title;
            $this->req_response ['reject_btn_text'] = $dialogue_lang->btn_disagree_title;
            $this->req_response ['setting_btn_text'] = $dialogue_lang->btn_preference_title;

            return true;
        });
    }

    /**
     * Creates new banner free for the specified user and domain in resource.
     * @return boolean
     */
    private function createBanner() {
        DB::transaction(function () {
            $free_banner =  CookieXrayDialogueBanner::firstOrCreate([
                'name'          => $this->getFreeBanner()['name'],
                'dialogue_id'   => $this->dialogue_id
            ]);

            if ( isset($this->req_params['body_text_color']) || isset($this->req_params['disagree_btn_color']) || isset($this->req_params['font_size']) || $free_banner->wasRecentlyCreated ) {
                $free_banner->fill($this->is_update ? $this->getCustomFreeBanner() : $this->getFreeBanner());
                $free_banner->dialogue_id = $this->dialogue_id;
                $free_banner->save();
            }

            return true;
        });
    }

    /**
     * Creates new script for the specified user and domain in resource.
     * @return boolean
     */
    private function createScript() {
        DB::transaction(function () {
            $script = CookieXrayScript::firstOrNew([
                'user_id' => $this->user->id,
                'domain_id' => $this->domain_id,
                'dialogue_id' => $this->dialogue_id,
            ]);

            if(!$script->exists){
                $script->simple_key = $this->user->id . $this->domain_name . 'seers';
                $key=bcrypt($script->simple_key);
                $key= str_replace('/','',$key);
                $script->key  = $key;
                $script->old_key  = $key;
                $script->path = 'scripts';
                $script->file_name = time() . '_script';
            }

            $script->save();

            $this->key = $script->key;

            return true;
        });
    }

    /**
     * Gets the dialogue default properties for the domain dialogue.
     * @return array
     */
    private function getDialogueProperties() {
        return [
            'web_url'                       => '',
            'cookie_policy_url'             => request()->get('policy_url') ?? null,
            'policy_design'                 => 'raw',
            'do_prior_consent'              => 0,
            'cookie_consent'                => 'generalised',
            'is_cookie_banner'              => $this->is_update ? $this->req_params['is_active'] === 'true' : true,
            'is_cookie_policy'              => false,
            'is_cookie_declaration_table'   => false,
            'has_badge'                     => $this->is_update ? $this->req_params['show_badge'] === 'true' : false,
            'enable_cookie_stats'           => false,
            'btn_disagree_title'            => 'Disable All',
            'btn_agree_title'               => 'Allow All',
            'btn_read_more_title'           => 'Settings',
            'consent_mode'                  => 'explicit',
            'show_once'                     => true,
            'auto_accept_on_scroll'         => false,
            'consent_type'                  => 'generalised',
            'preferences_checked'           => false,
            'statistics_checked'            => false,
            'targeting_checked'             => false,
            'agreement_expire'              => $this->is_update ? $this->req_params['cookies_expiry'] : 30,
            'title'                         => 'This website uses cookies',
            'body'                          => 'We use cookies to personalise content and ads, to provide social media features and to analyse our traffic. We also share information about your use of our site with our social media, advertising and analytics partners who may combine it with other information that you have provided to them or that they have collected from your use of their services. You consent to our cookies if you continue to use our website.',
            'cookies_body'                  => 'Cookies are to ensure website user gets best experience. Necessary cookies can be stored in the users devices. We need your permission for non essential cookies. Learn more about how we process personal data in our Privacy Policy?',
            'preference_title'              => 'Preferences',
            'preference_body'               => 'Preference cookies enable a website to remember information that changes the way the website behaves or looks, like your preferred language or the region that you are in.',
            'statistics_title'              => 'Statistics',
            'statistics_body'               => 'Statistic cookies help website owners to understand how visitors interact with websites by collecting and reporting information anonymously.',
            'marketing_title'               => 'Marketing',
            'marketing_body'                => 'Marketing cookies are used to track visitors across websites. The intention is to display ads that are relevant and engaging for the individual user and thereby more valuable for publishers and third-party advertisers.',
            'unclassified_title'            => 'Unclassified',
            'unclassified_body'             => 'Unclassified cookies are cookies that we are in the process of classifying, together with the providers of individual cookies.',
            'template_name'                 => 'seers-cx-top',
            'title_color'                   => '#1a1a1a',
            'body_text_color'               => '#1a1a1a',
            'agree_btn_color'               => '#009900',
            'disagree_btn_color'            => '#1a1a1a',
            'preferences_btn_color'         => '#272727',
            'agree_text_color'              => '#ffffff',
            'disagree_text_color'           => '#ffffff',
            'preferences_text_color'        => '#000000',
            'logo_bg_color'                 => '#fbfbfb',
            'banner_bg_color'               => '#fbfbfb',
            'logo'                          => 'seersco-logo.png',
            'logo_status'                   => 'seers',
            'is_auto_detection'             => false,
            'languages'                     => [],
            'banners'                       => [],
            'default_language'              => $this->getLanguageAttribute()
        ];
    }

    /**
     * checks whether domain already exists for the specified user or not.
     * @return boolean
     **/
    private function isDomainExist() {
        $domain = CbUsersDomains::hasUserDomain($this->user->id, $this->removedURL);
        if ($domain && isset($domain->name)) {
            $this->domain_name = $domain->name;
            $this->domain_id = $domain->id;
            return true;
        }
        return false;
    }

    /**
     * checks whether domain already exists for the specified user or not.
     * @return boolean
     **/
    private function HasUserDomains() {

    }

    /**
     * Comparing whether request secret matches the PLUGIN_SECRET or not.
     * @return boolean
     * */
    private function secretValidated() {
        return config('app.PLUGIN_SECRET') === request()->get('secret');
    }

    /**
     * returns the domain name with random string for password.
     * @return string
     * */
    private function getPassword() {
//        return $this->removedURL . '-' . str_random(5);
        return $this->removedURL;
    }

    private function getLanguageAttribute() {
        $lang = request()->get('lang', 'en_GB');

        $mappingArray = [
            'en_ZA'             => 'US',
            'en_US'             => 'US',
            'en_AU'             => 'US',
            'en_CA'             => 'US',
            'en_NZ'             => 'US',
            'en_GB'             => 'GB',
            'af'                => '',                         // Afghanistan
            'ar'                => 'SA',                         // Argentina
            'ary'               => 'SA',                         // Argentina
            "as"                => '',                         // American Samoa
            "az"                => '',                         // Azerbaijan
            "azb"               => '',                         // Azerbaijan
            "bel"               => '',                         // Belgium
            "bg_BG"             => 'BG',                         // Bulgaria
            "bn_BD"             => '',                         // Brunei
            "bo"                => '',                         // Bolivia
            "bs_BA"             => '',                         // Bahamas
            "ca"                => '',                         // Canada
            "ceb"               => '',
            "cs_CZ"             => 'CZ',
            "cy"                => '',                         // Cyprus
            "da_DK"             => 'DK',
            "de_DE_informal"    => 'DE',                       // Germany
            "de_CH_informal"    => 'DE',                       // Germany
            "de_CH"             => 'DE',                       // Germany
            "de_AT"             => 'DE',                       // Germany
            "de_DE"             => 'DE',                       // Germany
            "dzo"               => '',
            "el"                => 'EL',
            "en"                => 'GB',
            "eo"                => '',
            "es_PE"             => 'ES',
            "es_AR"             => 'ES',
            "es_CO"             => 'ES',
            "es_CR"             => 'ES',
            "es_CL"             => 'ES',
            "es_ES"             => 'ES',
            "es_PR"             => 'ES',
            "es_UY"             => 'ES',
            "es_GT"             => 'ES',
            "es_VE"             => 'ES',
            "es_MX"             => 'ES',
            "et"                => 'EE',
            "eu"                => 'EN',
            "fa_IR"             => '',
            "fi"                => 'FI',
            "fr_BE"             => 'FR',
            "fr_CA"             => 'FR',
            "fr_FR"             => 'FR',
            "fur"               => '',
            "ga"                => 'IE',
            "gd"                => '',
            "gl_ES"             => '',
            "gu"                => '',
            "haz"               => '',
            "he_IL"             => '',
            "hi_IN"             => '',
            "hr"                => 'HR',
            "hsb"               => '',
            "hu_HU"             => 'HU',
            "hy"                => '',
            "id_ID"             => '',
            "is_IS"             => '',
            "it_IT"             => 'IT',
            "ja"                => '',
            "jv_ID"             => '',
            "ka_GE"             => '',
            "kab"               => '',
            "kk"                => '',
            "km"                => '',
            "kn"                => '',
            "ko_KR"             => '',
            "ckb"               => '',
            "lo"                => '',
            "lt_LT"             => 'LT',
            "lv"                => 'LV',
            "mk_MK"             => '',
            "ml_IN"             => '',
            "mn"                => '',
            "mr"                => '',
            "ms_MY"             => '',
            "mt"                => 'MT',
            "my_MM"             => '',
            "nb_NO"             => '',
            "ne_NP"             => '',
            "nl_BE"             => 'NL',
            "nl_NL"             => 'NL',
            "nl_NL_formal"      => 'NL',
            "nn_NO"             => '',
            "oci"               => '',
            "pa_IN"             => '',
            "pl_PL"             => 'PL',
            "ps"                => '',
            "pt_AO"             => 'PT',
            "pt_BR"             => 'PT',
            "pt_PT_ao90"        => 'PT',
            "pt_PT"             => 'PT',
            "rhg"               => '',
            "ro_RO"             => 'RO',
            "ru_RU"             => '',
            "sah"               => '',
            "snd"               => '',
            "si_LK"             => '',
            "sk_SK"             => 'SK',
            "skr"               => '',
            "sl_SI"             => 'SI',
            "sq"                => 'AL',
            "sr_RS"             => '',
            "sv_SE"             => 'SE',
            "sw"                => '',
            "szl"               => '',
            "ta_IN"             => '',
            "te"                => '',
            "th"                => '',
            "tl"                => '',
            "tr_TR"             => 'TR',
            "tt_RU"             => '',
            "tah"               => '',
            "ug_CN"             => '',
            "uk"                => 'GB',
            "ur"                => '',
            "uz_UZ"             => '',
            "vi"                => '',
            "zh_CN"             => 'CN',
            "zh_TW"             => 'CN',
            "zh_HK"             => 'CN',
        ];

        if (array_key_exists($lang, $mappingArray)) {
            return $mappingArray[$lang] === '' ? 'GB' : strtoupper($mappingArray[$lang]);
        }
        return 'GB';
    }


    /**
     * Save all the credentials of the user and creates a new FREE banner for the specified domain for shopify
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getKeyForShopify(Request $request) {
        Log::channel('wp')->info(json_encode($request->header('Referer')));
        return response(['message' => 'please update your plugin', 200]);
        $validator = Validator::make($request->only('email', 'domain', 'secret', 'platform', 'policy_url'), $this->getRules(), $this->getMessages());

        if ($validator->fails()) {
            return response()->json([ 'errors' => $validator->errors() ], 400);
        }

        $this->removedURL = $this->removeProtocol($request->get('domain'));
        $referer = $this->removeProtocol($request->header('Referer'));

        if ($request->get('platform') !== 'shopify' && $this->removedURL !== $referer) {
            return response()->json([ 'message' => 'Referer is not matched'], 400);
        }

        if (!$this->secretValidated()) {
            return response()->json(['message' => 'Secret does not match'], 400);
        }

        if (!$this->validateDomain()) {
            return response()->json(['message' => 'You have entered an invalid domain. Please enter a valid domain address'], 400);
        }

        if (!$this->isUserExists()) {
            $this->isNew = true;
            $this->createUserAccount();
            try {
                $this->subscribeForProduct();
            } catch (\Exception $e) {

            }
        }

        if (!$this->isNew) {
            if (!$this->validateProduct() ) {
                try {
                    $this->subscribeForProduct();
                } catch (\Exception $e) {

                }
            }
        }

        if (!$this->isDomainExist()) {
            if (!$this->isAllowedToAddDomains()) {
                return response()->json([
                    'message' => 'You are not allowed to add more than ' . $this->domain_limit . ' domains'
                ], 400);
            }
            $this->storeDomain('shopify');
        }

        $this->createDialogue();
        $this->createLanguage();
        $this->createBanner();
        $this->createScript();

        return response()->json([
            'key' => $this->key
        ], 200);

//        return response()->json([
//            'key' => '$2y$10$A.R2o9g1H3Ax/RgNMk/cMeSdkgDQExa4T8vI7PcInsKqA7wHLQ/gS',
//            'domain' => $request->get('domain'),
//            'ref' => $request->header('Referer'),
//            'params' => $request->only('email', 'domain', 'secret', 'platform'),
//            'request' => $request->all(),
//        ], 200);
    }

    public function updateBannerSettings(Request $request){

        $validator = Validator::make($request->only('email', 'user_email', 'domain', 'user_domain', 'secret', 'platform'), $this->getRulesForShopify(), $this->getMessages());


        if ($validator->fails()) {
            return response()->json([ 'errors' => $validator->errors() ]);
        }

//        $this->removedURL = $this->removeProtocol($request->get('domain'));
        $this->removedURL = $this->removeProtocol($request->get('user_domain'));
        $referer = $this->removeProtocol($request->header('Referer'));

//        if ($request->get('platform') !== 'shopify' && $this->removedURL !== $referer) {
//            return response()->json([ 'message' => 'Referer is not matched'], 400);
//        }

        if (!$this->secretValidated()) {
            return response()->json([
                'message' => 'Secret does not match'
            ], 400);
        }

        if (!$this->validateDomain()) {
            return response()->json([
                'message' => 'You have entered an invalid domain. Please enter a valid domain address'
            ], 400);
        }

        if (!$this->isUserExists()) {
            $this->is_new_user = true;
            $this->createUserAccount();
            try {
                $this->subscribeForProduct();
            } catch (\Exception $e) {

            }
        }

        if (!$this->is_new_user) {
            if (!$this->validateProduct() ) {
                try {
                    $this->subscribeForProduct();
                } catch (\Exception $e) {

                }
            }
        }

        if (!$this->isDomainExist()) {
            if (!$this->isAllowedToAddDomains()) {
                return response()->json([
                    'message' => 'You are not allowed to add more than ' . $this->domain_limit . ' domains'
                ], 400);
            }
            $this->storeDomain('shopify');
            $this->is_new_domain = true;
        }

        if($this->is_new_user || $this->is_new_domain){
            $this->createDialogue();
            $this->createLanguage();
            $this->createBanner();
            $this->createScript();
        }else{
            $dialogue = CookieXrayDialogue::hasDialogue($this->user->id, $this->domain_id);
            if($dialogue){
                $dialogue->is_cookie_banner = $request->get('status') == '1' ? 1 : 0;
                $dialogue->save();

                $this->is_cookie_banner = $dialogue->is_cookie_banner;

                $script = CookieXrayScript::select('key')->whereDialogueIdAndDomainId($dialogue->id,$this->domain_id)->first();
                $this->key = $script->key;

            }else{
                return response()->json([
                    'message' => 'No Settings Found.'
                ], 400);
            }
        }

        return response()->json([
            'key' => $this->key,
            'status' => $this->is_cookie_banner,
            'message' => 'success'
        ], 200);

    }

    public function updateBannerCustomization(Request $request){

        $this->req_params = $request->all();

        $validator = Validator::make($request->only('email', 'domain', 'secret', 'platform'), $this->getRules(), $this->getMessages());

        if ($validator->fails()) {
            return response()->json([ 'errors' => $validator->errors() ]);
        }

        $this->removedURL = $this->removeProtocol($request->get('domain'));
        $referer = $this->removeProtocol($request->header('Referer'));

        // if ($request->get('platform') !== 'shopify' && $this->removedURL !== $referer) {
        //     return response()->json([ 'message' => 'Referer is not matched'], 400);
        // }

        if (!$this->secretValidated()) {
            return response()->json([
                'message' => 'Secret does not match'
            ], 400);
        }

        if (!$this->validateDomain()) {
            return response()->json([
                'message' => 'You have entered an invalid domain. Please enter a valid domain address'
            ], 400);
        }

//        if (!$this->isUserExists() || !$this->validateProduct() || !$this->isDomainExist()) {
//            return response()->json([
//                'message' => 'User does not exist or Invalid Product or Invalid Domain'
//            ], 400);
//        }
        if (!$this->isUserExists()) {
            return response()->json([
                'message' => 'User does not exist.'
            ], 400);
        }else{
            $this->is_update = true;

            if (!$this->validateProduct()) {
                return response()->json([
                    'message' => 'Invalid Product.'
                ], 400);
            }

            if (!$this->isDomainExist()) {
                return response()->json([
                    'message' => 'Invalid Domain'
                ], 400);
            }

            $this->createDialogue();
            $this->createLanguage();
            $this->createBanner();
        }

        $this->req_response ['message'] = 'Settings has been updated successfully';
        $this->req_response ['test_obj'] = $this->test;
        $script = CookieXrayScript::where('dialogue_id',$this->dialogue_id)->select('id','key')->first();
        $this->cache_key = $script->key;
        $response = $this->makeCurlToApiData($this->domain_name, $script);
        // cache()->forget('script_'.$this->cache_key.'');
        // cache()->forget('user_'.$this->cache_key.'');
        // cache()->forget('dialogue_'.$this->cache_key.'');
        // cache()->forget('banner_'.$this->cache_key.'');
        // cache()->forget('firstabove-language_'.$this->cache_key.'');
        // cache()->forget('first-language_'.$this->cache_key.'');
        // cache()->forget('firstlower-language_'.$this->cache_key.'');
        // cache()->forget('second-language_'.$this->cache_key.'');
        // cache()->forget('secondlower-language_'.$this->cache_key.'');
        // cache()->forget('third-language_'.$this->cache_key.'');
        // cache()->forget('thirdlower-language_'.$this->cache_key.'');
        // cache()->forget('fourth-language_'.$this->cache_key.'');
        // cache()->forget('else-language_'.$this->cache_key.'');
        // cache()->forget('lower-language_'.$this->cache_key.'');
        // cache()->forget('categories_'.$this->cache_key.'');
        // cache()->forget('cookies_'.$this->cache_key.'');

        return response()->json($this->req_response, 200);

    }

    private function makeCurlToApiData($domain, $script) {
        $payload = json_encode(['key' => $script->key, "internal" => true]);
        return curl_request("POST", config("app.cmp_url") . '/api/getApiData', $payload, "Referer: {$domain}");
    }

    public function updatePolicyUrl(Request $request){

        $this->req_params = $request->all();

        $validator = Validator::make($request->only('email', 'domain', 'secret', 'platform', 'policy_url'), $this->getRules(), $this->getMessages());

        if ($validator->fails()) {
            return response()->json([ 'errors' => $validator->errors() ]);
        }

        $this->removedURL = $this->removeProtocol($request->get('domain'));
        $referer = $this->removeProtocol($request->header('Referer'));

        if ($request->get('platform') !== 'shopify' && $this->removedURL !== $referer) {
            return response()->json([ 'message' => 'Referer is not matched'], 400);
        }

        if (!$this->secretValidated()) {
            return response()->json([
                'message' => 'Secret does not match'
            ], 400);
        }

        if (!$this->validateDomain()) {
            return response()->json([
                'message' => 'You have entered an invalid domain. Please enter a valid domain address'
            ], 400);
        }

        if (!$this->isUserExists()) {
            return response()->json([
                'message' => 'User does not exist.'
            ], 400);
        }else{

            if (!$this->validateProduct()) {
                return response()->json([
                    'message' => 'Invalid Product.'
                ], 400);
            }

            $domain = CbUsersDomains::hasUserDomain($this->user->id, $this->removedURL);
            if(!$domain)
                return response()->json([
                    'message' => 'Invalid Domain'
                ], 400);

            $dialogue = CookieXrayDialogue::hasDialogue($this->user->id, $domain->id);
            if($dialogue){
                $dialogue->cookie_policy_url = $this->req_params['enable_policy'] == 'true' ? $this->req_params['policy_url'] : null;

                //$dialogue->cookie_policy_url = $this->req_params['policy_url'];

                $dialogue->save();

                return response()->json([
                    'status' => $dialogue->is_cookie_banner,
                    'message' => 'Policy URL has been updated successfully'
                ], 200);
            }
        }

        return response()->json([
            'message' => 'Something went wrong.'
        ], 400);

    }

    public function getBannerSettings(Request $request){

        $this->req_params = $request->all();

        $validator = Validator::make($request->only('email', 'domain', 'secret', 'platform'), $this->getRules(), $this->getMessages());

        if ($validator->fails()) {
            return response()->json([ 'errors' => $validator->errors() ]);
        }

        $this->removedURL = $this->removeProtocol($request->get('domain'));
        $referer = $this->removeProtocol($request->header('Referer'));

        // if ($request->get('platform') !== 'shopify' && $this->removedURL !== $referer) {
        //     return response()->json([ 'message' => 'Referer is not matched'], 400);
        // }

        if (!$this->secretValidated()) {
            return response()->json([
                'message' => 'Secret does not match'
            ], 400);
        }

        if (!$this->validateDomain()) {
            return response()->json([
                'message' => 'You have entered an invalid domain. Please enter a valid domain address'
            ], 400);
        }

//        if (!$this->isUserExists() || !$this->validateProduct() || !$this->isDomainExist()) {
//            return response()->json([
//                'message' => 'User does not exist or Invalid Product or Invalid Domain'
//            ], 400);
//        }
        if (!$this->isUserExists()) {
            return response()->json([
                'message' => 'User does not exist.'
            ], 400);
        }else{
            $this->is_update = true;

            /*if (!$this->validateProduct()) {
                return response()->json([
                    'message' => 'Invalid Product.'
                ], 400);
            }*/

            if (!$this->isDomainExist()) {
                return response()->json([
                    'message' => 'Invalid Domain'
                ], 400);
            }
        }

        $this->req_response ['message'] = 'Banner Settings';

        $dialogue = CookieXrayDialogue::where( 'user_id',$this->user->id)->where('cb_users_domain_id', $this->domain_id)->firstOrFail();
        $dialogue_lang = [];
        if (!$dialogue) {
            $dialogue = [];
        }

        if (!empty($dialogue->id)) {
            $dialogue_lang = CookieXrayDialogueLanguage::where('dialogue_id', $dialogue->id)->where('country_code', $this->getLanguageAttribute())->firstOrFail();

            if (!$dialogue_lang) {
                $dialogue_lang = [];
            }
        }

        $this->req_response ['bannersettings'] = ((!empty($dialogue_lang)) ? $dialogue_lang : $dialogue );
        $dialoguebanners = $dialogue->banners();
        if (!empty($dialogue->id)) {
            $banners = CookieXrayDialogueBanner::where('dialogue_id', $dialogue->id)->firstOrFail();
            if ($banners) {
                $dialoguebanners = $banners;
            }

        }
        $this->req_response ['bannersettingsbanners'] = $dialoguebanners;

        return response()->json($this->req_response, 200);

    }
}
