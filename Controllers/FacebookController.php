<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Socialite;

class FacebookController extends Controller
{
    //
    public function redirectToProvider()
    {
        //dd('nnn');
        return Socialite::driver('facebook')->redirect();
    }

    /**
     * Obtain the user information from linkedin.
     *
     * @return Response
     */
    public function handleProviderCallback()
    {
        $user = Socialite::driver('facebook')->user();


        $token = $user->token;
        $refreshToken = $user->refreshToken; // not always provided
        $expiresIn = $user->expiresIn;
        $user = Socialite::driver('facebook')->userFromToken($token);

        $id = \Auth::user()->id;
        dd($id);
        dd($user);
        //1. insert new record in users table with the profile information from linkedin account
        //2. redirect to user profile edit page.

        return view('facebookAuth');

    }
}
