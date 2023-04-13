<?php

namespace App\Http\Controllers;

use App\Models\CbUsersDomains;
use App\Mail\SendDomainEmail;
use App\Models\User;
use App\Models\CbReportsReceivers;
use App\Models\Plan;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Redirect;
use App\Url;
use App\Models\WebUrl;
use App\Models\Testimonial;
use App\Models\UFeature;
use App\Models\UPlan;
use App\Models\UProduct;
use Carbon\Carbon;


class CookiebotController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */


    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    // public function index()
    // {
    //      $activePlans = \App\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
    //     return view('cookiebot.index')->with(['activePlans' => $activePlans, 'data_control' => 'data_control']);
    // }
    public function index()
    {
        if(Auth::check()){
            return Redirect::to('/cookie-consent-banner.html');
        }
        $page = request()->path();
        $web_url = WebUrl::where(['name'=> $page])->pluck('id');
        $testimonials = Testimonial::where(['web_urls_id' => $web_url])->get();
        $newscript = true;
        //  $activePlans = \App\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
        return view('cookiebot.index')->with(['testimonials' => $testimonials, 'data_control' => 'data_control', 'newscript' => $newscript]);
    }
    public function login()
    {
        return redirect()->route('login');
        return view('cookiebot.login-cookie-xray');
    }


    public function getUserEmail(Request $request)
    {
        //dd($request->all());
        return view('cookiebot.getemail')->with('website', $request->get("websiteScan"));
    }


    public function saveUserEmail(Request $request){
        // dd($request->all());
        $redirect = null;
        if($request->get('redirect')){
            $redirect = $request->get('redirect');
        }
        $user = User::where('email', $request->get('email'))->first();

        if (strpos($request->get('website'), ".") == false){
            //dd($_SERVER['HTTP_REFERER']);
            if(($redirect != null) && ($redirect == true)){
                return response()->json(['errors'=>'website'], 400);
            }
            return Redirect::back()->with('error', 'InvalidUrl');
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);
        //dd($validator->fails());
        if ($validator->fails()) {
            if(($redirect != null) && ($redirect == true)){
                return response()->json(['errors'=>'email'],400);
            }
            return Redirect::back()->with('error', 'InvalidEmail');
        }

        $website = $this->removeProtocol($request->get('website'));

        //return response()->json(['errors'=>gethostbyname($website)], 400);
        //return response()->json(['errors'=>checkdnsrr($website, 'MX')], 400);
        $domainFinal = explode("/", $website);
        if ( checkdnsrr($domainFinal[0], 'A') === false) {
            return response()->json(['error'=>'InvalidUrl'], 400);
            //return Redirect::back()->with('error', 'InvalidUrl');
        }

        if(!isset($user->email)){
            //echo "Store the user and create the domain and send him email";
            $user = new User();
            $user->fname = '';
            $user->lname = '';
            $user->email = $request->get('email');
            $user->company = '';
            $user->phone = '';
            $user->job_role = '';
            $user->address = '';
            $user->admin = 3;
            $user->password = '';
            $user->is_register = false;
            $user->save();

            $product = Product::where('name','cookie_consent')->first();
            $uproduct = new UProduct();
            $uproduct->name = $product->name;
            $uproduct->display_name = $product->display_name;
            $uproduct->description = $product->description;
            $uproduct->url = $product->url;
            $uproduct->price = $product->price;
            $uproduct->discount = $product->discount;
            $uproduct->sort_order = $product->sort_order;
            $uproduct->is_active = $product->is_active;
            $uproduct->expired_on = Carbon::now()->addMonth();
            $uproduct->currency = '';
            $uproduct->recursive_status = 'monthly';
            $uproduct->on_trial = $product->on_trial;
            $uproduct->trial_days = $product->trial_days;
            $uproduct->user_id  =$user->id;
            $uproduct->save();

            $plan = Plan::with('features')->where('name','free')->where('product_id',2)->first();
            $uplan = new UPlan();
            $uplan->name = $plan->name;
            $uplan->display_name = $plan->display_name;
            $uplan->slug = $plan->slug;
            $uplan->description = $plan->description;
            $uplan->price = $plan->price;
            $uplan->expired_on = $uproduct->expired_on;
            $uplan->sort_order = $plan->sort_order;
            $uplan->is_active = $plan->is_active;
            $uplan->u_product_id = $uproduct->id;
            $uplan->save();

            foreach($plan->features as $f){
                $ufeature = new UFeature();
                $ufeature->u_plan_id = $uplan->id;
                $ufeature->name = $f->name;
                $ufeature->display_name = $f->display_name;
                $ufeature->value = $f->value;
                $ufeature->price = $f->price;
                $ufeature->description = $f->description;
                $ufeature->is_visible = $f->is_visible;
                $ufeature->is_active = $f->is_active;
                $ufeature->sort_order = $f->sort_order;
                $ufeature->save();     
            }

            // $domain = new CbUsersDomains();
            // $domain->user_id = $user->id;
            // $domain->name = $website;
            // $domain->slug = strtolower($website) . '-' . rand(99,9999) .'-'.$user->id;
            // $domain->platform = 'audit';
            // $domain->scan_frequency = 'only_once';
            // $domain->save();

            // $reportTo = new CbReportsReceivers();
            // $reportTo->user_id = $user->id;
            // $reportTo->dom_id = $domain->id;
            // $reportTo->receiver_email = $user->email;
            // $reportTo->save();

            //dd($domain);




//            $cmpData = json_encode(['email' => $user->email, 'website' => $website,'user_id'=>$user->id]);
//            $res = curl_request('post',config('app.cmp_url').'/api/auth/save-email-cookieXray-cmp', $cmpData);
//            $res = json_decode($res);

            $domain = new CbUsersDomains();
            $domain->user_id = $user->id;
            $domain->name = $website;
            $domain->slug = strtolower($website) . '-' . rand(99,9999) .'-'.$user->id;
            $domain->platform = 'audit';
            $domain->scan_frequency = 'only_once';
            $domain->save();

            $reportTo = new CbReportsReceivers();
            $reportTo->user_id = $user->id;
            $reportTo->dom_id = $domain->id;
            $reportTo->receiver_email = $user->email;
            $reportTo->save();




            Mail::send(new SendDomainEmail($domain));
            session()->put('slug', $domain->slug);
            if(($redirect != null) && ($redirect == true)){
                return response()->json(['url'=>'/thank-you-cookie-scan?'.$domain->slug], 200);
            }
            return response()->json(['url'=>'/thank-you-cookie-scan?'.$domain->slug], 200);
            //return redirect('/register/business');
        }
        else{
//            $cmpData = json_encode(['email' => $user->email, 'website' => $website,'user_id'=>$user->id]);
//            $res = curl_request('post',config('app.cmp_url').'/api/auth/save-email-cookieXray-cmp', $cmpData);
//            $res = json_decode($res);
            $user_exist_id = User::where('email',$request->email)->first();
            $domain_exist = CbUsersDomains::where('name',$website)->where('user_id',$user_exist_id->id)->first();
            if(isset($domain_exist) && !empty($domain_exist)){
                return response()->json(['error'=>'Domain already exist.'],400);
            }
            else{
            $domain = new CbUsersDomains();
            $domain->user_id = $user->id;
            $domain->name = $website;
            $domain->slug = strtolower($website) . '-' . rand(99,9999) .'-'.$user->id;
            $domain->platform = 'audit';
            $domain->scan_frequency = 'only_once';
            $domain->script_platform = 'audit';
            $domain->save();

            $reportTo = new CbReportsReceivers();
            $reportTo->user_id = $user->id;
            $reportTo->dom_id = $domain->id;
            $reportTo->receiver_email = $user->email;
            $reportTo->save();




            Mail::send(new SendDomainEmail($domain));
            session()->put('slug', $domain->slug);
            if(($redirect != null) && ($redirect == true)){
                return response()->json(['url'=>'/thank-you-cookie-scan?'.$domain->slug], 200);
            }
            return response()->json(['url'=>'/thank-you-cookie-scan?'.$domain->slug], 200);
            }






            // if(($redirect != null) && ($redirect == true)){
            //     return response()->json(['url'=>route('login-cookie-xray')]);
            // }
            // return redirect('/login-cookie-xray.html');
            /*if(($user[0]->admin == 0)){
                //echo "Create the domain and send him to login page";
                $domain = CbUsersDomains::where(['user_id'=>$user[0]->id])->get();
                if($domain->count() > 0) {
                    if (($user[0]->MembershipPlans->slug == 'free') || ($user[0]->MembershipPlans->slug == 'silver')) {
                        if($domain->count() > 0){
                            return redirect('/cookieXray.html')->with('error', 'DomainScanOutOfLimit');
                        }
                    } else if (($user[0]->MembershipPlans->slug == 'gold') || ($user[0]->MembershipPlans->slug == 'platinum')) {
                        if($domain->count() >= 5){
                            return redirect('/cookieXray.html')->with('error', 'DomainScanOutOfLimit');
                        }
                    }
                }

                $domain = CbUsersDomains::where(['name'=> $website ,'user_id'=>$user[0]->id])->get();

                //dd($domain);
                if(!isset($domain[0]->name)) {
                    $domain = new CbUsersDomains();
                    $domain->user_id = $user[0]->id;
                    $domain->name = $website;
                    $domain->slug = strtolower($website) . '-' . rand(99, 9999) . '-' . $user[0]->id;
                    $domain->save();

                    $reportTo = new CbReportsReceivers();
                    $reportTo->user_id = $user[0]->id;
                    $reportTo->dom_id = $domain->id;
                    $reportTo->receiver_email = $user[0]->email;
                    $reportTo->save();

                    Mail::send(new SendDomainEmail($domain));
                }else{
                    //dd('DomainAlreadyExist');
                    return redirect('/cookieXray.html')->with('error', 'DomainAlreadyExist');
                }
                //Auth::guard()->login($user[0]);
                return redirect('/login');
            }else{
                $domain = CbUsersDomains::where(['user_id'=>$user[0]->id])->get();

                if($domain->count() > 0){
                    return redirect('/get-email-cookieXray.html?websiteScan='.$website)->with('error', 'SingleDomainError');
                }

                $domain = CbUsersDomains::where(['name'=> $website ,'user_id'=>$user[0]->id])->get();

                //dd($domain);
                if(!isset($domain[0]->name)) {
                    $domain = new CbUsersDomains();
                    $domain->user_id = $user[0]->id;
                    $domain->name = $website;
                    $domain->slug = strtolower($website) . '-' . rand(99, 9999) . '-' . $user[0]->id;
                    $domain->save();

                    $reportTo = new CbReportsReceivers();
                    $reportTo->user_id = $user[0]->id;
                    $reportTo->dom_id = $domain->id;
                    $reportTo->receiver_email = $user[0]->email;
                    $reportTo->save();
                }else{
                    return redirect('/cookieXray.html')->with('error', 'DomainAlreadyExist');
                }
                return redirect('/login');
                //return redirect('/register/business');
            }*/
        }
        //if($user)
    }
    public function saveUserEmailNew(Request $request){
        //dd($request->all());
        // return response()->json($request->all(), 200);
        $redirect = null;
        if($request->get('redirect')){
            $redirect = $request->get('redirect');
        }
        $user = User::where('email', $request->get('email'))->get();
        //dd($user[0]->email);

        if (strpos($request->get('website'), ".") == false){
            //dd($_SERVER['HTTP_REFERER']);
            if(($redirect != null) && ($redirect == true)){
                return response()->json(['errors'=>'website'], 400);
            }
            return Redirect::back()->with('error', 'InvalidUrl');
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);
        //dd($validator->fails());
        if ($validator->fails()) {
            if(($redirect != null) && ($redirect == true)){
                return response()->json(['errors'=>'email'],400);
            }
            return Redirect::back()->with('error', 'InvalidEmail');
        }

        $website = $this->removeProtocol($request->get('website'));

        //return response()->json(['errors'=>gethostbyname($website)], 400);
        //return response()->json(['errors'=>checkdnsrr($website, 'MX')], 400);
        $domainFinal = explode("/", $website);
        if ( checkdnsrr($domainFinal[0], 'A') === false) {
            return response()->json(['error'=>'InvalidUrl'], 400);
            //return Redirect::back()->with('error', 'InvalidUrl');
        }

        if(!isset($user[0]->email)){
            //echo "Store the user and create the domain and send him email";
            $user = new User();
            $user->fname = '';
            $user->lname = '';
            $user->email = $request->get('email');
            $user->company = '';
            $user->phone = '';
            $user->job_role = '';
            $user->address = '';
            $user->admin = 3;
            $user->password = '';
            $user->is_register = false;
            $user->save();

            $domain = new CbUsersDomains();
            $domain->user_id = $user->id;
            $domain->name = $website;
            $domain->scan_frequency = 'only_once';
            $domain->platform = 'audit';
            $domain->slug = strtolower($website) . '-' . rand(99,9999) .'-'.$user->id;
            $domain->save();

            $reportTo = new CbReportsReceivers();
            $reportTo->user_id = $user->id;
            $reportTo->dom_id = $domain->id;
            $reportTo->receiver_email = $user->email;
            $reportTo->save();

            //dd($domain);
            //return response()->json(['domain'=>$domain]);
            //session()->put('slug',$domain->slug);
            Mail::send(new SendDomainEmail($domain));
            if(($redirect != null) && ($redirect == true)){
                //session()->put('slug',$domain->slug);
                return response()->json(['url'=>'thank-you-cookie-scan?'.$domain->slug], 200);
            }
            //session()->put('slug',$domain->slug);
            return response()->json(['url'=>'/thank-you-cookie-scan?'.$domain->slug], 200);
            //return redirect('/register/business');
        }else{
            return redirect('/business/cookieXray');
            // if(($redirect != null) && ($redirect == true)){
            //     return response()->json(['url'=>route('login-cookie-xray')]);
            // }
            // return redirect('/login-cookie-xray.html');
            /*if(($user[0]->admin == 0)){
                //echo "Create the domain and send him to login page";
                $domain = CbUsersDomains::where(['user_id'=>$user[0]->id])->get();
                if($domain->count() > 0) {
                    if (($user[0]->MembershipPlans->slug == 'free') || ($user[0]->MembershipPlans->slug == 'silver')) {
                        if($domain->count() > 0){
                            return redirect('/cookieXray.html')->with('error', 'DomainScanOutOfLimit');
                        }
                    } else if (($user[0]->MembershipPlans->slug == 'gold') || ($user[0]->MembershipPlans->slug == 'platinum')) {
                        if($domain->count() >= 5){
                            return redirect('/cookieXray.html')->with('error', 'DomainScanOutOfLimit');
                        }
                    }
                }

                $domain = CbUsersDomains::where(['name'=> $website ,'user_id'=>$user[0]->id])->get();

                //dd($domain);
                if(!isset($domain[0]->name)) {
                    $domain = new CbUsersDomains();
                    $domain->user_id = $user[0]->id;
                    $domain->name = $website;
                    $domain->slug = strtolower($website) . '-' . rand(99, 9999) . '-' . $user[0]->id;
                    $domain->save();

                    $reportTo = new CbReportsReceivers();
                    $reportTo->user_id = $user[0]->id;
                    $reportTo->dom_id = $domain->id;
                    $reportTo->receiver_email = $user[0]->email;
                    $reportTo->save();

                    Mail::send(new SendDomainEmail($domain));
                }else{
                    //dd('DomainAlreadyExist');
                    return redirect('/cookieXray.html')->with('error', 'DomainAlreadyExist');
                }
                //Auth::guard()->login($user[0]);
                return redirect('/login');
            }else{
                $domain = CbUsersDomains::where(['user_id'=>$user[0]->id])->get();

                if($domain->count() > 0){
                    return redirect('/get-email-cookieXray.html?websiteScan='.$website)->with('error', 'SingleDomainError');
                }

                $domain = CbUsersDomains::where(['name'=> $website ,'user_id'=>$user[0]->id])->get();

                //dd($domain);
                if(!isset($domain[0]->name)) {
                    $domain = new CbUsersDomains();
                    $domain->user_id = $user[0]->id;
                    $domain->name = $website;
                    $domain->slug = strtolower($website) . '-' . rand(99, 9999) . '-' . $user[0]->id;
                    $domain->save();

                    $reportTo = new CbReportsReceivers();
                    $reportTo->user_id = $user[0]->id;
                    $reportTo->dom_id = $domain->id;
                    $reportTo->receiver_email = $user[0]->email;
                    $reportTo->save();
                }else{
                    return redirect('/cookieXray.html')->with('error', 'DomainAlreadyExist');
                }
                return redirect('/login');
                //return redirect('/register/business');
            }*/
        }
        //if($user)
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

    public function afterCookieAuditPage(){
        if(session()->get('slug') == "" || session()->get('slug') == null){
            return view('errors.404');
        }
        if( str_replace("_",".",str_replace("=", "", request()->getQueryString())) == session()->get('slug')){
            session()->forget('slug');
            return view('thank-you-cookie-scan-new');
        }
        else{
            return view('errors.404');
        }
    }


    /*protected function update($id, Request $request)
    {
        $data = $request->all();
        $faq = Faq::find($id);
        $faq->fill($data);
        $faq->save();
        return back()->with('success', 'Faq has been updated successfully!');
    }*/

}
