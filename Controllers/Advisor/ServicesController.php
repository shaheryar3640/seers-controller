<?php

namespace App\Http\Controllers\Advisor;

use App\Models\SellerService;
use App\Models\Service;
use App\Models\Setting;
use File;
use Image;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Auth;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;

class ServicesController extends Controller
{
    /**
     * Show a list of all of the application's users.
     *
     * @return Response
     */
    public function __construct()
    {
        $this->middleware('advisor');
    }


    public function index()
    {
        return view('advisor.services');
    }

    public function update(Request $request){

        $user = Auth::User();
        $advisor_subservices = $request->get('advisor_subservices');
        //$user->SellerServices()->delete();
        $user_services = $user->SellerServices;
        //dd($user_services);
        foreach($user_services as $user_service){ //dd($user_service->id);
            SellerService::destroy($user_service->id);
        }

        if(is_array($advisor_subservices) && count($advisor_subservices) > 0){
            foreach($advisor_subservices as $advisor_subservice){
                if(!$advisor_subservice['enabled']){
                    continue;
                }
                SellerService::create([
                    'service_id'=>$advisor_subservice['service']['id'],
                    'subservice_id'=>$advisor_subservice['id'],
                    'price'=>$advisor_subservice['price'],
                    /*'shop_id'=>$user->shop->id,*/
                    'user_id'=>$user->id,
                ]);
            }
        }
        return response()->json(['message'=>'Your services and pricing is updated!']);
    }

     public function routegetAdvisorServices()
    {
        return response()->json(['advisor_subservices'=>Auth::User()->seller_services_pricing,'avatar_link' => Auth::User()->avatar_link]);
    }
}