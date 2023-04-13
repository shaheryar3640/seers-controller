<?php

namespace App\Http\Controllers\Advisor;

use App\Models\Booking;
use App\Http\Controllers\Controller;
use App\Models\Rating;
use App\Models\User;
use App\Models\SellerService;
use Auth;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

use File;
use Image;
use Socialite;

class ProfileController extends Controller
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

    public function index(){
        $user = Auth::User();
        return response()->json(['advisor_profile'=>$user,'avatar_link' => $user->avatar_link,'days'=>$user->advisorDays]);
    }


    public function updateUserAvatar(Request $request){
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image64:jpeg,jpg,png'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors'=>$validator->errors()],400);
        } else {
            $user = Auth::User();
            if($user->photo != ''){
                File::delete(base_path('images/userphoto/' . $user->photo));
            }
            $imageData = $request->get('avatar');
            $user->photo = uniqid() . '.' . explode('/', explode(':',
                    substr($imageData, 0, strpos($imageData, ';')))[1])[1];
            $user->save();

            Image::make($request->get('avatar'))
                    ->resize(200, 200, function ($constraints) {
                        $constraints->aspectRatio();
                    })->save(base_path('images/userphoto/').$user->photo);

            return response()->json(['message'=>'Your new avatar is now updated!','avatar_link'=>$user->avatar_link,
                'error'=>false,'base_path'=>base_path('images/userphoto/').$user->photo]);
        }
    }

    public function update(Request $request){
        $validator = Validator::make($request->all(), [
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'password' => 'min:8',
            /*'phone' => 'required|string|max:11|min:11',*/
            'linkurl' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'street_number' => 'required|string|max:500',
            'route' => 'required|string|max:500',
            'locality' => 'required|string|max:500',
            'state' => 'required|string|max:500',
            'postal_code' => 'required|required|string|max:10|min:4',
            'country' => 'required|required|string|max:500',
        ],[
            'fname.required' => 'First Name is required',
            'lname.required' => 'Last Name is required',
           /* 'phone.required' => 'Phone No is required',*/
            'linkurl' =>  'Link Url is required',
            'address' =>  'Address is required',
            'street_number' =>  'Street Number is required',
            'route' =>  'Route is required',
            'locality' =>  'City is required',
            'state' =>  'State is required',
            'postal_code' =>  'Postal Code is required',
            'country' =>  'Country is required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors'=>$validator->errors(),'message'=>trans('advisor.advisor_register_error')],400);
        }
        $user = Auth::User();
        $user->fname = $request->get('fname');
        $user->lname = $request->get('lname');
        $user->phone = $request->get('phone');
        if($request->get('password') != ''){
            $user->password = bcrypt($request->get('password'));
        }
        $user->linkurl = $request->get('linkedin').$request->get('linkurl');
        $user->address = $request->get('address');
        $user->street_number = $request->get('street_number');
        $user->route = $request->get('route');
        $user->locality = $request->get('locality');
        $user->state = $request->get('state');
        $user->postal_code = $request->get('postal_code');
        $user->country = $request->get('country');
        $user->qualification = $request->get('qualification');
        if(is_array($request->get('days'))){
            $shop = $user->shop;
            $shop->shop_date = implode(',',$request->get('days'));
            $user->shop()->save($shop);
        }

        $user->save();
        return response()->json(['message'=>'Your profile is updated!']);
    }

	
	public function destroy()
	{

        $user = User::find(Auth::user()->id);
        //$user->SellerServices()->delete();
        $user_services = $user->SellerServices;
        //dd($user_services);
        foreach($user_services as $user_service){ //dd($user_service->id);
            SellerService::destroy($user_service->id);
        }

        //$user->ratings()->delete();
        $user_ratings = $user->ratings;
        foreach($user_ratings as $user_rating){
            Rating::destroy($user_rating->id);
        }

        //$user->sellerBookings()->delete();
        $user_bookings = $user->sellerBookings;
        foreach($user_bookings as $user_booking){
            Booking::destroy($user_booking->id);
        }

        //$user->delete();
        if($user != null){
            User::destroy($user->id);
        }

        Auth::logout();
        return response()->json(['message'=>'Your account has been delete '],200);

	}
    public function routeDashboard()
	{
        return view('advisor.dashboard');
	}
    public function routeProfile()
	{
        return view('advisor.profile');
	}
    public function routeGetAdvisorProfile()
	{
        $user = Auth::User();
        return response()->json(['advisor_profile'=>$user,'avatar_link' => $user->avatar_link]);
	}
    public function routeGetUnseenBookings()
	{
         return response()->json(['unseen_bookings'=> Booking::where(['seller_id'=>Auth::User()->id, 'seen' => 0])->count()]);
	}
    public function routegetAdvisorDashboard()
	{
         $user = Auth::User();
        return response()->json(['username'=>$user->fullName,'avatar_link' => $user->avatar_link]);
	}
    public function routeOrders()
	{
        return view('advisor.orders');
	}
    public function routegetAdvisorOrders()
	{
        return response()->json(['advisor_orders'=>Auth::User()->sellerBookings, 'avatar_link' => Auth::User()->avatar_link]);
	}
}