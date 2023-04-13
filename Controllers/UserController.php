<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeAdvisor;
use App\Models\SellerService;
use App\Models\Shop;
use App\Models\SubService;
use Illuminate\Support\Facades\Auth;
use App\Mail\NewsGuideMail;
use App\Models\User;
use App\Mail\WelcomeMailFree;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

use Cookie;

class UserController extends Controller
{
    /*
   |--------------------------------------------------------------------------
   | User Controller
   |--------------------------------------------------------------------------
   |
   | This controller handles the registration of new users as well as their
   | validation and creation. By default this controller uses a trait to
   | provide this functionality without requiring any additional code.
   |
   */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    public $redirectTo = '/dashboard';
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
        //$this->middleware('CheckReferral');
    }
    public function checkEmail(Request $request) {
        $email = $request->get('email');
        $user = User::where('email', 'like', "%{$email}%")->where('admin', '!=', 3)->count();
        $errors['email'][] = 'Email address is already taken!';
        if($user <= 0){
            return response()->json(['message'=>'Email is Available'],200);
        }else{
            return response()->json(['errors'=> $errors],400);
        }
    }

    public function getProfilePage($id){
        return view('registration')->with('id', $id);
    }

    public function RegisterBusiness(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
//             'phone' => ['sometimes','nullable','regex:/^(((\+44\s?\d{4}|\(?0\d{4}\)?)\s?\d{3}\s?\d{3})|((\+44\s?\d{3}|\(?0\d{3}\)?)\s?\d{3}\s?\d{4})|((\+44\s?\d{2}|\(?0\d{2}\)?)\s?\d{4}\s?\d{4}))(\s?\#(\d{4}|\d{3}))?$/'],
             'phone' => ['sometimes','min:16','nullable'],
        ],[
            'fname.required' => 'First Name is required',
            'lname.required' => 'Last Name is required',
            'phone.regex' => 'Phone format is invalid',
        ]);

        if ($validator->fails()) {
            $request->session()->put('id',$id);
            return redirect()->back()->withInput(
                $request->input()
            )->withErrors($validator);
            //return redirect('/register/business-profile')->withErrors($validator);
        }
        // dd($validator);
        



        /*if (($request->get('cb_user_id') != null) || ($request->get('email_exist') == true)) {
            $request->validate(User::getExistingBusinessRules(), User::getExistingBusinessMessages());
        } else {
            $request->validate(User::getBusinessRules(), User::getBusinessMessages());
        }

        if ($request->get('cb_user_id') != null) {
            $user = User::where('id', $request->get('cb_user_id'))->first();
        } else if ($request->get('email_exist') == true ) {
            $user = User::where('email', $request->get('email'))->where('admin', '3')->first();
            if($user == null){
                $user = new User;
            }
        }else{
            $user = new User;
        } 

        $this->validate($request, [
            'fname' => 'required',
        ]);
        */
        // if($request->get('fname') == ""){
        //     return redirect('/login');
        // }

        $request->session()->forget('show-signup2');
        $user = User::find($id);
        if(!isset($user->email)){
            return redirect('/register/business-profile/')->with('errormessage', 'Email address not found');
        }
        $referred_by = Cookie::get('referral');

        $user->fname = $request->get('fname');
        $user->lname =$request->get('lname');
       // $user->email = $request->get('email');
        $user->company = $request->get('company');
        if ($request->get('phone')) {
            $user->phone = $request->get('phone');
        }
        $user->job_role = $request->get('job_role');
        $user->address = $request->get('address');
        $user->business = true;

       // -------14 days free plan --------
        //$user->trial_ends_at = date("Y-m-d H:m:s", strtotime("14 days"));
        //$user->membership_plan_id  = '5';
        $user->on_trial = 1;
        $user->upgraded_at = date('Y-m-d H:m:s');

        //$user->password = bcrypt("$request->get('password')");
        
        // $user->is_new = 1;
        $user->save();

        $user->affiliate_id = str_random(10).'-'.$user->id;
        $user->referred_by = $referred_by;
        $user->save();

        Auth::guard()->login($user);

        //Mail::to($user->email)->bcc(config('app.hubspot_bcc'))->send(new WelcomeMail($user, $user->password));
        $to = ['email' => $user->email, 'name' => $user->name];
        $template = [
            'id' => config('sendgridtemplateid.New-User-Registration-6th-July'), 
            'data' => [ "first_name" => $user->fname ]
        ];
        // sendEmailViaSendGrid($to, $template);
        // Mail::to($user->email)->bcc(config('app.hubspot_bcc'))->send(new WelcomeMailFree($user));

        if (isset($user->bookname)) {
            $template['id'] = '';
            $template['data'] = [];
            sendEmailViaSendGrid($to, $template);
            // Mail::send(new NewsGuideMail($user));
            return redirect('/guide-download-successfully.html');
        }

//        session()->put('hide_header_footer', false);
        // dd( $request->get('pricing-page-redirect'));
        // if(session()->get('product-page') == 'cookie-consent') {
        //     return redirect('/price-plan?name=cookie-consent');
        // }
        // else if(session()->get('product-page') == 'gdpr-staff-e-training') {
        //     return redirect('/price-plan?name=gdpr-staff-e-training');
        // }
        // else if(session()->get('product-page') == 'dpia') {
        //     return redirect('/price-plan?name=dpia');
        // }
        // else if(session()->get('product-page') == 'assessment') {
        //     return redirect('/price-plan?name=assessment');
        // }
        // else if(session()->get('product-page') == 'subject-request-management') {
        //     return redirect('/price-plan?name=subject-request-management');
        // }
        // else if ($request->get('has_product') === 1 && $request->get('pricing-page-redirect') == 'true'){
        //     return redirect('/price-plan');
        // }
        // else if ($request->get('has_product') === 1) {
        //     return redirect('/register/account-profile');
        // }
        // else {
        //     return redirect('/price-plan');
        // }

//        $product_session = session()->get('product-page');
//        $route = '/price-plan';

        // dd($product_session !== null || $product_session !== false);
    
//        if($product_session){
//            $route = '/price-plan?name=' . $product_session;
//        }
//
//        if ($request->get('has_product') === 1) {
//            $route = '/register/account-profile';
//        }
//
//        session()->get('product-page') ? session()->forget('product-page') : '';

//        return redirect($route);
    if($request->get('acc-profile-redirect') == 'true'){
        return redirect('/register/account-profile');
    }
        return redirect(Auth::User()->dashboard_link);
    }

    public function RegisterAdvisor(Request $request){
        //response()->json(['data' => $request->all()],400);
        //$this->validateBusiness($request->all());

      //  $request->validate(User::getAdvisorRules(), User::getAdvisorMessages());

        $validator = Validator::make($request->all(), [
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|min:8',
           /*'phone' => 'required|string|max:11|min:11|regex:/^[0-9]/',
            'linkurl' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'street_number' => 'required|string|max:500',
            'route' => 'required|string|max:500',
            'locality' => 'required|string|max:500',
            'state' => 'required|string|max:500',
            'postal_code' => 'required|required|string|max:10|min:1',
            'country' => 'required|required|string|max:500',*/
            'sub_services' => 'required',
            'agree' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withInput($request->all())->with('fieldvalidation', 'Please fill the required fields');
        }
        $secret = config('app.RECAPTCHA_SECRET_KEY');
        $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$secret.'&response='.$request->input('g-recaptcha-response'));
        $responseData = json_decode($verifyResponse);
        //return response()->json(['message'=>json_decode($verifyResponse),'captchainput'=>$request->input('g-recaptcha-response'),'response'=>$responseData->success,'fname'=>$request['fname']],401);
        if($responseData->success==true)
        {

            $user = User::where('email', $request->get('email'))->get();

            if(isset($user[0]->email)){
                return redirect()->back()->withInput($request->all())->with('userexist', 'This email has already been taken.');
            }
            $referred_by = Cookie::get('referral');

            $user = new User;
            $user->fill($request->all());
            $user->advisor = true;
            $user->fname = $request->get('fname');
            $user->lname = $request->get('lname');
            $user->phone = $request->get('phoneno');
            $user->password = bcrypt($request->get('password'));
            $user->linkurl = $request->get('linkedin');
            //$user->linkedin = $request->get('linkurl');
            $user->address = $request->get('address');
            $user->street_number = $request->get('street_number');
            $user->route = $request->get('route');
            $user->locality = $request->get('locality');
            $user->state = $request->get('state');
            $user->postal_code = $request->get('postal_code');
            $user->country = $request->get('country');
            $user->slug = strtolower($user->fname) . '-' . rand(99,9999) . '-' . $user->id;
            $user->save();

            $user->affiliate_id = str_random(10).'-'.$user->id;
            $user->referred_by = $referred_by;
            $user->save();

            $user_subservices = $request->get('sub_services');
            foreach ($user_subservices as $user_subservice) {
                $sub_service = SubService::where(['id'=> $user_subservice])->first();
                SellerService::create([
                    'service_id'=> $sub_service->service,
                    'subservice_id'=> $user_subservice,
                    'user_id'=> $user->id,
                ]);
            }
            Mail::to($user->email)->bcc(config('app.hubspot_bcc'))->send(new WelcomeAdvisor($user));
//  $to = ['email' => $user->email, 'name' => $user->name];
//         $template = [
//             'id' => 'd-026b443099ea484a90a85840ef2474e0', 
//             'data' => [ "user" => $user ]
//         ];
//         sendEmailViaSendGrid($to, $template);
            Auth::guard()->login($user);
            return redirect(route('advisor.services'));
        }
        else
        {
            $errMsg = 'Error verifying reCAPTCHA, please try again.';
            return back()->with('errorMSG', $errMsg);
        }


    }
}
