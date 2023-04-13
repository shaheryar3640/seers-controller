<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Mail;
use URL;



class HiredController extends Controller
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

	public function sangvish_showpage() {

        $bookingupdate = DB::table('booking')
            ->where('book_id', '=', $cid)
            ->update(['status' => 'paid']);

      return view('success');
   }
}
