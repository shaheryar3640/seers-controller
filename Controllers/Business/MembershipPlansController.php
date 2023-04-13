<?php
namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Mail\BookingCustomerAfterPaying;
use App\Mail\PaymentConfirmation;
use App\Mail\PricePlanMail;
use Auth;
use Illuminate\Support\Facades\Mail;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Charge;
use App\Models\User;
use App\Models\PlanPurchaseHistory;

use App\Models\UProduct;
use App\Models\UPlan;
use App\Models\UFeature;

use App\Models\MembershipPlans;
use App\Models\PromoCodes;
use App\Models\PromocodesUsersLogs;
use App\Mail\AdvisorBeingBooked;
use PDF;



class MembershipPlansController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('business');
    }

    public function postPayWithStripe(Request $request)
    {
        $data = $request->all();
        // if($is_product_payment) {

        // }
        //dd('data',$data);
        $amount = 0;
        $period = $request->get('package_val');

        $userPlanPeriod = Auth::User()->plan_expiry;
        $userPlanUpgrade = new \DateTime(Auth::User()->upgraded_at);
        $userActive = Auth::User()->plan_active;

        //var_dump("user plan upgraded at--> ". $userPlanUpgrade->format("Y-m-d H:i:s"));

        $current_date = new \DateTime("now");
        $diff = $current_date->diff($userPlanUpgrade);


        if($period == 1 && $userPlanPeriod == 'year' && $userActive == 1 && $diff->format('%R%a') > 0){
            return response()->json(['errors'=>['same_package'=>'Your are using the same package'],'message'=>trans('business.profile_update_error')],400);
        }else if($period == 0 && $userPlanPeriod == 'month' && $userActive == 1 && $diff->format('%R%a') > 0){
            return response()->json(['errors'=>['same_package'=>'Your are using the same package'],'message'=>trans('business.profile_update_error')],400);
        }


        $membershipPlan = MembershipPlans::find($request->get('id'));
        $amount = $membershipPlan->price;
        $amount = ($period == '1') ? ($amount*2) : $amount;
        $amount = ($amount * $period);
        $discount = 0;
        if($request->get('promo_code') != null){
            $promocode = PromoCodes::where(['promo_code'=> $request->get('promo_code') , 'enabled' => 1])->first();

            if($promocode == null){
                return response()->json(['errors'=>['promo_code'=>'Promo code is not Valid'],'message'=>trans('business.profile_update_error')],400);
            } elseif (( date('Y-m-d H:m:s') > $promocode->expire_at ) || ( date('Y-m-d H:m:s') < $promocode->active_at )){
                return response()->json(['errors'=>['promo_code'=>'Promo code is not Valid'],'message'=>trans('business.profile_update_error')],400);
            }


            if($promocode->promo_code == 'Seersspecial£1'){
                $amount = 1;
            }else{
                $discount = $promocode->discount_percentage;
                $amount = ($amount-($amount*($discount / 100)));
            }


        }/*else{
            $discount = 50;
            $amount = ($amount-($amount*($discount / 100)));
        }*/
        $vat = 1.2;
        $amount = $amount * $vat;

        $user = Auth::User();
        //dd('user: ', $user);
        $token = $request->get('token');
        $paymentMethod = $request->get('paymentMethod');
        //return response()->json(['paymentMethod'=> $paymentMethod]);
        $stripe_customer = $this->getStripeCustomer($paymentMethod['card']);
        //return response()->json(['stripe_customer'=> $stripe_customer]);
        if($stripe_customer != null){
            try {
                Stripe::setApiKey(config('app.stripe_key'));
                $paymentIntent = PaymentIntent::create([
                    "payment_method" => $paymentMethod['id'],
                    "amount" => (int)($amount * 100),
                    "currency" => "gbp",
                    "customer" => $stripe_customer->id,
                    "payment_method_types" => ["card"],
                    'setup_future_usage' => 'off_session',
                    "description" => 'Purchase of membership plan '.$membershipPlan->name,
                ]);
                //dd($paymentIntent);
                //return response()->json(['paymentIntent'=> $paymentIntent]);
                $paymentResponse = $this->generatePaymentResponse($paymentIntent);
                //return response()->json(['paymentResponse'=> $paymentResponse]);
            } catch (Exception $e) {
                // Something else happened, completely unrelated to Stripe
                return response()
                    ->json(['e'=>$e,'message' =>'Your credit card has been declined.',
                        'page'=>route('business.membershipPlansFailed')],401);
            }
            if($paymentResponse->status == "succeeded") {
                $cardinfo = $paymentMethod['card'];
                $brand =  $cardinfo['brand'];
                $card_last4 =  $cardinfo['last4'];

                $old_plan = '';
                $plan_expiry = ($request->get('package_val') == '1') ? ('month') : ('year');
                //dd($stripe_customer);
                //return response()->json(['paymentMethod'=> $paymentMethod]);
                //return response()->json(['stripe_customer'=> $stripe_customer]);
                //return response()->json(['paymentResponse'=> $paymentResponse]);
                $user = User::find($user->id);
                $old_plan = MembershipPlans::find($user->membership_plan_id);
                $user->card_brand = $brand;
                $user->card_last_four = $card_last4;
                $user->payment_method = $paymentResponse->payment_method;
                $user->trial_ends_at = date("Y-m-d H:m:s");
                $user->membership_plan_id = $membershipPlan->id;
                $user->upgraded_at = date('Y-m-d H:m:s');
                $user->plan_expiry = $plan_expiry;
                $user->recursive_payment = true;
                $user->on_trial = 0;
                $user->save();

                $planPurchaseHistory = new PlanPurchaseHistory();
                $planPurchaseHistory->user_id = $user->id;
                $planPurchaseHistory->plan_id = $membershipPlan->id;
                $planPurchaseHistory->plan_price = $membershipPlan->price;
                $planPurchaseHistory->discount_percentage = $discount;
                $planPurchaseHistory->paid_price = $amount;
                $planPurchaseHistory->charged_id = $paymentIntent->id;
                $planPurchaseHistory->save();

                if($request->get('promo_code') != null) {
                    $promocode = PromoCodes::where(['promo_code' => $request->get('promo_code'), 'enabled' => 1])->first();

                    if($promocode != null){
                        $promocodelog = new PromocodesUsersLogs();
                        $promocodelog->promo_code = $promocode->id;
                        $promocodelog->user_id = $user->id;
                        $promocodelog->promocode_string = $promocode->promo_code;
                        $promocodelog->user_email = $user->email;
                        $promocodelog->discount = $discount;
                        $promocodelog->price = $amount;
                        $promocodelog->save();
                    }
                }

                $pdf = PDF::loadView('business.emails.price-invoice', ['planPurchaseHistory'=>$planPurchaseHistory , 'user'=>$user]);
                
                // var_dump($pdf);
                // die();

                Mail::to($user->email)->bcc(config('app.hubspot_bcc'))->send(new PricePlanMail($planPurchaseHistory, $old_plan, $pdf));

                  //  $to = ['email' => $user->email, 'name' => ''];
//         $template = [
//             'id' => 'd-026b443099ea484a90a85840ef2474e0', 
//             'data' => ['plan_purchase_history' => $planPurchaseHistory,'old_plan'=>$old_plan]
//         ];
//         sendEmailViaSendGrid($to, $template,$pdf);

                //return (new PricePlanMail($planPurchaseHistory, $old_plan))->render();
                //$this->notify($booking);
                return response()
                    ->json(['message' =>'Your package is successfully upgrade!',
                        'page' => route('business.membershipPlansSuccess'),
                        'paymentResponse' => $paymentResponse
                    ],200);
            }else{
                /*return response()
                    ->json([
                        'message' =>'Your credit card has been declined. Please try again or contact us.',
                        'page'=>route('business.membershipPlansFailed')],400);*/
            }
            //dd((int)($amount * 100));
        }
    }


    public function productPayment(Request $request)
    {
        $data = $request->all();
        $period = $request->get('package_val');
        $plan = \App\Models\Plan::find($request->get('id'));
        // $plan = \App\Plan::with('product')->whereId($request->get('id'))->first();
        $price = $plan->price;
        $price = ($period == '1') ? ($price * 2) : $price;
        $discount = 0;
        // if($request->get('promo_code') != null){
        //     $promocode = PromoCodes::where(['promo_code'=> $request->get('promo_code') , 'enabled' => 1])->first();
        //     if($promocode == null){
        //         return response()->json(['errors'=>['promo_code'=>'Promo code is not Valid'],'message'=>trans('business.profile_update_error')],400);
        //     } elseif (( date('Y-m-d H:m:s') > $promocode->expire_at ) || ( date('Y-m-d H:m:s') < $promocode->active_at )){
        //         return response()->json(['errors'=>['promo_code'=>'Promo code is not Valid'],'message'=>trans('business.profile_update_error')],400);
        //     }
        //     if($promocode->promo_code == 'Seersspecial£1'){
        //         $price = 1;
        //     }else{
        //         $discount = $promocode->discount_percentage;
        //         $price = ($price-($price*($discount / 100)));
        //     }
        // }
        $user = auth()->user();
        $product = $plan->product;
        $features = $plan->features;

        $u_product = UProduct::whereNameAndUserId($product->name, $user->id)->first();

        if (is_null($u_product)) {
            $u_product = new UProduct;//::create($product->toArray());
            $u_product->fill($product->toArray());
            $u_product->purchased_on = date("Y-m-d H:i:s", strtotime("now"));
            $u_product->on_trial = 0;
            $u_product->user_id = $user->id;
            $u_product->trial_days = 0;
            $u_product->save();
        
            $u_plan = UPlan::whereUProductId($u_product->id)->first();

            if (is_null($u_plan)) {
                $u_plan = new UPlan;//::create($plan->toArray());
                $u_plan->fill($plan->toArray());
                $u_plan->u_product_id = $u_product->id;
                $u_plan->purchased_on = date("Y-m-d H:i:s", strtotime("now"));
                $u_plan->expired_on = date("Y-m-d H:i:s", strtotime( '+' . $period . 'Month'));
                $u_plan->save();
                
                $u_features = UFeature::whereUPlanId($u_plan->id)->first();

                if (is_null($u_features)) {
                    foreach ($features as $feature) {
                        $u_features = new UFeature;//::create($features->toArray());
                        $u_features->fill($feature->toArray());
                        $u_features->u_plan_id = $u_plan->id;
                        $u_features->save();
                    }
                }
            }
            $user->is_new = 1;
            $user->save();
        }

        return response()->json(['response' => $data], 200);
    }


     public function generatePaymentResponse($intent) {
         //dd($intent);
         if ($intent->status == 'requires_action' && $intent->next_action->type == 'use_stripe_sdk') {
             # Tell the client to handle the action
             return $intent;
         }else if ($intent->status == "requires_payment_method") {
             return $intent;
         } else if ($intent->status == "requires_confirmation") {
             $confirm_payment = PaymentIntent::retrieve($intent->id);
             $confirm_payment->confirm();
             if ($confirm_payment->status == "succeeded") {
                 # The payment didn’t need any additional actions and completed!
                 # Handle post-payment fulfillment
                 return $confirm_payment;
             }else{
                 return $intent;
             }
         }else {
             # Invalid status
             return $intent;
         }
     }

    public function getStripeCustomer($intent)
    {
        Stripe::setApiKey(config('app.stripe_key'));
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
            $customer = Customer::retrieve(Auth::user()->stripe_id);
        }
        return $customer;
    }



    public function createStripeCustomer($intent)
    {
        Stripe::setApiKey(config('app.stripe_key'));
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


    public function setFreePlan(Request $request){
        if(Auth::check()){
            $user = Auth::User();
            $user = User::find($user->id);
            $membershipPlan = MembershipPlans::find($request->get('plan_id'));
            $old_plan = MembershipPlans::find($user->membership_plan_id);
            $user->on_trial = 0;
            $user->membership_plan_id = $membershipPlan->id;
            $user->trial_ends_at = null;
            $user->upgraded_at = date('Y-m-d H:m:s');
            $user->save();
            return response()
                ->json(['message' =>'Your package is successfully upgrade!',
                    'page' => route('business.membershipPlansSuccess')
                ],200);
        }else{
            return response()
                ->json(['error' =>'Please Login',
                ],400);
        }

    }
    public function routeMembershipPlansSuccess(){
        return view('business.membership_plans_success');
    }
    public function routeMembershipPlansFailed(){
        return view('business.membership_plans_failed');
    }
}