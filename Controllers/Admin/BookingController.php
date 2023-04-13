<?php

namespace App\Http\Controllers\Admin;



use File;
use Image;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Auth;
use Mail;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Booking;
use App\Models\Setting;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    /**
     * Show a list of all of the application's users.
     *
     * @return Response
     */
    public function index()
    {
		$set_id=1;
		//$setting = DB::table('settings')->where('id', $set_id)->get();

        $setting = Setting::where('id', $set_id)->get();

        $booking = Booking::orderBy('id','desc')->get();
        /*$booking = DB::table('bookings')
                       //->leftJoin('users', 'users.email', '=', 'booking.user_email')
                       ->leftJoin('users', 'users.id', '=', 'bookings.seller_id')
                       //->leftJoin('shop', 'shop.id', '=', 'bookings.shop_id')
                       ->orderBy('bookings.id','desc')
                        ->get();*/

        //dd($booking);

		$data=array('booking' => $booking, 'setting' => $setting);
        //dd($booking);
        return view('admin.booking')->with($data);
    }
	
	
	
	
	
	
	public function destroy($id) {

        //DB::delete('delete from booking where book_id = ?',[$id]);
        $booking = Booking::where('id', $id)->first();
        //$booking->delete();
        if($booking != null){
            Booking::destroy($booking->id);
        }

        return back();
      
   }
   
   
   
   
   
   
   
	
}