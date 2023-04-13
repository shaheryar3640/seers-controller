<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeAdvisor;
use App\Models\Hubspot;
use App\Models\Shop;
use App\Models\SubService;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Mail\WelcomeMail;
use App\Models\Newsletter;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;

use Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class BusinessRegisterController extends Controller
{
    //

     use RegistersUsers;

     public function __construct()
    {
        $this->middleware('guest');
        //$this->middleware('CheckReferral');
    }

	// public function show(){
	// 	return view('business_register');
	// }

    // public function showNew(){
    //     return view('business_register_new');
    // }

    public function show(Request $request){
        $login = false;
        $register = true;
        $marketPlaceEmail = null;
        if ($request->has("key")) {
            $secret = $request->query("key");
            $source = $request->query("source");
            $user = User::where(['market_place_secret' => $secret, 'type' => $source])->select("email", "id", "type")->first();
            if (Hash::check($user->email . "_" . $user->id . "_" . $user->type, $secret)) {
                $marketPlaceEmail = $user->email;
            }
        }
		return view('auth.login')->with(compact('login','register', 'marketPlaceEmail'));
	}

    public function index(Request $request){

        request()->validate([
			'email'=>'required',
			'password' => 'required',
            'terms' => 'required',
        ],
        [
            'email.required' => 'Please provide an email ID',
            'password.required' => 'Password is required',
            'terms.required' => 'You have to accept the terms and conditions'
        ]
        );

        $secret = config('app.RECAPTCHA_SECRET_KEY');
        $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$secret.'&response='.$request->input('g-recaptcha-response'));
        $responseData = json_decode($verifyResponse);
//        $success = true;
        $bypass_recaptcha = config('app.BYPASS_RECAPTCHA');
        //return response()->json(['message'=>json_decode($verifyResponse),'captchainput'=>$request->input('g-recaptcha-response'),'response'=>$responseData->success,'fname'=>$request['fname']],401);
         if($responseData->success==true || $bypass_recaptcha) {
//            if($success === true){
                //$succMsg = 'Your contact request have submitted successfully.';
                $user = User::where('email', $request->get('email'))->first();
                if($user && ($user->is_removed == 1 || $user->is_removed == true)) {
                    return back()->with('deleteduser', 'Your account has been deleted');
                }

                if($user && isset($user->email) && $user->is_register){
                    return back()->with('userexist', 'This email has already been Registered.');
                }

                if(!$user || !isset($user->email)){
                    $user = new user;
                }

                $referred_by = Cookie::get('referral');
                $user->fname = '';
                $user->lname = '';
                $user->email = $request->get('email');
                $user->company = '';
                $user->phone = '';
                $user->job_role = '';
                $user->address = '';
                $user->admin = 0;
                $user->is_register = true;
                $user->verification_code = random_int(10000, 99999);
                $user->password = bcrypt($request->get('password'));
                $user->bookname = $request->get('guidename');
                $tokenResult = $user->createToken('Personal Access Token');

                $token = $tokenResult->token;

                $token->expires_at = Carbon::now()->addDays(1);
                $user->access_token = $tokenResult->accessToken;
                // dd($tokenResult);
                $user->save();

                if($request->newsletter ){
                    $Newsletter = new Newsletter();
                    $Newsletter->email = $request->email;
                    $Newsletter->user_id = $user->id;
                    $Newsletter->save();
                }

                $user->affiliate_id = str_random(10).'-'.$user->id;
                $user->referred_by = $referred_by;
                $user->save();



               /* $arr = array(
                    'properties' => array(
                        array(
                            'property' => 'seers_user_id',
                            'value' => $user->id
                        ),
                        array(
                            'property' => 'email',
                            'value' => $user->email
                        ),
                        array(
                            'property' => 'firstname',
                            'value' => $user->fname
                        ),
                        array(
                            'property' => 'lastname',
                            'value' => $user->lname
                        ),
                        array(
                            'property' => 'phone',
                            'value' => $user->phone
                        ),
                        array(
                            'property' => 'company',
                            'value' => $user->company
                        ),
                        array(
                            'property' => 'full_address',
                            'value' => $user->address
                        ),
                        array(
                            'property' => 'business_advisor_',
                            'value' => 'Business'
                        ),
                        array(
                            'property' => 'user_registered_at',
                            'value' => date("Y-m-d H:i:s", strtotime($user->created_at))
                        ),
                        array(
                            'property' => 'user_profile_update',
                            'value' =>  date("Y-m-d H:i:s", strtotime($user->updated_at))
                        ),
                        array(
                            'property' => 'trial_end_date',
                            'value' =>  ($user->trial_ends_at != null) ? date("Y-m-d H:i:s", strtotime($user->trial_ends_at)): "null"
                        ),
                        array(
                            'property' => 'trial_status',
                            'value' => ($user->on_trial == 0) ? 'Expired': "Active"
                        ),
                        array(
                            'property' => 'membership_plan_type',
                            'value' => 'Free'
                        ),
                        array(
                            'property' => 'customer_registered',
                            'value' => 'Page 1 Registered'
                        ),
                        array(
                            'property' => 'plan_change_at',
                            'value' => date("Y-m-d H:i:s", strtotime($user->upgraded_at))
                        ),
                        array(
                            'property' => 'plan_duration',
                            'value' => 'Monthly'
                        ),
                        array(
                            'property' => 'customer_paid',
                            'value' => 'No'
                        ),
                    )
                );*/
                // $hubspot = new Hubspot();
                //$hubspot->updateHubspot($arr, $user->email);

                //Mail::to($user->email)->bcc(config('app.hubspot_bcc'))->send(new WelcomeMail($user, $user->password));
                $request->session()->put('show-signup2',true);
                Session::put('sessions_u_id',$user->id);
                if ($request->has('marketPlaceSecret') && ($request->get('marketPlaceSecret') == true)) {
                    $request->session()->put('hide-phone-field',true);
                }
                $settings = Setting::first();

                if(isset($settings) && $settings->email=='sendgrid'){
                    $to = ['email' => $user->email];
                    $template = [
                        'id' => config('sendgridtemplateid.Email-Verification'),
                        'data' => [
                            'email_verification_code' => $user->verification_code,
                        ]
                    ];
                    sendEmailViaSendGrid($to, $template);
                }
                else{
                    Session::put('verification_code', $user->verification_code);
                    $user->sendEmailVerificationNotification();
                }
                //   $to = ['email' => $user->email];
                // $template = [
                //     'id' => config('sendgridtemplateid.Email-Verification'),
                //     'data' => [
                //         'email_verification_code' => $user->verification_code,
                //     ]
                // ];
                // sendEmailViaSendGrid($to, $template);
                return redirect('/register/business-profile')->with('email', $user->email)->with('id', $user->id);
            }
            else
            {
                $errMsg = 'Error verifying reCAPTCHA, please try again.';
                return back()->with('errorMSG', $errMsg);
            }


    }
    public function checkingPage(){
        // if(Auth::user() && !empty(Auth::user()->id)){
        //     $id = Auth::user()->id;
            return view('auth.checking')->with(['success'=>'Verification Code Send Successfully. Check your spam folder and mark the email as "not spam"']);
        // }
        // else{
        //     return redirect('register/business');
        // }
    }
    public function checking(Request $request){
        request()->validate([
			'verification_code'=>'required',
        ],
        [
            'verification_code.required' => 'Verification Code is Required'
        ]
        );
        // $userId = $request->id;
        // dd(Auth::user());
        $verificationCode = $request->verification_code;
        $user_id = Auth::id();
        $user= User::find($user_id);
        $verification_code = $user->verification_code;
        if(isset($user) && !empty($verificationCode) && ($verification_code == $verificationCode)){

            $user->is_verified = 1;
            $user->save();
            $to = ['email' => $user->email, 'name' => $user->name];
            $template = [
                'id' => config('sendgridtemplateid.New-User-Registration-6th-July'), 
                'data' => [ "first_name" => $user->fname ]
            ];
            sendEmailViaSendGrid($to, $template);
            return redirect(Auth::User()->dashboard_link);
            // return redirect('/register/business-profile')->with('email', $user->email)->with('id', $user->id);
        }
        else{
            return back()->with('error','Please enter correct verification code.');
        }
        // return view('auth.checking');
    }
    public function refreshCode(){
        $user_id = Auth::id();
        $user= User::find($user_id);

        $user->verification_code = random_int(10000, 99999);
        $user->save();
        $settings = Setting::first();

        if(isset($settings) && $settings->email=='sendgrid'){
            $to = ['email' => $user->email];
            $template = [
                'id' => config('sendgridtemplateid.Email-Verification'),
                'data' => [
                    'email_verification_code' => $user->verification_code,
                ]
            ];
            sendEmailViaSendGrid($to, $template);
        }
        else{
            Session::put('verification_code', $user->verification_code);
            $user->sendEmailVerificationNotification();
        }
        return back()->with(['success'=>'Verification Code Send Successfully. Check your spam folder and mark the email as "not spam"']);
    }
    public function showAlmostDoneForm(Request $request)
    {
        if ($request->session()->get('show-signup2')) {
            return view('auth.almost-done');
        } else
        return redirect('/register/business');
    }

//    public function businessAccountProfile()
//    {
//        $activePlans = \App\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
//        return view('business-account-profile')->with('activePlans', $activePlans);
//    }

    /*public function accountProfile(){
        return redirect('/business/account-profile');
    }*/


}
