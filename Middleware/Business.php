<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Support\Facades\Auth;
use Closure;

class Business
{
    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * The response factory implementation.
     *
     * @var ResponseFactory
     */
    protected $response;

    /**
     * Create a new filter instance.
     *
     * @param  Guard  $auth
     * @param  ResponseFactory  $response
     * @return void
     */
    public function __construct(Guard $auth,
                                ResponseFactory $response)
    {
        $this->auth = $auth;
        $this->response = $response;
    }
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */

    public function handle($request, Closure $next)
    {
        $usercms = optional(Auth()->User())->cmsname;
//        $usercms = Auth()->User()->cmsname;

        // this condition will check if user is registerd from some CMS then user will
        // verfified we will not show them code verification screen
        if (!empty($usercms)) {
            Auth()->User()->is_verified = 1;
            Auth()->User()->save();
        }

        if(Auth::check() && Auth::User()->is_verified !== 1){

         return redirect('/register/checking');

            }
        /*if(Auth::check() && Auth::User()->on_trial == 1){
            if(Auth::User()->stripe_id == null) {
                if(Auth::User()->paypal_token == null){
                    return redirect('/register/account-profile', 302);
                }
            }

            //return redirect()->route('/business/account-profile');
            //return view('business.account-profile');
            //return $next($request);
        }*/

        if(!session()->has('data'))
        {
            $segment = $request->segment(1) ?? NULL;
            $token = $segment == 'staff-training' ? $request->segment(2) : NULL;
            if(Auth::check() && Auth::user()->isBusiness)
            {

            }
            else {
                if($token) {
                    $employee = \App\Models\StaffTraining::where('token', $token)->first();
                    // dd($employee);
                    if(!$employee) {
                        return view('errors.404');
                    }
                    $request->session()->put('data', array(
                        'staff_email' => $employee->email,
                        'token' => $employee->token,
                        'score' => $employee->score,
                        'current_url' => '/'
                    ));
                    return $next($request);
                }else {
                    return redirect('login');
                }
            }
        }

        return $next($request);
    }
}
