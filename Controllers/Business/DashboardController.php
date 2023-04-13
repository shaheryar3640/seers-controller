<?php

namespace App\Http\Controllers\Business;

use App\Models\Product;
use App\Models\Defaulter;
use App\Models\UserPaymentMethodDetails;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Auth;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Charge;
use App\Models\UProduct;
use App\Models\UPlan;
use App\Models\UFeature;
use App\Models\Rating;

class DashboardController extends Controller
{
    private $user = null;
    private $products = null;
    private $free_products = [];

    public function __construct()
    {
        $this->middleware('business');
    }

    public function index()
    {


        $this->free_products = Product::freeProducts();

        $products = $this->getProductList();

        $purchasedProducts = $this->getPurchasedProducts();
        $purchasable_products = [];

        foreach ($products as $product) {
            if (!in_array($product['name'], array_column($purchasedProducts, 'name')) && !array_search('cookie_consent',$product) && !array_search('gdpr_audit',$product)) {
                array_push($purchasable_products, $product);
            }
        }

        if (count($this->free_products) > 0) {
            foreach ($this->free_products as $product) {
                if (!in_array($product['name'], array_column($purchasedProducts, 'name'))) {
                    if ($product->name === 'assessment' && $product->is_active = 1) {
                        foreach ($product['plans'][0]['features'] as $feature) {
                            if ($feature['value'] == 1 && $feature['is_visible'] == 1) {
                                $feature['url'] = $this->getURL($feature['name']);
                                $feature['image'] = $this->getImageLink($feature['name'], true);
                                $feature['is_free'] = true;
                                array_push($purchasedProducts, $feature);
                            }
                        }
                    } else {
                        $product->image = $this->getImageLink($product['name'], true);
                        $product->is_free = true;
                        array_push($purchasedProducts, $product);
                    }
                }
            }
        }

        $country = null;
        try {
            $country = $this->getCountry();
        } catch (\Exception $e) {
            $country = null;
        }

        $user_id = auth()->user()->id;
        $defaulters = Defaulter::where('user_id','=',$user_id)->select('product_id')->get();

        $defaulter_products = [];
        $defaulter_products['products'] = $defaulters;

        if($defaulters){
            foreach($defaulters as $defaulter){
                $product = UProduct::where('id','=',$defaulter->product_id)->first();

                if($product->name === 'assessment'){
                    $plan = UPlan::where('u_product_id','=',$product->id )->first();
                    $features = UFeature::where('u_plan_id', '=', $plan->id)->select('id')->get();
                    $defaulter_products['features'] = $features;

                    // array_push($defaulter_products->features,)
                    //$defaulter_products->features
                }
            }
        }



        return view('business.dashboard-new')->with([
            'products' => $purchasable_products,
            'defaulter' => $defaulter_products?json_encode($defaulter_products):[],
            'purchasedProducts' => $purchasedProducts,
            'assetPath' => url('/'),
            'country' => $country,
        ]);
    }

    public function _index()
    {
     /*   $this->user = Auth::User();
        return response()->json([
            'username' => $this->user->fullName,
            'avatar_link' => $this->user->avatar_link,
            'MembershipPlans' => $this->user->MembershipPlans->id,
            'productsStatus' => $this->getProductsStatus()
        ]);*/
//        $this->user = Auth::User();
//        return response()->json([
//            'username' => $this->user->fullName,
//            'avatar_link' => $this->user->avatar_link,
//            'MembershipPlans' => $this->user->MembershipPlans->id,
//            'productsStatus' => $this->getProductsStatus()
//        ]);

        $this->free_products = Product::freeProducts();
        $products = $this->getProductList();

        $purchasedProducts = $this->getPurchasedProducts();
        $purchasable_products = [];
        foreach ($products as $product) {
            if (!in_array($product['name'], array_column($purchasedProducts, 'name'))) {
                array_push($purchasable_products, $product);
            }
        }


       // return view('business.dashboard-new')->with([
       //     'products' => $this->getProductList(),
       // ]);
//       if(count($this->free_products) > 0) {
//           foreach($this->free_products as $product) {
//               if (!in_array($product['name'], array_column($purchasedProducts, 'name'))) {
//                   if($product->name === 'assessment') {
//                       foreach($product['plans'][0]['features'] as $feature) {
//                           $feature['url'] = $this->getURL($feature['name']);
//                           $feature['image'] = $this->getImageLink($feature['name'], true);
//                           $feature['is_free'] = true;
//                           array_push($purchasedProducts, $feature);
//                       }
//                   } else {
//                       $product->image = $this->getImageLink($product['name'], true);
//                       $product->is_free = true;
//                       array_push($purchasedProducts, $product);
//                   }
//               }
//           }
//       }

        return view('business.dashboard-setting-new')->with([
            'products' => $purchasable_products,
            'purchasedProducts' => $purchasedProducts,
            'assetPath' => url('/')
        ]);
    }

    public function getProductsStatus () {
        $eligible_products = ['gdpr_training', 'cookie_consent', 'gdpr_audit', 'data_control', 'policy_pack', 'cyber_secure', 'pecr_audit', 'privacy_template', 'dpia'];
        $products = $this->user->products;
        if ($products->count() > 0) {
            foreach ($products as $product) {
                if ($product->name === 'assessment') {
                    $plan = $product->plan;
                    $features = $plan->features;
                    foreach ($features as $feature) {
                        if (in_array($feature->name, $eligible_products) && $feature->is_active == 1 && $feature->value == 1) {
                            $this->products[$feature->name] = true;
                        }
                    }
                }
                if(in_array($product->name, $eligible_products) && $product->is_active){
                    $this->products[$product->name] = true;
                }
            }
        }
        return $this->products;
    }

    private function getProductList() {
        $features = null;
        $products = Product::select('id', 'name', 'display_name', 'url')->where('is_active', 1)->orderBy('display_name', 'ASC')->get();
        if ($products && $products->count() > 0) {
            foreach ($products as $product) {
                if ($product->name === 'assessment' && $product->is_active = 1) {
                    $features = $product->plans()->where('name', '=', 'platinum')->first()->features()->where('is_visible', '=', 1)->orderBy('display_name', 'ASC')->get();
                    if ($features && $features->count() > 0) {
                        foreach ($features as $feature) {
                            if ($feature->value === 1 && $feature->is_visible == 1) {
                                $feature->url = $this->getURL($feature->name);
                                $feature->image = $this->getImageLink($feature->name, false);
                            }
                        }
                    }
                } else {
                    $product->image = $this->getImageLink($product->name, false);
                }
            }
        }
        if ($features && $features->count() > 0) {
            $products = array_merge($products->toArray(), $features->toArray());
        } else {
            $products = $products->toArray();
        }
        return $products;
    }

    public function getPurchasedProducts() {
        $products = Auth::User()->products;
        $features = null;
        if ($products && $products->count() > 0) {
            foreach($products as $product) {
                if ($product->name === 'assessment' && $product->is_active = 1) {
                    $features = $product->plan->features()->where(['value' => 1, 'is_visible' => 1])->get();
                    if($features && $features->count() > 0) {
                        foreach($features as $feature) {
                            if ($feature->value == 1 && $feature->is_visible == 1) {
                                $feature->url = $this->getURL($feature->name);
                                $feature->image = $this->getImageLink($feature->name, true);
                            }
                        }
                    }
                } else {
                    $product->image = $this->getImageLink($product->name, true);
                }
            }
        }
        if ($features && $features->count() > 0) {
            $this->products = array_merge($products->toArray(), $features->toArray());
        } else {
            $this->products = $products->toArray();
        }
        return $this->products;
    }

    private function getURL($name) {

        $urls = [
            'gdpr_audit'        => 'business/gdpr-audit',
            'data_control'      => 'datacontrol',
//            'policy_pack'       => 'business/policy-generator/data-protection-policies-for-organisation',
            'policy_pack'       => 'business/policy-generator',
            'cyber_secure'      => 'business/cyber-secure',
            'pecr_audit'        => 'business/pecr-audit',
            'privacy_template'  => 'business/templates/best-practice-template'
        ];

        return $urls[$name];
    }

    private function getImageLink($name, $isPurchased) {
        $links = [
            'cookie_consent'            => asset('/images/dashboard-icons/cookie consent.svg'),
            'dpia'                      => asset('/images/dashboard-icons/data protection impact assessment.svg'),
            'subject_request_management'=> asset('/images/dashboard-icons/DSAR.svg'),
            'breach_management_system'=> asset('/images/dashboard-icons/breach-management.svg'),
            'cyber_secure'              => asset('/images/dashboard-icons/cyber secure.svg'),
            'gdpr_training'             => asset('/images/dashboard-icons/gdpr e training.svg'),
            'policy_pack'               => asset('/images/dashboard-icons/policies pack.svg'),
            'privacy_template'          => asset('/images/dashboard-icons/templates pack.svg'),
            'gdpr_audit'                => asset('/images/dashboard-icons/gdpr audit.svg'),
            'data_control'              => asset('/images/dashboard-icons/cookie audit.svg'),
            'pecr_audit'                => asset('/images/dashboard-icons/PECR audit.svg'),
            'pecr_audit'                => asset('/images/dashboard-icons/PECR audit.svg'),
        ];

        $_links = [
            'cookie_consent'            => asset('/images/dashboard-icons/Seers Cookie consent.svg'),
            'dpia'                      => asset('/images/dashboard-icons/Data Protection impact assessment-DPIA.svg'),
            'subject_request_management'=> asset('/images/dashboard-icons/data subject access request-DSAR.svg'),
            'breach_management_system'  => asset('/images/dashboard-icons/breach-grey.svg'),
            'cyber_secure'              => asset('/images/dashboard-icons/Seers Cyber Secure.svg'),
            'gdpr_training'             => asset('/images/dashboard-icons/general data protection regulation e training.svg'),
            'policy_pack'               => asset('/images/dashboard-icons/Policy Pack.svg'),
            'privacy_template'          => asset('/images/dashboard-icons/Template pack.svg'),
            'gdpr_audit'                => asset('/images/dashboard-icons/general data protection regulation Audit.svg'),
            'data_control'              => asset('/images/dashboard-icons/Seers cookie audit.svg'),
            'pecr_audit'                => asset('/images/dashboard-icons/Privacy and Electronic Communications Regulations audit.svg')
        ];

        return $isPurchased ? $links[$name] : $_links[$name];
    }

    private function getCountry () {
        $ip = request()->ip();
        $query = "SELECT `code`,`countryName` FROM ip2countrylist WHERE INET_ATON('$ip') BETWEEN `ip_range_start_int` AND `ip_range_end_int` LIMIT 1";
        $country_code = \DB::connection('mysql2')->select($query);
        return $country_code && $country_code[0]->code != '-' ? $country_code : null;
    }

    public function getUserPaymentCardsDetails() {
        $cardDetail = \DB::table('user_payment_method_details')
            ->where('user_id', '=' , auth()->id())
            ->groupBy('stripe_card_last_four_digits')
            ->orderBy('is_primary', 'DESC')
            ->get();

        return response()->json(['card_details' => $cardDetail], 200);
    }

    private function getStripeCustomerObj ($intent) {
        Stripe::setApiKey(config('app.stripe_key'));
        return $this->createStripeCustomer($intent);
    }
    private function createStripeCustomer ($intent) {
        Stripe::setApiKey(config('app.stripe_key'));
        $customer = Customer::create(["description" => auth()->user()->email]);
        $this->stripe_id = $customer->id;
        return $customer;
    }

    public function addCreditCard(Request $request) {

        $paymentMethod = $request->get('payment_method') ?? null;
        $currency = $request->get('currency') ?? 'gbp';
        $postal_code = $request->get('postal_code') ?? null;
        $stripe_customer = $this->getStripeCustomerObj($paymentMethod['card']);

        if ($stripe_customer === null || $stripe_customer === 'error') {
            return response([ 'message' => 'Could not find valid stripe customer'], 403);
        }

        $lastFour = $paymentMethod['card']['last4'];
        $brand = $paymentMethod['card']['brand'];
        $expiry = \Carbon\Carbon::createFromDate($paymentMethod['card']['exp_year'], $paymentMethod['card']['exp_month'], random_int(1, 30));
        $expiry = date('Y-m-d H:i:s', strtotime($expiry));
        $payment_details = UserPaymentMethodDetails::where('user_id','=',auth()->id())
        ->where('stripe_card_last_four_digits','=',$lastFour)
        ->where('stripe_card_type','=',$brand)->first();
        if($payment_details){
          return response(['message' => 'Payment Method Already Exist'], 201);
        } else {
            $payment_details = UserPaymentMethodDetails::Create([
            'user_id' => auth()->id(),
            'stripe_card_last_four_digits' => $lastFour,
            'stripe_card_type' => $brand
        ]);
        $payment_details->stripe_id = $this->stripe_id;
        $payment_details->stripe_payment_method = json_encode($paymentMethod);
        $payment_details->stripe_payment_method_id = $paymentMethod['id'];
        $payment_details->stripe_card_last_four_digits = $lastFour;
        $payment_details->stripe_card_type = $brand;
        $payment_details->expire_at = $expiry;
        $payment_details->postal_code = $postal_code;
        $payment_details->currency = $currency;
        $payment_details->save();
        return response(['message' => 'Card added successfully'], 200);
        }

        $payment_details->stripe_id = $this->stripe_id;
        $payment_details->stripe_payment_method = json_encode($paymentMethod);
        $payment_details->stripe_payment_method_id = $paymentMethod['id'];
        $payment_details->stripe_card_last_four_digits = $lastFour;
        $payment_details->stripe_card_type = $brand;
        $payment_details->expire_at = $expiry;
        $payment_details->postal_code = $postal_code;
        $payment_details->currency = $currency;
        $payment_details->save();


    }
    public function updateCardDetailAction(Request $request)
    {
        $user_id = Auth::id();
        $u_product = UProduct::where('user_id','=',$user_id)->first();
        $currency  =  $u_product->currency;
        $postal_code = $request->get('zip_code') ?? null;
        $id          = $request->get('id');

        $paymentMethod = $request->get('payment_method');
        $stripe_customer = $this->getStripeCustomerObj($paymentMethod['card']);
        return response (['stripe_customers'=>$stripe_customer]);
        $user_payment_method_details = UserPaymentMethodDetails::firstOrCreate([
            'user_id' => $user_id,
            'id' => $id
        ]);

        $user_payment_method_details->stripe_id = $this->stripe_id;
        $user_payment_method_details->stripe_payment_method = json_encode(request()->get('payment_method'));
        $user_payment_method_details->stripe_payment_method_id = request()->get('payment_method')['id'];
        $user_payment_method_details->postal_code =  $postal_code;
        //$user_payment_method_details->stripe_card_last_four_digits ='';
        $user_payment_method_details->currency = $currency;
        $user_payment_method_details->save();
        return response([
            'success' => 'Card information updated successfully.',
        ], 200);
        //return response (['stripe_customers'=>$stripe_customer,'postal_code'=>$postal_code,'id'=>$id,'currency'=>$currency]);
    }

    public function togglePrimaryCard (Request $request) {
        $id = (int) $request->query('id', -1);
        $last_four = (int) $request->query('last_four', 0);

        if ($id <= -1 || $last_four === 0) return response(['message' => 'Card not found'], 400);

        $cards = UserPaymentMethodDetails::where('user_id', '=', auth()->id())->get();
        if ($cards->count() > 0) {
            foreach ($cards as $card) {
                if ($card->stripe_card_last_four_digits == $last_four) {
                    $card->is_primary = 1;
                    $card->save();
                } else {
                    $card->is_primary = 0;
                    $card->save();
                }
            }
        }

        return response(['message' => 'Card set as primary.'], 200);
    }

    public function deleteCard ($lastFour) {
        if ($lastFour <= 0) {
            return response(['message' => 'Card not found'], 400);
        }

        $newly_selected = UserPaymentMethodDetails::where('user_id', '=', auth()->id())->where('stripe_card_last_four_digits', '=', $lastFour)->get();
        if ($newly_selected->count() > 0) {
            foreach ($newly_selected as $card) {
                $card->delete();
            }
            return response(['message' => 'Payment card has been removed successfully'], 200);
        }

        return response(['message' => 'Could not remove the card'], 400);
    }
    public function routeDataProtectionImpactAssesmentDpi() {
        return view('business.data_protection_impact_assesment.dpi');
    }
    public function routegetBusinessProfile() {
        $user = auth()->user();
        $data = [
            'address' => $user->address,
            'fname' => $user->fname,
            'lname' => $user->lname,
            'email' => $user->email,
            'phone' => $user->phone,
            'job_role' => $user->job_role,
            'company' => $user->company,
            'avatar_link' => $user->avatar_link
        ];
        return response()->json(['profile' => $data], 200);
    }
    public function routenewdashboard() {
        return view('business.dashboard');
    }
    public function routeRegistrationSuccess() {
        return view('business.registration_success');
    }


}
