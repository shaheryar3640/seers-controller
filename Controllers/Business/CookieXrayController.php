<?php

namespace App\Http\Controllers\Business;

use App\CbCookies;
use App\CbUsersDomains;
use App\CookieXrayDefaultDialogueLanguage;
use App\CookieXrayDialogue;
use App\CookieXrayDialogueBanner;
use App\CookieXrayDialogueLanguage;
use App\CookieXrayPolicy;
use App\CookieXrayScript;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Auth;
use File;
use phpseclib\Crypt\Hash;
use Image;
class CookieXrayController extends Controller
{
    public function __construct()
    {
        //$this->middleware('business');
    }

    public function saveDialogueProperties(Request $request) {

        // Creating new dialogue or getting the existing one.
        $dialogue = CookieXrayDialogue::firstOrCreate([
            'cb_users_domain_id' => $request->get('domain_id'),
            'user_id' => auth()->id()
        ]);

        $dialogue->fill($request->all());
        $dialogue->save();

        if (!$dialogue || is_null($dialogue) || $dialogue === null) {
            return null;
        }

        $languages = $request->get('languages');

        if (count($languages) > 0) {
            $this->saveLanguages($languages, $dialogue);
        } else {
            $language = $request['language'];
            $lang = $dialogue->languages()->firstOrCreate([
                'name' => $language["name"],
            ]);
            if($lang){
                $lang->fill($language);
                $lang->save();
            }
        }

        $domain = CbUsersDomains::select('name')->whereId($request->get('domain_id'))->first();

        $script = CookieXrayScript::firstOrNew([
            'user_id' => $dialogue->user_id,
            'domain_id' => $dialogue->cb_users_domain_id,
            'dialogue_id' => $dialogue->id,
        ]);
        if(!$script->exists){
            $script->simple_key = Auth::user()->email . $domain->name . 'seers';
            $script->key  = bcrypt($script->simple_key);
            $script->path = 'scripts';
            $script->file_name = time() . '_script';
        }
        $script->save();

        $default_language = null;

        foreach ($dialogue->languages as $language) {
            if ($language->country_code === $request->default_language) {
                $default_language = $language;
            }
        }

        $banners = $request->get('banners');

        foreach ($banners as $banner) {
            $new_banner = CookieXrayDialogueBanner::firstOrCreate([
                'name' => $banner['name'],
                'dialogue_id' => $dialogue->id
            ]);
            $new_banner->fill($banner);
            $new_banner->dialogue_id = $dialogue->id;
            $new_banner->save();
        }

        $banners = $dialogue->banners;

        return response()->json([
            'cookie_xray_dialogue' => $dialogue,
            'dialogue_languages' =>  $dialogue->languages ?? null,
            'default_language' => $default_language ?? null,
            'script_key' => $script->key,
            'banners' => $banners
        ]);
    }

    private function saveLanguages ($languages, CookieXrayDialogue $dialogue) {
        foreach ($languages as $language) {
            $lang = $dialogue->languages()->firstOrCreate([
                'name' => $language["name"],
            ]);
            if ($lang) {
                $lang->fill($language);
                $lang->save();
            }
        }
    }

    public function saveDialogueLanguage(Request $request)
    {
        $language = CookieXrayDefaultDialogueLanguage::find($request->get('lang_id'));
        $dialogue = CookieXrayDialogue::find($request->get('dialogue_id'));
        $index = 0;
        if(!is_null($dialogue) && $dialogue->languages->count() > 0)
        {
            foreach ($dialogue->languages as $dialogue_lang)
            {
                if($dialogue_lang->name == $language->name)
                {
                    return response()
                        ->json(['message' => 'Language already exists'], 400);
                } else {
                    $index += 1;
                }
            }
            if($index == $dialogue->languages->count()) {
                $dialogue_lang = CookieXrayDialogueLanguage::create($language->toArray());
                $dialogue_lang->dialogue_id = $request->get('dialogue_id');
                $dialogue_lang->save();

                $languages = CookieXrayDialogueLanguage::where('dialogue_id', '=', $request->get('dialogue_id'))->get();

                return response([
                    'success' => 'Language added successfully',
                    'languages' => $languages,
                    'dialogue' => $dialogue
                ], 200);
            }
        } else {
            $dialogue_lang = CookieXrayDialogueLanguage::create($language->toArray());
            $dialogue_lang->dialogue_id = $request->get('dialogue_id');
            $dialogue_lang->save();

            $languages = CookieXrayDialogueLanguage::where('dialogue_id', '=', $request->get('dialogue_id'))->get();

            return response([
                'success' => 'Language added successfully',
                'languages' => $languages,
                'dialogue' => $dialogue
            ], 200);
        }
    }

    public function getDefaultLanguages()
    {
        $languages = CookieXrayDefaultDialogueLanguage::where('is_active', '=', 1)->orderBy('name','asc')->get();
        return $languages ?? null;
    }

    public function getDialogue($id)
    {
        $dialogue = CookieXrayDialogue::with('languages', 'banners')->where('cb_users_domain_id','=', $id)
            ->where('user_id', '=', Auth::User()->id)->first();

        $english = CookieXrayDefaultDialogueLanguage::where('country_code', '=', 'GB')->first();

        $policy = CookieXrayPolicy::where(['cb_users_domain_id' => $id, 'user_id' => auth()->user()->id])->first();

       $consentlog_limit_reached = CbUsersDomains::where(['id'=>$id])->pluck('enabled');


        // Dialogue is not created.
        if(is_null($dialogue))
        {
            return response()->json([
                'dialogue' => $dialogue,
                'policy' => ($policy != null) ? true : false,
                'default_language' => $english,
                'dialogue_languages' => Array($english),
                'preDefinedLanguages' => $this->getDefaultLanguages(),
                //'consent_log_enabled' => $consent_log_enabled
            ]);
        } else {
            $default_language = null;

            if(!is_null($dialogue) && !is_null($dialogue->languages)) {

                // if there is only one language in dialogue languages relation
                if($dialogue->languages->count() == 1) {
                    $languages = Array($dialogue->languages);
                } else {
                    // if there are more than one langauges in dialogue languages relation
                    $languages = $dialogue->languages;
                }
                // Selecting default language of dialogue from the given dialogue languages
                foreach ($dialogue->languages as $language) {
                    if($language->country_code == $dialogue->default_language)
                        $default_language = $language;
                }
            }
        }

        return response()->json([
            'dialogue' => $dialogue,
            'policy' => ($policy != null) ? true : false,
            'default_language' => $default_language ?? $english,
            'dialogue_languages' => count($dialogue->languages) > 0 ? $dialogue->languages : Array($english),
            'preDefinedLanguages' => $this->getDefaultLanguages(),
            'consentlog_limit_reached' => $consentlog_limit_reached
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getScript(Request $request)
    {
        $script = CookieXrayScript::where('domain_id','=',$request->get('domain_id'))
            ->where('user_id', '=', Auth::User()->id)->first();

        $domain = CbUsersDomains::find($request->get('domain_id'));



//        $public = public_path('scripts');
//        $data = "";

//        $data += "buildHTML() {";
//        $data += "var html = \"\";\"";
//        $data += "html += \"<div class=\"wraper seers_wraper\">\";\"";
//        $data += "html += \"<div class=\"contents contents-bottom top\" style=\"background-color: #ffffff; position:fixed; z-index: 999\">\";";
//        $data += "html += \"<div class=\"contents-bottom-contents\">\";";
//        $data += "html += \"<h3 style=\"color: #1a1a1a\">This website uses cookies</h3>\";";
//        $data += "html += \"<p style=\"color: #1a1a1a\">lorem text herelorem text herelorem text herelorem text herelorem text herelorem text here,lorem text herelorem text herelorem text herelorem text herelorem text herelorem text here</p>\";";
//        $data += "html += \"<div class=\"necesry\">\";";
//        $data += "html += \"<ul style=\"float:left; \">\";";
//        $data += "html += \"<li><label><input type=\"checkbox\" value=\"\">Preferences</label></li>\";";
//        $data += "html += \"<li><label><input type=\"checkbox\" value=\"\">Statistics</label></li>\";";
//        $data += "html += \"<li><label><input type=\"checkbox\" value=\"\">Marketing</label></li>\";";
//        $data += "html += \"</ul>\";";
//        $data += "html += \"<div class=\"button-wrapper\">\";";
//        $data += "html += \"<button class=\"accept\">Accept</button>\";";
//        $data += "html += \"<button class=\"decline\">Decline</button>\";";
//        $data += "html += \"<button id=\"showdetails\" class=\"\"><label>Show details <i class=\"fa fa-angle-down\"></i></label></button>\";";
//        $data += "html += \"</div>\";";
//        $data += "html += \"</div>\";";
//        $data += "html += \"</div>\";";
//        $data += "html += \"</div>\";";
//        $data += "html += \"</div>\";";
//        $data += "return html;\";";
//        $data += "}";

//        $data = json_encode(['Example 1','Example 2','Example 3',]);
//        $fileName = time() . '_datafile.js';
//        File::put(public_path('/scripts/'.$fileName),$data);

        return response()->json([
            'script' => $script,
            'domain' => $domain
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadLogo(Request $request){

        $dialogue = CookieXrayDialogue::firstOrNew(
            [
                'cb_users_domain_id'=> $request->get('domain_id'),
                'user_id'           => Auth::User()->id
            ]);

        $old_logo = $dialogue->logo;

        $logo_path = base_path('images/logo');

        $images = [];

        $photos = $request->file('file');

        if (!is_array($photos)) {
            $photos = [$photos];
        }

        if (!is_dir($logo_path)) {
            mkdir($logo_path, 0777);
        }

        for ($i = 0; $i < count($photos); $i++) {
            $photo = $photos[$i];
            $name = sha1(date('YmdHis') . str_random(30));
            $file_name = $name . '.' . $photo->getClientOriginalExtension();
            $orig_name = basename($photo->getClientOriginalName());


            Image::make($photo)
                ->resize(200, 200, function ($constraints) {
//                    $constraints->aspectRatio();
                })->save($logo_path . '/' . $file_name);

            $dialogue->logo = $file_name;

        }

        $dialogue->save();

        if($old_logo && $old_logo != 'seersco-logo.png' && file_exists($logo_path . '/' . $old_logo)){
            unlink($logo_path . '/' . $old_logo);
        }

        return response()->json([
            'message' => 'uploaded successfully',
            'logo' => $dialogue->logo
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCookies(Request $request)
    {
        $discovered_cookies = CbCookies::whereDomIdAndSelfDeclared($request->get('domain_id'), false)->get();
        $self_declared_cookies = CbCookies::whereDomIdAndSelfDeclared($request->get('domain_id'), true)->get();

        return response()->json([
            'discovered_cookies' => $discovered_cookies,
            'self_declared_cookies' => $self_declared_cookies
        ]);
    }

    public function createCookie(Request $request)
    {
        $cookieObj = $request->get('cookie');
        $found = false;
        $cookies = CbCookies::whereDomId($request->get('domain_id'))->get();

        foreach ($cookies as $cookie) {
            if($cookie->name == $cookieObj["name"]) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $cookie = new CbCookies;
            $cookie->name = $cookieObj["name"];
            $cookie->slug = $cookieObj["name"];
            $cookie->cb_cat_id = $cookieObj["category_id"];
            $cookie->dom_id = $request->get('domain_id');
            $cookie->cb_risk_id = 2;
            $cookie->first_found_url = $cookieObj["first_found_url"];
            $cookie->provider = $cookieObj["provider"];
            $cookie->domain_path = $cookieObj["domain_path"];
            $cookie->example_value = $cookieObj["example_value"];
            $cookie->purpose_desc = $cookieObj["purpose_desc"];
            $cookie->httponly = $cookieObj["http_only"];
            $cookie->secure = $cookieObj["secure"];
            $cookie->expiry = Carbon::now()->addDays($cookieObj["expiry"]);
            $cookie->type = $cookieObj["type"];
            $cookie->enabled = true;
            $cookie->self_declared = true;
            $cookie->save();

            return response()->json([
                'cookie' => $cookie
            ], 200);
        } else {
            return response()->json([
                'cookie_name' => $cookieObj["name"]
            ], 400);
        }
    }

    public function updateCookie(Request $request)
    {
        $cookie = CbCookies::whereDomIdAndId($request->get('domain_id'), $request->get('cookie_id'))->first();
        $cookieObj = $request->get('cookie');
        $cookie->cb_cat_id = $cookieObj["cb_cat_id"];
        $cookie->httponly = $cookieObj["httponly"];
        $cookie->secure = $cookieObj["secure"];
        $cookie->purpose_desc = $cookieObj["purpose_desc"];
        $cookie->save();

        return response()->json([
            'cookie' => $cookie
        ]);
    }

    public function removeCookie(Request $request)
    {
        $deletedCookie = CbCookies::destroy($request->get('id'));
        if($deletedCookie == 1) {
            return response()->json([
                'success' => true
            ], 200);
        } else {
            return response()->json([
                'error' => true,
                'message' => 'Could not delete cookie'
            ], 405);
        }
    }
}
