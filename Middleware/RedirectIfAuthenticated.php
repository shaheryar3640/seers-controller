<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\RegisterController;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        //dd("sssssss");
        if (Auth::guard($guard)->check()) {
            if(Auth::User()->admin >= '3'){        
                //$guestuser = new UserController;
                //$guestuser->RegisterBusiness($request, Auth::User()->id);
               
               $guestuser = new RegisterController;

               session()->put('show-signup2',true);

               $guestuser->showAlmostDoneForm($request);
                //return redirect('/register/business-profile');
            }
            
            //return redirect(Auth::User()->dashboard_link);
           // return redirect()->intended();
            //return redirect()->intended(Auth::User()->dashboard_link);
        }

        return $next($request);
    }
}
