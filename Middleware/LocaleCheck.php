<?php

namespace App\Http\Middleware;

use Closure;

class LocaleCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {   $locale = $request->route()->parameters();
        
        $arr = ['es','br','fr','de','business','gr'];
        if(in_array($locale['lang'], $arr)){
            \App::setLocale($locale['lang']);
            return $next($request);
        }
        abort(404);
    }
}
