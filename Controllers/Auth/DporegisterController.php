<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;

class DporegisterController extends Controller
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

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'email' => 'required|string|email|max:255|unique:users',
           /* 'name' => 'required|string|unique:users|max:255',*/
            'lname' => 'required|string|unique:users|max:255',
            'phoneno' => 'required|string|max:255',
            'password' => 'required|min:8|regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\X])(?=.*[!@^&*$#%]).*$/',
			//'gender' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'linkurl' => 'required|string|max:255',
            'qualification' => 'required|string|max:255',
            'usertype' => 'required|string|max:255',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {
		
        return User::create([
            'email' => $data['email'],
            /*'name' => $data['name'],*/
            'lastname' => $data['lname'],
            'phone' => $data['phoneno'],
            'password' => bcrypt($data['password']),
			//'photo' => '',
            //'gender' => $data['gender'],
            'address' => $data['address'],
            'linkurl' => $data['linkurl'],
			'qualification' => $data['qualification'],
			'admin' => $data['usertype'],
        ]);
    }
}
