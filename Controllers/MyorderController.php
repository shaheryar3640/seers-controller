<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Mail;
use Auth;

class MyorderController extends Controller
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
    public function __construct()
    {
        $this->middleware('auth');
    }
	
	
	public function sangvish_showpage() {
		
		 $email = Auth::user()->email;
		 
		 
		 $set_id=1;
		$setting = DB::table('settings')->where('id', $set_id)->get();
		
		
        $booking = DB::table('booking')
		           ->leftJoin('shop', 'shop.id', '=', 'booking.shop_id')
				   ->where('shop.status', '=', 'approved')
				   ->where('shop.seller_email', '=', $email)
				   ->orderBy('booking.book_id', 'desc')
				 ->get();
				
				 
				$count = DB::table('booking')
		           ->leftJoin('shop', 'shop.id', '=', 'booking.shop_id')
				   ->where('shop.status', '=', 'approved')
				   ->where('shop.seller_email', '=', $email)
				   ->orderBy('booking.book_id', 'desc')
				 ->count(); 
				 
		
		$data=array('booking' => $booking, 'setting' => $setting, 'email' => $email, 'count' => $count);
		 
		 
		
		
		
		
		
	 
	  
      return view('myorder')->with($data);
   }
   
   
   
  public function sangvish_destroy($id) {
		
		
	  
      DB::delete('delete from booking where book_id = ?',[$id]);
	   
      return back();
      
   }
	
	
	
	
	
	
	
	
	
	
}
