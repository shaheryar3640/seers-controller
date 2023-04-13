<?php

namespace App\Http\Controllers\dpia;

use App\Models\DpiaLogs;
use App\Models\DpiaStakeHolder;
use App\Mail\Dpia\DpiaCreateUserMail;
use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class UserManagementController extends Controller
{

    public function __construct()
    {
        $message = '';
        $this->status_code = 0;
    }
    /**
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function getSubUsers() {
        $subUsers = null;
        if (request()->ajax()) {
            $user = DpiaStakeHolder::where(['user_id' => Auth::user()->id])->first();
            $subUsers = DpiaStakeHolder::with('dpia_user')->where(['created_by_id' => $user->user_id])->orderby('created_at', 'ASC')->get();
            if ($subUsers->count() > 0) {
                $this->message = 'Found';
                $this->status_code = 200;
            } else {
                $this->message = 'Record not found';
                $this->status_code = 201;
            }
        } else {
            $this->message = 'Bad Request';
            $this->status_code = 400;
        }

        return response([
            'message' => $this->message,
            'subUsers' => $subUsers
        ], $this->status_code);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function updateUser(Request $request) {
        if($request->ajax()) {

            $data = $request->all();

            $validator = Validator::make($data, [
                'id' => 'required|numeric',
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'user_role' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 400);
            }

            $user = User::find($request->get('id'));
            if($user) {
                $user->fname = $data['first_name'];
                $user->lname = $data['last_name'];
                $user->save();
                $stake_holder = DpiaStakeHolder::where(['user_id' => $data['id']])->first();
                if($stake_holder) {
                    $stake_holder->name = $user->name;
                    $stake_holder->user_type = $data['user_role'];
                    $stake_holder->save();
                    $this->message = 'Record updated successfully';
                    $this->status_code = 200;
                }
            } else {
                $this->message = 'User Not Found';
                $this->status_code = 402;
            }
        } else {
            $this->message = 'Bad Request';
            $this->status_code = 400;
        }

        return response(['message' => $this->message ], $this->status_code);
    }

    public function toggleUser(Request $request) {
        if($request->ajax()) {
            $user = DpiaStakeHolder::where(['user_id' => $request->get('id')])->first();
            if ($user) {
                $user->enabled = $request->get('enabled');
                $user->save();
                $this->message = $request->get('enabled') == true ? 'User enabled successfully' : 'User disabled successfully';
                $this->status_code = 200;
            }
        } else {
            $this->message = 'Bad Request';
            $this->status_code = 400;
        }

        return response(['message' => $this->message], $this->status_code);
    }

    public function store (Request $request) {

        if ($request->ajax()) {

            $data = $request->all();

            $validator = Validator::make($data, [
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'email' => 'required|string|email|unique:users',
                'password' => 'required|string|min:8',
                'user_role' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 400);
            }

            $user = User::where('email', $request->get('email'))->first();

            if ($user && isset($user->email) && $user->is_register) {
                $this->message = 'This email has already been taken.';
                $this->status_code = 402;
            } elseif (!$user || !isset($user->email)) {
                $user = new User;
                $user->fname = $request->get('first_name');
                $user->lname = $request->get('last_name');
                $user->email = $request->get('email');
                $user->password = bcrypt($request->get('password'));
                $user->business = true;
                $user->on_trial = 0;
                $user->save();

                $stake_holder = new DpiaStakeHolder;
                $stake_holder->name = $data['first_name'] . ' ' . $data['last_name'];
                $stake_holder->contact_email = $data['email'];
                $stake_holder->user_type = $data['user_role'];
                $stake_holder->user_id = $user->id; // User ID from user's table
                $stake_holder->created_by_id = Auth::user()->id;
                $stake_holder->save();

//                $log = new DpiaLogs();
//                $log->dpia_id = $DpiaStakeHolder->id;
//                $log->type = 'Dpia Stake Holder';
//                $log->action = 'Add Dpia Stake Holder';
//                $log->user_id = Auth::User()->id;
//                $log->json = json_encode($DpiaStakeHolder);
//                $log->save();

                // Mail::to($user->email)->bcc(config('app.hubspot_bcc'))->send(new DpiaCreateUserMail($stake_holder, $request->get('password'), Auth::User()->name));
                // DPIA Add New User (Manager User Account) | sendgrid template name
                 $to = ['email' => $user->email, 'name' => $stake_holder->name];
                $template = [
                    'id' => config('sendgridtemplateid.DPIA-Add-New-User-(Manager-User-Account)'), 
                    'data' => [
                        'first_name' => $data['first_name'],
                        'dpia_owner' => Auth::User()->name,
                        'dpia_role' => $stake_holder->user_type,
                        'time' => $stake_holder->created_at,
                        'dpia_user_email' => $stake_holder->contact_email,
                        'dpia_user_password' => $request->get('password'),
                    ]
                ];
                sendEmailViaSendGrid($to, $template);
                $this->message = 'User Registered Successfully';
                $this->status_code = 200;
            }
        } else {
            $this->message = 'Bad Request';
            $this->status_code = 400;
        }

        return response(['message' => $this->message], $this->status_code);
    }
}
