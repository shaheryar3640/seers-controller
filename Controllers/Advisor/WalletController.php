<?php

namespace App\Http\Controllers\Advisor;

use App\Mail\WithdrawalCompleted;
use App\Mail\WithdrawalRequest;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Withdraw;
use File;
use Illuminate\Support\Facades\Mail;
use Image;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Auth;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
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
        return view('advisor.wallet');
    }
    public function createPaypal(Request $request)
    {
        //die('in function');
        $settings = Setting::first();
        $user = Auth::User();
        //dd($user->remaining_amount);
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|max:' . $user->remaining_amount . '|min:10',
            'paypal_id' => 'required|string|max:255',
        ],[
            'amount.required' => 'Withdraw amount is required',
            'paypal_id.required' => 'Paypal ID is required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors'=>$validator->errors(),'message'=>trans('advisor.withdraw_error')],400);
        }
        //dd('hello');
        $data = $request->all();
        $data['status'] = 'pending';
        $data['method'] = 'PayPal';
        $data['seller_id'] = $user->sellerBookings[0]->seller_id;
        $withdraw = Withdraw::create($data);

        //$url = URL::to("/");
        $site_logo = asset('/local/images/settings/' . $settings->site_logo) ;
        $site_name = $settings->site_name;
        $currency = $settings->site_currency;
        $user_email = $user->email;
        $username = $user->lname;

        $admindetails = User::where('admin','=','1')->first();

        $admin_email = $admindetails->email;

        $amount = $withdraw-> amount;
        $paypal_id = $withdraw-> paypal_id;
        $method = $withdraw-> method;
        $bank_acc_no = $withdraw->bank_acc_no;
        $bank_info = $withdraw->bank_info;
        $ifsc_code = $withdraw->ifsc_code;

        $datas = [
            'method' => $method,
            'amount' => $amount,
            'paypal_id' => $paypal_id,
            'bank_acc_no' => $bank_acc_no,
            'bank_info' => $bank_info,
            'ifsc_code' => $ifsc_code,
            'currency' => $currency,
            'site_logo' => $site_logo,
            'site_name' => $site_name
        ];

        Mail::send(new WithdrawalRequest($withdraw));
        //  $to = ['email' => $withdraw->seller->email, 'name' => $withdraw->seller->name];
//         $template = [
//             'id' => 'd-026b443099ea484a90a85840ef2474e0', 
//             'data' => [ "withdraw" => $withdraw ]
//         ];
//         sendEmailViaSendGrid($to, $template);
        return response()->json(['message'=>trans('advisor.withdraw_success')]);
    }
    public function createBank(Request $request)
    {
        //die('in function');
        $settings = Setting::first();
        $user = Auth::User();
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|max:' . $user->remaining_amount . '|min:10',
            'bank_acc_no' => 'required|string|max:255',
            'bank_info' => 'required|string|max:255',
            'ifsc_code' => 'required|string|max:255',
        ],[
            'amount.required' => 'Withdraw amount is required',
            'bank_acc_no.required' => 'Bank Account is required',
            'bank_info.required' => 'Bank Info is required',
            'ifsc_code.required' => 'IBAN code is required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors'=>$validator->errors(),'message'=>trans('advisor.withdraw_error')],400);
        }

        $data = $request->all();
        $data['status'] = 'pending';
        $data['method'] = 'Bank';
        $data['seller_id'] = $user->sellerBookings[0]->seller_id;
        $withdraw = Withdraw::create($data);

        //$url = URL::to("/");
        $site_logo = asset('/local/images/settings/' . $settings->site_logo) ;
        $site_name = $settings->site_name;
        $currency = $settings->site_currency;
        $user_email = $user->email;
        $username = $user->name;
        $admindetails = User::where('admin','1')->first();
        $admin_email = $admindetails->email;

        $amount = $withdraw-> amount;
        $paypal_id = $withdraw-> paypal_id;
        $method = $withdraw-> method;
        $bank_acc_no = $withdraw->bank_acc_no;
        $bank_info = $withdraw->bank_info;
        $ifsc_code = $withdraw->ifsc_code;

        $datas = [
            'method' => $method,
            'amount' => $amount,
            'paypal_id' => $paypal_id,
            'bank_acc_no' => $bank_acc_no,
            'bank_info' => $bank_info,
            'ifsc_code' => $ifsc_code,
            'currency' => $currency,
            'site_logo' => $site_logo,
            'site_name' => $site_name
        ];
        Mail::send(new WithdrawalRequest($withdraw));
        //  $to = ['email' => $withdraw->seller->email, 'name' => $withdraw->seller->name];
//         $template = [
//             'id' => 'd-026b443099ea484a90a85840ef2474e0', 
//             'data' => [ "withdraw" => $withdraw ]
//         ];
//         sendEmailViaSendGrid($to, $template);
        return response()->json(['message'=>trans('advisor.withdraw_success')]);
    }
    public function routegetWallet()
    {
        return response()->json(['advisor_wallet'=>['balance' => Auth::User()->remaining_amount, 'min_withdraw' => Setting::first()->withdraw_amt], 'avatar_link' => Auth::User()->avatar_link]);
    }
    public function routegetWithdrawals()
    {
        return response()->json(['advisor_withdrawals_completed'=>Auth::User()->CompletedWithdrawals, 'advisor_withdrawals_pending'=>Auth::User()->PendingWithdrawals, 'avatar_link' => Auth::User()->avatar_link]);
    }
}