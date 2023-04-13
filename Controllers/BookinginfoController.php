<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Mail;
use Auth;

class BookinginfoController extends Controller
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


    public function sangvish_viewbook()
    {
        $token = csrf_token();
        $idmy = Auth::user()->id;

        $bookingget = DB::table('booking')
            ->where('user_id', '=', $idmy)
            ->where('status', '=', 'pending')
            ->where('token', '=', $token)
            ->orderBy('book_id', 'desc')
            ->get();
        if(count($bookingget) <=0 )
            return redirect('/');
        $neshopp = $bookingget[0]->shop_id;
        $book_pincode = $bookingget[0]->booking_pincode;

        $shopnewie = DB::table('shop')
            ->where('id', '=', $neshopp)
            ->get();

        $id = $shopnewie[0]->user_id;


        $userdetails = DB::table('users')
            ->where('id', '=', $id)
            ->get();

        $booking = DB::table('booking')
            ->where('token', '=', $token)
            ->where('status', '=', 'pending')
            ->where('user_id', '=', $idmy)
            ->orderBy('book_id', 'desc')
            ->get();


        $ser_id = $booking[0]->services_id;
        $sel = explode(",", $ser_id);
        $lev = count($sel);
        $ser_name = "";
        $sum = "";
        $price = "";

        for ($i = 0; $i < $lev; $i++) {
            $id = $sel[$i];


            $fet1 = DB::table('subservices')
                ->where('id', '=', $id)
                ->get();
            $ser_name .= $fet1[0]->subname . '<br>';
            $ser_name .= ",";


            $fet2 = DB::table('seller_services')
                ->where('subservice_id', '=', $id)
                ->where('shop_id', '=', $neshopp)
                ->get();
            $price .= $fet2[0]->price . '<br>';
            $price .= ",";


            $ser_name = trim($ser_name, ",");

            $price = trim((int)$price, ",");

            (int)$sum .= (int)$fet2[0]->price;


        }

        $setid = 1;
        $setts = DB::table('settings')
            ->where('id', '=', $setid)
            ->get();


        if ($setts[0]->commission_mode == "fixed") {
            $sum = $sum + $setts[0]->commission_amt;
        } else if ($setts[0]->commission_mode == "percentage") {
            $sum1 = ($setts[0]->commission_amt * $sum) / 100;
            $sum = $sum + $sum1;
        } else {
            $sum += $fet2[0]->price;
        }

        $commission_amt = $setts[0]->commission_amt;
        $commission_mode = $setts[0]->commission_mode;
        $currency = $setts[0]->site_currency;


        $shop = DB::table('shop')
            ->where('id', '=', $booking[0]->shop_id)
            ->get();




        $admin_idd = 1;

        $admin_email = DB::table('users')
            ->where('id', '=', $admin_idd)
            ->get();

        $userid = Auth::user()->id;

        $user_email = DB::table('users')
            ->where('id', '=', $userid)
            ->orderBy('id', 'desc')
            ->get();

        $useremail = $user_email[0]->email;
        $usernamer = $user_email[0]->name;
        $userphone = $user_email[0]->phone;
        $fname = $user_email[0]->fname;

        $adminemail = $admin_email[0]->email;

        $shopid = $booking[0]->shop_id;

        $shoptbl = DB::table('shop')
            ->where('id', '=', $shopid)
            ->get();
        $selleremail = $shoptbl[0]->seller_email;


        $data = array(
            'booking' => $booking,
            'booking_pincode' => $book_pincode,
            'shop' => $shop,
            'ser_name' => $ser_name,
            'price' => $price,
            'commission_amt' => $commission_amt,
            'commission_mode' => $commission_mode,
            'currency' => $currency,
            'sum' => $sum,
            'adminemail' => $adminemail,
            'useremail' => $useremail,
            'usernamer' => $usernamer,
            'fname' => $fname,
            'userphone' => $userphone,
            'selleremail' => $selleremail
        );
        return view('old.booking_info')->with($data);


    }


}
