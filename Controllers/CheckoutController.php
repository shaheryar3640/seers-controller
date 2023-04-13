<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\DpiaStakeHolder;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\CbUsersDomains;
use App\Models\UserPaymentMethodDetails;
use Carbon\Carbon;
use App\Models\CookieXrayDialogue;
use Illuminate\Http\Request;
use Auth;
use App\Events\NewProductHasPurchasedEvent;
use App\Models\Plan;
use App\Models\User;
use App\Models\Defaulter;
use App\Models\UProduct;
use App\Models\UPlan;
use App\Models\UFeature;
use App\Models\PromoCodes;
use App\Models\UPromo;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use PDF;
use Stripe\PaymentIntent;
use Stripe;
use Stripe\Customer;
use Stripe\Charge;
use App\Models\StripeOrderDetail;
use Stripe\StripeClient;
use function foo\func;
use GuzzleHttp\Client;
use Log;

use DB;
use Illuminate\Support\Facades\Config;

class CheckoutController extends Controller
{
    private $user = null;
    private $product = null;
    private $plan = null;
    private $features = null;
    //    private $products = null;
    private $payment_response = null;
    private $stripe_id = null;
    //    private $staff_training_total_price = 0;
    private $promo_code = '';
    private $promo_code_value = 0;
    private $from_date = null;
    private $to_date = null;
    private $u_product = null;
    private $u_plan = null;
    // private $VAT = null;
    private $source = null;
    private $email = null;
    private $priceWithoutVatAndDiscountCC = 0;

    public function __construct()
    {
        //        $this->middleware('business');
    }

    public function index()
    {
        return view('checkout');
    }

    public function three_d_secure(Request $request) {
        $this->source = null;
        if(isset($request->source)){
            $this->source = $request->source;
        }
        $this->user = User::where('email','=',$request->email)->first();
        $this->email = $request->email;
        $promoCode = null;
        if(isset($request->promocode)){
            $promoCode = $request->promocode;
        }
        $currency = $request->currency;
        $discountApplied = $request->discountApplied;
        $tenure = $request->tenure;
        $plan_name = $request->plan_name;
        $members = $request->members;
        $costPrice = $request->costPrice;
        $totalPrice = $request->totalPrice;
        $promoValue = $request->promoValue;
        $product_name = $request->product_name;
        $domain_limit = $request->domainLimit;
        //dd($request->all());
        $stripe_key = Config::get('app.stripe_key');
        Stripe::setApiKey($stripe_key);
        $paymentResponse = PaymentIntent::retrieve($request->payment_intent);
        if ($paymentResponse->status === 'succeeded') {
            $this->stripe_id = $paymentResponse->customer;
            $this->payment_response = $paymentResponse;
            $this->product = Product::where('name', '=', $product_name)->first();
            if($this->email && ($this->source == 'srm' || $this->source == 'bms')){
                $this->insertThreeDEntries(false, false, $currency, $tenure, $plan_name, $members);
                if ($promoCode !== null || $promoCode !== '' && $discountApplied) {
                    $this->saveUserPromoCodeLog($promoCode);
                }
            } else{
                $this->insertThreeDEntries(false, false, $currency, $tenure, $plan_name, $members,$domain_limit);
                if ($promoCode !== null || $promoCode !== '' && $discountApplied) {
                    $this->saveUserPromoCodeLog($promoCode);
                }
            }
            session()->put('link', $this->product->url);
            $this->insertStripeDetail($paymentResponse);

            $this->u_product->price = $costPrice;
            $this->product->price = $costPrice;

            if($this->email && ($this->source == 'srm' || $this->source == 'bms')){
                $name = $this->user['name'];
                $id = $this->user['id'];
                $email = $this->email;
                $user = $this->user;
            } else {
                $name = $this->user['name'];
                $id = $this->user['id'];
                $email = $this->email;
                $user = $this->user;
            }
            $invoice = Invoice::add(
                $name,
                $email,
                $this->u_product->display_name,
                $this->u_plan->display_name,
                $this->u_product->price,
                Config::get('app.VAT'),
                // 1.2,
                $totalPrice,
                strtolower($currency),
                $this->u_product->recursive_status,
                $this->u_product->purchased_on,
                $this->u_product->expired_on,
                $this->u_product->id,
                $id
            );

            $pdf = PDF::loadView('invoices.product-payment-invoice', [
                'invoice' => $invoice,
                'user' => $user,
                'product' => $this->u_product,
                'plan' => $this->u_plan,
                'promo_value' => $promoValue,
                'from_date' => $this->u_product->purchased_on,
                'to_date' => $this->u_product->expired_on,
                'currency' => $currency
            ]);
            // $pdf->SetProtection(['copy', 'print'], '', 'pass');

            //Code Added for Cookie Consent Log Limit Start
            //Reset All cookie consent limit attributes
            // if($product_name == 'cookie_consent'){
            //     $domains = CbUsersDomains::Where('user_id','=',$this->user['id'])->get();
            //     foreach ($domains as $domain) {
            //         $this->resetCookieConsentLimit($domain);
            //     }

            // }
            if ($product_name == 'cookie_consent') {
                $res = curl_request('get',config('app.cmp_url').'/api/auth/reset-cookie-consent-limit?user_id='.$this->user['id'],[]);
            }
            //Code Added for Cookie Consent Log Limit End

            /* Check the Defaulters Start */

            if($this->u_product->expired_on > Carbon::now()->format('Y-m-d H:i:s')){
                $defaulter_expired = Defaulter::where([
                    ['user_id','=',$user->id],
                    ['product_id','=',$this->product->id]
                ])->first();
                if($defaulter_expired){
                    $defaulter_expired->delete();
                }
            }

            /* Check the Defaulters End */

            event(new NewProductHasPurchasedEvent($this->product, $user, $pdf, $promoValue, $currency));
            return view('3ds-redirect-passed')->with(['product'=>str_replace("_", "-", $this->product->name),'link'=>$this->product->url]);

        }else{
            return view('3ds-redirect-failed');
        }
  }

    public function subscribeProduct (Request $request) {


        $this->source = $request->source;
        $this->email = $request->user_email;
        /*
         * 1. if plan = 'free', then subscribe the product and keep track of the product on every cron start.
         * 2. if product is paid but on trial, then subscribe the product with specified trial and keep track of product's trial expiry,
         * 3. if product is paid and not on trial, then subscribe the product and keep track of product on every cron start.
         * */
        //if (!$request->ajax()) { return response(['message' => 'Bad Request'], 400); }

        $plan_name = $request->get('plan_name');
        $product_name = $request->get('product_name');
        // dd($request->all());
        $price = $request->get('price');
        $tenure = $request->get('tenure');
        $currency = $request->get('currency');
        $domain_limit = $request->get('domain_limit');
        $members = $request->get('members') ?? 0;
        $paymentMethod = $request->get('payment_method');
        $promoCode = $request->get('promo_code') ?? null;
        $hasDiscount = $request->get('has_discount');
        $discountApplied = false;
        $promoValue = 0;

        $this->product = Product::where('name', '=', $product_name)->first();

        // Point 1.
        if ($plan_name === 'free') {
            if($this->email && ($this->source == 'srm' || $this->source == 'bms')){
                $this->insertSrmEntries(true, false, $currency, $tenure, $plan_name, 0);
            } else{
                $this->insertEntries(true, false, $currency, $tenure, $plan_name, 0);
            }
            session()->put('link', $this->product->url);
            return response([
                'message' => 'Product has been subscribed successfully (FREE)',
                'url' => route('thank-you-for-checkout', ['product' => str_replace("_", "-", $this->product->name)])
            ], 200);
        }

        $stripe_customer = $this->getStripeCustomerObj($paymentMethod['card']);
        $paymentResponse = null;

        if ($stripe_customer === null || $stripe_customer === 'error') {
            return response(['message' => 'Could not find valid stripe customer'], 403);
        }

        $totalPrice = $discount = 0;
        $costPrice = floatval($this->calculateTotalCost($plan_name, $tenure,$domain_limit));

        if ($promoCode !== null && $hasDiscount) {
            $discount = $this->getPromoDiscount($promoCode,['plan_name'=>$plan_name,'tenure'=>$tenure,'product_name'=>$product_name]);
        }

        if ($discount === -1) {
            return response(['message' => 'Promo Code is not valid'], 403);
        } elseif ($discount === -2) {
            return response(['message' => 'Promo Code is not valid'], 403);
        } elseif ($discount === -3) {
            $totalPrice = 1;
        } elseif ($discount > 0) {
            $promoValue = number_format((($costPrice / 100) * $discount), 2, '.', '');
            $totalPrice = ($costPrice - $promoValue);
            $discountApplied = true;
        } else {
            $totalPrice = $costPrice;
        }

        if ($currency === 'GBP' && $totalPrice !== 1)  $totalPrice *= Config::get('app.VAT');;
        if($this->email && ($this->source == 'srm' || $this->source == 'bms')){
            if (strpos($this->email, '@seersco.com' ) !== false ) $totalPrice = 1.00;

        } else {
            if (strpos(auth()->user()->email, '@seersco.com' ) !== false ) $totalPrice = 1.00;
        }
        $totalPrice = floatval(number_format($totalPrice , 2, '.', ''));
        // Point 2.
        if ($this->product && $this->product->on_trial === 1) {
            $this->insertEntries(false, true, $currency, $tenure, $plan_name, $members);
            session()->put('link', $this->product->url);
            return response([
                'message' => 'Product has been subscribed successfully (TRIAL)',
                'url' => route('thank-you-for-checkout', ['product' => str_replace("_", "-", $this->product->name)])
            ], 200);
        }

        $skippableUsers = [
            'adnan.zaheer@seersco.com'
        ];

        try {
            // Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
            $stripe_key = Config::get('app.stripe_key');
            Stripe::setApiKey($stripe_key);
            if($this->email && ($this->source == 'srm' || $this->source == 'bms')){
                $description = $this->email . ' is purchasing ' . $this->product->display_name . ': ' . $plan_name;
                $user_email = $this->email;
            }else {
                $description = auth()->user()->email . ' is purchasing ' . $this->product->display_name . ': ' . $plan_name;
                $user_email = auth()->user()->email;
            }
            // dd($totalPrice);
            $paymentIntent = PaymentIntent::create([
                "payment_method" => $paymentMethod['id'],
                "amount" => (int) ($totalPrice * 100),
                "currency" => strtolower($currency),
                "customer" => $stripe_customer->id,
                "payment_method_types" => ['card'],
                "setup_future_usage" => 'off_session',
                "description" => $description,
                "confirm" => true,
                "return_url" => route('three-d-secure', ['product_name' => $product_name,'email'=>$user_email,'plan_name'=>$plan_name,'source'=>$this->source,'promocode'=>$promoCode,'currency'=>$currency,'tenure'=>$tenure,'members'=>$members,'discountApplied'=>$discountApplied,'costPrice'=>$costPrice,'totalPrice'=>$totalPrice,'promoValue'=>$promoValue,'domainLimit'=>$domain_limit]),
            ]);
            $paymentResponse = $this->generatePaymentResponse($paymentIntent);
        } catch (\Exception $e) {
            //            $paymentResponse = null;
            return response([
                'e' => $e,
                'message' => 'Your credit card has been declined.',
                'page' => route('business.membershipPlansFailed')
            ], 401);
        }

        /* if Payment made successfully */

        if ($paymentResponse->status === 'requires_action' || $paymentResponse->status === "requires_source_action") {
            if ($paymentResponse->next_action->type === 'redirect_to_url') {
                return response()->json([
                    "requires_action" => true,
                    "client_secret" => $paymentResponse->client_secret,
                    "next_action" => $paymentResponse->next_action
                ], 200);
            }
        }


        if ($paymentResponse->status === 'succeeded') {
            $this->payment_response = $paymentResponse;

            if($this->email && ($this->source == 'srm' || $this->source == 'bms')){
                $this->insertSrmEntries(false, false, $currency, $tenure, $plan_name, $members);
                if ($promoCode !== null || $promoCode !== '' && $discountApplied) {
                    $this->saveUserPromoCodeLog($promoCode);
                }
            }
            else{
                $this->insertEntries(false, false, $currency, $tenure, $plan_name, $members,$domain_limit);
                if ($promoCode !== null || $promoCode !== '' && $discountApplied) {
                    $this->saveUserPromoCodeLog($promoCode);
                }
            }
            session()->put('link', $this->product->url);
            $this->insertStripeDetail($paymentResponse);

            $this->u_product->price = $costPrice;
            $this->product->price = $costPrice;

            if($this->email && ($this->source == 'srm' || $this->source == 'bms')){
                $name = $this->user['name'];
                $id = $this->user['id'];
                $email = $this->email;
                $user = $this->user;
            } else {
                $name = auth()->user()->name;
                $id = auth()->id();
                $email = auth()->user()->email;
                $user = auth()->user();
            }
            $invoice = Invoice::add(
                $name,
                $email,
                $this->u_product->display_name,
                $this->u_plan->display_name,
                $this->u_product->price,
                Config::get('app.VAT'),
                // 1.2,
                $totalPrice,
                strtolower($currency),
                $this->u_product->recursive_status,
                $this->u_product->purchased_on,
                $this->u_product->expired_on,
                $this->u_product->id,
                $id
            );

            $pdf = PDF::loadView('invoices.product-payment-invoice', [
                'invoice' => $invoice,
                'user' => $user,
                'product' => $this->u_product,
                'plan' => $this->u_plan,
                'promo_value' => $promoValue,
                'from_date' => $this->u_product->purchased_on,
                'to_date' => $this->u_product->expired_on,
                'currency' => $currency
            ]);
            // $pdf->SetProtection(['copy', 'print'], '', 'pass');

            //Code Added for Cookie Consent Log Limit Start
            //Reset All cookie consent limit attributes
            if ($product_name == 'cookie_consent') {
                $res = curl_request('get',config('app.cmp_url').'/api/auth/reset-cookie-consent-limit?user_id='.auth()->user()->id,[]);
                // $domains = CbUsersDomains::Where('user_id', '=', auth()->user()->id)->get();
                // foreach ($domains as $domain) {
                //     $this->resetCookieConsentLimit($domain);
                // }
            }
            //Code Added for Cookie Consent Log Limit End

            /* Check the Defaulters Start */

            if($this->u_product->expired_on > Carbon::now()->format('Y-m-d H:i:s')){
                $defaulter_expired = Defaulter::where([
                    ['user_id','=',$user->id],
                    ['product_id','=',$this->product->id]
                ])->first();
                if($defaulter_expired){
                    $defaulter_expired->delete();
                }
            }

            /* Check the Defaulters End */

            event(new NewProductHasPurchasedEvent($this->product, $user, $pdf, $promoValue, $currency));

            return response([
                'message' => 'Your package is successfully upgrade! (PAID)',
                'paymentResponse' => $paymentResponse,
                'url' => route('thank-you-for-checkout', ['product' => str_replace("_", "-", $this->product->name)])
            ], 200);
        }

        return response([
            'user' => auth()->user()->email,
            'paymentResponse' => $paymentResponse
        ], 403);
    }

    private function insertEntries($isFree, $onTrial, $currency, $tenure, $plan_name, $members,$domain_limit = null)
    {
        $plan = null;
        $todayDate = Carbon::now();
        $gdprMembersPrice = 0;

        $previousMembers = 0;

        $duration = $tenure === 'yearly' ? 'year' : 'month';
        /* get Plan for GDPR Training Product */
        if ($members > 0) {
            $plans = $this->product->plans;
            foreach ($plans as $_plan) {
                $min = $max = $unitPrice = 0;
                $features = $_plan->features;
                foreach ($features as $feature) {
                    if ($feature->name === 'min_limit') $min = $feature->value;
                    if ($feature->name === 'max_limit') $max = $feature->value;
                    if ($feature->name === 'unit_price_of_user') $unitPrice = $feature->value;
                }

                if ($members >= $min && $members <= $max) {
                    $plan = $_plan;
                    if ($currency === 'USD')
                        $unitPrice *= config('app.USD_CONVERSION_RATE');
                    elseif ($currency === 'EUR')
                        $unitPrice *= config('app.EUR_CONVERSION_RATE');

                    $unitPrice = ($unitPrice - intval($unitPrice) > 0.5)
                        ? ceil($unitPrice)
                        : floor($unitPrice);

                    $gdprMembersPrice = $unitPrice * $members;
                    $plan->price = $gdprMembersPrice;
                    break;
                }
            }

            $purchased = auth()->user()->products()->where('name', '=', $this->product->name)->first();
            if ($purchased !== null) {
                $previousMembers = (int) $purchased->plan->features()->where('name', '=', 'no_of_employees')->first()->value;
                $members += $previousMembers;
            }
        } else {
            $plan = $this->product->plans()->where('name', '=', $plan_name)->first();
        }

        /* Subscribe other products */
        $u_product = auth()->user()->products()->where('name', '=', $this->product->name)->first();
        // dd($todayDate >= date("Y-m-d H:i:s", strtotime("-15 days", strtotime($u_product->expired_on))));
        // if ($u_product === null || $plan->name !== $u_product->plan->name || $todayDate >= date("Y-m-d H:i:s", strtotime("-15 days", strtotime($u_product->expired_on)))) {

            $features = $plan->features->sortBy('sort_order');

            if ($u_product === null) $u_product = new UProduct;

            $u_product->fill($this->product->toArray());
            $this->from_date = $u_product->expired_on !== null ? $u_product->expired_on : $todayDate;
            $u_product->recursive_status = $tenure;
            $u_product->purchased_on = $todayDate;
            $u_product->expired_on = $this->product->on_trial ? date("Y-m-d H:i:s", strtotime($this->product->trial_days . " days")) : date("Y-m-d H:i:s", strtotime("+1 " .  $duration, strtotime($u_product->purchased_on)));
            $u_product->upgraded_on = $u_product->purchased_on;
            $u_product->on_trial = $this->product->on_trial ? 1 : 0;
            $u_product->trial_days = $this->product->on_trial ? $this->product->trial_days : 0;
            $u_product->trial_expire_on = $this->product->on_trial ? date("Y-m-d H:i:s", strtotime($this->product->trial_days . " days")) : null;
            $u_product->discount = $this->product->discount;
            $u_product->currency = strtolower($currency);
            $u_product->user_id = auth()->id();

            if ($members === 0) {
                $this->calculatePriceWithoutConversion($tenure,$plan->price,$u_product->currency,$domain_limit);
                $u_product->price = $this->priceWithoutVatAndDiscountCC;
            } else {
                $u_product->price = $gdprMembersPrice;
            }
            //            $this->to_date = $u_product->expired_on;
            $u_product->save();

            /* Getting plan for the product */
            $u_plan = $u_product->plan;
            if ($u_plan === null) $u_plan = new UPlan;

            $u_plan->fill($plan->toArray());
            if ($u_product->name !== 'gdpr_training')
                // if ($tenure === 'yearly') $u_plan->price = intval($u_plan->price - (($u_plan->price * 25) / 100 )) + 0.99;
                $u_plan->price = $u_product->price;

            $u_plan->u_product_id = $u_product->id;
            $u_plan->purchased_on = $todayDate;
            $u_plan->expired_on = $this->product->on_trial ? date("Y-m-d H:i:s", strtotime($this->product->trial_days . " days")) : date("Y-m-d H:i:s", strtotime("+1 " . $duration, strtotime($u_product->purchased_on)));
            $u_plan->currency = strtolower($currency);
            $u_plan->save();
            $this->to_date = $u_plan->expired_on;
            $u_features = $u_plan->features->sortBy('sort_order');

            // if there are existing features of that that plan, remove them all first.
            $prev_features_ids = null;
            if (!is_null($u_features)) {
                $prev_features_ids = $u_features->pluck('id')->toArray();
            }
            $features_to_be_added = [];
            foreach ($features->toArray() as $feature) {
                unset($feature['id']);
                unset($feature['plan_id']);
                $feature['created_at'] = $todayDate;
                $feature['updated_at'] = $todayDate;
                $feature['u_plan_id']  = $u_plan->id;
                 if($domain_limit !== null && $feature['name'] === 'domain_limit'){
                    $feature['value'] = $domain_limit;
                }
                array_push($features_to_be_added, $feature);
            }

            $inserted = false;
            if (count($features_to_be_added) > 0) $inserted = UFeature::insert($features_to_be_added);
            if ($inserted && $prev_features_ids) UFeature::destroy($prev_features_ids);

            // if product is GDPR Staff Training, then one object of the features will be added extra.
            if ($members > 0) {
                $u_feature = new UFeature;
                $u_feature->name = 'no_of_employees';
                $u_feature->display_name = 'No. of Employees';
                $u_feature->value = $members;
                $u_feature->price = $gdprMembersPrice;
                $u_feature->description = 'You can add Maximum ' . $members . ' Users';
                $u_feature->is_visible = true;
                $u_feature->sort_order = 5;
                $u_feature->is_active = true;
                $u_feature->u_plan_id = $u_plan->id;
                $u_feature->save();
            }

            /* if DPIA product is being purchased */
            if ($u_product->name === 'dpia') {
                $stake_holder = DpiaStakeHolder::where(['user_id' => auth()->id(), 'created_by_id' => auth()->id()])->first();
                if ($stake_holder === null) {
                    $stake_holder = new DpiaStakeHolder;
                    $stake_holder->name = auth()->user()->name;
                    $stake_holder->contact_email = auth()->user()->email;
                    $stake_holder->user_type = 'owner';
                    $stake_holder->user_id = auth()->id();
                    $stake_holder->created_by_id = auth()->id();
                    $stake_holder->sort_order = 1;
                    $stake_holder->enabled = 1;
                    $stake_holder->save();
                }
            }

            if (!$isFree) $this->updateUserPaymentMethodDetails($u_product);

            $action = $isFree ? 'Product Subscribed (FREE)' : ($onTrial ? 'Product Subscribed (On Trial)' : 'Product Purchased');
            $currency_sign = $currency === 'GBP' ? '£' : ($currency === 'USD' ? '$' : ($currency === 'BRL' ? 'R$' : '€'));

            $status = 'info';
            if ($isFree) {
                $message = auth()->user()->email . ' has subscribed ' . $u_product->display_name . ' product with ' . $u_plan->display_name . ' plan.';
            } elseif ($onTrial) {
                $message = auth()->user()->email . ' has subscribed ' . $u_product->display_name . ' product with ' . $u_plan->display_name . ' plan on: ' . $u_product->trial_days . ' days trial which will expire on: ' . $u_plan->price->expired_on . ' having worth of ' . $currency_sign . ' ' . $u_plan->price . ' only.';
            } else {
                $message = auth()->user()->email . ' has purchased ' . $u_product->display_name . ' product with ' . $u_plan->display_name . ' plan which will expire on: ' . $u_plan->expired_on . ' & paid ' . $currency_sign . ' ' . $u_product->price . ' only.';
                $status = 'success';
            }

            /* Saving log */
            ActivityLog::add($action, $message, $status, $u_product->name);

            $this->u_product = $u_product;
            $this->u_plan = $u_plan;
            $this->features = $features_to_be_added;
        // } else {
        //     $this->u_product = $u_product;
        //     $this->u_plan = $u_product->plan;
        // }

        /*
         * 1 : for making new user in dsar database when user purchases SRM
         * 2 : for updating plan in dsar database for SRM
         * 3 : Direct Apis are hit for new entry and database is used to update and make new company
         */

        if($this->product->name == 'subject_request_management'){
            if($this->email && $this->source == "srm"){
                $user = $this->user;
                $dsarid = DB::connection('dsarsql')->select('select * from dsar_users where seers_user_id = :id ',['id' => $this->user->id]);
            } else{
                $user = auth()->user();
                $dsarid = DB::connection('dsarsql')->select('select * from dsar_users where seers_user_id = :id ',['id' => $user->id]);
            }
            $url = Config::get('app.dsarurl');
            $loginUrl = $url.'/api/auth/login';
            $CreateUrl = $url.'/api/auth/create_user';
            $CompanyUrl = $url.'/api/auth/add_company';
            $CompanyeditUrl = $url.'/api/auth/update_company';
            // $dsarid = DB::connection('dsarsql')->select('select * from dsar_users where seers_user_id = :id ',['id' => $this->user->id]);
            if(count($dsarid) == 0){
                // $newuser = DB::connection('dsarsql')->table('dsar_users')->insertGetId(['name'=> $user->name,'email'=>$user->email,'seers_user_id'=>$user->id,]);

                // Request for generating bearer token

                $options = [
                    "multipart" => [
                        [
                            'name'     => 'email',
                            'contents' => 'jhondoe@gmail.com',
                        ],
                        [
                            'name'     => 'password',
                            'contents' => 'jhondoe@gmail.com-seers',
                        ],
                        [
                            'name'     => 'verified',
                            'contents' => true,
                        ],
                    ],
                    'verify' => false
                ];
                $client = new Client();
                $response = $client->request('POST', $loginUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);
                // request for creating new user in dsar

                $bearertoken = $result->token_type . ' ' . $result->access_token;
                $options = [
                    "multipart" => [
                        [
                            'name'     => 'email',
                            'contents' => $user->email,
                        ],
                        [
                            'name'     => 'seers_user_id',
                            'contents' => $user->id,
                        ],
                        [
                            'name'     => 'name',
                            'contents' => $user->name,
                        ],
                        [
                            'name'     => 'role',
                            'contents' => 'admin',
                        ],
                    ],
                    'verify' => false,
                    'headers' => [
                        'Authorization' => $bearertoken
                    ]
                ];
                $client = new Client();
                $response = $client->request('POST', $CreateUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);
                if($this->features == null){
                    $Dsarfeature = $this->u_plan['features'];
                } else {
                    $Dsarfeature = $this->features;
                }

                $product_info = array(
                    'product' => $this->u_product,
                    'plan' => $this->u_plan,
                    'feature' => $Dsarfeature,
                );
                // new add company
                $product_json = json_encode($product_info); //$u_product;
                if ($user->company != null) {
                    $company_name = $user->company;
                } else {
                    $company_name = $user->fname;
                }
                $options = [
                    "multipart" => [
                        [
                            'name'     => 'name',
                            'contents' => $company_name,
                        ],
                        [
                            'name'     => 'email',
                            'contents' => $user->email,
                        ],
                        [
                            'name'     => 'owner',
                            'contents' => $result->user_id,
                        ],
                        [
                            'name'     => 'address',
                            'contents' => $user->address,
                        ],
                        [
                            'name'     => 'product_info',
                            'contents' => $product_json,
                        ],
                    ],
                    'verify' => false,
                    'headers' => [
                        'Authorization' => $bearertoken
                    ]
                ];
                $client = new Client();
                $response = $client->request('POST', $CompanyUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);
                //end add company
                // $product_json = json_encode($product_info); //$u_product;
                // $newcompany = DB::connection('dsarsql')->table('companies')->insertGetId(['name'=> $user->company,'email'=>$user->email,'owner'=>$result->user_id,'address'=>$user->address,'product_info'=>$product_json]);
                // $insertid = DB::connection('dsarsql')->table('dsar_users')->where('id','=',$result->user_id)->update(['company_id'=> $newcompany]);
            } else {
                // $product_info = array(
                //     'product'=> $this->u_product,
                //     'plan'=> $this->u_plan,
                //     'feature'=> $this->features,
                // );
                // $product_json = json_encode($product_info); //$u_product;
                // $newcompany = DB::connection('dsarsql')->table('companies')->where('owner','=',$dsarid[0]->id)->update(['name'=> $user->company,'email'=>$user->email,'address'=>$user->address,'product_info'=>$product_json]);
                $options = [
                    "multipart" => [
                        [
                            'name'     => 'email',
                            'contents' => 'jhondoe@gmail.com',
                        ],
                        [
                            'name'     => 'password',
                            'contents' => 'jhondoe@gmail.com-seers',
                        ],
                        [
                            'name'     => 'verified',
                            'contents' => true,
                        ],
                    ],
                    'verify' => false
                ];
                $client = new Client();
                $response = $client->request('POST', $loginUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);
                // request for creating new user in dsar
                $bearertoken = $result->token_type . ' ' . $result->access_token;
                if ($this->features == null) {
                    $Dsarfeature = $this->u_plan['features'];
                } else {
                    $Dsarfeature = $this->features;
                }
                $product_info = array(
                    'product' => $this->u_product,
                    'plan' => $this->u_plan,
                    'feature' => $Dsarfeature,
                );
                // new add company
                $product_json = json_encode($product_info); //$u_product;
                if ($user->company != null) {
                    $company_name = $user->company;
                } else {
                    $company_name = $user->fname;
                }
                $options = [
                    "multipart" => [
                        [
                            'name'     => 'name',
                            'contents' => $company_name,
                        ],
                        [
                            'name'     => 'email',
                            'contents' => $user->email,
                        ],
                        [
                            'name'     => 'owner',
                            'contents' => $dsarid[0]->id,
                        ],
                        [
                            'name'     => 'address',
                            'contents' => $user->address,
                        ],
                        [
                            'name'     => 'product_info',
                            'contents' => $product_json,
                        ],
                    ],
                    'verify' => false,
                    'headers' => [
                        'Authorization' => $bearertoken
                    ]
                ];
                $client = new Client();
                $response = $client->request('POST', $CompanyeditUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);
            }
        }

        if($this->product->name == 'breach_management_system'){
            if($this->email && $this->source == "bms"){
                $user = $this->user;
                $bmsid = DB::connection('bmssql')->select('select * from users where seers_user_id = :id ',['id' => $this->user->id]);
            } else{
                $user = auth()->user();
                $bmsid = DB::connection('bmssql')->select('select * from users where seers_user_id = :id ',['id' => $user->id]);
            }
            $url = Config::get('app.bmsapiurl');
            $loginUrl = $url.'/api/auth/login';
            $CreateUrl = $url.'/api/auth/create_user';
            $CompanyUrl = $url.'/api/auth/add_company';
            $CompanyeditUrl = $url.'/api/auth/update_company';
            if(count($bmsid) == 0){

                // Request for generating bearer token

                $options = [
                    "multipart" => [
                        [
                            'name'     => 'email',
                            'contents' => 'jhondoe@gmail.com',
                        ],
                        [
                            'name'     => 'password',
                            'contents' => 'jhondoe@gmail.com-seers',
                        ],
                        [
                            'name'     => 'verified',
                            'contents' => true,
                        ],
                    ],
                    'verify' => false
                ];
                $client = new Client();
                $response = $client->request('POST', $loginUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);

                // request for creating new user in dsar

                $bearertoken = $result->token_type.' '.$result->access_token;
                $options = [
                    "multipart" => [
                        [
                            'name'     => 'email',
                            'contents' => $user->email,
                        ],
                        [
                            'name'     => 'seers_user_id',
                            'contents' => $user->id,
                        ],
                        [
                            'name'     => 'name',
                            'contents' => $user->name,
                        ],
                        [
                            'name'     => 'role',
                            'contents' => 'admin',
                        ],
                    ],
                    'verify' => false,
                    'headers' => [
                        'Authorization' => $bearertoken
                    ]
                ];
                $client = new Client();
                $response = $client->request('POST', $CreateUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);
                if($this->features == null){
                    $Bmsfeature = $this->u_plan['features'];
                }else{
                    $Bmsfeature = $this->features;
                }

                $product_info = array(
                    'product'=> $this->u_product,
                    'plan'=> $this->u_plan,
                    'feature'=> $Bmsfeature,
                );
                // new add company
                $product_json = json_encode($product_info); //$u_product;
                if($user->company != null){
                    $company_name = $user->company;
                } else {
                    $company_name = $user->fname;
                }
                $options = [
                    "multipart" => [
                        [
                            'name'     => 'name',
                            'contents' => $company_name,
                        ],
                        [
                            'name'     => 'email',
                            'contents' => $user->email,
                        ],
                        [
                            'name'     => 'owner',
                            'contents' => $result->user_id,
                        ],
                        [
                            'name'     => 'address',
                            'contents' => $user->address,
                        ],
                        [
                            'name'     => 'product_info',
                            'contents' => $product_json,
                        ],
                    ],
                    'verify' => false,
                    'headers' => [
                        'Authorization' => $bearertoken
                    ]
                ];
                $client = new Client();
                $response = $client->request('POST', $CompanyUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);
            }else{
                $options = [
                    "multipart" => [
                        [
                            'name'     => 'email',
                            'contents' => 'jhondoe@gmail.com',
                        ],
                        [
                            'name'     => 'password',
                            'contents' => 'jhondoe@gmail.com-seers',
                        ],
                        [
                            'name'     => 'verified',
                            'contents' => true,
                        ],
                    ],
                    'verify' => false
                ];
                $client = new Client();
                $response = $client->request('POST', $loginUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);
                // request for creating new user in dsar
                $bearertoken = $result->token_type.' '.$result->access_token;
                if($this->features == null){
                    $Bmsfeature = $this->u_plan['features'];
                }else{
                    $Bmsfeature = $this->features;
                }
                $product_info = array(
                    'product'=> $this->u_product,
                    'plan'=> $this->u_plan,
                    'feature'=> $Bmsfeature,
                );
                // new add company
                $product_json = json_encode($product_info); //$u_product;
                if ($user->company != null) {
                    $company_name = $user->company;
                } else {
                    $company_name = $user->fname;
                }
                $options = [
                    "multipart" => [
                        [
                            'name'     => 'name',
                            'contents' => $company_name,
                        ],
                        [
                            'name'     => 'email',
                            'contents' => $user->email,
                        ],
                        [
                            'name'     => 'owner',
                            'contents' => $bmsid[0]->id,
                        ],
                        [
                            'name'     => 'address',
                            'contents' => $user->address,
                        ],
                        [
                            'name'     => 'product_info',
                            'contents' => $product_json,
                        ],
                    ],
                    'verify' => false,
                    'headers' => [
                        'Authorization' => $bearertoken
                    ]
                ];
                $client = new Client();
                $response = $client->request('POST', $CompanyeditUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);
            }
        }


        if ($this->product->name == 'breach_management_system') {
            if ($this->email && $this->source == "bms") {
                $user = $this->user;
                $bmsid = DB::connection('bmssql')->select('select * from users where seers_user_id = :id ', ['id' => $this->user->id]);
            } else {
                $user = auth()->user();
                $bmsid = DB::connection('bmssql')->select('select * from users where seers_user_id = :id ', ['id' => $user->id]);
            }
            $url = Config::get('app.bmsapiurl');
            $loginUrl = $url . '/api/auth/login';
            $CreateUrl = $url . '/api/auth/create_user';
            $CompanyUrl = $url . '/api/auth/add_company';
            $CompanyeditUrl = $url . '/api/auth/update_company';
            if (count($bmsid) == 0) {

                // Request for generating bearer token

                $options = [
                    "multipart" => [
                        [
                            'name'     => 'email',
                            'contents' => 'jhondoe@gmail.com',
                        ],
                        [
                            'name'     => 'password',
                            'contents' => 'jhondoe@gmail.com-seers',
                        ],
                        [
                            'name'     => 'verified',
                            'contents' => true,
                        ],
                    ],
                    'verify' => false
                ];
                $client = new Client();
                $response = $client->request('POST', $loginUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);

                // request for creating new user in dsar

                $bearertoken = $result->token_type . ' ' . $result->access_token;
                $options = [
                    "multipart" => [
                        [
                            'name'     => 'email',
                            'contents' => $user->email,
                        ],
                        [
                            'name'     => 'seers_user_id',
                            'contents' => $user->id,
                        ],
                        [
                            'name'     => 'name',
                            'contents' => $user->name,
                        ],
                        [
                            'name'     => 'role',
                            'contents' => 'admin',
                        ],
                    ],
                    'verify' => false,
                    'headers' => [
                        'Authorization' => $bearertoken
                    ]
                ];
                $client = new Client();
                $response = $client->request('POST', $CreateUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);
                if ($this->features == null) {
                    $Bmsfeature = $this->u_plan['features'];
                } else {
                    $Bmsfeature = $this->features;
                }

                $product_info = array(
                    'product' => $this->u_product,
                    'plan' => $this->u_plan,
                    'feature' => $Bmsfeature,
                );
                // new add company
                $product_json = json_encode($product_info); //$u_product;
                if ($user->company != null) {
                    $company_name = $user->company;
                } else {
                    $company_name = $user->fname;
                }
                $options = [
                    "multipart" => [
                        [
                            'name'     => 'name',
                            'contents' => $company_name,
                        ],
                        [
                            'name'     => 'email',
                            'contents' => $user->email,
                        ],
                        [
                            'name'     => 'owner',
                            'contents' => $result->user_id,
                        ],
                        [
                            'name'     => 'address',
                            'contents' => $user->address,
                        ],
                        [
                            'name'     => 'product_info',
                            'contents' => $product_json,
                        ],
                    ],
                    'verify' => false,
                    'headers' => [
                        'Authorization' => $bearertoken
                    ]
                ];
                $client = new Client();
                $response = $client->request('POST', $CompanyUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);
            } else {
                $options = [
                    "multipart" => [
                        [
                            'name'     => 'email',
                            'contents' => 'jhondoe@gmail.com',
                        ],
                        [
                            'name'     => 'password',
                            'contents' => 'jhondoe@gmail.com-seers',
                        ],
                        [
                            'name'     => 'verified',
                            'contents' => true,
                        ],
                    ],
                    'verify' => false
                ];
                $client = new Client();
                $response = $client->request('POST', $loginUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);
                // request for creating new user in dsar
                $bearertoken = $result->token_type . ' ' . $result->access_token;
                if ($this->features == null) {
                    $Bmsfeature = $this->u_plan['features'];
                } else {
                    $Bmsfeature = $this->features;
                }
                $product_info = array(
                    'product' => $this->u_product,
                    'plan' => $this->u_plan,
                    'feature' => $Bmsfeature,
                );
                // new add company
                $product_json = json_encode($product_info); //$u_product;
                if ($user->company != null) {
                    $company_name = $user->company;
                } else {
                    $company_name = $user->fname;
                }
                $options = [
                    "multipart" => [
                        [
                            'name'     => 'name',
                            'contents' => $company_name,
                        ],
                        [
                            'name'     => 'email',
                            'contents' => $user->email,
                        ],
                        [
                            'name'     => 'owner',
                            'contents' => $bmsid[0]->id,
                        ],
                        [
                            'name'     => 'address',
                            'contents' => $user->address,
                        ],
                        [
                            'name'     => 'product_info',
                            'contents' => $product_json,
                        ],
                    ],
                    'verify' => false,
                    'headers' => [
                        'Authorization' => $bearertoken
                    ]
                ];
                $client = new Client();
                $response = $client->request('POST', $CompanyeditUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);
            }
        }
    }

    private function insertThreeDEntries ($isFree, $onTrial, $currency, $tenure, $plan_name, $members,$domain_limit = null) {


        $plan = null;
        $todayDate = Carbon::now();
        $gdprMembersPrice = 0;

        $previousMembers = 0;
        $email = $this->email;
        $duration = $tenure === 'yearly' ? 'year' : 'month';
        $this->user = User::where('email','=',$email)->first();
        /* get Plan for GDPR Training Product */
        if ($members > 0) {
            $plans = $this->product->plans;

            foreach ($plans as $_plan) {
                $min = $max = $unitPrice = 0;
                $features = $_plan->features;
                foreach ($features as $feature) {
                    if ($feature->name === 'min_limit') $min = $feature->value;
                    if ($feature->name === 'max_limit') $max = $feature->value;
                    if ($feature->name === 'unit_price_of_user') $unitPrice = $feature->value;
                }

                if ($members >= $min && $members <= $max) {
                    $plan = $_plan;
                    if ($currency === 'USD')
                        $unitPrice *= config('app.USD_CONVERSION_RATE');
                    elseif ($currency === 'EUR')
                        $unitPrice *= config('app.EUR_CONVERSION_RATE');

                    $unitPrice = ($unitPrice - intval($unitPrice) > 0.5)
                        ? ceil($unitPrice)
                        : floor($unitPrice);

                    $gdprMembersPrice = $unitPrice * $members;
                    $plan->price = $gdprMembersPrice;
                    break;
                }
            }

            $purchased = $this->user->products()->where('name', '=', $this->product->name)->first();
            if ($purchased !== null) {
                $previousMembers = (int) $purchased->plan->features()->where('name', '=', 'no_of_employees')->first()->value;
                $members += $previousMembers;
            }
        } else {
            $plan = $this->product->plans()->where('name', '=', $plan_name)->first();
        }

        /* Subscribe other products */
        // $u_product = auth()->user()->products()->where('name', '=', $this->product->name)->first();
        if($this->user){
            // $u_product = UProduct::where('user_id','=',$this->user['id'])->where('name','=', $this->product->name)->first();
            $u_product = $this->user->products()->where('name','=', $this->product->name)->first();
            if($u_product){
                $this->u_plan =  $u_product->plan;
                // $this->u_plan =  UPlan::where('u_product_id','=',$u_product['id'])->first();

            }
        }

        // if ($u_product === null || $plan->name !== $u_product->plan->name || $todayDate >= date("Y-m-d H:i:s", strtotime("-15 days" , strtotime($u_product->expired_on)))) {

            $features = $plan->features->sortBy('sort_order');

            if ($u_product === null) $u_product = new UProduct;

            $u_product->fill($this->product->toArray());
            $this->from_date = $u_product->expired_on !== null ? $u_product->expired_on : $todayDate;
            $u_product->recursive_status = $tenure;
            $u_product->purchased_on = $todayDate;
            $u_product->expired_on = $this->product->on_trial ? date("Y-m-d H:i:s", strtotime($this->product->trial_days . " days")) : date("Y-m-d H:i:s", strtotime("+1 " .  $duration, strtotime($u_product->purchased_on)));
            $u_product->upgraded_on = $u_product->purchased_on;
            $u_product->on_trial = $this->product->on_trial ? 1 : 0;
            $u_product->trial_days = $this->product->on_trial ? $this->product->trial_days : 0;
            $u_product->trial_expire_on = $this->product->on_trial ? date("Y-m-d H:i:s", strtotime($this->product->trial_days . " days")) : null;
            $u_product->discount = $this->product->discount;
            $u_product->currency = strtolower($currency);
            $u_product->user_id = $this->user['id'];

            if ($members === 0) {
                // $u_product->price = $tenure === 'yearly'
                //     ? number_format((($plan->price / 1.25) * 12), 2, '.', '')
                //     : $plan->price;
                    $u_product->price = $this->calculatePriceWithoutConversion($tenure,$plan->price,$u_product->currency,$domain_limit);
            } else {
                $u_product->price = $gdprMembersPrice;
            }
//            $this->to_date = $u_product->expired_on;
            $u_product->save();

            /* Getting plan for the product */
            $u_plan = $u_product->plan;
            if ($u_plan === null) $u_plan = new UPlan;

            $u_plan->fill($plan->toArray());
            if ($u_product->name !== 'gdpr_training')
                if ($tenure === 'yearly') $u_plan->price = intval($u_plan->price - (($u_plan->price * 25) / 100 )) + 0.99;

            $u_plan->u_product_id = $u_product->id;
            $u_plan->purchased_on = $todayDate;
            $u_plan->expired_on = $this->product->on_trial ? date("Y-m-d H:i:s", strtotime($this->product->trial_days . " days")) : date("Y-m-d H:i:s", strtotime("+1 " . $duration, strtotime($u_product->purchased_on)));
            $u_plan->currency = strtolower($currency);
            $u_plan->save();
            $this->to_date = $u_plan->expired_on;
            $u_features = $u_plan->features->sortBy('sort_order');

            // if there are existing features of that that plan, remove them all first.
            $prev_features_ids = null;
            if (!is_null($u_features)) {
                $prev_features_ids = $u_features->pluck('id')->toArray();
            }
            $features_to_be_added = [];
            foreach ($features->toArray() as $feature) {
                unset($feature['id']);
                unset($feature['plan_id']);
                $feature['created_at'] = $todayDate;
                $feature['updated_at'] = $todayDate;
                $feature['u_plan_id']  = $u_plan->id;
                 if($domain_limit !== null && $feature['name'] === 'domain_limit'){
                    $feature['value'] = $domain_limit;
                }
                array_push($features_to_be_added, $feature);
            }

            $inserted = false;
            if (count($features_to_be_added) > 0) $inserted = UFeature::insert($features_to_be_added);
            if ($inserted && $prev_features_ids) UFeature::destroy($prev_features_ids);

            // if product is GDPR Staff Training, then one object of the features will be added extra.
            if ($members > 0) {
                $u_feature = new UFeature;
                $u_feature->name = 'no_of_employees';
                $u_feature->display_name = 'No. of Employees';
                $u_feature->value = $members;
                $u_feature->price = $gdprMembersPrice;
                $u_feature->description = 'You can add Maximum '. $members .' Users';
                $u_feature->is_visible = true;
                $u_feature->sort_order = 5;
                $u_feature->is_active = true;
                $u_feature->u_plan_id = $u_plan->id;
                $u_feature->save();
            }

            /* if DPIA product is being purchased */
            if ($u_product->name === 'dpia') {
                $stake_holder = DpiaStakeHolder::where(['user_id' => $this->user['id'], 'created_by_id' => $this->user['id']])->first();
                if ($stake_holder === null) {
                    $stake_holder = new DpiaStakeHolder;
                    $stake_holder->name = $this->user['name'];
                    $stake_holder->contact_email = $this->user['email'];
                    $stake_holder->user_type = 'owner';
                    $stake_holder->user_id = $this->user['id'];
                    $stake_holder->created_by_id = $this->user['id'];
                    $stake_holder->sort_order = 1;
                    $stake_holder->enabled = 1;
                    $stake_holder->save();
                }
            }

            if (!$isFree) $this->updateUserPaymentMethodDetails($u_product);

            $action = $isFree ? 'Product Subscribed (FREE)' : ($onTrial ? 'Product Subscribed (On Trial)' : 'Product Purchased');
            $currency_sign = $currency === 'GBP' ? '£' : ($currency === 'USD' ? '$' : ($currency === 'BRL' ? 'R$' :'€'));

            $status = 'info';
            if ($isFree) {
                $message = $this->user['email'] . ' has subscribed ' . $u_product->display_name . ' product with ' . $u_plan->display_name . ' plan.';
            } elseif ($onTrial) {
                $message = $this->user['email'] . ' has subscribed ' . $u_product->display_name . ' product with ' . $u_plan->display_name . ' plan on: ' . $u_product->trial_days . ' days trial which will expire on: ' . $u_plan->price->expired_on . ' having worth of '. $currency_sign . ' '. $u_plan->price . ' only.';
            } else {
                $message = $this->user['email'] . ' has purchased ' . $u_product->display_name . ' product with ' . $u_plan->display_name . ' plan which will expire on: ' . $u_plan->expired_on . ' & paid '. $currency_sign . ' '. $u_product->price . ' only.';
                $status = 'success';
            }

            /* Saving log */
            ActivityLog::add($action, $message, $status, $u_product->name);

            $this->u_product = $u_product;
            $this->u_plan = $u_plan;
            $this->features = $features_to_be_added;
        // } else {
        //     $this->u_product = $u_product;
        //     $this->u_plan = $u_product->plan;
        // }

        /*
         * 1 : for making new user in dsar database when user purchases SRM
         * 2 : for updating plan in dsar database for SRM
         * 3 : Direct Apis are hit for new entry and database is used to update and make new company
         */

        if($this->product->name == 'subject_request_management'){
            if($this->email && $this->source == "srm"){
                $user = $this->user;
                $dsarid = DB::connection('dsarsql')->select('select * from dsar_users where seers_user_id = :id ',['id' => $this->user->id]);
            } else{
                $user = auth()->user();
                $dsarid = DB::connection('dsarsql')->select('select * from dsar_users where seers_user_id = :id ',['id' => $user->id]);
            }
            $url = Config::get('app.dsarurl');
            $loginUrl = $url.'/api/auth/login';
            $CreateUrl = $url.'/api/auth/create_user';
            $CompanyUrl = $url.'/api/auth/add_company';
            $CompanyeditUrl = $url.'/api/auth/update_company';
            // $dsarid = DB::connection('dsarsql')->select('select * from dsar_users where seers_user_id = :id ',['id' => $this->user->id]);
            if(count($dsarid) == 0){
                // $newuser = DB::connection('dsarsql')->table('dsar_users')->insertGetId(['name'=> $user->name,'email'=>$user->email,'seers_user_id'=>$user->id,]);

                // Request for generating bearer token

                $options = [
                    "multipart" => [
                        [
                            'name'     => 'email',
                            'contents' => 'jhondoe@gmail.com',
                        ],
                        [
                            'name'     => 'password',
                            'contents' => 'jhondoe@gmail.com-seers',
                        ],
                        [
                            'name'     => 'verified',
                            'contents' => true,
                        ],
                    ],
                    'verify' => false
                ];
                $client = new Client();
                $response = $client->request('POST', $loginUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);
                // request for creating new user in dsar

                $bearertoken = $result->token_type.' '.$result->access_token;
                $options = [
                    "multipart" => [
                        [
                            'name'     => 'email',
                            'contents' => $user->email,
                        ],
                        [
                            'name'     => 'seers_user_id',
                            'contents' => $user->id,
                        ],
                        [
                            'name'     => 'name',
                            'contents' => $user->name,
                        ],
                        [
                            'name'     => 'role',
                            'contents' => 'admin',
                        ],
                    ],
                    'verify' => false,
                    'headers' => [
                        'Authorization' => $bearertoken]
                ];
                $client = new Client();
                $response = $client->request('POST', $CreateUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);
                if($this->features == null){
                    $Dsarfeature = $this->u_plan['features'];
                }else{
                    $Dsarfeature = $this->features;
                }

                $product_info = array(
                    'product'=> $this->u_product,
                    'plan'=> $this->u_plan,
                    'feature'=> $Dsarfeature,
                );
                // new add company
                $product_json = json_encode($product_info); //$u_product;
                if($user->company != null){
                    $company_name = $user->company;
                }else{
                    $company_name = $user->fname;
                }
                $options = [
                    "multipart" => [
                        [
                            'name'     => 'name',
                            'contents' => $company_name,
                        ],
                        [
                            'name'     => 'email',
                            'contents' => $user->email,
                        ],
                        [
                            'name'     => 'owner',
                            'contents' => $result->user_id,
                        ],
                        [
                            'name'     => 'address',
                            'contents' => $user->address,
                        ],
                        [
                            'name'     => 'product_info',
                            'contents' => $product_json,
                        ],
                    ],
                    'verify' => false,
                    'headers' => [
                        'Authorization' => $bearertoken]
                ];
                $client = new Client();
                $response = $client->request('POST', $CompanyUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);
                //end add company
                // $product_json = json_encode($product_info); //$u_product;
                // $newcompany = DB::connection('dsarsql')->table('companies')->insertGetId(['name'=> $user->company,'email'=>$user->email,'owner'=>$result->user_id,'address'=>$user->address,'product_info'=>$product_json]);
                // $insertid = DB::connection('dsarsql')->table('dsar_users')->where('id','=',$result->user_id)->update(['company_id'=> $newcompany]);
            }else{
                // $product_info = array(
                //     'product'=> $this->u_product,
                //     'plan'=> $this->u_plan,
                //     'feature'=> $this->features,
                // );
                // $product_json = json_encode($product_info); //$u_product;
                // $newcompany = DB::connection('dsarsql')->table('companies')->where('owner','=',$dsarid[0]->id)->update(['name'=> $user->company,'email'=>$user->email,'address'=>$user->address,'product_info'=>$product_json]);
                $options = [
                    "multipart" => [
                        [
                            'name'     => 'email',
                            'contents' => 'jhondoe@gmail.com',
                        ],
                        [
                            'name'     => 'password',
                            'contents' => 'jhondoe@gmail.com-seers',
                        ],
                        [
                            'name'     => 'verified',
                            'contents' => true,
                        ],
                    ],
                    'verify' => false
                ];
                $client = new Client();
                $response = $client->request('POST', $loginUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);
                // request for creating new user in dsar
                $bearertoken = $result->token_type.' '.$result->access_token;
                if($this->features == null){
                    $Dsarfeature = $this->u_plan['features'];
                }else{
                    $Dsarfeature = $this->features;
                }
                $product_info = array(
                    'product'=> $this->u_product,
                    'plan'=> $this->u_plan,
                    'feature'=> $Dsarfeature,
                );
                // new add company
                $product_json = json_encode($product_info); //$u_product;
                if($user->company != null){
                    $company_name = $user->company;
                }else{
                    $company_name = $user->fname;
                }
                $options = [
                    "multipart" => [
                        [
                            'name'     => 'name',
                            'contents' => $company_name,
                        ],
                        [
                            'name'     => 'email',
                            'contents' => $user->email,
                        ],
                        [
                            'name'     => 'owner',
                            'contents' => $dsarid[0]->id,
                        ],
                        [
                            'name'     => 'address',
                            'contents' => $user->address,
                        ],
                        [
                            'name'     => 'product_info',
                            'contents' => $product_json,
                        ],
                    ],
                    'verify' => false,
                    'headers' => [
                        'Authorization' => $bearertoken]
                ];
                $client = new Client();
                $response = $client->request('POST', $CompanyeditUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);
            }
        }

        if($this->product->name == 'breach_management_system'){
            if($this->email && $this->source == "bms"){
                $user = $this->user;
                $bmsid = DB::connection('bmssql')->select('select * from users where seers_user_id = :id ',['id' => $this->user->id]);
            } else{
                $user = auth()->user();
                $bmsid = DB::connection('bmssql')->select('select * from users where seers_user_id = :id ',['id' => $user->id]);
            }
            $url = Config::get('app.bmsapiurl');
            $loginUrl = $url.'/api/auth/login';
            $CreateUrl = $url.'/api/auth/create_user';
            $CompanyUrl = $url.'/api/auth/add_company';
            $CompanyeditUrl = $url.'/api/auth/update_company';
            if(count($bmsid) == 0){

                // Request for generating bearer token

                $options = [
                    "multipart" => [
                        [
                            'name'     => 'email',
                            'contents' => 'jhondoe@gmail.com',
                        ],
                        [
                            'name'     => 'password',
                            'contents' => 'jhondoe@gmail.com-seers',
                        ],
                        [
                            'name'     => 'verified',
                            'contents' => true,
                        ],
                    ],
                    'verify' => false
                ];
                $client = new Client();
                $response = $client->request('POST', $loginUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);

                // request for creating new user in dsar

                $bearertoken = $result->token_type.' '.$result->access_token;
                $options = [
                    "multipart" => [
                        [
                            'name'     => 'email',
                            'contents' => $user->email,
                        ],
                        [
                            'name'     => 'seers_user_id',
                            'contents' => $user->id,
                        ],
                        [
                            'name'     => 'name',
                            'contents' => $user->name,
                        ],
                        [
                            'name'     => 'role',
                            'contents' => 'admin',
                        ],
                    ],
                    'verify' => false,
                    'headers' => [
                        'Authorization' => $bearertoken]
                ];
                $client = new Client();
                $response = $client->request('POST', $CreateUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);
                if($this->features == null){
                    $Bmsfeature = $this->u_plan['features'];
                }else{
                    $Bmsfeature = $this->features;
                }

                $product_info = array(
                    'product'=> $this->u_product,
                    'plan'=> $this->u_plan,
                    'feature'=> $Bmsfeature,
                );
                // new add company
                $product_json = json_encode($product_info); //$u_product;
                if($user->company != null){
                    $company_name = $user->company;
                }else{
                    $company_name = $user->fname;
                }
                $options = [
                    "multipart" => [
                        [
                            'name'     => 'name',
                            'contents' => $company_name,
                        ],
                        [
                            'name'     => 'email',
                            'contents' => $user->email,
                        ],
                        [
                            'name'     => 'owner',
                            'contents' => $result->user_id,
                        ],
                        [
                            'name'     => 'address',
                            'contents' => $user->address,
                        ],
                        [
                            'name'     => 'product_info',
                            'contents' => $product_json,
                        ],
                    ],
                    'verify' => false,
                    'headers' => [
                        'Authorization' => $bearertoken]
                ];
                $client = new Client();
                $response = $client->request('POST', $CompanyUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);
            }else{
                $options = [
                    "multipart" => [
                        [
                            'name'     => 'email',
                            'contents' => 'jhondoe@gmail.com',
                        ],
                        [
                            'name'     => 'password',
                            'contents' => 'jhondoe@gmail.com-seers',
                        ],
                        [
                            'name'     => 'verified',
                            'contents' => true,
                        ],
                    ],
                    'verify' => false
                ];
                $client = new Client();
                $response = $client->request('POST', $loginUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);
                // request for creating new user in dsar
                $bearertoken = $result->token_type.' '.$result->access_token;
                if($this->features == null){
                    $Bmsfeature = $this->u_plan['features'];
                }else{
                    $Bmsfeature = $this->features;
                }
                $product_info = array(
                    'product'=> $this->u_product,
                    'plan'=> $this->u_plan,
                    'feature'=> $Bmsfeature,
                );
                // new add company
                $product_json = json_encode($product_info); //$u_product;
                if($user->company != null){
                    $company_name = $user->company;
                }else{
                    $company_name = $user->fname;
                }
                $options = [
                    "multipart" => [
                        [
                            'name'     => 'name',
                            'contents' => $company_name,
                        ],
                        [
                            'name'     => 'email',
                            'contents' => $user->email,
                        ],
                        [
                            'name'     => 'owner',
                            'contents' => $bmsid[0]->id,
                        ],
                        [
                            'name'     => 'address',
                            'contents' => $user->address,
                        ],
                        [
                            'name'     => 'product_info',
                            'contents' => $product_json,
                        ],
                    ],
                    'verify' => false,
                    'headers' => [
                        'Authorization' => $bearertoken]
                ];
                $client = new Client();
                $response = $client->request('POST', $CompanyeditUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);
            }
        }

    }

    private function insertSrmEntries ($isFree, $onTrial, $currency, $tenure, $plan_name, $members) {

        $plan = null;
        $todayDate = Carbon::now();
        $plan = $this->product->plans()->where('name', '=', $plan_name)->first();

        $email = $this->email;
        $duration = $tenure === 'yearly' ? 'year' : 'month';
        $this->user = User::where('email','=',$email)->first();

        if($this->user){
            // $u_product = UProduct::where('user_id','=',$this->user['id'])->where('name','=', $this->product->name)->first();
            $u_product = $this->user->products()->where('name','=', $this->product->name)->first();
            if($u_product){
                $this->u_plan =  $u_product->plan;
                // $this->u_plan =  UPlan::where('u_product_id','=',$u_product['id'])->first();

            }
        }

        // if($u_product === null || $this->u_plan->name !== $plan_name){

            $features = $plan->features->sortBy('sort_order');


            if ($u_product === null) $u_product = new UProduct;


            $this->from_date = $u_product->expired_on ? $u_product->expired_on : $todayDate;
            $u_product->fill($this->product->toArray());
            $u_product->recursive_status = $tenure;
            $u_product->purchased_on = $todayDate;
            $u_product->expired_on = $this->product->on_trial ? date("Y-m-d H:m:s", strtotime($this->product->trial_days . " days")) : date("Y-m-d H:i:s", strtotime("+1 " .  $duration, strtotime($u_product->purchased_on)));
            $u_product->upgraded_on = $u_product->purchased_on;
            $u_product->on_trial = $this->product->on_trial ? 1 : 0;
            $u_product->trial_days = $this->product->on_trial ? $this->product->trial_days : 0;
            $u_product->trial_expire_on = $this->product->on_trial ? date("Y-m-d H:m:s", strtotime($this->product->trial_days . " days")) : null;
            $u_product->discount = $this->product->discount;
            $u_product->currency = strtolower($currency);
            $u_product->user_id = $this->user['id'];


            $this->to_date = $u_product->expired_on;
            $u_product->save();

            /* Getting plan for the product */
            $u_plan = $u_product->plan;
            if (is_null($u_plan)) $u_plan = new UPlan;

            $u_plan->fill($plan->toArray());

            $u_plan->u_product_id = $u_product->id;
            $u_plan->purchased_on = $todayDate;
            $u_plan->expired_on = $this->product->on_trial ? date("Y-m-d H:m:s", strtotime($this->product->trial_days . " days")) : date("Y-m-d H:i:s", strtotime("+1 " . $duration, strtotime($u_product->purchased_on)));
            $u_plan->currency = strtolower($currency);
            $u_plan->save();



            $u_features = $u_plan->features->sortBy('sort_order');

            // if there are existing features of that that plan, remove them all first.
            $prev_features_ids = null;
            if (!is_null($u_features)) {
                $prev_features_ids = $u_features->pluck('id')->toArray();
            }
            $features_to_be_added = [];
            foreach ($features->toArray() as $feature) {
                unset($feature['id']);
                unset($feature['plan_id']);
                $feature['created_at'] = $todayDate;
                $feature['updated_at'] = $todayDate;
                $feature['u_plan_id']  = $u_plan->id;
                array_push($features_to_be_added, $feature);
            }
            $inserted = false;
            if (count($features_to_be_added) > 0) $inserted = UFeature::insert($features_to_be_added);
            if ($inserted && $prev_features_ids) UFeature::destroy($prev_features_ids);

            if (!$isFree) $this->updateUserPaymentMethodDetails($u_product,$this->user['id']);

            $action = $isFree ? 'Product Subscribed (FREE)' : ($onTrial ? 'Product Subscribed (On Trial)' : 'Product Purchased');
            $currency_sign = $currency === 'GBP' ? '£' : ($currency === 'USD' ? '$' : '€');

            $status = 'info';
            if ($isFree) {
                $message = $this->email . ' has subscribed ' . $u_product->display_name . ' product with ' . $u_plan->display_name . ' plan.';
            } elseif ($onTrial) {
                $message = $this->email . ' has subscribed ' . $u_product->display_name . ' product with ' . $u_plan->display_name . ' plan on: ' . $u_product->trial_days . ' days trial which will expire on: ' . $u_plan->price->expired_on . ' having worth of £ ' . $u_plan->price . ' only.';
            } else {
                $message = $this->email . ' has purchased ' . $u_product->display_name . ' product with ' . $u_plan->display_name . ' plan which will expire on: ' . $u_plan->expired_on . ' & paid £ ' . $u_product->price . ' only.';
                $status = 'success';
            }
            /* Saving log */
            ActivityLog::add($action, $message, $status, $u_product->name);

            $this->u_product = $this->user->products()->where('name','=', $this->product->name)->first();
            $this->u_plan = $this->u_plan;
            // dd( $this->u_plan['features']);
            $this->features = $features_to_be_added;
        // } else {

        //     $this->u_product = $this->user->products()->where('name','=', $this->product->name)->first();
        //     // dd( $this->u_product);
        // }

        if($this->product->name == 'subject_request_management'){
            if($this->email && $this->source == "srm"){
                // dd($this->user);
                $user = $this->user;
                $dsarid = DB::connection('dsarsql')->select('select * from dsar_users where seers_user_id = :id ',['id' => $this->user->id]);
            } else{
                $user = auth()->user();
                $dsarid = DB::connection('dsarsql')->select('select * from dsar_users where seers_user_id = :id ',['id' => $user->id]);
            }

            $url = Config::get('app.dsarurl');
            $loginUrl = $url.'/api/auth/login';
            $CreateUrl = $url.'/api/auth/create_user';
            $CompanyUrl = $url.'/api/auth/add_company';
            $CompanyeditUrl = $url.'/api/auth/update_company';
            // $dsarid = DB::connection('dsarsql')->select('select * from dsar_users where seers_user_id = :id ',['id' => $this->user->id]);
            // dd($dsarid);
            if(count($dsarid) == 0){
                // $newuser = DB::connection('dsarsql')->table('dsar_users')->insertGetId(['name'=> $user->name,'email'=>$user->email,'seers_user_id'=>$user->id,]);
                // Request for generating bearer token
                $options = [
                    "multipart" => [
                        [
                            'name'     => 'email',
                            'contents' => 'jhondoe@gmail.com',
                        ],
                        [
                            'name'     => 'password',
                            'contents' => 'jhondoe@gmail.com-seers',
                        ],
                        [
                            'name'     => 'verified',
                            'contents' => true,
                        ],
                    ],
                    'verify' => false
                ];
                $client = new Client();
                $response = $client->request('POST',$loginUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);
                // request for creating new user in dsar
                $bearertoken = $result->token_type.' '.$result->access_token;
                $options = [
                    "multipart" => [
                        [
                            'name'     => 'email',
                            'contents' => $user->email,
                        ],
                        [
                            'name'     => 'seers_user_id',
                            'contents' => $user->id,
                        ],
                        [
                            'name'     => 'name',
                            'contents' => $user->name,
                        ],
                        [
                            'name'     => 'role',
                            'contents' => 'admin',
                        ],
                    ],
                    'verify' => false,
                    'headers' => [
                        'Authorization' => $bearertoken]
                ];
                $client = new Client();
                $response = $client->request('POST', $CreateUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);

                if($this->features == null){

                    $Dsarfeature = $this->u_plan['features'];
                }else{
                    $Dsarfeature = $this->features;
                }
                $product_info = array(
                    'product'=> $this->u_product,
                    'plan'=> $this->u_plan,
                    'feature'=> $Dsarfeature,
                );
                // new add company
                $product_json = json_encode($product_info); //$u_product;
                if($user->company != null){
                    $company_name = $user->company;
                }else{
                    $company_name = $user->fname;
                }
                $options = [
                    "multipart" => [
                        [
                            'name'     => 'name',
                            'contents' => $company_name,
                        ],
                        [
                            'name'     => 'email',
                            'contents' => $user->email,
                        ],
                        [
                            'name'     => 'owner',
                            'contents' => $result->user_id,
                        ],
                        [
                            'name'     => 'address',
                            'contents' => $user->address,
                        ],
                        [
                            'name'     => 'product_info',
                            'contents' => $product_json,
                        ],
                    ],
                    'verify' => false,
                    'headers' => [
                        'Authorization' => $bearertoken]
                ];
                $client = new Client();
                $response = $client->request('POST', $CompanyUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);
                //end add company
                // $product_json = json_encode($product_info); //$u_product;
                // $newcompany = DB::connection('dsarsql')->table('companies')->insertGetId(['name'=> $user->company,'email'=>$user->email,'owner'=>$result->user_id,'address'=>$user->address,'product_info'=>$product_json]);
                // $insertid = DB::connection('dsarsql')->table('dsar_users')->where('id','=',$result->user_id)->update(['company_id'=> $newcompany]);
            }else{
                // $product_info = array(
                //     'product'=> $this->u_product,
                //     'plan'=> $this->u_plan,
                //     'feature'=> $this->features,
                // );
                // $product_json = json_encode($product_info); //$u_product;
                // $newcompany = DB::connection('dsarsql')->table('companies')->where('owner','=',$dsarid[0]->id)->update(['name'=> $user->company,'email'=>$user->email,'address'=>$user->address,'product_info'=>$product_json]);
                $options = [
                    "multipart" => [
                        [
                            'name'     => 'email',
                            'contents' => 'jhondoe@gmail.com',
                        ],
                        [
                            'name'     => 'password',
                            'contents' => 'jhondoe@gmail.com-seers',
                        ],
                        [
                            'name'     => 'verified',
                            'contents' => true,
                        ],
                    ],
                    'verify' => false
                ];
                $client = new Client();
                $response = $client->request('POST', $loginUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);
                // request for creating new user in dsar
                $bearertoken = $result->token_type.' '.$result->access_token;
                if($this->features == null){
                    $Dsarfeature = $this->u_plan['features'];
                }else{
                    $Dsarfeature = $this->features;
                }
                $product_info = array(
                    'product'=> $this->u_product,
                    'plan'=> $this->u_plan,
                    'feature'=> $Dsarfeature,
                );
                // new add company
                $product_json = json_encode($product_info); //$u_product;
                if($user->company != null){
                    $company_name = $user->company;
                }else{
                    $company_name = $user->fname;
                }
                $options = [
                    "multipart" => [
                        [
                            'name'     => 'name',
                            'contents' => $company_name,
                        ],
                        [
                            'name'     => 'email',
                            'contents' => $user->email,
                        ],
                        [
                            'name'     => 'owner',
                            'contents' => $dsarid[0]->id,
                        ],
                        [
                            'name'     => 'address',
                            'contents' => $user->address,
                        ],
                        [
                            'name'     => 'product_info',
                            'contents' => $product_json,
                        ],
                    ],
                    'verify' => false,
                    'headers' => [
                        'Authorization' => $bearertoken]
                ];
                $client = new Client();
                $response = $client->request('POST', $CompanyeditUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);
            }
        }


        if($this->product->name == 'breach_management_system'){
            if($this->email && $this->source == "bms"){
                $user = $this->user;
                $bmsid = DB::connection('bmssql')->select('select * from users where seers_user_id = :id ',['id' => $this->user->id]);
            } else{
                $user = auth()->user();
                $bmsid = DB::connection('bmssql')->select('select * from users where seers_user_id = :id ',['id' => $user->id]);
            }
            $url = Config::get('app.bmsapiurl');
            $loginUrl = $url.'/api/auth/login';
            $CreateUrl = $url.'/api/auth/create_user';
            $CompanyUrl = $url.'/api/auth/add_company';
            $CompanyeditUrl = $url.'/api/auth/update_company';
            if(count($bmsid) == 0){

                // Request for generating bearer token

                $options = [
                    "multipart" => [
                        [
                            'name'     => 'email',
                            'contents' => 'jhondoe@gmail.com',
                        ],
                        [
                            'name'     => 'password',
                            'contents' => 'jhondoe@gmail.com-seers',
                        ],
                        [
                            'name'     => 'verified',
                            'contents' => true,
                        ],
                    ],
                    'verify' => false
                ];
                $client = new Client();
                $response = $client->request('POST', $loginUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);

                // request for creating new user in dsar

                $bearertoken = $result->token_type.' '.$result->access_token;
                $options = [
                    "multipart" => [
                        [
                            'name'     => 'email',
                            'contents' => $user->email,
                        ],
                        [
                            'name'     => 'seers_user_id',
                            'contents' => $user->id,
                        ],
                        [
                            'name'     => 'name',
                            'contents' => $user->name,
                        ],
                        [
                            'name'     => 'role',
                            'contents' => 'admin',
                        ],
                    ],
                    'verify' => false,
                    'headers' => [
                        'Authorization' => $bearertoken]
                ];
                $client = new Client();
                $response = $client->request('POST', $CreateUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);
                if($this->features == null){
                    $Bmsfeature = $this->u_plan['features'];
                }else{
                    $Bmsfeature = $this->features;
                }

                $product_info = array(
                    'product'=> $this->u_product,
                    'plan'=> $this->u_plan,
                    'feature'=> $Bmsfeature,
                );
                // new add company
                $product_json = json_encode($product_info); //$u_product;
                if($user->company != null){
                    $company_name = $user->company;
                }else{
                    $company_name = $user->fname;
                }
                $options = [
                    "multipart" => [
                        [
                            'name'     => 'name',
                            'contents' => $company_name,
                        ],
                        [
                            'name'     => 'email',
                            'contents' => $user->email,
                        ],
                        [
                            'name'     => 'owner',
                            'contents' => $result->user_id,
                        ],
                        [
                            'name'     => 'address',
                            'contents' => $user->address,
                        ],
                        [
                            'name'     => 'product_info',
                            'contents' => $product_json,
                        ],
                    ],
                    'verify' => false,
                    'headers' => [
                        'Authorization' => $bearertoken]
                ];
                $client = new Client();
                $response = $client->request('POST', $CompanyUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);
            }else{
                $options = [
                    "multipart" => [
                        [
                            'name'     => 'email',
                            'contents' => 'jhondoe@gmail.com',
                        ],
                        [
                            'name'     => 'password',
                            'contents' => 'jhondoe@gmail.com-seers',
                        ],
                        [
                            'name'     => 'verified',
                            'contents' => true,
                        ],
                    ],
                    'verify' => false
                ];
                $client = new Client();
                $response = $client->request('POST', $loginUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);
                // request for creating new user in dsar
                $bearertoken = $result->token_type.' '.$result->access_token;
                if($this->features == null){
                    $Bmsfeature = $this->u_plan['features'];
                }else{
                    $Bmsfeature = $this->features;
                }
                $product_info = array(
                    'product'=> $this->u_product,
                    'plan'=> $this->u_plan,
                    'feature'=> $Bmsfeature,
                );
                // new add company
                $product_json = json_encode($product_info); //$u_product;
                if($user->company != null){
                    $company_name = $user->company;
                }else{
                    $company_name = $user->fname;
                }
                $options = [
                    "multipart" => [
                        [
                            'name'     => 'name',
                            'contents' => $company_name,
                        ],
                        [
                            'name'     => 'email',
                            'contents' => $user->email,
                        ],
                        [
                            'name'     => 'owner',
                            'contents' => $bmsid[0]->id,
                        ],
                        [
                            'name'     => 'address',
                            'contents' => $user->address,
                        ],
                        [
                            'name'     => 'product_info',
                            'contents' => $product_json,
                        ],
                    ],
                    'verify' => false,
                    'headers' => [
                        'Authorization' => $bearertoken]
                ];
                $client = new Client();
                $response = $client->request('POST', $CompanyeditUrl, $options);
                $result = $response->getBody()->getContents();
                $result = json_decode($result);
            }
        }

    }
    private function getStripeCustomerObj ($intent) {
        // Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        $stripe_key = Config::get('app.stripe_key');
        Stripe::setApiKey($stripe_key);
        return $this->createStripeCustomer($intent);
    }

    private function createStripeCustomer($intent)
    {
        // Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        $stripe_key = Config::get('app.stripe_key');
        Stripe::setApiKey($stripe_key);
        if(($this->source == "srm" || $this->source == 'bms') && $this->email){
            $customer = Customer::create(["description" => $this->email]);
        } else {
            $customer = Customer::create(["description" => auth()->user()->email]);
        }
        $this->stripe_id = $customer->id;
        return $customer;
    }

    private function updateUserPaymentMethodDetails($u_product, $user_id = null)
    {

        if($this->email && ($this->source == "srm" || $this->source == 'bms')){
            $total_records = UserPaymentMethodDetails::where('user_id', $user_id)->count();
        }else{
            $total_records = UserPaymentMethodDetails::where('user_id', auth()->id())->count();
        }


        if($this->email && ($this->source == "srm" || $this->source == 'bms')){
            $user_payment_method_details = UserPaymentMethodDetails::firstOrCreate([
                'user_id' => $user_id,
                'u_product_id' => $u_product['id']
            ]);
        }   else    {
            if(auth()->id()){
                $user_payment_method_details = UserPaymentMethodDetails::firstOrCreate([
                    'user_id' => auth()->id(),
                    'u_product_id' => $u_product['id']
                ]);
            }else{
                $user_payment_method_details = UserPaymentMethodDetails::firstOrCreate([
                    'user_id' => $this->user['id'],
                    'u_product_id' => $u_product['id']
                ]);
            }

        }
        if (!$u_product['on_trial']) {
            $card = $this->payment_response->charges->data[0]['payment_method_details']['card'];
            $user_payment_method_details->stripe_id = $this->stripe_id;
            $user_payment_method_details->stripe_customer_id = $this->payment_response['customer'];
            $user_payment_method_details->stripe_card_type = $card['brand'];
            $user_payment_method_details->stripe_card_last_four_digits = $card['last4'];
            $user_payment_method_details->stripe_payment_method = json_encode(request()->get('payment_method'));
            $user_payment_method_details->stripe_payment_method_id = $this->payment_response['payment_method'];
            $user_payment_method_details->stripe_response_id = $this->payment_response['id'];
            $user_payment_method_details->stripe_client_secret_key = $this->payment_response['client_secret'];
            $user_payment_method_details->stripe_confirmation_method = $this->payment_response['confirmation_method'];
            $user_payment_method_details->currency = $this->payment_response['currency'];
            $user_payment_method_details->amount_deducted = $this->payment_response['amount'] / 100;
            $user_payment_method_details->is_primary = $total_records === 0 ? 1 : 0;
            $user_payment_method_details->expire_at = Carbon::createFromDate($card['exp_year'], $card['exp_month'], random_int(1, 30), null)->format('Y-m-d H:i:s');
        } else {
            $user_payment_method_details->stripe_id = $this->stripe_id;
            $user_payment_method_details->stripe_payment_method = json_encode(request()->get('payment_method'));
            $user_payment_method_details->stripe_payment_method_id = request()->get('payment_method')['id'];
        }

        $user_payment_method_details->save();
    }

    private function calculateTotalCost($req_plan_name, $req_tenure, $domain_limit = Null)
    {

        $totalPrice = 0;

        // if GDPR Staff Training
        if ($this->product->name === 'gdpr_training') {
            $members = request()->get('members');
            $plans = $this->product->plans;
            foreach ($plans as $plan) {
                $features = $plan->features;
                $min = $max = 0;
                $unit_price_of_user = 0;
                foreach ($features as $feature) {
                    if ($feature->name === 'min_limit') $min = $feature->value;
                    if ($feature->name === 'max_limit') $max = $feature->value;
                    if ($feature->name === 'unit_price_of_user') $unit_price_of_user = $feature->value;
                }
                if ($members >= $min && $members <= $max) {
                    if (request()->get('currency') === 'USD')
                        $unit_price_of_user *= config('app.USD_CONVERSION_RATE');
                    elseif (request()->get('currency') === 'EUR')
                        $unit_price_of_user *= config('app.EUR_CONVERSION_RATE');

                    $unit_price_of_user = ($unit_price_of_user - intval($unit_price_of_user) > 0.5)
                        ? ceil($unit_price_of_user)
                        : floor($unit_price_of_user);

                    $totalPrice = $unit_price_of_user * $members;
                    break;
                }
            }
        }
        elseif($domain_limit != null && $this->product->name === 'cookie_consent'){
            $plan = $this->product->plans()->whereName($req_plan_name)->first();
            $totalPrice = $plan->price * $domain_limit;
                if($domain_limit == 1){
                    $totalPrice = $totalPrice-0.01;
                }elseif($domain_limit > 5){
                    $totalPrice = round($totalPrice*(1-0.20))-0.01;
                }else{
                    $totalPrice =  round($totalPrice*(1-0.15))-0.01;
                }
                 if ($req_tenure === 'yearly') {
                    $totalPrice = round(($totalPrice * 12 * 0.833)/12)-0.01;
                }
                 if (request()->get('currency') === 'USD') {
                $totalPrice *= config('app.USD_CONVERSION_RATE');
                $totalPrice = ($totalPrice - intval($totalPrice) > 0.5)
                    ? ceil($totalPrice)
                    : floor($totalPrice);
                $totalPrice = number_format((($totalPrice - 0.01)), 2, '.', '');
                } elseif (request()->get('currency') === 'EUR') {
                    $totalPrice *= config('app.EUR_CONVERSION_RATE');
                    $totalPrice = ($totalPrice - intval($totalPrice) > 0.5)
                        ? ceil($totalPrice)
                        : floor($totalPrice);
                    $totalPrice = number_format((($totalPrice - 0.01)), 2, '.', '');
                }
                elseif (request()->get('currency') === 'BRL') {
                    $totalPrice *= config('app.BRL_CONVERSION_RATE');
                    $totalPrice = ($totalPrice - intval($totalPrice) > 0.5)
                        ? ceil($totalPrice)
                        : floor($totalPrice);
                    $totalPrice = number_format((($totalPrice - 0.01)) , 2,'.', '');
                }

        }else {
            $plan = $this->product->plans()->whereName($req_plan_name)->first();

            $totalPrice = ($req_tenure === 'yearly')
                ? number_format((($plan->price / 1.25)), 2, '.', '')
                : $plan->price;

            if (request()->get('currency') === 'USD') {
                $totalPrice *= config('app.USD_CONVERSION_RATE');
                $totalPrice = ($totalPrice - intval($totalPrice) > 0.5)
                    ? ceil($totalPrice)
                    : floor($totalPrice);
                $totalPrice = number_format((($totalPrice - 0.01)), 2, '.', '');
            } elseif (request()->get('currency') === 'EUR') {
                $totalPrice *= config('app.EUR_CONVERSION_RATE');
                $totalPrice = ($totalPrice - intval($totalPrice) > 0.5)
                    ? ceil($totalPrice)
                    : floor($totalPrice);
                $totalPrice = number_format((($totalPrice - 0.01)), 2, '.', '');
            }
            elseif (request()->get('currency') === 'BRL') {
                $totalPrice *= config('app.BRL_CONVERSION_RATE');
                $totalPrice = ($totalPrice - intval($totalPrice) > 0.5)
                    ? ceil($totalPrice)
                    : floor($totalPrice);
                $totalPrice = number_format((($totalPrice - 0.01)) , 2,'.', '');
            }
        }

        return $req_tenure === 'yearly' ? ($totalPrice * 12) : $totalPrice;
    }

    private function generatePaymentResponse ($intent) {
        // return $intent;
        switch ($intent->status) {
            case 'requires_action':
            case 'requires_source_action':
                // if ($intent->next_action->type === 'redirect_to_url') {
                //     return ["requires_action" => true, "client_secret" => $intent->client_secret];
                // }
                return $intent;
                break;
            case 'requires_payment_method':
                return $intent;
                break;
            case 'requires_confirmation':
                $confirm_payment = PaymentIntent::retrieve($intent->id);
                $confirm_payment->confirm();
                return $confirm_payment;
                break;
            case 'succeeded':
                return $intent;
                break;
            default:
                # code...
                return $intent;
                break;
        }
    }

    private function insertStripeDetail ($paymentResponse) {
        $stripeDetail = new StripeOrderDetail;
        $stripeDetail->resposid = $paymentResponse['id'];
        $stripeDetail->amount = $paymentResponse['amount'];
        $stripeDetail->amount_receive = $paymentResponse['amount_received'];
        $stripeDetail->capture_method = $paymentResponse['capture_method'];
        $stripeDetail->client_secret = $paymentResponse['client_secret'];
        $stripeDetail->confirmation_method = $paymentResponse['confirmation_method'];
        $stripeDetail->currency = $paymentResponse['currency'];
        $stripeDetail->customer = $paymentResponse['customer'];
        $stripeDetail->description = $paymentResponse['description'];
        $stripeDetail->save();
    }

    private function calculateDiscounts($promoCode)
    {

        $promo = PromoCodes::where(['promo_code' => $promoCode, 'enabled' => 1])->first();

        if ($promo === null) {
            return -1;
        } elseif ((date('Y-m-d H:i:s') > $promo->expire_at) || (date('Y-m-d H:i:s') < $promo->active_at)) {
            return -2;
        }

        if ($promo->promo_code === 'Seersspecial£1') {
            return -3;
        } else {
            $this->promo_code_value = $promo->discount_percentage;
        }
        return 0;
    }

    private function isStripeKeyExist()
    {
        return User::where('id', $this->user->id)->whereNotNull('stripe_id')->first();
    }

    private function getStripeCustomer($intent)
    {
        // Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        $stripe_key = Config::get('app.stripe_key');
        Stripe::setApiKey($stripe_key);
        $customer = null;
        if (!$this->isStripeCustomer()) {
            $customer = $this->createStripeCustomer($intent);
        } else {
            $customer = Customer::retrieve($this->user->stripe_id);
        }
        return $customer;
    }

    private function isStripeCustomer()
    {
        return User::where('id', $this->user->id)->whereNotNull('stripe_id')->first();
    }

    public function applyPromoCode(Request $request)
    {
        if (!$request->ajax()) {
            return response(['message' => 'Bad Request', 'data' => '', 'code' => 400], 400);
        }

        // if (!auth()->check()) {
        //     return response(['message' => 'User must be logged In', 'data' => '', 'code' => 400], 400);
        // }

        $promoCode = $request->get('promo_code');
        $message = '';
        $data = 0;
        $code = 302;
        $discount = $this->getPromoDiscount($promoCode,$request->all());
        if ($discount === -1) {
            $message = 'Invalid Promo Code';
        } else if ($discount === -2) {
            $message = 'Promo Code expired';
        } else if ($discount === -3) {
            $message = 'Promo Code applied';
            $data = 100;
            $code = 200;
        } else {
            $message = 'Promo Code applied';
            $data = $discount;
            $code = 200;
        }
        return response(['discount' => $data, 'message' => $message], $code);
    }

    private function getPromoDiscount($promoCode,$request)
    {
        $tenure = $request['tenure'];
        $product_name = $request['product_name'];
        $plan_name = isset($request['plan_name'])?$request['plan_name']:'';
        $personalSeersCodes = ['Seersspecial£1'];
        $promo = PromoCodes::with('product','plan')->where(['promo_code' => $promoCode , 'enabled' => 1])->first();

        if ($promo === null) {
            return -1;
        }

        $notActivated = date('Y-m-d H:i:s') < date('Y-m-d H:i:s', strtotime($promo->active_at));
        $isExpired = date('Y-m-d H:i:s') > date('Y-m-d H:i:s', strtotime($promo->expire_at));
        $allUsed = ($promo->quantity - $promo->availed) == 0;

        if ($notActivated || $isExpired || $allUsed || $product_name!=$promo->product->name ) {
            return -2;
        }
        if ($product_name!=='gdpr_training')
        {
            if ($product_name!=='assessment')
            {
                if ($tenure != $promo->tenure || $plan_name != $promo->plan->name) {
                    return -2;
                }
            }else{
                if ($plan_name != $promo->plan->name) {
                    return -2;
                }
            }

        }
        if (in_array($promo->promo_code, $personalSeersCodes)) {
            return -3;
        }
        return $promo->discount_percentage;
    }

    private function saveUserPromoCodeLog($promoCode)
    {
        $promoObj = PromoCodes::where(['promo_code' => $promoCode, 'enabled' => 1])->first();
        $tenure = $promoObj->tenure;
        $promoObj->availed += 1;
        $promoObj->save();

        $userPromo = UPromo::firstOrCreate([
            'user_id' => auth()->id(),
            'u_product_id' => $this->u_product->id,
            'u_plan_id' => $this->u_plan->id,
            'promo_code' => $promoCode,
            'tenure' => $tenure,
        ]);

        $userPromo->discount_percentage = $promoObj->discount_percentage;
        $userPromo->slug = $promoObj->slug;
        $userPromo->is_recursive = $promoObj->is_recursive;
        $userPromo->duration = $promoObj->duration; //How many attempts user will get to avail promo code
        $userPromo->remaining_attempts = intval($promoObj->duration) - 1; //Attempts will decrement and when the remaining_attempts = 0 then UPromo will become invalid
        $userPromo->quantity = $promoObj->quantity;
        $userPromo->quantity_on_avail = $promoObj->availed;
        $userPromo->activation_date = $promoObj->active_at; //When PromoCode was launched
        $userPromo->expire_date = $promoObj->expire_at; // When PromoCode will Expire if not availed.
        $userPromo->expire_on = $this->evaluateExpireOn(intval($promoObj->duration)); //Date when the UPromo will expire while adding duration to the current date/When UPromo availed
        $userPromo->user_id = auth()->id();
        $userPromo->u_product_id = $this->u_product->id;
        $userPromo->u_plan_id = $this->u_plan->id;
        $userPromo->save();
    }

    private function evaluateExpireOn($duration){
        if($this->u_product->recursive_status != 'yearly'){
            $date_apply = Carbon::now();
            $expire = $date_apply->addMonths($duration);
            return $expire;
        } else if ($this->u_product->recursive_status == 'yearly') {
            $date_apply = Carbon::now();
            $expire = $date_apply->addYears($duration);
            return $expire;
        }
    }

    //Code Added for Cookie Consent Log Limit Start
    //Reset Cookie Consent limit of Each Domain
    private function resetCookieConsentLimit(CbUsersDomains $domain){
        $domain->total_consents = 0;
        $domain->last_limit_reached = null;
        $domain->last_consent_time = null;
        $domain->last_reset_consent_at = null;
        $domain->is_emailed = 0;
        if(!$domain->enabled){
            $cookieXray = CookieXrayDialogue::Where('cb_users_domain_id','=',$domain->id)->first();
            $cookieXray->is_cookie_banner = 1;
            $cookieXray->save();
        }
        $domain->enabled = 1;
        $domain->save();

    }

    //Code Added for Cookie Consent Log Limit Start
    public function testPayment()
    {
        return view('unusedFiles.stripe-test-payment');
    }

    public function createProduct(Request $request)
    {
        \Stripe\Stripe::setApiKey(config("app.stripe_key"));

        $customer = \Stripe\Customer::create([
            "email" => $request->email,
            "name" => auth()->user()->name,
            "description" => $request->email .' is purchasing ' . $request->product_name,
            'payment_method' => $request->payment_method['id'],
            'invoice_settings' => [
                'default_payment_method' => $request->payment_method['id'],
            ],
        ]);

        // $paymentIntent = PaymentIntent::create([
        //     "payment_method" => $request->payment_method['id'],
        //     "amount" => (int) (39 * 100),
        //     "currency" => strtolower('USD'),
        //     "customer" => $customer->id,
        //     "payment_method_types" => ['card'],
        //     "setup_future_usage" => 'off_session',
        //     "description" => $request->email .' is purchasing ' . $request->product_name,
        //     "confirm" => true,
        // ]);

        // if ($paymentIntent->status === '') {

        // }

        \Stripe\Subscription::create([
            "customer" => $customer->id,
            "default_tax_rates" => ["txr_1J5qt7JIlkEyiFxmH0OOLhnj"],
            "coupon" => "V9y2PnfH",
            "off_session" => false,
            'payment_behavior' => 'default_incomplete',
            'expand' => ['latest_invoice.payment_intent'],
            "metadata" => [

            ],
            "items" => [
                [
                    "price" => "price_1J5PXkJIlkEyiFxmqrdvssD8",
                ],
            ]
        ]);

        return response()->json($request->all(), 200);
    }
    public function calculatePrice($tenure,$price,$currency,$domain_limit = null){
        $totalPrice = 0;
        if($domain_limit !== null){
            $totalPrice = $price * $domain_limit;
                if($domain_limit == 1){
                    $totalPrice = $totalPrice-0.01;
                }elseif($domain_limit > 5){
                    $totalPrice = round($totalPrice*(1-0.20))-0.01;
                }else{
                    $totalPrice =  round($totalPrice*(1-0.15))-0.01;
                }
                 if ($tenure === 'yearly') {
                    $totalPrice = round(($totalPrice * 12 * 0.833)/12)-0.01;
                }
        }else{
            $totalPrice = ($tenure === 'yearly')
                ? number_format((($price / 1.25) * 12), 2, '.', '')
                : $price;
        }
            if ($currency === 'usd') {
                $totalPrice *= config('app.USD_CONVERSION_RATE');
                $totalPrice = ($totalPrice - intval($totalPrice) > 0.5)
                    ? ceil($totalPrice)
                    : floor($totalPrice);
                $totalPrice = number_format((($totalPrice - 0.01)) , 2,'.', '');
            } elseif ($currency === 'eur') {
                $totalPrice *= config('app.EUR_CONVERSION_RATE');
                $totalPrice = ($totalPrice - intval($totalPrice) > 0.5)
                    ? ceil($totalPrice)
                    : floor($totalPrice);
                $totalPrice = number_format((($totalPrice - 0.01)) , 2,'.', '');
            }
            elseif ($currency === 'brl') {
                $totalPrice *= config('app.BRL_CONVERSION_RATE');
                $totalPrice = ($totalPrice - intval($totalPrice) > 0.5)
                    ? ceil($totalPrice)
                    : floor($totalPrice);
                $totalPrice = number_format((($totalPrice - 0.01)) , 2,'.', '');
            }
            return $totalPrice;
    }
    public function calculatePriceWithoutConversion($tenure,$price,$currency,$domain_limit = null){
        $totalPrice = 0;
        if($domain_limit !== null){
            $totalPrice = $price * $domain_limit;
                if($domain_limit == 1){
                    $totalPrice = $totalPrice-0.01;
                }elseif($domain_limit > 5){
                    $totalPrice = round($totalPrice*(1-0.20))-0.01;
                }else{
                    $totalPrice =  round($totalPrice*(1-0.15))-0.01;
                }
            $this->priceWithoutVatAndDiscountCC=$totalPrice;
            if ($tenure === 'yearly') {
                    $totalPrice = round(($totalPrice * 12 * 0.833)/12)-0.01;
                }
        }else{
            $totalPrice = ($tenure === 'yearly')
                ? number_format((($price / 1.25) * 12), 2, '.', '')
                : $price;
        }
            // if ($currency === 'usd') {
            //     $totalPrice *= config('app.USD_CONVERSION_RATE');
            //     $totalPrice = ($totalPrice - intval($totalPrice) > 0.5)
            //         ? ceil($totalPrice)
            //         : floor($totalPrice);
            //     $totalPrice = number_format((($totalPrice - 0.01)) , 2,'.', '');
            // } elseif ($currency === 'eur') {
            //     $totalPrice *= config('app.EUR_CONVERSION_RATE');
            //     $totalPrice = ($totalPrice - intval($totalPrice) > 0.5)
            //         ? ceil($totalPrice)
            //         : floor($totalPrice);
            //     $totalPrice = number_format((($totalPrice - 0.01)) , 2,'.', '');
            // }
            // elseif ($currency === 'brl') {
            //     $totalPrice *= config('app.BRL_CONVERSION_RATE');
            //     $totalPrice = ($totalPrice - intval($totalPrice) > 0.5)
            //         ? ceil($totalPrice)
            //         : floor($totalPrice);
            //     $totalPrice = number_format((($totalPrice - 0.01)) , 2,'.', '');
            // }
            return $totalPrice;
    }

    public function stripe_checkout(Request $request)
    {
        $currency = $request->currency;
        Stripe\Stripe::setApiKey(config('services.stripe.secret'));
        $mode = 'subscription';
        if ($request->tenure == 'yearly') {
            $priceId = $request->price_id_yearly;
        } elseif($request->tenure == 'monthly') {
            $priceId = $request->price_id_monthly;
        } else {
            $mode = 'payment';
            $priceId = $request->price_id_monthly;
        }
        $payment_method_types = ['card'];
        $automatic_tax = [
            'enabled' => false,
        ];
        if ($currency == 'GBP') {
            if ($request->tenure == 'yearly' || $request->tenure == 'monthly')
            {
                $payment_method_types = ['card'/*, 'bacs_debit'*/];
            }

            $automatic_tax = [
                'enabled' => true,
            ];
        }
        $adjustable_quantity = [
            'enabled' => false,
        ];
        $quantity= 1;
        if($request->name=='cookie_consent')
        {
            $quantity=$request->domain_limit;
            $adjustable_quantity = [
                'enabled' => true,
                'minimum' => 1,
                'maximum' => 1000,
            ];
        }elseif($request->name=='gdpr_training')
        {
            $quantity=$request->members;
        }

        $email=auth()->user()->email;
        $session = Stripe\Checkout\Session::create([
            'success_url' => url('checkout-success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkout-canceled'),
            'payment_method_types' => $payment_method_types,
            'mode' => $mode,
            'customer_email'=>$email,
            'allow_promotion_codes' => true,
            'line_items' => [[
                'price' => $priceId,
                'adjustable_quantity' => $adjustable_quantity,
                // For metered billing, do not pass quantity
                'quantity' => $quantity,
            ]],
            'locale'=>'auto',
            'currency'=>$currency,

            'automatic_tax' => $automatic_tax,
        ]);
        return $session->url;
        // dd($session->url);
        // Redirect to the URL returned on the Checkout Session.
        if($session->url){
            return redirect($session->url);
        }else{
            Session::flash('failure','Something went wrong.');
            return back();
        }

    }

    public function checkout_success(Request $request)
    {
        $session_id = $request->session_id ?? null;
        return redirect()->route('thank-you-for-checkout');
    }
    public function checkout_canceled()
    {
        return redirect()->route('price-plan');
    }
    public function checkoutWebhook(Request $request)
    {
//        Stripe\Stripe::setApiKey(config('services.stripe.secret'));
//        $webhookSecret = env('STRIPE_WEBHOOK_SECRET');
//        $signature = $request->header('stripe-signature');
//        if ($webhookSecret) {
//            try {
//                $event = Stripe\Webhook::constructEvent(
//                    json_encode($request->all()),
//                    $signature,
//                    $webhookSecret
//                );
//            } catch (\Exception $e) {
//                return \response([ 'error' => $e->getMessage() ]);
//            }
//        } else {
            $event = $request->all();
//        }
        $type = $request->type;
//        Log::channel('recursivePayment')->info('type=>'.json_encode($type));

        $object =  $request->data['object'];
//        Log::channel('recursivePayment')->info('obj=>'.json_encode($object));
        switch ($type) {
            case 'checkout.session.completed':
                break;
            case 'invoice.paid':
                Log::channel('recursivePayment')->info('invoice.paid=>'.json_encode($event));
                $product_name = $object['lines']['data'][0]['plan']['name'];
                $email = $object['customer_email'];
                $product_id = $object['lines']['data'][0]['plan']['product'];
                $tenure = $object['lines']['data'][0]['plan']['interval'];
                $currency = $object['lines']['data'][0]['plan']['currency'];
                $livemode = $object['lines']['data'][0]['plan']['livemode'];
                $price_id = $object['lines']['data'][0]['price']['id'];
                $price_type = $object['lines']['data'][0]['price']['type'];//recurring
                $quantity = $object['lines']['data'][0]['quantity'];
                $amount = $object['total'];
//                $discount = $object['total_discount_amounts'];
                $discount = 0;
                $amount = $amount/100;
                $this->updateUserProduct($email,$tenure,$price_id,$quantity,$currency,$amount,$price_type,$livemode,$discount);
                break;
            case 'invoice.payment_failed':
                Log::channel('recursivePayment')->info('request ==>'.json_encode($request));


                break;
            default:
                Log::channel('recursivePayment')->info('default =>'.json_encode($event));

        }
        Log::channel('recursivePayment')->info('====================================================');

        return \response([ 'status' => 'success' ]);
    }

    public function updateUserProduct($email,$tenure,$price_id,$quantity,$currency,$amount,$price_type,$livemode,$discount)
    {
        $todayDate = Carbon::now();
        $expiry_date = $tenure == 'month'? Carbon::now()->addMonth() : Carbon::now()->addYear();
        $user=User::where('email',$email)->first();
        $user_id=$user->id;
        $plan = Plan::with('product','features')->where('price_id_monthly',$price_id)->orWhere('price_id_yearly',$price_id)->first();
        Log::channel('recursivePayment')->info('plan=====>'.json_encode($plan));
        Log::channel('recursivePayment')->info('user id=====>'.json_encode($user_id));

        $product=$plan->product;

        $product_name=$plan->product->name;
        $u_product = UProduct::where('user_id',$user_id)->whereName($product_name)->first();
        if ($u_product) {
            $u_product->delete();
        }

            $u_product = new UProduct;
            $u_product->fill($product->toArray());
            $u_product->recursive_status = $tenure;
            $u_product->purchased_on = $todayDate;
            $u_product->expired_on = $expiry_date;
            $u_product->upgraded_on = $u_product->purchased_on;
            $u_product->on_trial = 0;
            $u_product->trial_days = 0;
            $u_product->trial_expire_on = null;
            $u_product->discount = $discount;
            $u_product->currency = strtolower($currency);
            $u_product->user_id = $user_id;
            $u_product->price = $amount;
            $u_product->save();

        $u_plan = $u_product->plan;
        if ($u_plan) {
            UPlan::find($u_plan->id)->delete();
        }
        $u_plan = new UPlan();
        $u_plan->fill($plan->toArray());
        $u_plan->price = $amount;
        $u_plan->u_product_id = $u_product->id;
        $u_plan->purchased_on = $todayDate;
        $u_plan->expired_on =  $expiry_date;
        $u_plan->currency = strtolower($currency);
        $u_plan->save();
        $features = $plan->features->sortBy('sort_order');
        $u_features = $u_plan->features->sortBy('sort_order');
        // if there are existing features of that that plan, remove them all first.
        $prev_features_ids = null;
        if (!is_null($u_features)) {
            $prev_features_ids = $u_features->pluck('id')->toArray();
        }
        $features_to_be_added = [];
        foreach ($features->toArray() as $feature) {
            unset($feature['id']);
            unset($feature['plan_id']);
            $feature['created_at'] = $todayDate;
            $feature['updated_at'] = $todayDate;
            $feature['u_plan_id']  = $u_plan->id;
            if($quantity !== null && $feature['name'] === 'domain_limit'){
                $feature['value'] = $quantity;
            }
            array_push($features_to_be_added, $feature);
        }

        $inserted = false;
        if (count($features_to_be_added) > 0) $inserted = UFeature::insert($features_to_be_added);
        if ($inserted && $prev_features_ids) UFeature::destroy($prev_features_ids);

        // if product is GDPR Staff Training, then one object of the features will be added extra.
        if ($product_name == 'gdpr_training') {
            $u_feature = new UFeature;
            $u_feature->name = 'no_of_employees';
            $u_feature->display_name = 'No. of Employees';
            $u_feature->value = $quantity;
            $u_feature->price = $amount;
            $u_feature->description = 'You can add Maximum ' . $quantity . ' Users';
            $u_feature->is_visible = true;
            $u_feature->sort_order = 5;
            $u_feature->is_active = true;
            $u_feature->u_plan_id = $u_plan->id;
            $u_feature->save();
        }
    }

}
