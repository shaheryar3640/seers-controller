<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Mail;
use Auth;
use Crypt;
use URL;

class WalletController extends Controller
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
    public function __construct()
    {
        $this->middleware('auth');
    }


	public function sangvish_showpage() {

		 $email = Auth::user()->email;


		 $set_id=1;
		$setting = DB::table('settings')->where('id', $set_id)->get();




		 $shop_count = DB::table('shop')

				   ->where('status', '=', 'approved')
				   ->where('seller_email', '=', $email)
				 ->count();
				 if($shop_count!=0)
		{
                    $shop = DB::table('shop')
				   ->where('status', '=', 'approved')
				   ->where('seller_email', '=', $email)
				 ->get();
				$shop_id = $shop[0]->id;

			$check_count = DB::table('booking')
					->where('shop_id', '=', $shop_id)
					->where('status', '=', 'paid')
					 ->count();

		$with_count = DB::table('withdraw')
					->where('withdraw_shop_id', '=', $shop_id)
					 ->count();

		if(!empty($with_count))
		{

			$withdraws = DB::table('withdraw')
				              ->where('withdraw_shop_id', '=', $shop_id)
							  ->where('withdraw_status', '=', 'pending')
							  ->orderBy('wid','desc')
							   ->get();


				$withdraws_cc = DB::table('withdraw')
				              ->where('withdraw_shop_id', '=', $shop_id)
							  ->where('withdraw_status', '=', 'completed')
							  ->orderBy('wid','desc')
							   ->get();

			$queryy=DB::table('withdraw')
					        ->where('withdraw_shop_id', '=', $shop_id)
							->orderBy('wid','desc')
							->limit(1)->offset(0)
							->get();


            $shop_balance="";
					foreach($queryy as $balances)
					{
                    //dd($balances);
                        $shop_balance .= (int)$balances->total_balance - (int)$balances->withdraw_amt;
                        //dd($shop_balance);
					}

		$data=array('shop_id' => $shop_id, 'setting' => $setting, 'with_count' => $with_count,  'shop_balance' => $shop_balance, 'withdraws' => $withdraws,
		'withdraws_cc' => $withdraws_cc, 'check_count' => $check_count,'shop_count' => $shop_count);
		}
	    if(empty($with_count))
		{
			$nquery=DB::table('booking')
							->where('shop_id', '=', $shop_id)
							 ->get();

					$bal=0;
					//dd($nquery);
					foreach($nquery as $nbalance)
					{
						$bal += (int)$nbalance->total_amt;
						$currency=$nbalance->currency;
					}



			$data=array('shop_id' => $shop_id, 'setting' => $setting, 'with_count' => $with_count, 'bal' => $bal, 'check_count' => $check_count,'shop_count' => $shop_count);
		}
		}
		if($shop_count==0)
		{
			$with_count=0;
			$check_count=0;
			$data=array('shop_count' => $shop_count,'with_count' => $with_count,'check_count' => $check_count,'setting' => $setting);
		}

		 return view('wallet')->with($data);
   }



  public function sangvish_savedata(Request $request)
   {
	   $data = $request->all();


	  $shop_balance =  $data['shop_balance'];
	  $total_bal = $data['shop_balance'];

	   $withdraw_amt = $data['withdraw_amt'];
	   $withdraw_mode = $data['withdraw_mode'];

	   if(!empty($data['paypal_id']))
	   {
	   $paypal_id = $data['paypal_id'];
	   }
	   else
	   {
		   $paypal_id="";
	   }

	   if(!empty($data['bank_acc_no']))
	   {
	   $bank_acc_no = $data['bank_acc_no'];
	   }
	   else
	   {
		   $bank_acc_no ="";
	   }

	   if(!empty($data['bank_name']))
	   {

	   $bank_name = $data['bank_name'];
	   }
	   else
	   {
		   $bank_name ="";
	   }
	   if(!empty($data['ifsc_code']))
	   {
	   $ifsc_code = $data['ifsc_code'];
	   }
	   else
	   {
		   $ifsc_code="";
	   }
	   $shop_id = $data['shop_id'];
	   $min_with_amt = $data['min_with_amt'];

	   $with_status = 'pending';

	   $shop = DB::table('shop')
			   ->where('id', '=', $shop_id)
			   ->get();

	   $shop_name = $shop[0]->shop_name;


	   $setid=1;
		$setts = DB::table('settings')
		->where('id', '=', $setid)
		->get();

		$url = URL::to("/");

		$site_logo=$url.'/local/images/settings/'.$setts[0]->site_logo;

		$site_name = $setts[0]->site_name;

		$currency = $setts[0]->site_currency;

		$user_email = Auth::user()->email;
		$username = Auth::user()->name;

		$aid=1;
		$admindetails = DB::table('users')
		 ->where('id', '=', $aid)
		 ->first();

		$admin_email = $admindetails->email;


	   if($min_with_amt<=$withdraw_amt && $shop_balance>=$withdraw_amt)
		{
			DB::insert('insert into withdraw (shop_balance,withdraw_amt,total_balance,withdraw_mode,paypal_id,bank_acc_no,bank_info,ifsc_code,withdraw_shop_id,withdraw_status
			) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$shop_balance,$withdraw_amt,$total_bal,$withdraw_mode,$paypal_id,$bank_acc_no,$bank_name,$ifsc_code,$shop_id,$with_status]);



			$withdraw = DB::table('withdraw')
					        ->where('withdraw_shop_id', '=', $shop_id)
							->orderBy('wid','desc')
							->limit(1)->offset(0)
							->first();

	   $w_withdraw_amt = $withdraw->withdraw_amt;
	   $w_withdraw_mode = $withdraw->withdraw_mode;
	   $w_paypal_id = $withdraw->paypal_id;
	   $w_bank_acc_no = $withdraw->bank_acc_no;
	   $w_bank_info = $withdraw->bank_info;
	   $w_ifsc_code = $withdraw->ifsc_code;


		$datas = [
            'w_withdraw_amt' => $w_withdraw_amt, 'w_withdraw_mode' => $w_withdraw_mode, 'w_paypal_id' => $w_paypal_id, 'w_bank_acc_no' => $w_bank_acc_no,
			'w_bank_info' => $w_bank_info, 'w_ifsc_code' => $w_ifsc_code, 'shop_name' => $shop_name, 'currency' => $currency, 'site_logo' => $site_logo, 'site_name' => $site_name
        ];




		Mail::send('withdrawemail', $datas , function ($message) use ($admin_email,$user_email,$username)
        {
            $message->subject('Withdrawal Request');

            /*$message->from($user_email, $username);

            $message->to($admin_email);*/

			 $message->from($admin_email,'Admin');
            $message->bcc(config('app.hubspot_bcc'));
            $message->to($admin_email);


        });

		//  $to = ['email' => $admin_email, 'name' => ''];
//         $template = [
//             'id' => 'd-026b443099ea484a90a85840ef2474e0', 
//             'data' => ['w_withdraw_amt' => $w_withdraw_amt, 'w_withdraw_mode' => $w_withdraw_mode, 'w_paypal_id' => $w_paypal_id, 'w_bank_acc_no' => $w_bank_acc_no,			'w_bank_info' => $w_bank_info, 'w_ifsc_code' => $w_ifsc_code, 'shop_name' => $shop_name, 'currency' => $currency, 'site_logo' => $site_logo, 'site_name' => $site_name        ]
//         ];
//         sendEmailViaSendGrid($to, $template);



			return redirect()->back()->with('message', 'Updated Successfully');



		}
		else
		{
			return redirect()->back()->with('message', 'Please Check Minimum Withdraw Amount and Advisor Balance');
		}





   }

}