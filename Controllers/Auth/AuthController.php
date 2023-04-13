<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use \Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use Socialite;
use App\Models\Product;
use Auth;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{

    // use AuthenticatesAndRegistersUsers, ThrottlesLogins;
    private $userType = null;
    protected $redirectTo = '/';

    public function __construct()
    {

        $this->middleware('guest', ['except' => 'logout']);
    }


    public function login(Request $request)
    {
        // dd('in');
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            // 'remember_me' => 'boolean'
        ]);
        $credentials = request(['email', 'password']);
        if (!Auth::attempt($credentials))
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;
        $token->expires_at = Carbon::now()->addWeeks(1);
        $token->save();
        return response()->json([
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString()
        ]);
    }


    protected function validator(array $data)
    {
        return Validator::make($data, [
            /* 'name' => 'required|max:255',*/
            'email' => 'required|email|max:255|unique:users',
            // 'password' => 'required|confirmed|min:6',
            'password' => 'required|min:8|regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\X])(?=.*[!$#%]).*$/',
        ]);
    }

    protected function create(array $data)
    {
        return User::create([
            /*'name' => $data['name'],*/
            'email' => $data['email'],
            'password' => 'required|min:8|regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\X])(?=.*[!$#%]).*$/',
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'full_name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'required|string|max:20',
            //            'company_name' => 'required|string|max:255',
            //            'company_address' => 'required|string|max:255',
            'terms_and_conditions' => 'accepted',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $name = explode(" ", $data["full_name"]);

        $user = new User;
        $user->fname = count($name) > 1 ? $name[0] : $data["full_name"];
        $user->lname =  count($name) > 1 ? $name[1] ?? '' : '';
        $user->email = $request->get('email');
        $user->password = bcrypt($request->get('password'));
        $user->phone = $request->get('phone');
        $user->company = $request->get('company_name') ?? '';
        $user->membership_plan_id = 1;
        $user->admin = 0;
        $user->is_register = true;
        $user->on_trial = 0;
        $user->save();

        if (!Auth::check()) {
            Auth::guard()->login($user);
        }

        return response()->json([
            'success' => true,
        ], 201);
    }

    public function redirectToLinkedin()
    {
        return Socialite::driver('linkedin')->redirect();
    }
    public function getUser(Request $request){
       $user = User::where(['id' => $request->user_id])->first();
       return $user;
       if(!$user){
           Log::info("User not found with id ".$request->user_id);
           return $user;
       } else{
        return $user->toArray();
    //    return response()->json(["id"=>$user->id,"name"=>$user->fname." ".$user->lname,"email"=>$user->email]);
       }
	}

    public function handleLinkedinCallback()
    {
        try {
            $user = Socialite::driver('linkedin')->user();
            $create['name'] = $user->name;
            $create['email'] = $user->email;
            $create['linkedin_id'] = $user->id;

            $userModel = new User;
            $createdUser = $userModel->addNew($create);
            Auth::loginUsingId($createdUser->id);
            return redirect()->route('home');
        } catch (Exception $e) {
            return redirect('auth/linkedin');
        }
    }

    public function redirectToFacebook()
    {
        return Socialite::driver('facebook')->redirect();
    }


    public function handlefacebookCallback(Request $request)
    {


        try {
            //  var_dump('try handlefacebookCallback');
            $user = Socialite::driver('facebook')->user();

            // $newUser = User::find($user->email);
            //  dd($user);
            //            if($usertype == 'login'){
            //                if(empty($newUser)){
            //                    return redirect('/login');
            //                }else{
            //                    return redirect('https://seers.local/business/dashboard');
            //                }
            // }else if($usertype == 'business'){
            $newuser = new User;

            $newuser->email = $user->email;                 // done
            $newuser->password = bcrypt('zubair@123');      // done
            $newuser->company = 'Seers';                    // done
            $newuser->address = 'Address';                  // done
            $newuser->street_number = 'Street';             // done
            $newuser->route = 'Route';                      // done
            $newuser->locality = 'Locality';                // done
            $newuser->state = 'State';                      // done
            $newuser->postal_code = 'postal code';          // done
            $newuser->country = 'Country';                  // done
            $newuser->gender = 'male';                      // done
            $newuser->fname = $user->name;                  // done
            $newuser->lname = 'Last Name';                  // done
            $newuser->linkurl = 'Linkedin';                 // done
            $newuser->qualification = 'Maters';             // done
            $newuser->photo = 'https://local/image.png';    // done
            $newuser->admin = '0';                          // done
            $newuser->affiliate_id = '1';                   // done
            $newuser->membership_plan_id = '1';             // done
            //dd($newuser);
            $newuser->save();

            return redirect('https://seers.local/business/dashboard');
            //  }



            // return redirect()->route('home');
        } catch (Exception $e) {
            //dd($e);
            return redirect('https://seers.local/business/dashboard');
        }
    }

    public function getUserPlan(Request $request)
    {
        // return $request->all();
        Log::info("inside getUserPlan in Seersapp with Payload ". json_encode($request->all()) );
        $user_product = null;
        $plan = null;
        $features = [];

        if(!$request->has('user_id')){
            Log::info("no user found in get-users-plans");
            return response([
                "error" => true,
                "message" => "user not found",
                "status" => 404,
            ]);
        }

        $user = User::where(['id' => $request->user_id])->select('id', 'fname', 'lname', 'email')->first();
        Log::info("User => " . json_encode($user));
        if($user){
            $user_product = $user->products()->where('name',$request->product_name)->select('id','name','expired_on','is_active')->first();
            Log::info("User Product => " . json_encode($user_product));
            if($user_product){
                if($request->plan_name === true){
                    $plan = $user_product->plan()->select("id","name","expired_on")->first();
                    Log::info("Product Plan => " . json_encode($plan));
                    if($request->features != null){
                        $features =   $plan->features();
                        Log::info("Plan Features => " . json_encode($features));
                        if($request->features === true){
                            $features = $features->select("name","value")->get();
                        } else {
                            $features = $features->where("name" ,$request->features)->select("name","value")->first()->toArray();
                            $temp = [];
                            array_push($temp, $features);
                            $features = $temp;
                        }
                    }
                }
            } else {
                Log::info("no product found in get-users-plans");
                return response([
                "error" => true,
                "message" => "product not found",
                "status" => 404,
                ]);
            }
        }


        Log::info("Returning User information with product and plan");
        // array_push($realProduct, $product);
        return response()->json([
            'error' => false,
            'data' => [
                'user' => ["id"=>$user->id,"name"=>$user->fname." ".$user->lname,"email"=>$user->email],
                'product' => ['name'=>$user_product->name,"id"=>$user_product->id,"expired_on"=>$user_product->expired_on],
                'plan' => $plan != null ? ["id"=>$plan->id, "name"=>$plan->name] : $plan,
                'feature' => $features
            ],
            'status_code'=>200
        ]);
    }

    public function getCmpUsers(Request $request)
    {
        $ids = [];
        $final_data = [];
        $domains = $request->domains;
        foreach ($domains as $domain) {
            $user_based_domains[$domain['user_id']][] = $domain;
            array_push($ids,$domain['user_id']);
        }
        $users = DB::table('users')
        ->join('u_products',
            'users.id',
            '=',
            'u_products.user_id'
        )
        ->join('u_plans', 'u_plans.u_product_id', '=', 'u_products.id')
        ->select(
            'users.id as user_id',
            'users.fname',
            'users.lname',
            'users.email',
            'u_products.name as product_name',
            'u_products.id as product_id',
            'u_products.expired_on',
            'u_plans.id as plan_id',
            'u_plans.name as plan_name'
        )
        ->whereIn('users.id',array_unique($ids))
        ->where(["u_products.name"=> "cookie_consent"])
        ->get();
         foreach($users as $user){
           $user_domain = $user_based_domains[$user->user_id];
            foreach ($user_domain as $value) {
                $data = [
                    "user_email"=>$user->email,
                    "user_fname"=>$user->fname,
                    "user_lname"=>$user->lname,
                    "product_name"=>$user->product_name,
                    "plan_name"=>$user->plan_name,
                    "domain_id"=>$value['id'],
                    "domain_name" => $value['name'],
                ];
                array_push($final_data,$data);
            }
        }
        return response($final_data);
    }

    public function checkDomainLimit(Request $request)
    {
        $ids = [];
        $final_data = [];
        $domains = $request->domains;
        // dd($domains);
        if($domains){
        foreach ($domains as $domain) {
            $user_based_domains[$domain['user_id']][] = $domain;
            array_push($ids,$domain['user_id']);
        }
    }
        // dd($user_based_domains);
        $users = DB::table('users')
        ->join('u_products',
            'users.id',
            '=',
            'u_products.user_id'
        )
        ->join('u_plans', 'u_plans.u_product_id', '=', 'u_products.id')
        ->join('u_features', 'u_features.u_plan_id', '=', 'u_plans.id')
        ->select(
            'users.id as user_id',
            'users.fname',
            'users.lname',
            'users.email',
            'u_products.name as product_name',
            'u_products.id as product_id',
            'u_products.expired_on',
            'u_plans.id as plan_id',
            'u_plans.name as plan_name',
            'u_features.name as feature_name',
            'u_features.value'
        )
        ->whereIn('users.id',array_unique($ids))
        ->where(["u_features.name"=> "consent_log_limit"])
        ->get();
        foreach($users as $user){
           $user_domain = $user_based_domains[$user->user_id];
                foreach ($user_domain as $value) {
                    // dd($value);
                    $result = $this->get_percentage($value['total_consents'], $user->value);

                    $data = [
                        "user_email"=>$user->email,
                        "user_fname"=>$user->fname,
                        "consent_limit_in_percent"=>$result,
                        "domain_id"=>$value['id'],
                        "domain_name" => $value['name'],
                        // "level"=>$value['level'],
                        // "last_limit_reached"=>$value['last_limit_reached']
                    ];
                    array_push($final_data,$data);
                }
        }
        return response($final_data);
    }
    public function get_percentage($total, $allow){
        // dd($allow);
        if($total >= $allow)
            return 100;
        return ($total / $allow) * 100;
    }

    public function getPlans(Request $request) {
        Log::info("Inside getPlans function ");
        $users = DB::table('users')
        ->join('u_products',
            'users.id',
            '=',
            'u_products.user_id'
        )
        ->join('u_plans', 'u_plans.u_product_id', '=', 'u_products.id')
        ->select(
            'users.id as user_id',
            'u_products.upgraded_on',
            'u_products.expired_on'
        )
        ->whereIn('users.id',array_unique($request->user_ids))
        ->where(["u_products.name"=> "cookie_consent"])
        ->get();

        Log::info("resutes got from query =>  " . json_encode($users));
        return $users;
    }
}
