<?php

namespace App\Http\Middleware;

use Closure;

class Guest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $token = $request->segment(2);        
        $dummy_token = \App\Models\StaffTraining::where('dummy_token', $token)->first();
        
        if(!$dummy_token){            
            return abort('404');
        }
       
        return $next($request);
    }
}
