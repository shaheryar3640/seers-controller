<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Mail;
use Auth;
use Crypt;
use URL;

class PaymentController extends Controller
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
	
	
	public function sangvish_showpage(Request $request) {
		 $datas = $request->all();
		 
		
		
		
		$prices = $datas['price'];
		$sum = $datas['price'];
		
		$admin_email = $datas['admin_email'];
		$user_email =$datas['user_email'];


		 $usernamer = $datas['usernamer'];
	$userphone = $datas['userphone'];
		
		$currency=$datas['currency'];
		
		 $seller_email =$datas['seller_email'];
		
		 $token =csrf_token();
		 $id = Auth::user()->id;
	   $userdetails = DB::table('users')
		 ->where('id', '=', $id)
		 ->get();
		
		$getbookid = DB::table('booking')
						->where([['user_email', '=', $userdetails[0]->email],['status', '=', 'pending'],['token', '=', $token]])
						->orderBy('book_id','desc')
						->limit(1)->offset(0)
						->get();
				$bookid = $getbookid[0]->book_id;
				
		$bookingupdate = DB::table('booking')
						->where([['user_email', '=', $userdetails[0]->email],['token', '=', $token],['book_id', '=', $bookid] ],['user_id', '=', $userdetails[0]->id])
						->update(['total_amt' => $sum]);

        
$booking = DB::table('booking')
               ->where('token', '=', $token)
			   ->where('user_email', '=', $userdetails[0]->email)
			    ->where('status', '=', 'pending')
			   ->orderBy('book_id','desc')
                ->get();



		$ser_id=$booking[0]->services_id;
			$sel=explode("," , $ser_id);
			$lev=count($sel);
			$ser_name="";
			$sum="";
			$price="";		
		for($i=0;$i<$lev;$i++)
			{
				$id=$sel[$i];	
                
				
				
				$fet1 = DB::table('subservices')
								 ->where('id', '=', $id)
								 ->get();
				$ser_name.=$fet1[0]->subname.'<br>';
				$ser_name.=",";				 
				
				
				
				$fet2 = DB::table('seller_services')
								 ->where('subservice_id', '=', $id)
								 ->get();
				$price.=$fet2[0]->price.'<br>';
				$price.=",";	
				
								
				
				
				$ser_name=trim($ser_name,",");
				$price=trim($price,",");	
				$sum.=$fet2[0]->price;
			}
            
			$booking_time=$booking[0]->booking_time;
		if($booking_time>12)
		{
			$final_time=$booking_time-12;
			$final_time=$final_time."PM";
		}
		else
		{
			$final_time=$booking_time."AM";
		}	
			
		/*  user email    */
		
		 
		 
				
		$booking_id=$booking[0]->book_id;		
		$booking_pincode=$booking[0]->booking_pincode;
		$total_amt=$booking[0]->total_amt;
		$currency = $booking[0]->currency;
		
		
		
		
		$setid=1;
		$setts = DB::table('settings')
		->where('id', '=', $setid)
		->get();
		
		$url = URL::to("/");
		
		$site_logo=$url.'/local/images/settings/'.$setts[0]->site_logo;
		
		$site_name = $setts[0]->site_name;
		
		$paypal_id = $setts[0]->paypal_id;
		
		$paypal_url =$setts[0]->paypal_url;
		
		
		Mail::send('old.bookinguseremail', ['booking_id' => $booking_id, 'ser_name' => $ser_name, 'booking_pincode' => $booking_pincode, 'final_time' => $final_time, 'total_amt' => $total_amt,
			 'currency' => $currency, 'site_logo' => $site_logo, 'site_name' => $site_name], function ($message)
        {
            $message->subject('Booking Details');
			
            $message->from(Input::get('admin_email'), 'Admin');

            $message->to(Input::get('user_email'));

        }); 
		//     $to = ['email' => Input::get('user_email'), 'name' => ''];
            // $template = [
            //     'id' => 'd-026b443099ea484a90a85840ef2474e0', 
            //     'data' => ['booking_id' => $booking_id, 'ser_name' => $ser_name, 'booking_pincode' => $booking_pincode, 'final_time' => $final_time, 'total_amt' => $total_amt, 'currency' => $currency, 'site_logo' => $site_logo, 'site_name' => $site_name]
            // ];
            // sendEmailViaSendGrid($to, $template);

		
		
		

       /* end user email */
	   
	   
	   
	   
	   /* admin email */
	   
	   
	   
	   Mail::send('old.bookingadminemail', ['booking_id' => $booking_id, 'ser_name' => $ser_name, 'booking_pincode' => $booking_pincode, 'final_time' => $final_time, 'total_amt' => $total_amt,
			 'currency' => $currency, 'site_logo' => $site_logo, 'site_name' => $site_name, 'user_email' => $user_email, 'usernamer' => $usernamer, 'userphone' => $userphone], function ($message)
        {
            $message->subject('New Order Received');
			
            $message->from(Input::get('admin_email'), 'Admin');

            $message->to(Input::get('admin_email'));

        }); 
		//     $to = ['email' => Input::get('admin_email'), 'name' => ''];
            // $template = [
            //     'id' => 'd-026b443099ea484a90a85840ef2474e0', 
            //     'data' => ['booking_id' => $booking_id, 'ser_name' => $ser_name, 'booking_pincode' => $booking_pincode, 'final_time' => $final_time, 'total_amt' => $total_amt,'currency' => $currency, 'site_logo' => $site_logo, 'site_name' => $site_name, 'user_email' => $user_email, 'usernamer' => $usernamer, 'userphone' => $userphone]
            // ];
            // sendEmailViaSendGrid($to, $template);
		
		
	/* end admin email */
	
	
	
	/* seller email */
	
	
	Mail::send('old.bookingadminemail', ['booking_id' => $booking_id, 'ser_name' => $ser_name, 'booking_pincode' => $booking_pincode, 'final_time' => $final_time, 'total_amt' => $total_amt,
			 'currency' => $currency, 'site_logo' => $site_logo, 'site_name' => $site_name,  'user_email' => $user_email, 'usernamer' => $usernamer, 'userphone' => $userphone], function ($message)
        {
            $message->subject('New Order Received');
			
            $message->from(Input::get('admin_email'), 'Admin');

            $message->to(Input::get('seller_email'));

        }); 

			//     $to = ['email' => Input::get('seller_email'), 'name' => ''];
            // $template = [
            //     'id' => 'd-026b443099ea484a90a85840ef2474e0', 
            //     'data' => ['booking_id' => $booking_id, 'ser_name' => $ser_name, 'booking_pincode' => $booking_pincode, 'final_time' => $final_time, 'total_amt' => $total_amt,'currency' => $currency, 'site_logo' => $site_logo, 'site_name' => $site_name,  'user_email' => $user_email, 'usernamer' => $usernamer, 'userphone' => $userphone]
            // ];
            // sendEmailViaSendGrid($to, $template);
		
	/* end seller email */	
	
	
		
		
		$service_name=str_replace("<br>",",",$datas['service_name']);
		$service_names = rtrim($service_name,",");
		$booking_pincode=$datas['booking_pincode'];
	 
	  $data = array('prices' => $prices, 'currency' => $currency, 'service_names' => $service_names, 'booking_pincode' => $booking_pincode, 'paypal_id' => $paypal_id,
	  'paypal_url' => $paypal_url, 'booking_id' => $booking_id);
      return view('old.payment')->with($data);
   }
   
   
   
   protected function savedata(Request $request)
    {
        
		
		
		 $data = $request->all();
		 
		 
		
		 
	}
	
	
	
	
	
	
	
	
	
	
}
