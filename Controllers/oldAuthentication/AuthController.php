<?php

namespace App\Http\Controllers\Authentication;

use App\User;
use \Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use Socialite;
use Auth;
use Exception;
use Illuminate\Http\Request;
class AuthController extends Controller
{

   // use AuthenticatesAndRegistersUsers, ThrottlesLogins;
    private $userType = null;
    protected $redirectTo = '/';

    public function __construct()
    {
        $this->middleware('guest', ['except' => 'logout']);
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

    public function register (Request $request)
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
            return response()->json(['errors' => $validator->errors()],400);
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

        if(!Auth::check()) {
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

    public function redirectToFacebook( )
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



}

?>