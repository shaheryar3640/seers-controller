<?php

namespace App\Http\Controllers;

use App\Models\MembershipPlans;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use PayPal\Api\Agreement;
use PayPal\Api\Amount;
use PayPal\Api\Currency;
use PayPal\Api\Details;
use PayPal\Api\Item;
/** All Paypal Details class **/
use PayPal\Api\ItemList;
use PayPal\Api\MerchantPreferences;
use PayPal\Api\Patch;
use PayPal\Api\PatchRequest;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PaymentDefinition;
use PayPal\Api\PaymentExecution;
use PayPal\Api\Plan;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Common\PayPalModel;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Rest\ApiContext;
use Illuminate\Support\Facades\Mail;
use Redirect;
use Session;
use URL;

class ProfilePaypalPaymentController extends Controller
{
    private $_api_context;
    public function __construct()
    {
        /** PayPal api context **/
        $paypal_conf = \Config::get('paypal');
        $this->_api_context = new ApiContext(new OAuthTokenCredential(
                $paypal_conf['client_id'],
                $paypal_conf['secret'])
        );
        $this->_api_context->setConfig($paypal_conf['settings']);
    }


    public function payWithpaypal(Request $request)
    {
        $amount = 0;
        $selectedPeriod = null;
        $period = $request->get('package_val');
        $membershipPlan = MembershipPlans::where(['slug'=> $request->get('slug')])->first();
//        $period = '1';
//        $membershipPlan = MembershipPlans::where(['slug'=> 'gold'])->first();
        $amount = $membershipPlan->price;
        $amount = ($period == '1') ? ($amount*2) : ($amount*12);
        $selectedPeriod = ($period == '1') ? 'Month' : 'Year';
        //$amount = ($amount*$period);
        $vat = 1.2;
        $amount = $amount * $vat;

        $plan = $this->setPlan($membershipPlan);
        $trialPaymentDefinition = $this->setTrialPaymentDefinition();
        $paymentDefinition = $this->setPaymentDefinition($amount,$selectedPeriod);
        $merchantPreferences = $this->setMerchantPreference($amount,$selectedPeriod);
        $plan->setPaymentDefinitions(array($trialPaymentDefinition, $paymentDefinition));
        $plan->setMerchantPreferences($merchantPreferences);

        //dd($plan);

        try {
            $createdPlan = $plan->create($this->_api_context);

            try {
                $patch = new Patch();
                $value = new PayPalModel('{"state":"ACTIVE"}');
                $patch->setOp('replace')
                    ->setPath('/')
                    ->setValue($value);
                $patchRequest = new PatchRequest();
                $patchRequest->addPatch($patch);
                $createdPlan->update($patchRequest, $this->_api_context);
                $patchedPlan = Plan::get($createdPlan->getId(), $this->_api_context);

                $agreement = $this->setAgreement($patchedPlan);


                return response()->json([
                    'request'=>$request->all(),
                    'amount'=>$amount,
                    'url'=>$agreement->getApprovalLink()
                ],200);
            } catch (PayPalConnectionException $ex) {
                return response()->json([
                    'error'=>$ex,
                    'getData'=>json_decode($ex->getData()),
                ],$ex->getCode());
            } catch (\Exception $ex) {
                return response()->json([
                    'error'=>$ex,
                ],400);
            }
            //dd($patchedPlan);
        } catch (PayPalConnectionException $ex) {
            return response()->json([
                'error'=>$ex,
                'getData'=>json_decode($ex->getData()),
            ],$ex->getCode());

        }catch (\Exception $ex) {
            return response()->json([
                'error'=>$ex,
            ],400);
        }
        //dd($createdPlan);

    }

    public function setAgreement($patchedPlan){
        $startDate = date('c', time() + 3600);
        $agreement = new Agreement();
        $agreement->setName('Recursive Payment Plan')
            ->setDescription('Recursive Payment Plan Information to add payment agreement')
            ->setStartDate($startDate);

        // Set plan id
        $plan = new Plan();
        $plan->setId($patchedPlan->getId());
        $agreement->setPlan($plan);

        // Add payer type
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');
        $agreement->setPayer($payer);

        try{
            $agreement = $agreement->create($this->_api_context);

            // Extract approval URL to redirect user
            $approvalUrl = $agreement->getApprovalLink();
            return $agreement;
            /*header("Location: " . $approvalUrl);
            exit();*/
        } catch (PayPalConnectionException $ex) {
            return response()->json([
                'error'=>$ex,
                'getData'=>json_decode($ex->getData()),
            ],$ex->getCode());
        } catch (Exception $ex) {
            return response()->json([
                'error'=>$ex,
            ],400);
        }
    }

    public function setPlan($membershipPlan){
        $plan = new Plan();
        $plan->setName($membershipPlan->name)
            ->setDescription('Through account profile buy price plan '.$membershipPlan->name)
            ->setType('INFINITE');
        return $plan;
    }
    public function setTrialPaymentDefinition(){
        $trialPaymentDefinition = new PaymentDefinition();
        $trialPaymentDefinition->setName('One-off Trial Payment')
            ->setType('TRIAL')
            ->setFrequency('Week')
            ->setFrequencyInterval('2')
            ->setCycles('1')
            ->setAmount(new Currency(array(
                'value' => 0,
                'currency' => 'GBP'
            )));

        return $trialPaymentDefinition;
    }
    public function setPaymentDefinition($amount,$selectedPeriod){
        $setFrequency = ($selectedPeriod == 'Month') ? '12' : '1';
        $paymentDefinition = new PaymentDefinition();
        $paymentDefinition->setName('REGULAR')
            ->setType('REGULAR')
            ->setFrequency($selectedPeriod)
            ->setFrequencyInterval($setFrequency)
            ->setCycles('0')
            ->setAmount(new Currency(array(
                'value' => $amount,
                'currency' => 'GBP'
            )));

        return $paymentDefinition;
    }
    public function setMerchantPreference($amount,$selectedPeriod){
        $merchantPreferences = new MerchantPreferences();
        $merchantPreferences->setReturnUrl(env('APP_URL').'/recuringStatus?status=success'.'&selectedPeriod='.$selectedPeriod)
            ->setCancelUrl(env('APP_URL').'/register/account-profile?status=cancel')
            ->setAutoBillAmount('yes')
            ->setInitialFailAmountAction('CONTINUE')
            ->setMaxFailAttempts('0')
            ->setSetupFee(new Currency(array(
                'value' => 0,
                'currency' => 'GBP'
                )));

        return $merchantPreferences;
    }


    public function recuringStatus(Request $request){

        if (!empty($request->get('status'))) {
            if($request->get('status') == "success") {
                $token = $request->get('token');
                $selectedPeriod = $request->get('selectedPeriod');
                $agreement = new Agreement();

                try {
                    // Execute agreement
                    $agreement->execute($token, $this->_api_context);
                    //dd($selectedPeriod);
                    //return $agreement;
                    if(Auth::check()){
                        $user = Auth::User();
                        $user->paypal_token = $token;
                        $user->plan_expiry = $selectedPeriod;
                        $user->trial_ends_at = date("Y-m-d H:m:s", strtotime("14 days"));
                        $user->upgraded_at = date('Y-m-d H:m:s');
                        $user->membership_plan_id  = '5';
                        $user->save();
                    }

                    return redirect('/business/registration_success');

                } catch (PayPalConnectionException $ex) {
                    return response()->json([
                        'error'=>$ex,
                        'getData'=>json_decode($ex->getData()),
                    ],$ex->getCode());
                } catch (Exception $ex) {
                    return response()->json([
                        'error'=>$ex,
                    ],400);
                }
            } else {
                return redirect('/business/membership_plans_failed');
                //return response()->json(['getData'=>"user canceled agreement"]);
            }
            //dd("kkkkkkkkkkk");
            //return response()->json(['getData'=>"Payment Canceled"]);
        }
        //dd("SSSSSSS");
    }
}
