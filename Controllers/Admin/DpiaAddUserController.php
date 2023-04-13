<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\DpiaStakeHolder;
use App\Models\DpiaLogs;
use Auth;

class DpiaAddUserController extends Controller
{

    public function index() {
        $dpiauser = DpiaStakeHolder::where('user_type', 'owner')->orderby('created_at', 'ASC')->get();
        return view('admin.dpia.dpia_user.show', compact(['dpiauser']));
    }

    public function create() {
        return view('admin.dpia.dpia_user.create');
    }

    public function store(Request $request){

    	$user = User::where('email', $request->get('email'))->first();

    	if($user && isset($user->email) && $user->is_register){
    		return back()->with('error', 'This email has already been taken.');
    	}

        if(!$user || !isset($user->email)){
            $user = new user;
        }
    	
        $user->fname = $request->get('fname');
        $user->lname = $request->get('lname');
        $user->email = $request->get('email');
        $user->password = bcrypt($request->get('password'));
        $user->address = $request->get('address');
        $user->country = $request->get('country');
        $user->phone = $request->get('phone');
        $user->gender = $request->get('gender');       
        $user->business = true;
        $user->on_trial = 0;
        $user->save();

        $DpiaStakeHolder = new DpiaStakeHolder;
        $DpiaStakeHolder->name = $request->get('fname').' '.$request->get('lname');
        $DpiaStakeHolder->contact_email = $request->get('email');
        $DpiaStakeHolder->user_type = 'owner';
        $DpiaStakeHolder->user_id = $user->id; // User ID user table
        $DpiaStakeHolder->created_by_id = Auth::User()->id;
        $DpiaStakeHolder->save();

        $log = new DpiaLogs();
        $log->dpia_id = $user->id;
        $log->type = 'dpia user';
        $log->action = 'add dpia user';
        $log->user_id = Auth::User()->id;
        $log->json = json_encode($user);
        $log->save();

        return back()->with('success', 'User Registered Successfully');
    }

    public function edit($id) {
        $dpiauser = User::find($id);
        return view('admin.dpia.dpia_user.edit',['dpiauser' => $dpiauser]);
    }

    public function update(Request $request, $id){

        $user = User::find($id);
        $user->fname = $request->get('fname');
        $user->lname = $request->get('lname');
        $user->address = $request->get('address');
        $user->country = $request->get('country');
        $user->phone = $request->get('phone');
        $user->gender = $request->get('gender');
        $user->save();

        $DpiaStakeHolder = DpiaStakeHolder::where('user_id', $id)->first();
        $DpiaStakeHolder->name = $request->get('fname').' '.$request->get('lname');
        $DpiaStakeHolder->save();

        $log = new DpiaLogs();
        $log->dpia_id = $user->id;
        $log->type = 'dpia user';
        $log->action = 'update dpia user';
        $log->user_id = Auth::User()->id;
        $log->json = json_encode($user);
        $log->save();

        return back()->with('success', 'User Registered Successfully');
    }

    public function enable($id){
        $dpiaUser = DpiaStakeHolder::find($id);
        if(isset($dpiaUser->id)) {
            $dpiaUser->enabled = 1;
            $dpiaUser->save();

            $log = new DpiaLogs();
            $log->dpia_id = $dpiaUser->id;
            $log->type = 'dpia user';
            $log->action = 'enable dpia user';
            $log->user_id = Auth::User()->id;
            $log->json = json_encode($dpiaUser);
            $log->save();
        }
        return back();
    }

    public function disable($id){
        $dpiaUser = DpiaStakeHolder::find($id);
        if(isset($dpiaUser->id)) {
            $dpiaUser->enabled = 0;
            $dpiaUser->save();

            $dpiaSubUser = DpiaStakeHolder::where('created_by_id', $dpiaUser->user_id)->get();
            foreach($dpiaSubUser as $subuser){
                $subuser->enabled = 0;
                $subuser->save();
            }

            $log = new DpiaLogs();
            $log->dpia_id = $dpiaUser->id;
            $log->type = 'dpia user';
            $log->action = 'disable dpia user';
            $log->user_id = Auth::User()->id;
            $log->json = json_encode($dpiaUser);
            $log->save();
        }

        return back();
    }
}
