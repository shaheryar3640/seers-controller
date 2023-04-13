<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\User;
use File;
use Image;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;

class AdduserController extends Controller
{
    
   

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('admin');
    }
    public function formview()

    {

        return view('admin.adduser');

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
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
	 
	  protected $fillable = ['name', 'email','password','phone'];
	 
    protected function adduserdata(Request $request)
    {
        /*return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);*/
		
		
		
		 $this->validate($request, [

        		'name' => 'required',

        		'email' => 'required|email',

        		'password' => 'required'
				
				

        	]);
         
		 
				
		$input['email'] = Input::get('email');
		
		$input['name'] = Input::get('name');
       
		
		/* $rules = array('email' => 'unique:users,email');*/
		
		 $data = $request->all();
		$rules = array(
        
       
		
        'email'=>'required|email|unique:users,email',
		'name' => 'required|regex:/^[\w-]*$/|max:255|unique:users,name',
		'photo' => 'max:1024|mimes:jpg,jpeg,png'
		
        );
		
		
		$messages = array(
            
            'email' => 'The :attribute field is already exists',
            'name' => 'The :attribute field must only be letters and numbers (no spaces)'
			
        );

		
		$validator = Validator::make(Input::all(), $rules, $messages);

		if ($validator->fails())
		{
			$failedRules = $validator->failed();
			return back()->withErrors($validator);
		}
		else
		{ 
		
		
		$image = Input::file('photo');
		if($image!="")
		 {
		 
            $filename  = time() . '.' . $image->getClientOriginalExtension();
            $userphoto="/userphoto/";
            $path = base_path('images'.$userphoto.$filename);
 
        
                Image::make($image->getRealPath())->resize(200, 200)->save($path);
               /* $user->image = $filename;
                $user->save();*/
			$namef=$filename;	
			}
		 else
		 {
			 $namef="";
		 }	
		

		
		 

			/*User::create([
            'name' => $data['name'],
            'email' => $data['email'],
			'admin' => '0',
            'password' => bcrypt($data['password']),
			'phone' => $data['phone']
			
        ]);*/
		$name=$data['name'];
		$email=$data['email'];
		$password=bcrypt($data['password']);
		$phone=$data['phone'];
		
		$admin=$data['usertype'];
		$gender='';
		
		
		
		//DB::insert('insert into users (name,email,password,phone,photo,admin,gender) values (?, ?,?, ?,?,?,?)', [$name,$email,$password,$phone,$namef,$admin,$gender]);

		    $user = new User();
            $user->name = $name;
            $user->email = $email;
            $user->password = $password;
            $user->phone = $phone;
            $user->photo = $namef;
            $user->admin = $admin;
            $user->gender = $gender;
            $user->save();

			return back()->with('success', 'Account has been created');
        }
		
		
		
		
    }
}
