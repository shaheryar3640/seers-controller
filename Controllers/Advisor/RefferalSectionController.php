<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use Auth;
use App\Models\User;

use Illuminate\Http\Request;

class RefferalSectionController extends Controller
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
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(){

        $user = Auth::User();

        return view('advisor.refferalsection.index')->with(['user' => $user]);
    }

    public function createRefferalLink(){

        $user = User::where('id', Auth::User()->id)->first();
        $user->affiliate_id = str_random(10).'-'.$user->id;
        $user->save();

        return response()->json(['redirect' => route('advisor.refferalsection')]);
    }

    public function getRefferedByTotal(){
        $users = User::where('referred_by', Auth::User()->affiliate_id)->where('admin', '0')->get();

        return response()->json(['refferedByTotal' => $users->count()]);
    }

}
