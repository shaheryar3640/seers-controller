<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Shop;
use App\Models\SubService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests;
use App\Models\User;

use Mail;
use Auth;

class BookingController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    
	
    public function showService($shop_id,$subservice_id,$user_id) {
        $user = User::find($user_id);
        $subservice = SubService::find($subservice_id);
      return view('old.booking')->with('data',['subservice'=>$subservice,'user'=>$user]);
    }
   
   public function viewbook()
   {
	   return view('old.booking_info');
	   
   }
   
   
	
	public function sangvish_savedata(Request $request) {
        $settings = Setting::first();
	    $data = $request->all();
	    $services=$data['services'];
		$getserv="";
		foreach($services as $getservice)
		{
			$getserv .=$getservice.',';
		}
		$viewservicee=rtrim($getserv,",");
		
		/*$booking_per_hour=$data['booking_per_hour'];
		$start_time=$data['start_time'];
		$end_time=$data['end_time'];*/
		$shop_id=$data['shop_id'];
		$services_id=$data['services_id'];

		/*$booking_date=date("Y-m-d",strtotime($data['datepicker']));
		$time=$data['time'];
		$payment_mode=$data['payment_mode'];
		
		$book_address=$data['book_address'];
		$book_city=$data['book_city'];*/
		$book_pincode=$data['book_pincode'];


		$status ='pending';
		$cur_date=date("Y-m-d");
		
		
		$setid=1;
		$setts = DB::table('settings')
		->where('id', '=', $setid)
		->get();
		
		$currency=$setts[0]->site_currency;



		if (Auth::guest()) 
		{
		$name=$data['name'];
		$email=$data['email'];
		
		$phoneno=$data['phoneno'];
		$password=bcrypt($data['password']);
		$gender=$data['gender'];
		$usertype=$data['usertype'];
		}
		else if (Auth::check())
		{
			$idd = Auth::user()->id;
		
		$userdetails = DB::table('users')
		 ->where('id', '=', $idd)
		 ->get();
			$email=$userdetails[0]->email;
			$userid=$userdetails[0]->id;
		}
		$token=$data['_token'];
		
		
//		$count = DB::table('booking')
//				 ->where('book_pincode', '=', $book_pincode)
//				 ->count();
		$count_two =DB::table('booking')
		            ->where('status', '=', 'pending')
					->where('token', '=', $token)
					->where('user_email', '=', $email)
                    ->orderBy('book_id', 'desc')
                    ->count();	

		$usercount = DB::table('users')
	                 ->where('email', '=', $email)
					 ->count(); 
		if(	$book_pincode != '' )
		{

			if (Auth::guest()) 
			{
				$getidvals =DB::table('users')
			          ->orderBy('id', 'desc')
					  ->get();
            $usernewids = $getidvals[0]->id+1;				
			}
			else if (Auth::check())
			{
				$userdetails = DB::table('users')
		 ->where('id', '=', $idd)
		 ->get();
			
			$usernewids=$userdetails[0]->id;
			}
			
			
		   	if($count_two==0)
			{
				DB::insert('insert into booking (token,services_id,user_email,booking_pincode,user_id,status,shop_id,currency) values (?, ?, ?, ?, ?, ?, ?, ?)', [$token,
				$viewservicee,$email,$book_pincode,$usernewids,$status,$shop_id,$currency,$cur_date]);
			}
			else
			{
				DB::update('update booking set services_id="'.$viewservicee.'",booking_pincode="'.$book_pincode.'",user_id="'.$usernewids.'",
				shop_id="'.$shop_id.'",currency="'.$currency.'" where user_email ="'.$email.'" and status="pending" and token="'.$token.'"');
			
			
			}
			
			
			
			
			if($usercount==0)
			{
				$input['email'] = $data['email'];
                $input['name'] = $data['name'];
				$rules = array(
        'email'=>'required|email|unique:users,email',
		'name' => 'required|regex:/^[\w-]*$/|max:255|unique:users,name'
		);
				$validator = Validator::make($input, $rules);
				if ($validator->fails())
				{
					return redirect()->back()->with('message', 'Username or email address invalid');
				}
				else
				{
				
				DB::insert('insert into users (name,email,password,phone,admin,gender,remember_token) values (?, ?, ?, ?, ?, ?, ?)', [$name,$email,$password,$phoneno,
				$usertype,$gender,$token]);
				
				
				if (Auth::guest()) 
				{
					if (Auth::attempt(['email' => $data['email'], 'password' => $data['password']]))
						{
	               
				   return redirect('booking_info');
				   }
				}
				else if (Auth::check())
				{
					return redirect('booking_info');
				}








				}
			}
			else
			{
			
				if (Auth::guest()) 
				{
					if (Auth::attempt(['email' => $data['email'], 'password' => $data['password']]))
						{
	   
				   return redirect('booking_info');
				   }
				}
				else if (Auth::check())
				{
					return redirect('booking_info');
				}
			
			} 












		}
		else
		{
			/*return back()->with('error', 'That time already booked.Please select another time');*/
			return redirect()->back()->with('message', 'That time already booked.Please select another time');
		}
				 
		
		
		
		
    }
	
	
	
	
	
	
}
