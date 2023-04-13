<?php

namespace App\Http\Controllers\Authentication;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\User;
use App\Mail\WelcomeMail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

use Cookie;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
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
    protected $redirectTo = '/dashboard';
    private $types = [0 => 'buyer', 2=>'seller'];

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function checkEmail(Request $request) {

        $error_message = 'Email address is already taken!';
        $validator = Validator::make(['email' => $request->get('fieldValue')],['email' => 'required|string|email|max:255|unique:users']);
        $errors = false;

        if ($validator->fails()) {
            $errors = $validator->errors();
            $error_message = $errors->first('email');
            $errors = true;
        }
        //dd($errors->first('fieldValue'));
        //header("Access-Control-Allow-Origin: *");
        return response()->json(['email',$errors ? false : true, $errors ? $error_message : 'Available!' ]);

    }

    public function checkName(Request $request) {
        /*$error_message = 'Username is already taken!';
        $validator = Validator::make(['name' => $request->get('fieldValue')],['name'=>'required|alpha_num|max:255|unique:users'],['alpha_num' => 'No Spaces or special characters only letter and numbers']);
        $errors = false;*/

        /*if ($validator->fails()) {
            $errors = $validator->errors();
            $error_message = $errors->first('name');
            $errors = true;
        }*/
        //dd($errors->first('fieldValue'));
        //header("Access-Control-Allow-Origin: *");
        /*return response()->json(['name',$errors ? false : true, $errors ? $error_message : 'Available!' ]);*/

    }



    public function postRegisterBuyer(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255|unique:users',
            /*'name' => 'required|alpha_num|max:255|unique:users',*/
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'phoneno' => 'required|string|max:11|min:11',
            'password' => 'required|min:8|regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[!@#$%^&*()?_-])[A-Za-z\d!@#$%^&*()?_-]{8,}$/',
            'company' => 'required|string|max:355',
            'job_role' => 'required|string|max:255',
            'address' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withInput(
                $request->input()
            )->withErrors($validator);
        }

        $referred_by = Cookie::get('referral');

        $user = new User;
        $user->email = $request->get('email');
        /*$user->name = $request->get('name');*/
        $user->fname = $request->get('fname');
        $user->lname = $request->get('lname');
        $user->company = $request->get('company');
        $user->phone = $request->get('phoneno');
        $user->job_role = $request->get('job_role');
        $user->address = $request->get('address');
        $user->admin = '0';
        $user->password = bcrypt($request->get('password'));
        $user->save();

        $user->affiliate_id = str_random(10).'-'.$user->id;
        $user->referred_by = $referred_by;
        $user->save();

        Mail::to($user->email)->bcc(config('app.hubspot_bcc'))->send(new WelcomeMail($user));

        Auth::guard()->login($user);

        return $this->registered($request, $user)
            ?: redirect($this->redirectPath());
    }

    public function postRegisterSeller(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255|unique:users',
           /* 'name' => 'required|alpha_num|max:255|unique:users',*/
            'fname' => 'required|string|max:32',
            'lname' => 'required|string|max:32',
            'phoneno' => 'required|string|max:11|min:11',
            'password' => 'required|min:8|regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[!@#$%^&*()?_-])[A-Za-z\d!@#$%^&*()?_-]{8,}$/',
            'linkurl' => 'required|string|max:255|regex:/^[a-zA-Z0-9\-\.]+\.(com|org|net|pk|edu|COM|ORG|NET|PK|EDU)$/',
            'address' => 'required|string|max:500',
            'street_number' => 'string|max:500',
            'route' => 'string|max:500',
            'locality' => 'string|max:500',
            'state' => 'string|max:500',
            'postal_code' => 'required|string|max:10|min:4',
            'country' => 'required|string|max:500',
            'subservices' => 'required',
        ]);

        $referred_by = Cookie::get('referral');

        $user =  new User;
        $user->email = $request->get('email');
       /* $user->name = $request->get('name');*/
        $user->fname = $request->get('fname');
        $user->lname = $request->get('lname');
        $user->phone = $request->get('phoneno');
        $user->password = bcrypt($request->get('password'));
        $user->linkurl = $request->get('linkedin').$request->get('linkurl');
        //$user->linkedin = $request->get('linkurl');
        $user->address = $request->get('address');
        $user->street_number = $request->get('street_number');
        $user->route = $request->get('route');
        $user->locality = $request->get('locality');
        $user->state = $request->get('state');
        $user->postal_code = $request->get('postal_code');
        $user->country = $request->get('country');

        $user->admin = '2';
        $user->save();

        $user->affiliate_id = str_random(10).'-'.$user->id;
        $user->referred_by = $referred_by;
        $user->save();

        $shop_name = $request->get('fname');
        $sellermail = $request->get('email');
        $country = $request->get('country');
        $state = $request->get('state');
        $city = $request->get('locality');
        $address = $request->get('address');
        $phone = $request->get('phoneno');
        $sellerid = $user->id;

        $shop_id = DB::table('shop')
            ->insertGetId([
                    'shop_name'=> $shop_name,
                    'seller_email' =>$sellermail,
                    'country' =>$country,
                    'state' =>$state,
                    'city' =>$city,
                    'address' =>$address,
                    'shop_phone_no' =>$phone,
                    'user_id' => $sellerid,
                    'status' =>'approved']);
        $user_subservices = $request->get('subservices');
        foreach ($user_subservices as $user_subservic) {
            $service_id = DB::table('subservices')->where('id', $user_subservic)->pluck('service');
            DB::insert('insert into seller_services (service_id,subservice_id,user_id,shop_id) values (?, ?, ?, ?)',
                [$service_id[0],$user_subservic,$user->id,$shop_id]);
        }
        /*
        $service_id = array();
        foreach ($user_subservices as $user_subservic) {
            $service_id[$user_subservic] = DB::table('subservices')->where('id', $user_subservic)->first();
            foreach ($service_id as $item) {
                DB::insert('insert into seller_services (service_id,subservice_id,user_id,shop_id) values (?, ?, ?, ?)',
                    [$item->service,$user_subservic,$user->id,$shop_id]);
            }
        }
        */

        Mail::to($user->email)->bcc(config('app.hubspot_bcc'))->send(new WelcomeMail($user));


        Auth::guard()->login($user);
        return $this->registered($request, $user)
            ?: redirect($this->redirectPath());
    }


    public function showRegistrationForm()
    {
        $subservices = DB::table('subservices')->get();

        return view('auth.register')->withSubservices($subservices);
    }

    public function showSellerRegistrationForm()
    {
        $subservices = DB::table('subservices')->get();

        return view('auth.sellerregister')->withSubservices($subservices);
    }
    public function showAlmostDoneForm(Request $request){
        if($request->session()->get('show-signup2')){
            return view('auth.almost-done');
        }
        else
        return redirect('/register/business');
    }

}
