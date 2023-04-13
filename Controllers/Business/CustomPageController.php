<?php

namespace App\Http\Controllers\Business;

use App\Models\FourteenDaysUsersStripeInfo;
use App\Http\Controllers\Controller;
use App\Mail\AddDomain;
use App\Models\MembershipPlans;
use App\Models\PlanPurchaseHistory;
use App\Models\User;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Exception;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Customer;
use PDF;

use App\Mail\WelcomeMail;

class CustomPageController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('business');

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function accountProfile()
    {
        if(Auth::check()) {
            $user = Auth::User();
//            if($user->stripe_id != null){
//                return redirect("/business/dashboard");
//            }
            session()->forget('upgrade_plan');
            $activePlans = \App\Models\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
            return view('business.account-profile')->with('activePlans', $activePlans);
        }else{
            return redirect("/login");
        }
    }

    public function paymentInfoData(Request $request)
    {
        //dd('hello');
        $data = $request->all();

        //dd('data',$data);
        $amount = 0;
        $period = $request->get('package_val');
        $membershipPlan = MembershipPlans::where(['slug'=> $request->get('slug')])->first();
        $amount = $membershipPlan->price;
        $amount = ($period == '1') ? ($amount*2) : ($amount*12);

        $selectedPeriod = ($period == '1') ? 'Month' : 'Year';
        //$amount = ($amount*$period);
        $discount = 0;
        $vat = 1.2;
        $amount = $amount * $vat;

        $user = Auth::User();
        //dd('user: ', $user);
        $token = $request->get('token');
        $paymentMethod = $request->get('paymentMethod');
        //return response()->json(['paymentMethod'=> $paymentMethod]);
        $stripe_customer = $this->getStripeCustomer($paymentMethod['card']);
        //return response()->json(['stripe_customer'=> $stripe_customer]);
        if($stripe_customer != null && $stripe_customer != 'error'){

            $user = User::find($user->id);
            $user->trial_ends_at = date("Y-m-d H:m:s", strtotime("14 days"));
            $user->membership_plan_id  = '5';
            $user->upgraded_at = date('Y-m-d H:m:s');
            $user->plan_expiry = $selectedPeriod;
            $user->save();

            $stripeUsersInfo = new FourteenDaysUsersStripeInfo();
            $stripeUsersInfo->user_id = $user->id;
            $stripeUsersInfo->plan_id = $membershipPlan->id;
            //$stripeUsersInfo->trial_ends_at = date("Y-m-d H:m:s", strtotime("14 days"));
            $stripeUsersInfo->plan_period = $period;
            $stripeUsersInfo->plan_price = $amount;
            $stripeUsersInfo->payment_method = json_encode($paymentMethod);
            $stripeUsersInfo->done = true;
            $stripeUsersInfo->save();

            //Mail::to($user->email)->bcc(config('app.hubspot_bcc'))->send(new WelcomeMail($user, $user->password));
            //Mail::to($user->email)->bcc(config('app.hubspot_bcc'))->send(new PricePlanMail($planPurchaseHistory, $old_plan, $pdf));
            //return (new PricePlanMail($planPurchaseHistory, $old_plan))->render();
            //$this->notify($booking);
            return response()
                ->json(['message' =>'Card information is successfully upgrade!',
                    'page' => route('business.membershipPlansSuccess'),
                    'paymentResponse' => $stripeUsersInfo
                ],200);

            //dd((int)($amount * 100));
        }else{
            return response()
                ->json(['Already_user' =>'This process has been already performed by this user'],401);
        }



    }


    public function getStripeCustomer($intent)
    {
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        //Stripe::setApiKey('sk_test_4eC39HqLyjWDarjtT1zdp7dc');
        /*var_dump('In getStripeCustomer $this->isStripeCustomer()',$this->isStripeCustomer());
        var_dump('In getStripeCustomer Auth::user()->id',Auth::user()->id);*/
        $customer = null;
        if (!$this->isStripeCustomer())
        {
//            var_dump('In getStripeCustomer if');
            $customer = $this->createStripeCustomer($intent);
        }
        else
        {
//            var_dump('In getStripeCustomer else');
            $customer = 'error';
        }
        return $customer;
    }



    public function createStripeCustomer($intent)
    {
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        //Stripe::setApiKey('sk_test_4eC39HqLyjWDarjtT1zdp7dc');
        $customer = Customer::create(array(
            "description" => Auth::user()->email
        ));
        Auth::user()->stripe_id = $customer->id;
        //return response()->json(['customer'=> $customer]);
        Auth::user()->save();
        return $customer;
    }

    /**
     * Check if the Stripe customer exists.
     *
     * @return boolean
     */
    public function isStripeCustomer()
    {
        return Auth::check() && User::where('id', Auth::user()->id)->whereNotNull('stripe_id')->first();
    }


}
