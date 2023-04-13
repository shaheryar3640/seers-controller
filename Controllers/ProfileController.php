<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Http\Controllers\Controller;
use App\Models\Rating;
use App\Models\User;
use Auth;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

use File;
use Image;
use Socialite;

class ProfileController extends Controller
{

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */

    public function viewAdvisorProfile($slug,Request $request){
      $advisor = User::with(['SellerServices','sellerRatings'])->where(['slug' => $slug])->onlyAdvisor()->firstOrFail();
    $debug = $request->get('debug');

      if(!$advisor)
          return view('errors.404');

      $total_stars = $advisor->seller_rating_counts[6];
      $total_reviews = $advisor->sellerRatings->count();
    if($debug){
      return response()->json(compact('advisor','total_stars','total_reviews'));
    }
      return view('view_advisor_profile')->with(compact('advisor','total_stars','total_reviews'));
  }
}


