<?php
/**
 * Created by PhpStorm.
 * User: ahmad
 * Date: 19/05/18
 * Time: 10:47 PM
 */

namespace App\Http\Controllers;

use App\Models\SellerService;
use App\Models\SubService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

class ListingController extends Controller
{

    public function index($slug){
        return redirect('/');

        $sub_service = SubService::where(['slug'=>$slug])->first();

        if(!$sub_service)
            return view('errors.404');

        Session::put('onlySubService',$sub_service->id);
        $advisors = User::onlyAdvisor()->with('OnlySellerService')->has('OnlySellerService')->get();
        Session::remove('onlySubService');
        $advisorCount= count($advisors);
        $newscript = true;

        return view('listing')->with(compact('advisors','sub_service','advisorCount','newscript'))->with("title", $sub_service->subname. ' to successfuly comply with GDPR');

    }

    public function all(){
        return redirect('/');
        //return response()->json(User::with('advisor_services')->where(['slug'=>$slug]));
        //return response()->json(User::where(['admin'=>2])->with('advisorServices')->SubServices->where(['slug'=>$slug])->get());


        $advisors = User::onlyAdvisor()->has('ValidSellerService')->paginate(12);
        //return response()->json($advisors);
        //return response()->json(Auth::User()->isBusiness);

        $currnetpage = ($advisors->currentPage() > 1) ? ('Page '.$advisors->currentPage()) : '';
        return view('allListing')->with(compact('advisors'))->with('title', 'All Advisors at Seers platform to help different Businesses regarding Data Protection & Cyber Security '. $currnetpage);

    }

    public function indexTest($slug){
        return redirect('/');
        //return response()->json(User::with('advisor_services')->where(['slug'=>$slug]));
        //return response()->json(User::where(['admin'=>2])->with('advisorServices')->SubServices->where(['slug'=>$slug])->get());
        $sub_service = SubService::where(['slug'=>$slug])->first();
        $advisors = SellerService::where(['subservice_id'=>$sub_service->id])->get();

        return response()->json(['advisors'=>$advisors]);

    }
    public function localitySubServiceListing($subservice_slug, $locality_slug){
        return redirect('/');
        $locality = str_replace('-',' ',$locality_slug);
        $sub_service = SubService::where(['slug'=>$subservice_slug])->first();

        Session::put('onlySubService',$sub_service->id);
        $advisors = User::onlyAdvisor()->onlyLocality($locality)->with('OnlySellerService')->has('OnlySellerService')->paginate(12);
        Session::remove('onlySubService');

        return view('listing')->with(['advisors'=>$advisors,'sub_service' => $sub_service])->with('title', 'All ' . str_replace('-',' ',$subservice_slug) . ' in '. $locality);
    }

    public function localityListing($locality_slug){
        return redirect('/');
        $locality = str_replace('-',' ',$locality_slug);

        $advisors = User::onlyAdvisor()->onlyLocality($locality)->paginate(12);

        return view('allListing')->with(['advisors'=>$advisors])->with('title', 'All advisors of GDPR & Cyber Security in '.$locality);
    }
}
