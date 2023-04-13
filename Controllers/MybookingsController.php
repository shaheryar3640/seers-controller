<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Mail;
use Auth;

class MybookingsController extends Controller
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
				   
				   
				  ->leftJoin('users', 'users.email', '=', 'shop.seller_email')
				 ->where('booking.user_email', '=', $email)
				  ->where('booking.status', '=', 'paid')
				  ->where('shop.status', '=', 'approved')
				  
				  ->orderBy('booking.book_id', 'desc')
				  /*->groupBy('booking.shop_id')*/
				  
				 ->get();
				 
		$count = DB::table('booking')
		          ->leftJoin('shop', 'shop.id', '=', 'booking.shop_id')
				   
				   
				  ->leftJoin('users', 'users.email', '=', 'shop.seller_email')
				 ->where('booking.user_email', '=', $email)
				  ->where('booking.status', '=', 'paid')
				  ->where('shop.status', '=', 'approved')
				  
				  ->orderBy('booking.book_id', 'desc')
				  ->groupBy('booking.shop_id')
				 ->count();
				 
				 
		$data=array('booking' => $booking, 'count' => $count, 'setting' => $setting, 'email' => $email);
		 
		 
		
		
		
		
		
	 
	  
      return view('old.my_bookings')->with($data);
   }
   
   
   
   public function sangvish_savedata(Request $request)
   {
	   $data = $request->all();
	   
	   $comment=$data['comment'];
    if(!empty($data['rating']))
	{		
	$rating=$data['rating'];		
	}
	else
	{
		$rating="";
	}
	$shop_id=$data['shop_id'];
	$rate_id=$data['rate_id'];
	$email = Auth::user()->email;
	
	
	
	$rating_count = DB::table('rating')
	          ->where('rshop_id', '=', $shop_id)
			  ->where('email', '=', $email)
			  ->count();
			  if($rating_count==0)
			  {
				  DB::insert('insert into rating (rating,email,rshop_id,comment) values (?, ?, ?, ?)', [$rating,$email,$shop_id,$comment]);
				  return redirect()->back()->with('message', 'Your Comments Added Successfully.');
			  }
			  else
			  {
				  if($rate_id!="")
				  {
				  DB::update('update rating set rating="'.$rating.'",comment="'.$comment.'" where rid = ?', [$rate_id]);
				  return redirect()->back()->with('message', 'Your Comments Updated Successfully.');
				  }
				  
			  }
			  
			  
	   return redirect('old.my_bookings');
	   
	   
   }
   
   
   
   
  public function destroy($id) {
		
		
	  
      DB::delete('delete from booking where book_id = ?',[$id]);
	   
      return back();
      
   }
	
	
	
	
	
	
	
	
	
	
}
