<?php

namespace App\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\DpiaStakeHolder;
use App\Models\DpiaLogs;

class DpiaSubUserController extends Controller
{
    public function index($id) {
        $dpiauser = DpiaStakeHolder::where('created_by_id', $id)->orderby('created_at', 'ASC')->get();
        return view('admin.dpia.dpia_user.subuser.show', compact(['dpiauser', 'id']));
    }

    public function create($id) {
        return view('admin.dpia.dpia_user.subuser.create', compact(['id']));
    }

    public function store(Request $request, $id){
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
        $user->business = true;
        $user->on_trial = 0;
        $user->save();

        $DpiaStakeHolder = new DpiaStakeHolder;
        $DpiaStakeHolder->name = $request->get('fname').' '.$request->get('lname');
        $DpiaStakeHolder->contact_email = $request->get('email');
        $DpiaStakeHolder->user_type = $request->get('userrole');
        $DpiaStakeHolder->user_id = $user->id; // User ID user table
        $DpiaStakeHolder->created_by_id = $id;
        $DpiaStakeHolder->save();

        $log = new DpiaLogs();
        $log->dpia_id = $DpiaStakeHolder->id;
        $log->type = 'Dpia Stake Holder';
        $log->action = 'Add Dpia Stake Holder';
        $log->user_id = Auth::User()->id;
        $log->json = json_encode($DpiaStakeHolder);
        $log->save();

        return back()->with('success', 'User Registered Successfully');
    }

    public function edit($id) {
        $dpiauser = User::find($id);
        $dpiaUserType = DpiaStakeHolder::where('user_id', $id)->first();
        return view('admin.dpia.dpia_user.subuser.edit',['dpiauser' => $dpiauser, 'dpiaUserType' => $dpiaUserType]);
    }

    public function update(Request $request, $id){

        $user = User::find($id);
        $user->fname = $request->get('fname');
        $user->lname = $request->get('lname');
        $user->save();

        $DpiaStakeHolder = DpiaStakeHolder::where('user_id', $id)->first();
        $DpiaStakeHolder->name = $request->get('fname').' '.$request->get('lname');
        $DpiaStakeHolder->save();

        $log = new DpiaLogs();
        $log->dpia_id = $DpiaStakeHolder->id;
        $log->type = 'Dpia Stake Holder';
        $log->action = 'Update Dpia Stake Holder';
        $log->user_id = Auth::User()->id;
        $log->json = json_encode($DpiaStakeHolder);
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
            $log->type = 'Dpia Stake Holder';
            $log->action = 'Enable Dpia Stake Holder';
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

            $log = new DpiaLogs();
            $log->dpia_id = $dpiaUser->id;
            $log->type = 'Dpia Stake Holder';
            $log->action = 'Disable Dpia Stake Holder';
            $log->user_id = Auth::User()->id;
            $log->json = json_encode($dpiaUser);
            $log->save();
        }
        return back();
    }
}
