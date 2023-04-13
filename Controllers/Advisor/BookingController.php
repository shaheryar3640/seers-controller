<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use Auth;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;;

use App\Http\Requests;
use App\Models\User;
use App\Models\Booking;

class BookingController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('advisor');

    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */

    protected function update()
    {
        //if seen = 0 (User has not seen the notification)
        $id = Auth::User()->id;
        $update_notifications = Booking::where([
            ['seen','=', 0],
            ['seller_id', '=', $id],
        ])->get();
        foreach($update_notifications as $item){
            $item->seen = true;
            $item->save();
        }
        return response()->json(['message'=>'Notification is updated!']);
    }

    public function getAdvisorBookingsUnique(){
        $bookings =Auth::User()->sellerBookings;
        $uniqueBookings = array();

        $unreadMessages = array();

        $allUnReadMessages = 0;

        foreach($bookings as $booking){

            if($booking->bookingRatings == null){
                $uniqueBookings[] = $booking;

                if($booking->conversation != null) {
                    $unreadConv = $this->getUnreadMessagesOfConversation($booking->conversation);
                    //dd($unreadConv);
                    $unreadMessages[$booking->id] = $unreadConv;
                    $allUnReadMessages = $allUnReadMessages + $unreadConv;
                }
            }
        }

        /*foreach($bookings as $booking){
            $check = 0;
            if($booking->conversation != null){

                foreach($uniqueBookings as $uniqueBooking){
                    if((($uniqueBooking->seller_id == $booking->seller_id) && ($uniqueBooking->buyer_id == $booking->buyer_id)) || (($uniqueBooking->seller_id == $booking->buyer_id) && ($uniqueBooking->buyer_id == $booking->seller_id))){
                        $check = 1;
                    }
                }

                if($check == 0) {
                    $uniqueBookings[] = $booking;
                }
            }
        }*/

        //dd($uniqueBookings);

        return response()->json(['advisor_bookings'=>$uniqueBookings, 'unreadMessages'=>$unreadMessages, 'allUnReadMessages'=>$allUnReadMessages ,'avatar_link' => Auth::User()->avatar_link]);
    }

    public function getUnreadMessagesOfConversation($conversation){

        $conv_count = 0;
        foreach($conversation->messages as $message){
            if(($message->read == 0) && ($message->user_id != Auth::User()->id)){
                $conv_count ++;
            }
        }
        return $conv_count;
    }

    public static function getUserBookings()
    {
        $bookings = Auth::User()->sellerBookings;

        //$uniqueBookings = $bookings;
        $uniqueBookings = array();

        foreach ($bookings as $booking) {

            if ($booking->bookingRatings == null) {
                $uniqueBookings[] = $booking;
            }
        }

        return $uniqueBookings;
    }
    public function routeBookings()
	{
        return view('advisor.booking');
	}
    public function routegetAdvisorBookings()
	{
        $bookings =Auth::User()->sellerBookings;
        return response()->json(['advisor_bookings'=>$bookings->reverse()->values(),'avatar_link' => Auth::User()->avatar_link]);
	}
}